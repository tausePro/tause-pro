<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Support\Facades\Log;

class EnhancedGeneratorService extends GeneratorService
{
    protected QuickReplyService $quickReplyService;
    protected ProductCardService $productCardService;
    
    public function __construct()
    {
        $this->quickReplyService = app(QuickReplyService::class);
        $this->productCardService = app(ProductCardService::class);
    }
    
    /**
     * Generate enhanced response with Quick Replies and Product Cards
     */
    public function generateEnhanced(): array
    {
        $baseResponse = $this->generate();
        
        // Detect if we should show product cards
        $productCards = $this->detectAndGenerateProductCards();
        
        // Generate quick replies based on context
        $quickReplies = $this->generateQuickReplies($baseResponse);
        
        return [
            'message' => $baseResponse,
            'quick_replies' => $quickReplies,
            'product_cards' => $productCards,
            'action_type' => $productCards ? 'product_carousel' : 'text'
        ];
    }
    
    /**
     * Detect if we should show product cards and generate them
     */
    protected function detectAndGenerateProductCards(): ?array
    {
        $message = strtolower($this->prompt);
        $response = strtolower($this->generate());
        
        // Keywords that indicate product interest
        $productKeywords = [
            'producto', 'comprar', 'precio', 'catálogo', 'tienda', 'product',
            'buy', 'shop', 'catalogo', 'venta', 'oferta', 'descuento',
            'vitamina', 'suplemento', 'wellness', 'salud', 'bienestar'
        ];
        
        $hasProductKeywords = false;
        foreach ($productKeywords as $keyword) {
            if (str_contains($message, $keyword) || str_contains($response, $keyword)) {
                $hasProductKeywords = true;
                break;
            }
        }
        
        if (!$hasProductKeywords) {
            return null;
        }
        
        // Get products based on context
        $filters = [];
        
        // Detect category from message
        $categories = ['vitaminas', 'suplementos', 'wellness', 'salud', 'bienestar'];
        foreach ($categories as $category) {
            if (str_contains($message, $category)) {
                $filters['category'] = $category;
                break;
            }
        }
        
        // Generate product cards
        $productCards = $this->productCardService->generateProductCards($this->chatbot, $filters, 3);
        
        return $productCards['products'] ?? null;
    }
    
    /**
     * Generate quick replies based on context
     */
    protected function generateQuickReplies(string $response): ?array
    {
        // Don't show quick replies if we're showing product cards
        $productCards = $this->detectAndGenerateProductCards();
        if ($productCards) {
            return null;
        }
        
        // Generate contextual quick replies
        $quickReplies = $this->quickReplyService->generateQuickReplies(
            $this->prompt,
            $this->chatbot,
            ['response' => $response]
        );
        
        return $quickReplies;
    }
    
    /**
     * Save enhanced message to database
     */
    public function saveEnhancedMessage(ChatbotConversation $conversation, array $enhancedResponse): ChatbotHistory
    {
        return ChatbotHistory::create([
            'user_id' => $this->chatbot->user_id,
            'chatbot_id' => $this->chatbot->id,
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'message' => $enhancedResponse['message'],
            'quick_replies' => $enhancedResponse['quick_replies'],
            'metadata' => [
                'product_cards' => $enhancedResponse['product_cards'],
                'action_type' => $enhancedResponse['action_type']
            ],
            'action_type' => $enhancedResponse['action_type'],
            'created_at' => now(),
        ]);
    }
}
