<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotAgent;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Orquestación de Agentes (Refactorizado)
 *
 * Coordina múltiples agentes (External Chatbot, Sales Agent, Support Agent, etc.)
 * y decide cuál debe activarse según el contexto de la conversación usando análisis de intención mejorado
 */
class AgentOrchestratorService
{
    protected AgentIntelligenceService $intelligence;

    public function __construct(AgentIntelligenceService $intelligence)
    {
        $this->intelligence = $intelligence;
    }

    /**
     * Orquestar respuesta del chatbot con múltiples agentes
     *
     * @param  string  $userQuery  Query del usuario
     * @param  string  $aiResponse  Respuesta generada por el AI
     * @param  array  $context  Contexto adicional de la conversación
     *
     * @return array Respuesta orquestada con datos de agentes activados
     */
    public function orchestrate(Chatbot $chatbot, string $userQuery, string $aiResponse, array $context = []): array
    {
        // Analizar intención del usuario con servicio de inteligencia
        $intent = $this->intelligence->analyzeIntent($userQuery, $aiResponse, $context);

        // Asegurar que el Sales Agent existe si sales_agent_enabled está activo
        if ($chatbot->sales_agent_enabled) {
            $salesAgentExists = ChatbotAgent::where('chatbot_id', $chatbot->id)
                ->where('agent_type', 'sales')
                ->exists();

            if (! $salesAgentExists) {
                // Usar el servicio para crear el Sales Agent automáticamente
                $chatbotService = app(\App\Extensions\Chatbot\System\Services\ChatbotService::class);
                $chatbotService->ensureSalesAgent($chatbot);
            }
        }

        // Obtener agentes activos del chatbot ordenados por prioridad
        $agents = ChatbotAgent::getActiveAgents($chatbot->id);

        // Si no hay agentes configurados, crear External Agent por defecto si es necesario
        if ($agents->isEmpty()) {
            // Intentar crear agente external por defecto si no existe
            $this->ensureDefaultExternalAgent($chatbot);
            $agents = ChatbotAgent::getActiveAgents($chatbot->id);
        }

        if ($agents->isEmpty()) {
            // Si aún no hay agentes, retornar solo el mensaje
            return [
                'message'                => $aiResponse,
                'agents_activated'       => [],
                'orchestration_metadata' => [
                    'total_agents'     => 0,
                    'agents_evaluated' => 0,
                    'intent'           => $intent,
                ],
            ];
        }

        // Evaluar qué agentes deben activarse con contexto mejorado
        $activatedAgents = $this->evaluateAgents($agents, $userQuery, $aiResponse, $intent, $context);

        // Construir respuesta orquestada
        $orchestratedResponse = [
            'message'                => $aiResponse,
            'agents_activated'       => [],
            'orchestration_metadata' => [
                'total_agents'           => $agents->count(),
                'agents_evaluated'       => $agents->count(),
                'agents_activated_count' => $activatedAgents->count(),
                'intent'                 => $intent,
            ],
        ];

        // Procesar cada agente activado ordenado por prioridad
        foreach ($activatedAgents->sortByDesc('priority') as $agent) {
            $agentData = $this->processAgent($agent, $chatbot, $userQuery, $aiResponse, $context);

            if ($agentData) {
                $orchestratedResponse['agents_activated'][] = $agentData;
            }
        }

        // Log detallado de orquestación (solo en desarrollo)
        if (config('app.debug')) {
            Log::info('Agent Orchestration', [
                'chatbot_id'        => $chatbot->id,
                'agents_activated'  => $activatedAgents->pluck('agent_type')->toArray(),
                'user_query'        => substr($userQuery, 0, 100),
                'intent_type'       => $intent['type'],
                'intent_confidence' => $intent['confidence'],
            ]);
        }

        return $orchestratedResponse;
    }

    /**
     * Asegurar que existe un External Agent por defecto
     */
    protected function ensureDefaultExternalAgent(Chatbot $chatbot): void
    {
        $existingExternal = ChatbotAgent::where('chatbot_id', $chatbot->id)
            ->where('agent_type', 'external')
            ->first();

        if (! $existingExternal) {
            ChatbotAgent::create([
                'chatbot_id'    => $chatbot->id,
                'agent_type'    => 'external',
                'name'          => 'External Chatbot',
                'description'   => 'Agente conversacional principal',
                'is_enabled'    => true,
                'priority'      => 10,
                'triggers'      => ['always_active' => true],
                'configuration' => [
                    'uses_embeddings'     => true,
                    'uses_knowledge_base' => true,
                ],
            ]);
        }
    }

    /**
     * Evaluar qué agentes deben activarse con análisis de intención mejorado
     */
    protected function evaluateAgents(
        Collection $agents,
        string $userQuery,
        string $aiResponse,
        array $intent = [],
        array $context = []
    ): Collection {
        return $agents->filter(function (ChatbotAgent $agent) use ($userQuery, $aiResponse, $intent, $context) {
            // Log detallado para debugging
            if (config('app.debug')) {
                $shouldActivate = $agent->shouldActivate($userQuery, $aiResponse, $intent, $context);
                Log::debug('Agent Evaluation', [
                    'agent_id'          => $agent->id,
                    'agent_type'        => $agent->agent_type,
                    'agent_name'        => $agent->name,
                    'is_enabled'        => $agent->is_enabled,
                    'should_activate'   => $shouldActivate,
                    'user_query'        => substr($userQuery, 0, 50),
                    'intent_type'       => $intent['type'] ?? 'unknown',
                    'intent_confidence' => $intent['confidence'] ?? 0,
                    'triggers'          => $agent->triggers,
                ]);

                return $shouldActivate;
            }

            return $agent->shouldActivate($userQuery, $aiResponse, $intent, $context);
        });
    }

    /**
     * Procesar un agente específico
     */
    protected function processAgent(
        ChatbotAgent $agent,
        Chatbot $chatbot,
        string $userQuery,
        string $aiResponse,
        array $context = []
    ): ?array {
        switch ($agent->agent_type) {
            case 'external':
                return $this->processExternalAgent($agent, $chatbot, $aiResponse);

            case 'sales':
                return $this->processSalesAgent($agent, $chatbot, $userQuery, $aiResponse, $context);

            case 'support':
                return $this->processSupportAgent($agent, $chatbot, $userQuery, $aiResponse, $context);

            default:
                return null;
        }
    }

    /**
     * Procesar External Chatbot Agent
     */
    protected function processExternalAgent(ChatbotAgent $agent, Chatbot $chatbot, string $aiResponse): array
    {
        return [
            'agent_type' => 'external',
            'agent_name' => $agent->name,
            'priority'   => $agent->priority,
            'data'       => [
                'message'             => $aiResponse,
                'uses_embeddings'     => $agent->getConfig('uses_embeddings', false),
                'uses_knowledge_base' => $agent->getConfig('uses_knowledge_base', false),
            ],
        ];
    }

    /**
     * Procesar Sales Agent
     */
    protected function processSalesAgent(
        ChatbotAgent $agent,
        Chatbot $chatbot,
        string $userQuery,
        string $aiResponse,
        array $context = []
    ): array {
        // Buscar productos mencionados
        $products = $this->findMentionedProducts($chatbot, $userQuery, $aiResponse);

        return [
            'agent_type' => 'sales',
            'agent_name' => $agent->name,
            'priority'   => $agent->priority,
            'data'       => [
                'products'            => $products->toArray(),
                'show_product_grid'   => $agent->getConfig('show_product_grid', true) && $products->isNotEmpty(),
                'woocommerce_enabled' => $agent->getConfig('woocommerce_enabled', false) || $chatbot->woocommerce_enabled,
                'wompi_enabled'       => $agent->getConfig('wompi_enabled', false) || $chatbot->wompi_enabled,
                'epayco_enabled'      => $agent->getConfig('epayco_enabled', false) || $chatbot->epayco_enabled,
                'total_products'      => $products->count(),
            ],
        ];
    }

    /**
     * Procesar Support Agent (futuro)
     */
    protected function processSupportAgent(
        ChatbotAgent $agent,
        Chatbot $chatbot,
        string $userQuery,
        string $aiResponse,
        array $context = []
    ): array {
        return [
            'agent_type' => 'support',
            'agent_name' => $agent->name,
            'priority'   => $agent->priority,
            'data'       => [
                'support_available'     => true,
                'ticket_system_enabled' => $agent->getConfig('ticket_system_enabled', false),
            ],
        ];
    }

    /**
     * Buscar productos mencionados en la conversación
     */
    protected function findMentionedProducts(Chatbot $chatbot, string $userQuery, string $aiResponse): Collection
    {
        $searchText = $userQuery . ' ' . $aiResponse;

        // Obtener productos activos y en stock
        $allProducts = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->active()->inStock()
            ->get();

        if ($allProducts->isEmpty()) {
            return collect([]);
        }

        $mentionedProducts = collect([]);

        // Buscar productos cuyo nombre aparece en el texto
        foreach ($allProducts as $product) {
            $productName = strtolower($product->name);
            $searchTextLower = strtolower($searchText);

            // Buscar URL en embeddings si product_url está vacío o es el home
            $embeddingUrl = null;
            if (! $product->product_url ||
                $product->product_url === 'https://www.aliviate.com.co/' ||
                $product->product_url === 'https://aliviate.com.co/') {
                $embeddingUrl = $this->findProductUrlInEmbeddings($chatbot, $product->name);
            }

            // Coincidencia exacta o parcial del nombre
            if (str_contains($searchTextLower, $productName)) {
                $mentionedProducts->push($this->formatProduct($product, $embeddingUrl));

                continue;
            }

            // Buscar por palabras clave significativas
            $productWords = explode(' ', $productName);
            $significantWords = array_filter($productWords, fn ($word) => strlen($word) > 3);

            foreach ($significantWords as $word) {
                if (str_contains($searchTextLower, $word)) {
                    $mentionedProducts->push($this->formatProduct($product, $embeddingUrl));

                    break;
                }
            }
        }

        // Si no se encontraron productos específicos, retornar los más relevantes
        if ($mentionedProducts->isEmpty()) {
            return $allProducts->take(6)->map(function ($p) use ($chatbot) {
                $embeddingUrl = null;
                if (! $p->product_url ||
                    $p->product_url === 'https://www.aliviate.com.co/' ||
                    $p->product_url === 'https://aliviate.com.co/') {
                    $embeddingUrl = $this->findProductUrlInEmbeddings($chatbot, $p->name);
                }

                return $this->formatProduct($p, $embeddingUrl);
            });
        }

        // Limitar a 6 productos
        return $mentionedProducts->take(6);
    }

    /**
     * Formatear producto para el frontend (Mejorado)
     */
    protected function formatProduct(ChatbotProduct $product, ?string $embeddingUrl = null): array
    {
        $price = (float) $product->price;

        // Validar y obtener URL del producto con mejor lógica
        $productUrl = $this->getValidProductUrl($product, $embeddingUrl);

        return [
            'id'                => $product->id,
            'woocommerce_id'    => $product->woocommerce_id,
            'name'              => $product->name,
            'description'       => $product->description ? strip_tags($product->description) : '',
            'short_description' => $product->short_description,
            'price'             => $price,
            'formatted_price'   => '$' . number_format($price, 0, ',', '.') . ' COP',
            'image_url'         => $product->image_url,
            'sku'               => $product->sku,
            'in_stock'          => true,
            'stock_quantity'    => $product->stock_quantity,
            'product_url'       => $productUrl,
        ];
    }

    /**
     * Obtener URL válida del producto con validación mejorada
     */
    protected function getValidProductUrl(ChatbotProduct $product, ?string $embeddingUrl = null): ?string
    {
        // URLs del home que NO debemos usar
        $invalidUrls = [
            'https://www.aliviate.com.co/',
            'https://aliviate.com.co/',
            'https://aliviate.com.co',
            'http://www.aliviate.com.co/',
            'http://aliviate.com.co/',
        ];

        // Prioridad 1: URL de embedding (si existe y es válida)
        if ($embeddingUrl && ! in_array($embeddingUrl, $invalidUrls)) {
            if ($this->isValidUrl($embeddingUrl)) {
                return $embeddingUrl;
            }
        }

        // Prioridad 2: product_url de BD (si existe y es válida)
        if ($product->product_url && ! in_array($product->product_url, $invalidUrls)) {
            if ($this->isValidUrl($product->product_url)) {
                return $product->product_url;
            }
        }

        // Prioridad 3: purchase_url (si existe y es válida)
        if (isset($product->purchase_url) && $product->purchase_url) {
            if ($this->isValidUrl($product->purchase_url) && ! in_array($product->purchase_url, $invalidUrls)) {
                return $product->purchase_url;
            }
        }

        // Si no hay URL válida, retornar null (no usar URLs del home)
        return null;
    }

    /**
     * Validar si una URL es válida
     */
    protected function isValidUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Validar formato básico de URL
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Buscar URL del producto en embeddings del website entrenado
     */
    protected function findProductUrlInEmbeddings(Chatbot $chatbot, string $productName): ?string
    {
        $embeddings = $chatbot->embeddings()
            ->where('type', 'website')
            ->whereNotNull('url')
            ->get();

        if ($embeddings->isEmpty()) {
            return null;
        }

        $productNameLower = strtolower($productName);

        foreach ($embeddings as $embedding) {
            $title = strtolower($embedding->title ?? '');
            $url = $embedding->url;

            // Si el título contiene el nombre del producto, usar esa URL
            if (str_contains($title, $productNameLower) || str_contains($productNameLower, $title)) {
                return $url;
            }

            // También buscar en el contenido si está disponible
            if ($embedding->content) {
                $content = strtolower($embedding->content);
                if (str_contains($content, $productNameLower)) {
                    return $url;
                }
            }
        }

        return null;
    }

    /**
     * Obtener agente específico por tipo
     */
    public function getAgentByType(Chatbot $chatbot, string $agentType): ?ChatbotAgent
    {
        return ChatbotAgent::where('chatbot_id', $chatbot->id)
            ->where('agent_type', $agentType)
            ->enabled()
            ->first();
    }

    /**
     * Verificar si un agente está activo
     */
    public function isAgentActive(Chatbot $chatbot, string $agentType): bool
    {
        return ChatbotAgent::where('chatbot_id', $chatbot->id)
            ->where('agent_type', $agentType)
            ->enabled()
            ->exists();
    }
}
