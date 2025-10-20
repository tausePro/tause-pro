<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Support\Collection;

class ProductCardService
{
    /**
     * Generate product cards for display in chat
     */
    public function generateProductCards(Chatbot $chatbot, array $filters = [], int $limit = 3): array
    {
        $products = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->when(isset($filters['category']), function($query) use ($filters) {
                return $query->where('category', $filters['category']);
            })
            ->when(isset($filters['price_range']), function($query) use ($filters) {
                [$min, $max] = $filters['price_range'];
                return $query->whereBetween('price', [$min, $max]);
            })
            ->when(isset($filters['search']), function($query) use ($filters) {
                return $query->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->limit($limit)
            ->get();
        
        return $this->formatProductCards($products);
    }
    
    /**
     * Generate a single product card
     */
    public function generateSingleProductCard(int $productId): ?array
    {
        $product = ChatbotProduct::find($productId);
        
        if (!$product) {
            return null;
        }
        
        return $this->formatSingleProduct($product);
    }
    
    /**
     * Search products and return as cards
     */
    public function searchProducts(Chatbot $chatbot, string $query, int $limit = 5): array
    {
        $products = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%')
                  ->orWhere('category', 'like', '%' . $query . '%');
            })
            ->limit($limit)
            ->get();
        
        return $this->formatProductCards($products);
    }
    
    /**
     * Get recommended products based on user behavior
     */
    public function getRecommendedProducts(Chatbot $chatbot, array $context = [], int $limit = 3): array
    {
        // Por ahora, retornamos productos aleatorios
        // TODO: Implementar ML para recomendaciones personalizadas
        $products = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
        
        return $this->formatProductCards($products);
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory(Chatbot $chatbot, string $category, int $limit = 6): array
    {
        $products = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->where('category', $category)
            ->limit($limit)
            ->get();
        
        return $this->formatProductCards($products);
    }
    
    /**
     * Format products as cards
     */
    protected function formatProductCards(Collection $products): array
    {
        return [
            'type' => 'product_carousel',
            'products' => $products->map(function($product) {
                return $this->formatSingleProduct($product);
            })->toArray()
        ];
    }
    
    /**
     * Format a single product
     */
    protected function formatSingleProduct(ChatbotProduct $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $this->truncateDescription($product->description, 100),
            'price' => $product->price,
            'currency' => $product->currency ?? 'USD',
            'formatted_price' => $this->formatPrice($product->price, $product->currency ?? 'USD'),
            'image' => $product->image ?? $this->getPlaceholderImage(),
            'url' => $product->url,
            'category' => $product->category,
            'in_stock' => $product->stock > 0,
            'stock' => $product->stock,
            'rating' => $product->rating ?? null,
            'buttons' => [
                [
                    'type' => 'link',
                    'label' => '🛒 Ver producto',
                    'url' => $product->url,
                    'target' => '_blank'
                ],
                [
                    'type' => 'quick_reply',
                    'label' => '💬 Más info',
                    'value' => 'info_product_' . $product->id
                ],
                [
                    'type' => 'quick_reply',
                    'label' => '✨ Similar',
                    'value' => 'similar_product_' . $product->id
                ]
            ]
        ];
    }
    
    /**
     * Truncate description
     */
    protected function truncateDescription(?string $description, int $length = 100): string
    {
        if (!$description) {
            return '';
        }
        
        if (strlen($description) <= $length) {
            return $description;
        }
        
        return substr($description, 0, $length) . '...';
    }
    
    /**
     * Format price with currency
     */
    protected function formatPrice(float $price, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'COP' => '$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($price, 2);
    }
    
    /**
     * Get placeholder image
     */
    protected function getPlaceholderImage(): string
    {
        return 'https://via.placeholder.com/300x300?text=Product';
    }
}




