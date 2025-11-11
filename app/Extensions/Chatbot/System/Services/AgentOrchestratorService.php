<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotAgent;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Orquestación de Agentes
 * 
 * Coordina múltiples agentes (External Chatbot, Sales Agent, Support Agent, etc.)
 * y decide cuál debe activarse según el contexto de la conversación
 */
class AgentOrchestratorService
{
    /**
     * Orquestar respuesta del chatbot con múltiples agentes
     * 
     * @param Chatbot $chatbot
     * @param string $userQuery Query del usuario
     * @param string $aiResponse Respuesta generada por el AI
     * @return array Respuesta orquestada con datos de agentes activados
     */
    public function orchestrate(Chatbot $chatbot, string $userQuery, string $aiResponse): array
    {
        // Obtener agentes activos del chatbot ordenados por prioridad
        $agents = ChatbotAgent::getActiveAgents($chatbot->id);

        if ($agents->isEmpty()) {
            // Si no hay agentes configurados, retornar solo el mensaje
            return [
                'message' => $aiResponse,
                'agents_activated' => [],
                'orchestration_metadata' => [
                    'total_agents' => 0,
                    'agents_evaluated' => 0,
                ],
            ];
        }

        // Evaluar qué agentes deben activarse
        $activatedAgents = $this->evaluateAgents($agents, $userQuery, $aiResponse);

        // Construir respuesta orquestada
        $orchestratedResponse = [
            'message' => $aiResponse,
            'agents_activated' => [],
            'orchestration_metadata' => [
                'total_agents' => $agents->count(),
                'agents_evaluated' => $agents->count(),
                'agents_activated_count' => $activatedAgents->count(),
            ],
        ];

        // Procesar cada agente activado
        foreach ($activatedAgents as $agent) {
            $agentData = $this->processAgent($agent, $chatbot, $userQuery, $aiResponse);
            
            if ($agentData) {
                $orchestratedResponse['agents_activated'][] = $agentData;
            }
        }

        // Log de orquestación (solo en desarrollo)
        if (config('app.debug')) {
            Log::info('Agent Orchestration', [
                'chatbot_id' => $chatbot->id,
                'agents_activated' => $activatedAgents->pluck('agent_type')->toArray(),
                'user_query' => substr($userQuery, 0, 100),
            ]);
        }

        return $orchestratedResponse;
    }

    /**
     * Evaluar qué agentes deben activarse
     */
    protected function evaluateAgents(Collection $agents, string $userQuery, string $aiResponse): Collection
    {
        return $agents->filter(function (ChatbotAgent $agent) use ($userQuery, $aiResponse) {
            return $agent->shouldActivate($userQuery, $aiResponse);
        });
    }

    /**
     * Procesar un agente específico
     */
    protected function processAgent(ChatbotAgent $agent, Chatbot $chatbot, string $userQuery, string $aiResponse): ?array
    {
        switch ($agent->agent_type) {
            case 'external':
                return $this->processExternalAgent($agent, $chatbot, $aiResponse);
            
            case 'sales':
                return $this->processSalesAgent($agent, $chatbot, $userQuery, $aiResponse);
            
            case 'support':
                return $this->processSupportAgent($agent, $chatbot, $userQuery, $aiResponse);
            
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
            'priority' => $agent->priority,
            'data' => [
                'message' => $aiResponse,
                'uses_embeddings' => $agent->getConfig('uses_embeddings', false),
                'uses_knowledge_base' => $agent->getConfig('uses_knowledge_base', false),
            ],
        ];
    }

    /**
     * Procesar Sales Agent
     */
    protected function processSalesAgent(ChatbotAgent $agent, Chatbot $chatbot, string $userQuery, string $aiResponse): array
    {
        // Buscar productos mencionados
        $products = $this->findMentionedProducts($chatbot, $userQuery, $aiResponse);

        return [
            'agent_type' => 'sales',
            'agent_name' => $agent->name,
            'priority' => $agent->priority,
            'data' => [
                'products' => $products->toArray(),
                'show_product_grid' => $agent->getConfig('show_product_grid', true) && $products->isNotEmpty(),
                'woocommerce_enabled' => $agent->getConfig('woocommerce_enabled', false),
                'wompi_enabled' => $agent->getConfig('wompi_enabled', false),
                'total_products' => $products->count(),
            ],
        ];
    }

    /**
     * Procesar Support Agent (futuro)
     */
    protected function processSupportAgent(ChatbotAgent $agent, Chatbot $chatbot, string $userQuery, string $aiResponse): array
    {
        return [
            'agent_type' => 'support',
            'agent_name' => $agent->name,
            'priority' => $agent->priority,
            'data' => [
                'support_available' => true,
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
            ->active()
            ->inStock()
            ->get();

        if ($allProducts->isEmpty()) {
            return collect([]);
        }

        $mentionedProducts = collect([]);

        // Buscar productos cuyo nombre aparece en el texto
        foreach ($allProducts as $product) {
            $productName = strtolower($product->name);
            $searchTextLower = strtolower($searchText);

            // Coincidencia exacta o parcial del nombre
            if (str_contains($searchTextLower, $productName)) {
                $mentionedProducts->push($this->formatProduct($product));
                continue;
            }

            // Buscar por palabras clave significativas
            $productWords = explode(' ', $productName);
            $significantWords = array_filter($productWords, fn($word) => strlen($word) > 3);

            foreach ($significantWords as $word) {
                if (str_contains($searchTextLower, $word)) {
                    $mentionedProducts->push($this->formatProduct($product));
                    break;
                }
            }
        }

        // Si no se encontraron productos específicos, retornar los más relevantes
        if ($mentionedProducts->isEmpty()) {
            return $allProducts->take(6)->map(fn($p) => $this->formatProduct($p));
        }

        // Limitar a 6 productos
        return $mentionedProducts->take(6);
    }

    /**
     * Formatear producto para el frontend
     */
    protected function formatProduct(ChatbotProduct $product): array
    {
        $price = (float) $product->price;
        
        return [
            'id' => $product->id,
            'woocommerce_id' => $product->woocommerce_id,
            'name' => $product->name,
            'description' => $product->description ? strip_tags($product->description) : '',
            'short_description' => $product->short_description,
            'price' => $price,
            'formatted_price' => '$' . number_format($price, 0, ',', '.') . ' COP',
            'image_url' => $product->image_url,
            'sku' => $product->sku,
            'in_stock' => true,
            'stock_quantity' => $product->stock_quantity,
            'product_url' => $product->product_url ?? $product->purchase_url,
        ];
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
