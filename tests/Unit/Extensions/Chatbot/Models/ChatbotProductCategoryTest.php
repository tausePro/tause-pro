<?php

namespace Tests\Unit\Extensions\Chatbot\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Extensions\Chatbot\System\Models\ChatbotProductCategory;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Models\User;

class ChatbotProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the chatbot extension migrations
        $this->artisan('migrate', ['--path' => 'app/Extensions/Chatbot/database/migrations']);
    }

    public function test_category_can_be_created()
    {
        $user = User::factory()->create();

        $category = ChatbotProductCategory::create([
            'user_id' => $user->id,
            'name' => 'Electronics',
            'description' => 'Electronic products'
        ]);

        $this->assertInstanceOf(ChatbotProductCategory::class, $category);
        $this->assertEquals('Electronics', $category->name);
        $this->assertEquals('Electronic products', $category->description);
    }

    public function test_category_belongs_to_user()
    {
        $user = User::factory()->create();
        $category = ChatbotProductCategory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user->id);
    }

    public function test_category_has_parent_child_relationship()
    {
        $parentCategory = ChatbotProductCategory::factory()->create(['name' => 'Electronics']);
        $childCategory = ChatbotProductCategory::factory()->create([
            'name' => 'Smartphones',
            'parent_id' => $parentCategory->id
        ]);

        $this->assertInstanceOf(ChatbotProductCategory::class, $childCategory->parent);
        $this->assertEquals($parentCategory->id, $childCategory->parent->id);
        $this->assertCount(1, $parentCategory->children);
        $this->assertEquals($childCategory->id, $parentCategory->children->first()->id);
    }

    public function test_category_has_many_products()
    {
        $category = ChatbotProductCategory::factory()->create();
        $product1 = ChatbotProduct::factory()->create(['category_id' => $category->id]);
        $product2 = ChatbotProduct::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->products);
        $this->assertInstanceOf(ChatbotProduct::class, $category->products->first());
    }

    public function test_category_full_path_attribute()
    {
        $parentCategory = ChatbotProductCategory::factory()->create(['name' => 'Electronics']);
        $childCategory = ChatbotProductCategory::factory()->create([
            'name' => 'Smartphones',
            'parent_id' => $parentCategory->id
        ]);
        $grandchildCategory = ChatbotProductCategory::factory()->create([
            'name' => 'iPhone',
            'parent_id' => $childCategory->id
        ]);

        $this->assertEquals('Electronics', $parentCategory->full_path);
        $this->assertEquals('Electronics > Smartphones', $childCategory->full_path);
        $this->assertEquals('Electronics > Smartphones > iPhone', $grandchildCategory->full_path);
    }

    public function test_category_depth_attribute()
    {
        $parentCategory = ChatbotProductCategory::factory()->create(['name' => 'Electronics']);
        $childCategory = ChatbotProductCategory::factory()->create([
            'name' => 'Smartphones',
            'parent_id' => $parentCategory->id
        ]);
        $grandchildCategory = ChatbotProductCategory::factory()->create([
            'name' => 'iPhone',
            'parent_id' => $childCategory->id
        ]);

        $this->assertEquals(0, $parentCategory->depth);
        $this->assertEquals(1, $childCategory->depth);
        $this->assertEquals(2, $grandchildCategory->depth);
    }

    public function test_category_roots_scope()
    {
        $rootCategory1 = ChatbotProductCategory::factory()->create(['parent_id' => null]);
        $rootCategory2 = ChatbotProductCategory::factory()->create(['parent_id' => null]);
        $childCategory = ChatbotProductCategory::factory()->create(['parent_id' => $rootCategory1->id]);

        $rootCategories = ChatbotProductCategory::roots()->get();

        $this->assertCount(2, $rootCategories);
        $this->assertTrue($rootCategories->contains($rootCategory1));
        $this->assertTrue($rootCategories->contains($rootCategory2));
        $this->assertFalse($rootCategories->contains($childCategory));
    }

    public function test_category_has_products_method()
    {
        $categoryWithProducts = ChatbotProductCategory::factory()->create();
        $categoryWithoutProducts = ChatbotProductCategory::factory()->create();
        
        ChatbotProduct::factory()->create(['category_id' => $categoryWithProducts->id]);

        $this->assertTrue($categoryWithProducts->hasProducts());
        $this->assertFalse($categoryWithoutProducts->hasProducts());
    }

    public function test_category_total_products_count_attribute()
    {
        $parentCategory = ChatbotProductCategory::factory()->create();
        $childCategory = ChatbotProductCategory::factory()->create(['parent_id' => $parentCategory->id]);
        
        // Add products to parent category
        ChatbotProduct::factory()->count(2)->create(['category_id' => $parentCategory->id]);
        
        // Add products to child category
        ChatbotProduct::factory()->count(3)->create(['category_id' => $childCategory->id]);

        // Refresh to load relationships
        $parentCategory->refresh();

        $this->assertEquals(5, $parentCategory->total_products_count);
        $this->assertEquals(3, $childCategory->total_products_count);
    }
}