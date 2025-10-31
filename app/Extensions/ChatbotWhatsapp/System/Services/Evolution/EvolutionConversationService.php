<?php

namespace App\Extensions\ChatbotWhatsapp\System\Services\Evolution;

use App\Extensions\Chatbot\System\Enums\InteractionType;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotChannel;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Services\GeneratorService;
use App\Extensions\ChatbotAgent\System\Services\ChatbotForPanelEventAbly;
use App\Helpers\Classes\MarketplaceHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EvolutionConversationService
{
    protected ?ChatbotConversation $conversation = null;
    protected ?ChatbotHistory $history = null;
    protected ?Chatbot $chatbot = null;
    protected int $chatbotId;
    protected int $channelId;
    protected ?string $ipAddress = null;
    protected ?array $payload = null;
    protected bool $existMessage = false;

    public function handleWhatsapp(): void
    {
        $evolutionService = app(EvolutionWhatsappService::class)
            ->setChatbotChannel($this->getChatbotChannel());

        // Parsear datos de Evolution API
        $remoteJid = data_get($this->payload, 'data.key.remoteJid');
        $phoneNumber = $this->extractPhoneFromJid($remoteJid);
        $messageBody = data_get($this->payload, 'data.message.conversation') 
                    ?? data_get($this->payload, 'data.message.extendedTextMessage.text');

        $conversation = $this->conversation;
        $chatbot = $conversation->chatbot;

        Log::info('Evolution: Processing WhatsApp message', [
            'conversation_id' => $conversation->id,
            'phone' => $phoneNumber,
            'connected_to_agent' => (bool)$conversation->connect_agent_at
        ]);

        // Si ya está conectado a agente humano
        if ($conversation->connect_agent_at) {
            if ($conversation->last_activity_at->diffInMinutes() > 10) {
                $this->closeInactiveConversation($conversation, $evolutionService, $phoneNumber);
                return;
            }
            // Si está conectado a agente, no procesar con IA
            return;
        }

        $conversation->update(['last_activity_at' => now()]);

        if ($messageBody) {
            $this->processTextMessage($messageBody, $conversation, $chatbot, $evolutionService, $phoneNumber);
        } else {
            $this->sendUnsupportedMessageType($conversation, $chatbot, $evolutionService, $phoneNumber);
        }
    }

    protected function extractPhoneFromJid(string $jid): string
    {
        // Evolution envía: "5511999999999@s.whatsapp.net"
        // Extraer solo el número
        $phone = explode('@', $jid)[0];
        
        return $phone;
    }

    protected function closeInactiveConversation(
        ChatbotConversation $conversation,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        $conversation->update(['connect_agent_at' => null]);
        $message = trans('The conversation has been closed due to inactivity.');
        $this->insertMessage($conversation, $message, 'assistant', $conversation->chatbot->ai_model);
        $service->sendText($message, $phoneNumber);
        
        Log::info('Evolution: Conversation closed due to inactivity', [
            'conversation_id' => $conversation->id
        ]);
    }

    protected function processTextMessage(
        string $messageBody,
        ChatbotConversation $conversation,
        Chatbot $chatbot,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        if ($this->isHumanAgentCommand($chatbot, $messageBody)) {
            $this->connectToHumanAgent($chatbot, $conversation, $service, $phoneNumber);
            return;
        }

        // NUEVO: Verificar si está en flujo de compra de WhatsApp
        if ($chatbot->sales_agent_enabled) {
            $purchaseFlow = app(\App\Extensions\Chatbot\System\Services\WhatsAppPurchaseFlowService::class);
            
            // Si está en flujo de compra activo, procesar con el servicio de compra
            if ($purchaseFlow->isInPurchaseFlow($conversation)) {
                $response = $purchaseFlow->processUserResponse(
                    $conversation,
                    $messageBody,
                    $chatbot,
                    $phoneNumber
                );
                
                $service->sendText($response, $phoneNumber);
                $this->insertMessage($conversation, $response, 'assistant', $chatbot->ai_model);
                
                Log::info('Evolution: Purchase flow response sent', [
                    'conversation_id' => $conversation->id,
                    'state' => $purchaseFlow->getState($conversation)
                ]);
                
                return;
            }
            
            // Detectar si el usuario quiere comprar un producto (por número)
            if ($this->isPurchaseIntent($messageBody)) {
                $productNumber = $this->extractProductNumber($messageBody);
                if ($productNumber) {
                    $response = $this->startPurchaseFromProductNumber(
                        $conversation,
                        $chatbot,
                        $productNumber,
                        $purchaseFlow
                    );
                    
                    $service->sendText($response, $phoneNumber);
                    $this->insertMessage($conversation, $response, 'assistant', $chatbot->ai_model);
                    return;
                }
            }
        }

        $response = $this->generateResponse($messageBody) 
                 ?? trans("Sorry, I can't answer right now.");

        Log::info('Evolution: Sending response to WhatsApp', [
            'conversation_id' => $conversation->id,
            'phone' => $phoneNumber,
            'response_length' => strlen($response),
            'full_response' => $response
        ]);

        $service->sendText($response, $phoneNumber);
        $this->insertMessage($conversation, $response, 'assistant', $chatbot->ai_model);
        
        // Enviar tip de agente humano solo la primera vez
        $this->sendHumanAgentTipIfNeeded($conversation, $chatbot, $service, $phoneNumber);
    }
    
    protected function sendHumanAgentTipIfNeeded(
        ChatbotConversation $conversation,
        Chatbot $chatbot,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        // Solo enviar si:
        // 1. No está conectado a agente
        // 2. Es modo SMART_SWITCH
        // 3. Está registrado el módulo de agente
        // 4. NO se ha enviado el tip antes
        // 5. Es la primera interacción (menos de 2 mensajes en la conversación)
        if ($conversation->connect_agent_at 
            || $chatbot->interaction_type !== InteractionType::SMART_SWITCH 
            || !MarketplaceHelper::isRegistered('chatbot-agent')
            || $conversation->histories()->count() > 2) {
            return;
        }
        
        // Obtener mensaje personalizado o usar el default
        $command = $chatbot->human_agent_command ?? 'humanagent';
        $tipMessage = $chatbot->human_agent_tip_message 
            ?? "💡 **Tip:** En cualquier momento puedes escribir #{$command} para ser atendido por un asesor humano.";
        
        $service->sendText($tipMessage, $phoneNumber);
        $this->insertMessage($conversation, $tipMessage, 'assistant', $chatbot->ai_model);
    }

    protected function sendUnsupportedMessageType(
        ChatbotConversation $conversation,
        Chatbot $chatbot,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        $message = trans('The chatbot does not support the type of message you are sending.');
        $this->insertMessage($conversation, $message, 'assistant', $chatbot->ai_model);
        $service->sendText($message, $phoneNumber);
    }

    protected function connectToHumanAgent(
        Chatbot $chatbot,
        ChatbotConversation $conversation,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        $conversation->update(['connect_agent_at' => now()]);

        Log::info('Evolution: Connecting to human agent', [
            'conversation_id' => $conversation->id,
            'chatbot_id' => $chatbot->id
        ]);

        if ($connectMessage = $chatbot->connect_message) {
            $chatbotHistory = $this->insertMessage($conversation, $connectMessage, 'assistant', $chatbot->ai_model, true);
            $service->sendText($connectMessage, $phoneNumber);
            $this->dispatchAgentEvent($chatbot, $conversation, $chatbotHistory);
        }
    }

    protected function dispatchAgentEvent(
        Chatbot $chatbot,
        ChatbotConversation $conversation,
        ?ChatbotHistory $chatbotHistory
    ): void {
        if (MarketplaceHelper::isRegistered('chatbot-agent')) {
            ChatbotForPanelEventAbly::dispatch($chatbot, $conversation, $chatbotHistory);
        }
    }

    protected function isHumanAgentCommand(Chatbot $chatbot, string $messageBody): bool
    {
        $command = $chatbot->human_agent_command ?? 'humanagent';
        $commands = ['#' . $command, $command];
        $normalizedMessage = strtolower(trim($messageBody));

        foreach ($commands as $cmd) {
            if (str_contains($normalizedMessage, strtolower($cmd))) {
                return true;
            }
        }

        return false;
    }

    protected function generateResponse(string $message): ?string
    {
        try {
            $generatorService = app(GeneratorService::class);
            $generatorService
                ->setChatbot($this->chatbot)
                ->setConversation($this->conversation)
                ->setPrompt($message);
            
            $response = $generatorService->generate();
            
            Log::info('Evolution: AI Response generated', [
                'conversation_id' => $this->conversation->id,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 200)
            ]);
            
            // Integrar Sales Agent si está habilitado
            if ($this->chatbot->sales_agent_enabled) {
                $response = $this->enhanceWithSalesAgent($response, $message);
            }
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Evolution: Error generating response', [
                'error' => $e->getMessage(),
                'conversation_id' => $this->conversation->id ?? 'unknown'
            ]);
            return null;
        }
    }

    protected function enhanceWithSalesAgent(string $aiResponse, string $userMessage): string
    {
        try {
            // FILTRO: Solo procesar si hay intención clara de búsqueda/compra
            if (!$this->hasProductSearchIntent($userMessage)) {
                return $aiResponse; // Retornar respuesta normal sin productos
            }
            
            // Limpiar cache de productos anteriores cuando hay nueva búsqueda
            Cache::forget("whatsapp_shown_products_{$this->conversation->id}");
            
            $orchestrator = app(\App\Extensions\Chatbot\System\Services\ProductOrchestratorService::class);
            
            $result = $orchestrator->orchestrate(
                chatbot: $this->chatbot,
                aiResponse: $aiResponse,
                userQuery: $userMessage
            );
            
            // Si se detectaron productos, guardarlos en cache y formatear con números
            if ($result['show_sales_grid'] && !empty($result['products'])) {
                $products = is_array($result['products']) ? $result['products'] : $result['products']->toArray();
                
                // Guardar productos en cache (30 minutos)
                Cache::forget("whatsapp_shown_products_{$this->conversation->id}");
                Cache::put(
                    "whatsapp_shown_products_{$this->conversation->id}",
                    $products,
                    now()->addMinutes(30)
                );
                
                // Formatear productos con números claros
                $productsText = "\n\n🛍️ *Productos disponibles:*\n\n";
                foreach (array_slice($products, 0, 5) as $index => $product) {
                    $num = $index + 1;
                    $productsText .= "*{$num}. {$product['name']}*\n";
                    $productsText .= "💰 Precio: \$" . number_format($product['price'], 0, ',', '.') . " COP\n";
                    
                    if (!empty($product['short_description'])) {
                        $description = strip_tags($product['short_description']);
                        $description = mb_substr($description, 0, 80);
                        if (mb_strlen($product['short_description']) > 80) {
                            $description .= '...';
                        }
                        $productsText .= "📝 {$description}\n";
                    }
                    $productsText .= "\n";
                }
                
                $productsText .= "💡 *Escribe el número del producto para comprarlo*";
                
                // Agregar productos formateados a la respuesta
                $aiResponse .= $productsText;
                
                Log::info('Evolution: Products cached and formatted', [
                    'conversation_id' => $this->conversation->id,
                    'products_count' => count($products),
                    'products' => array_column($products, 'name')
                ]);
            }
            
            // Si se activó negociación
            if (($result['negotiation_triggered'] ?? false) && $this->chatbot->negotiation_enabled) {
                $couponText = $this->generateCouponForWhatsApp();
                if ($couponText) {
                    $aiResponse .= "\n\n" . $couponText;
                    
                    Log::info('Evolution: Coupon added to response', [
                        'conversation_id' => $this->conversation->id
                    ]);
                }
            }
            
            return $aiResponse;
            
        } catch (\Exception $e) {
            Log::error('Evolution: Error enhancing with Sales Agent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $aiResponse; // Retornar respuesta original si falla
        }
    }

    protected function generateCouponForWhatsApp(): ?string
    {
        try {
            if (!$this->chatbot->negotiation_enabled) {
                return null;
            }
            
            $wooCommerceService = app(\App\Extensions\Chatbot\System\Services\WooCommerceService::class);
            
            $result = $wooCommerceService->createDynamicCoupon($this->chatbot, [
                'discount' => $this->chatbot->negotiation_max_discount,
                'duration' => $this->chatbot->negotiation_coupon_duration,
                'min_cart_value' => $this->chatbot->negotiation_min_cart_value,
            ]);
            
            if ($result['success']) {
                $coupon = $result['coupon'];
                
                return "🎁 *¡Cupón Especial Generado!*\n\n" .
                       "📋 Código: *{$coupon['code']}*\n" .
                       "💰 Descuento: *{$coupon['discount']}%*\n" .
                       "⏰ Válido por: *{$coupon['expires_in_minutes']} minutos*\n\n" .
                       "Copia este código y úsalo en el checkout para obtener tu descuento.";
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Evolution: Error generating coupon', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function insertMessage(
        ChatbotConversation $conversation,
        string $message,
        string $role,
        string $model,
        bool $isConnectMessage = false
    ): ?ChatbotHistory {
        return ChatbotHistory::query()->create([
            'chatbot_id' => $conversation->chatbot_id,
            'conversation_id' => $conversation->id,
            'model' => $model,
            'role' => $role,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    public function storeConversation(): ChatbotConversation
    {
        $remoteJid = data_get($this->payload, 'data.key.remoteJid');
        $phone = $this->extractPhoneFromJid($remoteJid);

        $this->conversation = ChatbotConversation::query()
            ->where('chatbot_id', $this->chatbotId)
            ->where('chatbot_channel_id', $this->channelId)
            ->where('customer_channel_id', $phone)
            ->first();

        if (!$this->conversation) {
            $this->conversation = ChatbotConversation::query()->create([
                'chatbot_id' => $this->chatbotId,
                'chatbot_channel_id' => $this->channelId,
                'customer_channel_id' => $phone,
                'session_id' => $phone, // Usar phone como session ID
                'ip_address' => $this->ipAddress,
                'last_activity_at' => now(),
                'created_at' => now(),
            ]);
        }

        return $this->conversation;
    }

    public function getChatbot(): Chatbot
    {
        if (!$this->chatbot) {
            $this->chatbot = Chatbot::query()->find($this->chatbotId);
        }

        return $this->chatbot;
    }

    public function getChatbotChannel(): ChatbotChannel
    {
        return ChatbotChannel::query()
            ->where('chatbot_id', $this->chatbotId)
            ->where('id', $this->channelId)
            ->firstOrFail();
    }

    public function setIpAddress(): self
    {
        $this->ipAddress = request()->ip();
        return $this;
    }

    public function setChatbotId(int $chatbotId): self
    {
        $this->chatbotId = $chatbotId;
        return $this;
    }

    public function setChannelId(int $channelId): self
    {
        $this->channelId = $channelId;
        return $this;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Detectar si el mensaje es una intención de compra
     */
    protected function isPurchaseIntent(string $message): bool
    {
        $message = strtolower(trim($message));
        
        // Detectar números simples (1, 2, 3, etc.)
        if (preg_match('/^[0-9]+$/', $message)) {
            return true;
        }
        
        // Detectar frases de compra
        $purchaseKeywords = [
            'quiero el',
            'comprar el',
            'me interesa el',
            'dame el',
            'quiero comprar',
            'lo quiero',
            'me lo llevo'
        ];
        
        foreach ($purchaseKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extraer número de producto del mensaje
     */
    protected function extractProductNumber(string $message): ?int
    {
        $message = trim($message);
        
        // Si es solo un número
        if (preg_match('/^([0-9]+)$/', $message, $matches)) {
            return (int) $matches[1];
        }
        
        // Si contiene "el 1", "número 2", etc.
        if (preg_match('/(?:el|número|numero|#)\s*([0-9]+)/i', $message, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }

    /**
     * Detectar si el mensaje tiene intención de búsqueda/compra de productos
     */
    protected function hasProductSearchIntent(string $message): bool
    {
        $message = strtolower(trim($message));
        
        // Excluir saludos simples
        $greetings = [
            'hola', 'hi', 'hello', 'buenos dias', 'buenas tardes', 'buenas noches',
            'buen dia', 'buena tarde', 'buena noche', 'hey', 'saludos',
            'que tal', 'como estas', 'como esta', 'alo', 'aló'
        ];
        
        foreach ($greetings as $greeting) {
            if ($message === $greeting || $message === $greeting . ' ali') {
                return false;
            }
        }
        
        // Detectar palabras clave de búsqueda/compra
        $searchKeywords = [
            'quiero', 'necesito', 'busco', 'buscando', 'comprar', 'compra',
            'vender', 'venta', 'precio', 'cuanto cuesta', 'cuánto cuesta',
            'tienes', 'tienen', 'hay', 'venden', 'ofrecen', 'disponible',
            'producto', 'productos', 'articulo', 'artículo', 'catalogo', 'catálogo',
            'mostrar', 'ver', 'mirar', 'enseñar', 'recomendar', 'sugerir',
            'informacion', 'información', 'detalles', 'caracteristicas', 'características'
        ];
        
        foreach ($searchKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Iniciar compra desde número de producto
     */
    protected function startPurchaseFromProductNumber(
        ChatbotConversation $conversation,
        Chatbot $chatbot,
        int $productNumber,
        $purchaseFlow
    ): string {
        // Obtener productos mostrados del cache
        $cachedProducts = Cache::get("whatsapp_shown_products_{$conversation->id}");
        
        if (!$cachedProducts || empty($cachedProducts)) {
            return "❌ Lo siento, no encontré productos recientes. Por favor dime qué estás buscando.";
        }
        
        // Verificar que el número esté en rango
        if ($productNumber < 1 || $productNumber > count($cachedProducts)) {
            return "❌ Por favor selecciona un número válido entre 1 y " . count($cachedProducts) . ".";
        }
        
        // Obtener el producto del cache (índice basado en 0)
        $productData = $cachedProducts[$productNumber - 1];
        
        if (!isset($productData['woocommerce_id'])) {
            return "❌ Error al obtener el producto. Por favor intenta nuevamente.";
        }
        
        // Iniciar flujo de compra
        return $purchaseFlow->startPurchaseFlow(
            $conversation,
            $productData['woocommerce_id'],
            $chatbot
        );
    }
}


