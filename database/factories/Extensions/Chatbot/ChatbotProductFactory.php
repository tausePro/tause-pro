<?php

namespace Database\Factories\Extensions\Chatbot;

use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\ChatbotProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatbotProductFactory extends Factory
{
    protected $model = ChatbotProduct::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'chatbot_id' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
            'category_id' => ChatbotProductCategory::factory(),
            'image_url' => $this->faker->imageUrl(),
            'purchase_url' => $this->faker->url(),
            'availability' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'discontinued']),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'metadata' => [
                'brand' => $this->faker->company(),
                'model' => $this->faker->word(),
                'color' => $this->faker->colorName(),
            ],
        ];
    }

    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability' => 'in_stock',
            'stock_quantity' => $this->faker->numberBetween(1, 100),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'availability' => 'out_of_stock',
            'stock_quantity' => 0,
        ]);
    }
}