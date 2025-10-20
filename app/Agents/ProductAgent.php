<?php

namespace App\Agents;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\Toolkit;
use NeuronAI\Chat\Messages\UserMessage;

class ProductAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: config('neuron.providers.openai.key'),
            model: config('neuron.providers.openai.model'),
        );
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                "You are a product recommendation expert for an e-commerce store.",
                "You help customers find the right products based on their needs.",
                "You provide detailed product information including benefits, pricing, and availability.",
                "You always recommend products that are currently in stock.",
                "You format your responses with clear product names, descriptions, and prices.",
                "You include a 'Comprar aquí' link for each recommended product.",
            ],
            instructions: [
                "When a customer asks about products, search the product database first.",
                "Provide 2-3 relevant product recommendations.",
                "Include product name, key benefits, price, and purchase link.",
                "If no products match the query, suggest similar categories.",
                "Always be helpful and informative.",
            ]
        );
    }

    protected function tools(): array
    {
        return [
            Toolkit::make([
                Tool::make(
                    name: 'search_products',
                    description: 'Search for products in the store database',
                    properties: [
                        ToolProperty::make('query', 'string', 'Search query for products'),
                        ToolProperty::make('category', 'string', 'Product category filter (optional)'),
                        ToolProperty::make('limit', 'integer', 'Maximum number of products to return (default: 5)'),
                    ],
                    handler: function (array $arguments) {
                        return $this->searchProducts($arguments['query'], $arguments['category'] ?? null, $arguments['limit'] ?? 5);
                    }
                ),
            ])
        ];
    }

    /**
     * Search products in the store
     */
    private function searchProducts(string $query, ?string $category = null, int $limit = 5): array
    {
        // Mock data for now
        return [
            'products' => [
                [
                    'id' => 1,
                    'name' => 'Muleta Convencional en Aluminio',
                    'description' => 'Muleta ligera, ajustable en altura y brinda una asistencia segura para la movilidad.',
                    'price' => 102000,
                    'formatted_price' => '$102,000 COP',
                    'image_url' => 'https://example.com/muleta.jpg',
                    'in_stock' => true,
                    'stock_quantity' => 15,
                    'purchase_link' => 'https://aliviate.com.co/producto/muleta-convencional-en-aluminio',
                ],
            ],
            'total' => 1,
            'query' => $query,
        ];
    }
}
