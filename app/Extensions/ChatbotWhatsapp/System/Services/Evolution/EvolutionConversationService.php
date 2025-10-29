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
use Illuminate\Support\Facades\Log;

class EvolutionConversationService
{
    protected ?ChatbotConversation $conversation = null;
    protected ?ChatbotHistory $history = null;
    protected ?Chatbot $chatbot = null;
    protected string $humanAgentCommand = 'humanagent';
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

        $response = $this->generateResponse($messageBody) 
                 ?? trans("Sorry, I can't answer right now.");

        if (!$conversation->connect_agent_at 
            && $chatbot->interaction_type === InteractionType::SMART_SWITCH 
            && MarketplaceHelper::isRegistered('chatbot-agent')) {
            $response .= "\n\n\nTo speak with a live support agent, please enter the #{$this->humanAgentCommand} command.";
        }

        $service->sendText($response, $phoneNumber);
        $this->insertMessage($conversation, $response, 'assistant', $chatbot->ai_model);
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
        $commands = ['#' . $this->humanAgentCommand, $this->humanAgentCommand];
        $normalizedMessage = strtolower(trim($messageBody));

        foreach ($commands as $command) {
            if (str_contains($normalizedMessage, strtolower($command))) {
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
            $orchestrator = app(\App\Extensions\Chatbot\System\Services\ProductOrchestratorService::class);
            
            $result = $orchestrator->orchestrate(
                chatbot: $this->chatbot,
                aiResponse: $aiResponse,
                userQuery: $userMessage
            );
            
            // Si se detectaron productos
            if ($result['show_sales_grid'] && !empty($result['products'])) {
                $productsText = $this->formatProductsForWhatsApp($result['products']);
                $aiResponse .= "\n\n" . $productsText;
                
                Log::info('Evolution: Products added to response', [
                    'conversation_id' => $this->conversation->id,
                    'products_count' => count($result['products'])
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

    protected function formatProductsForWhatsApp(array $products): string
    {
        $text = "🛍️ *Productos disponibles:*\n\n";
        
        foreach (array_slice($products, 0, 5) as $index => $product) {
            $num = $index + 1;
            $text .= "*{$num}. {$product['name']}*\n";
            $text .= "💰 Precio: \$" . number_format($product['price'], 0, ',', '.') . "\n";
            
            if (!empty($product['short_description'])) {
                $description = strip_tags($product['short_description']);
                $description = mb_substr($description, 0, 100);
                if (mb_strlen($product['short_description']) > 100) {
                    $description .= '...';
                }
                $text .= "📝 {$description}\n";
            }
            
            if (!empty($product['product_url'])) {
                $text .= "🔗 {$product['product_url']}\n";
            }
            
            $text .= "\n";
        }
        
        $text .= "Para más información sobre algún producto, escribe su número.";
        
        return $text;
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
}


