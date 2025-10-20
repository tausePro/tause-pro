<?php

namespace Tests\Unit\Extensions\Chatbot\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\ChatbotProductCategory;
use App\Extensions\Chatbot\System\Models\ChatbotKnowledgeBaseArticle;
use App\Models\User;

class ChatbotProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the chatbot extension migrations
        $this->artisan('migrate', ['--path' => 'app/Extensions/Chatbot/database/migrations']);
    }

    public function test_product_can_be_created()
    {
        $user = User::factory()->create();
        $category = ChatbotProductCategory::factory()->create(['user_id' => $user->id]);

        $product = ChatbotProduct::create([
            'user_id' => $user->id,
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 99.99,
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'availability' => 'in_stock',
            'stock_quantity' => 10
        ]);

        $this->assertInstanceOf(ChatbotProduct::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals('TEST-001', $product->sku);
    }

    public function test_product_belongs_to_user()
    {
        $user = User::factory()->create();
        $product = ChatbotProduct::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $product->user);
        $this->assertEquals($user->id, $product->user->id);
    }

    public function test_product_belongs_to_category()
    {
        $category = ChatbotProductCategory::factory()->create();
        $product = ChatbotProduct::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(ChatbotProductCategory::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_has_many_knowledge_base_articles()
    {
        $product = ChatbotProduct::factory()->create();
        $article = ChatbotKnowledgeBaseArticle::factory()->create();
        
        $product->knowledgeBaseArticles()->attach($article->id);

        $this->assertCount(1, $product->knowledgeBaseArticles);
        $this->assertInstanceOf(ChatbotKnowledgeBaseArticle::class, $product->knowledgeBaseArticles->first());
    }

    public function test_product_formatted_price_attribute()
    {
        $product = ChatbotProduct::factory()->create(['price' => 99.99]);

        $this->assertEquals('$99.99', $product->formatted_price);
    }

    public function test_product_is_in_stock_method()
    {
        $inStockProduct = ChatbotProduct::factory()->create([
            'availability' => 'in_stock',
            'stock_quantity' => 5
        ]);

        $outOfStockProduct = ChatbotProduct::factory()->create([
            'availability' => 'out_of_stock',
            'stock_quantity' => 0
        ]);

        $this->assertTrue($inStockProduct->isInStock());
        $this->assertFalse($outOfStockProduct->isInStock());
    }

    public function test_product_availability_status_attribute()
    {
        $inStockProduct = ChatbotProduct::factory()->create([
            'availability' => 'in_stock',
            'stock_quantity' => 5
        ]);

        $outOfStockProduct = ChatbotProduct::factory()->create([
            'availability' => 'out_of_stock',
            'stock_quantity' => 0
        ]);

        $this->assertEquals('In Stock (5)', $inStockProduct->availability_status);
        $this->assertEquals('Out of Stock', $outOfStockProduct->availability_status);
    }

    public function test_product_available_scope()
    {
        ChatbotProduct::factory()->create([
            'availability' => 'in_stock',
            'stock_quantity' => 5
        ]);

        ChatbotProduct::factory()->create([
            'availability' => 'out_of_stock',
            'stock_quantity' => 0
        ]);

        $availableProducts = ChatbotProduct::available()->get();

        $this->assertCount(1, $availableProducts);
    }

    public function test_product_search_scope()
    {
        ChatbotProduct::factory()->create(['name' => 'iPhone 15']);
        ChatbotProduct::factory()->create(['name' => 'Samsung Galaxy']);
        ChatbotProduct::factory()->create(['description' => 'iPhone accessories']);

        $results = ChatbotProduct::search('iPhone')->get();

        $this->assertCount(2, $results);
    }
}