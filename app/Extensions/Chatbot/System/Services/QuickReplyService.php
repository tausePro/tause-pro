<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;

class QuickReplyService
{
    /**
     * Generate context-aware quick replies based on the conversation
     */
    public function generateQuickReplies(string $message, Chatbot $chatbot, array $context = []): ?array
    {
        $message = strtolower($message);
        
        // Detección de intención por contexto
        if ($this->isGreeting($message)) {
            return $this->getGreetingReplies($chatbot);
        }
        
        if ($this->isProductInquiry($message)) {
            return $this->getProductInquiryReplies($chatbot);
        }
        
        if ($this->isHelpRequest($message)) {
            return $this->getHelpReplies($chatbot);
        }
        
        if ($this->isPriceInquiry($message)) {
            return $this->getPriceReplies();
        }
        
        // Quick replies por defecto
        return $this->getDefaultReplies($chatbot);
    }
    
    /**
     * Get quick replies for product recommendations
     */
    public function getProductQuickReplies(Chatbot $chatbot, ?string $category = null): array
    {
        $products = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->when($category, function($query, $category) {
                return $query->where('category', $category);
            })
            ->limit(3)
            ->get();
        
        $replies = [];
        foreach ($products as $product) {
            $replies[] = [
                'type' => 'product',
                'label' => '🛍️ ' . $product->name,
                'value' => 'product_' . $product->id,
                'metadata' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                ]
            ];
        }
        
        return $replies;
    }
    
    protected function isGreeting(string $message): bool
    {
        $greetings = ['hola', 'hello', 'hi', 'hey', 'buenos días', 'buenas tardes', 'buenas noches'];
        foreach ($greetings as $greeting) {
            if (str_contains($message, $greeting)) {
                return true;
            }
        }
        return false;
    }
    
    protected function isProductInquiry(string $message): bool
    {
        $keywords = ['producto', 'comprar', 'precio', 'catálogo', 'tienda', 'product', 'buy', 'shop'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }
    
    protected function isHelpRequest(string $message): bool
    {
        $keywords = ['ayuda', 'help', 'asistencia', 'soporte', 'support'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }
    
    protected function isPriceInquiry(string $message): bool
    {
        $keywords = ['precio', 'cuesta', 'cost', 'price', 'cuánto'];
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        return false;
    }
    
    protected function getGreetingReplies(Chatbot $chatbot): array
    {
        return [
            [
                'type' => 'text',
                'label' => '🛍️ Ver productos',
                'value' => 'Ver catálogo de productos',
            ],
            [
                'type' => 'text',
                'label' => '💬 Hacer una consulta',
                'value' => 'Tengo una pregunta',
            ],
            [
                'type' => 'text',
                'label' => '👤 Hablar con un agente',
                'value' => '#' . ($chatbot->human_agent_command ?? 'humanagent'),
            ]
        ];
    }
    
    protected function getProductInquiryReplies(Chatbot $chatbot): array
    {
        // Obtener categorías de productos
        $categories = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->take(3)
            ->toArray();
        
        $replies = [];
        foreach ($categories as $category) {
            $replies[] = [
                'type' => 'category',
                'label' => '📦 ' . ucfirst($category),
                'value' => 'category_' . $category,
                'metadata' => ['category' => $category]
            ];
        }
        
        // Agregar opción de ver todos
        $replies[] = [
            'type' => 'text',
            'label' => '🔍 Ver todos los productos',
            'value' => 'Muéstrame todos los productos disponibles',
        ];
        
        return $replies;
    }
    
    protected function getHelpReplies(Chatbot $chatbot): array
    {
        return [
            [
                'type' => 'text',
                'label' => '📦 Seguimiento de pedido',
                'value' => 'Quiero rastrear mi pedido',
            ],
            [
                'type' => 'text',
                'label' => '🔄 Devoluciones',
                'value' => 'Información sobre devoluciones',
            ],
            [
                'type' => 'text',
                'label' => '📞 Contacto',
                'value' => 'Quiero contactar con soporte',
            ],
            [
                'type' => 'text',
                'label' => '👤 Agente humano',
                'value' => '#' . ($chatbot->human_agent_command ?? 'humanagent'),
            ]
        ];
    }
    
    protected function getPriceReplies(): array
    {
        return [
            [
                'type' => 'text',
                'label' => '💰 Ver precios',
                'value' => 'Muéstrame los precios',
            ],
            [
                'type' => 'text',
                'label' => '🎁 Ofertas especiales',
                'value' => '¿Tienen ofertas o descuentos?',
            ],
            [
                'type' => 'text',
                'label' => '💳 Métodos de pago',
                'value' => '¿Qué métodos de pago aceptan?',
            ]
        ];
    }
    
    protected function getDefaultReplies(Chatbot $chatbot): array
    {
        return [
            [
                'type' => 'text',
                'label' => '🛍️ Productos',
                'value' => 'Ver productos',
            ],
            [
                'type' => 'text',
                'label' => '❓ Ayuda',
                'value' => 'Necesito ayuda',
            ],
            [
                'type' => 'text',
                'label' => '👤 Agente',
                'value' => '#' . ($chatbot->human_agent_command ?? 'humanagent'),
            ]
        ];
    }
}




