<?php

namespace Database\Factories\Extensions\Chatbot;

use App\Extensions\Chatbot\System\Models\ChatbotKnowledgeBaseArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatbotKnowledgeBaseArticleFactory extends Factory
{
    protected $model = ChatbotKnowledgeBaseArticle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(3, true),
            'media_urls' => [],
            'product_ids' => [],
            'content_type' => $this->faker->randomElement(['text', 'multimedia', 'product', 'social']),
            'interactive_elements' => [],
            'source_platform' => null,
            'last_updated_from_source' => null,
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
            'chatbots' => [],
        ];
    }

    public function withMultimedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'multimedia',
            'media_urls' => [
                $this->faker->imageUrl(),
                $this->faker->imageUrl(),
            ],
        ]);
    }

    public function withProducts(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'product',
            'product_ids' => [
                $this->faker->numberBetween(1, 100),
                $this->faker->numberBetween(1, 100),
            ],
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function fromSocialMedia(string $platform = 'instagram'): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => 'social',
            'source_platform' => $platform,
            'last_updated_from_source' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}