<?php

namespace Tests\Unit\Extensions\Chatbot\Services;

use Tests\TestCase;
use App\Extensions\Chatbot\System\Services\ChatbotEnhancedService;
use App\Extensions\Chatbot\System\Services\ChatbotKnowledgeBaseService;
use App\Extensions\Chatbot\System\Services\ProductCatalogService;
use App\Extensions\Chatbot\System\Services\ChatbotCategoryService;
use App\Extensions\Chatbot\System\Services\ChatbotSocialIntegrationService;
use App\Extensions\Chatbot\System\Models\ChatbotKnowledgeBaseArticle;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\ChatbotProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatbotEnhancedServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChatbotEnhancedService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->service = new ChatbotEnhancedService(
            app(ChatbotKnowledgeBaseService::class),
            app(ProductCatalogService::class),
            app(ChatbotCategoryService::class),
            app(ChatbotSocialIntegrationService::class)
        );
    }

    public function test_enhanced_search_returns_articles_and_products()
    {
        // Create test data
        $category = ChatbotProductCategory::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Electronics'
        ]);

        $product = ChatbotProduct::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Smartphone',
            'description' => 'Latest smartphone model',
            'category_id' => $category->id
        ]);

        $article = ChatbotKnowledgeBaseArticle::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Smartphone Guide',
            'content' => 'How to use your smartphone effectively',
            'product_ids' => [$product->id]
        ]);

        // Perform search
        $results = $this->service->enhancedSearch('smartphone');

        // Assert results structure
        $this->assertIsArray($results);
        $this->assertArrayHasKey('articles', $results);
        $this->assertArrayHasKey('products', $results);
        $this->assertArrayHasKey('total_results', $results);
        $this->assertArrayHasKey('response_time_ms', $results);
    }

    public function test_get_dashboard_data_returns_comprehensive_metrics()
    {
        $dashboardData = $this->service->getDashboardData();

        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('knowledge_base', $dashboardData);
        $this->assertArrayHasKey('products', $dashboardData);
        $this->assertArrayHasKey('categories', $dashboardData);
        $this->assertArrayHasKey('recent_activity', $dashboardData);
        $this->assertArrayHasKey('performance_metrics', $dashboardData);
    }

    public function test_optimize_content_processes_articles_and_products()
    {
        // Create article without description
        $article = ChatbotKnowledgeBaseArticle::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Article',
            'content' => 'This is a test article with some content that should be used for description.',
            'description' => null
        ]);

        // Create product with inconsistent stock/availability
        $product = ChatbotProduct::factory()->create([
            'user_id' => $this->user->id,
            'stock_quantity' => 0,
            'availability' => 'in_stock'
        ]);

        $results = $this->service->optimizeContent();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('articles_processed', $results);
        $this->assertArrayHasKey('products_processed', $results);
        
        // Verify optimizations were applied
        $article->refresh();
        $this->assertNotNull($article->description);
        
        $product->refresh();
        $this->assertEquals('out_of_stock', $product->availability);
    }
}