<?php

namespace App\Extensions\Chatbot\System\Services\Traits;

use App\Extensions\Chatbot\System\Services\ChatbotEnhancedService;
use App\Extensions\Chatbot\System\Services\ChatbotKnowledgeBaseService;
use App\Extensions\Chatbot\System\Services\ProductCatalogService;
use App\Extensions\Chatbot\System\Services\ChatbotCategoryService;
use App\Extensions\Chatbot\System\Services\ChatbotSocialIntegrationService;

trait EnhancedKnowledgeBaseTrait
{
    protected ?ChatbotEnhancedService $enhancedService = null;

    /**
     * Get the enhanced service instance.
     */
    protected function getEnhancedService(): ChatbotEnhancedService
    {
        if (!$this->enhancedService) {
            $this->enhancedService = new ChatbotEnhancedService(
                app(ChatbotKnowledgeBaseService::class),
                app(ProductCatalogService::class),
                app(ChatbotCategoryService::class),
                app(ChatbotSocialIntegrationService::class)
            );
        }

        return $this->enhancedService;
    }

    /**
     * Perform enhanced search across knowledge base and products.
     */
    public function enhancedSearch(
        string $query,
        ?int $chatbotId = null,
        array $options = []
    ): array {
        return $this->getEnhancedService()->enhancedSearch($query, $chatbotId, $options);
    }

    /**
     * Get enhanced dashboard data.
     */
    public function getEnhancedDashboardData(?int $chatbotId = null, int $days = 30): array
    {
        return $this->getEnhancedService()->getDashboardData($chatbotId, $days);
    }

    /**
     * Optimize content for better search results.
     */
    public function optimizeContent(?int $chatbotId = null): array
    {
        return $this->getEnhancedService()->optimizeContent($chatbotId);
    }

    /**
     * Handle product-related queries in conversations.
     */
    public function handleProductQuery(string $query, int $chatbotId): array
    {
        $searchResults = $this->enhancedSearch($query, $chatbotId, [
            'product_limit' => 5,
            'article_limit' => 3,
            'availability' => 'in_stock'
        ]);

        // Format results for conversation response
        $response = [];
        
        if (!empty($searchResults['products'])) {
            $response['products'] = array_slice($searchResults['products'], 0, 3);
            $response['has_products'] = true;
        }

        if (!empty($searchResults['articles'])) {
            $response['articles'] = array_slice($searchResults['articles'], 0, 2);
            $response['has_articles'] = true;
        }

        $response['total_results'] = $searchResults['total_results'];
        $response['response_time'] = $searchResults['response_time_ms'];

        return $response;
    }

    /**
     * Get product recommendations based on conversation context.
     */
    public function getProductRecommendations(int $chatbotId, array $context = []): array
    {
        $query = '';
        
        // Extract keywords from conversation context
        if (!empty($context['recent_messages'])) {
            $query = implode(' ', array_slice($context['recent_messages'], -3));
        } elseif (!empty($context['keywords'])) {
            $query = implode(' ', $context['keywords']);
        }

        if (empty($query)) {
            // Return featured products if no context
            return $this->enhancedSearch('featured', $chatbotId, [
                'product_limit' => 5,
                'article_limit' => 0
            ]);
        }

        return $this->enhancedSearch($query, $chatbotId, [
            'product_limit' => 5,
            'article_limit' => 2
        ]);
    }

    /**
     * Import social media content and convert to knowledge base.
     */
    public function importSocialContent(
        string $platform,
        array $credentials,
        int $userId,
        ?int $chatbotId = null
    ): array {
        $socialService = app(ChatbotSocialIntegrationService::class);
        
        // Import content
        $importResult = $socialService->importSocialContent(
            $platform,
            $credentials,
            $userId,
            $chatbotId
        );

        // Process suitable content for knowledge base
        if ($importResult['success'] && $importResult['imported_count'] > 0) {
            $processResult = $socialService->processContentForKnowledgeBase(
                $platform,
                'positive', // Only positive sentiment
                50 // Minimum engagement
            );

            $importResult['knowledge_base_articles'] = $processResult['converted_count'];
        }

        return $importResult;
    }

    /**
     * Get analytics for enhanced features.
     */
    public function getEnhancedAnalytics(?int $chatbotId = null, int $days = 30): array
    {
        $knowledgeBaseService = app(ChatbotKnowledgeBaseService::class);
        $socialService = app(ChatbotSocialIntegrationService::class);

        return [
            'knowledge_base' => $knowledgeBaseService->getAnalyticsSummary($chatbotId, $days),
            'social_media' => $socialService->getSocialAnalytics(null, $chatbotId, $days),
            'enhanced_searches' => $this->getEnhancedSearchAnalytics($chatbotId, $days),
        ];
    }

    /**
     * Get enhanced search analytics.
     */
    protected function getEnhancedSearchAnalytics(?int $chatbotId = null, int $days = 30): array
    {
        // This would typically query the ChatbotAnalytics model
        // for enhanced search specific metrics
        return [
            'total_enhanced_searches' => 0,
            'average_response_time' => 0,
            'product_click_through_rate' => 0,
            'article_engagement_rate' => 0,
        ];
    }
}