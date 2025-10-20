<?php

namespace Tests\Unit\Extensions\Chatbot\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Extensions\Chatbot\System\Models\ChatbotKnowledgeBaseArticle;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\ChatbotAnalytics;
use App\Extensions\Chatbot\System\Models\ChatbotSocialContent;
use App\Models\User;

class ChatbotKnowledgeBaseArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the chatbot extension migrations
        $this->artisan('migrate', ['--path' => 'app/Extensions/Chatbot/database/migrations']);
    }

    public function test_article_can_be_created()
    {
        $user = User::factory()->create();

        $article = ChatbotKnowledgeBaseArticle::create([
            'user_id' => $user->id,
            'title' => 'Test Article',
            'description' => 'A test article',
            'content' => 'This is test content',
            'content_type' => 'text'
        ]);

        $this->assertInstanceOf(ChatbotKnowledgeBaseArticle::class, $article);
        $this->assertEquals('Test Article', $article->title);
        $this->assertEquals('text', $article->content_type);
    }

    public function test_article_belongs_to_user()
    {
        $user = User::factory()->create();
        $article = ChatbotKnowledgeBaseArticle::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $article->user);
        $this->assertEquals($user->id, $article->user->id);
    }

    public function test_article_has_many_products()
    {
        $article = ChatbotKnowledgeBaseArticle::factory()->create();
        $product = ChatbotProduct::factory()->create();
        
        $article->products()->attach($product->id);

        $this->assertCount(1, $article->products);
        $this->assertInstanceOf(ChatbotProduct::class, $article->products->first());
    }

    public function test_article_has_multimedia_method()
    {
        $articleWithMedia = ChatbotKnowledgeBaseArticle::factory()->create([
            'media_urls' => ['https://example.com/image.jpg', 'https://example.com/video.mp4']
        ]);

        $articleWithoutMedia = ChatbotKnowledgeBaseArticle::factory()->create([
            'media_urls' => []
        ]);

        $this->assertTrue($articleWithMedia->hasMultimedia());
        $this->assertFalse($articleWithoutMedia->hasMultimedia());
    }

    public function test_article_has_products_method()
    {
        $articleWithProducts = ChatbotKnowledgeBaseArticle::factory()->create([
            'product_ids' => [1, 2, 3]
        ]);

        $articleWithoutProducts = ChatbotKnowledgeBaseArticle::factory()->create([
            'product_ids' => []
        ]);

        $this->assertTrue($articleWithProducts->hasProducts());
        $this->assertFalse($articleWithoutProducts->hasProducts());
    }

    public function test_article_content_type_display_attribute()
    {
        $textArticle = ChatbotKnowledgeBaseArticle::factory()->create(['content_type' => 'text']);
        $multimediaArticle = ChatbotKnowledgeBaseArticle::factory()->create(['content_type' => 'multimedia']);
        $productArticle = ChatbotKnowledgeBaseArticle::factory()->create(['content_type' => 'product']);

        $this->assertEquals('Text Only', $textArticle->content_type_display);
        $this->assertEquals('Multimedia', $multimediaArticle->content_type_display);
        $this->assertEquals('Product Information', $productArticle->content_type_display);
    }

    public function test_article_first_media_url_attribute()
    {
        $article = ChatbotKnowledgeBaseArticle::factory()->create([
            'media_urls' => ['https://example.com/first.jpg', 'https://example.com/second.jpg']
        ]);

        $this->assertEquals('https://example.com/first.jpg', $article->first_media_url);
    }

    public function test_article_add_product_method()
    {
        $article = ChatbotKnowledgeBaseArticle::factory()->create(['product_ids' => []]);
        
        $article->addProduct(1);
        $article->addProduct(2);
        $article->addProduct(1); // Should not duplicate

        $article->refresh();
        $this->assertEquals([1, 2], $article->product_ids);
    }

    public function test_article_remove_product_method()
    {
        $article = ChatbotKnowledgeBaseArticle::factory()->create(['product_ids' => [1, 2, 3]]);
        
        $article->removeProduct(2);

        $article->refresh();
        $this->assertEquals([1, 3], $article->product_ids);
    }

    public function test_article_add_media_url_method()
    {
        $article = ChatbotKnowledgeBaseArticle::factory()->create(['media_urls' => []]);
        
        $article->addMediaUrl('https://example.com/image1.jpg');
        $article->addMediaUrl('https://example.com/image2.jpg');
        $article->addMediaUrl('https://example.com/image1.jpg'); // Should not duplicate

        $article->refresh();
        $this->assertEquals([
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg'
        ], $article->media_urls);
    }

    public function test_article_chatbot_assignment_methods()
    {
        $article = ChatbotKnowledgeBaseArticle::factory()->create(['chatbots' => []]);
        
        $article->assignToChatbot(1);
        $article->assignToChatbot(2);
        
        $article->refresh();
        $this->assertTrue($article->isAssignedToChatbot(1));
        $this->assertTrue($article->isAssignedToChatbot(2));
        $this->assertFalse($article->isAssignedToChatbot(3));

        $article->unassignFromChatbot(1);
        
        $article->refresh();
        $this->assertFalse($article->isAssignedToChatbot(1));
        $this->assertTrue($article->isAssignedToChatbot(2));
    }

    public function test_article_scopes()
    {
        ChatbotKnowledgeBaseArticle::factory()->create(['content_type' => 'text']);
        ChatbotKnowledgeBaseArticle::factory()->create(['content_type' => 'multimedia']);
        ChatbotKnowledgeBaseArticle::factory()->create(['source_platform' => 'instagram']);
        ChatbotKnowledgeBaseArticle::factory()->create(['is_featured' => true]);
        ChatbotKnowledgeBaseArticle::factory()->create(['media_urls' => ['image.jpg']]);
        ChatbotKnowledgeBaseArticle::factory()->create(['product_ids' => [1, 2]]);

        $this->assertCount(1, ChatbotKnowledgeBaseArticle::byContentType('text')->get());
        $this->assertCount(1, ChatbotKnowledgeBaseArticle::bySourcePlatform('instagram')->get());
        $this->assertCount(1, ChatbotKnowledgeBaseArticle::featured()->get());
        $this->assertCount(1, ChatbotKnowledgeBaseArticle::withMultimedia()->get());
        $this->assertCount(1, ChatbotKnowledgeBaseArticle::withProducts()->get());
    }

    public function test_article_search_scope()
    {
        ChatbotKnowledgeBaseArticle::factory()->create(['title' => 'iPhone 15 Review']);
        ChatbotKnowledgeBaseArticle::factory()->create(['description' => 'iPhone accessories guide']);
        ChatbotKnowledgeBaseArticle::factory()->create(['content' => 'Samsung Galaxy features']);

        $results = ChatbotKnowledgeBaseArticle::search('iPhone')->get();

        $this->assertCount(2, $results);
    }
}