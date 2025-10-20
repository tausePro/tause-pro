<?php

namespace Database\Factories\Extensions\Chatbot;

use App\Extensions\Chatbot\System\Models\ChatbotProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatbotProductCategoryFactory extends Factory
{
    protected $model = ChatbotProductCategory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'parent_id' => null,
        ];
    }

    public function withParent(ChatbotProductCategory $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent ? $parent->id : ChatbotProductCategory::factory(),
        ]);
    }
}