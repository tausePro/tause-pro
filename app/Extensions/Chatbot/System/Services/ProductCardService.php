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
            ->active()
            ->inStock()
            ->when(isset($filters['category']), function($query) use ($filters) {
                return $query->whereJsonContains('categories', ['name' => $filters['category']]);
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
            ->active()
            ->inStock()
            ->where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%')
                  ->orWhereJsonContains('categories', ['name' => $query]);
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
            ->active()
            ->inStock()
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
            ->active()
            ->inStock()
            ->whereJsonContains('categories', ['name' => $category])
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
        // Extraer primera categoría si existe
        $categories = $product->categories ?? [];
        $firstCategory = !empty($categories) && isset($categories[0]['name']) 
            ? $categories[0]['name'] 
            : null;
        
        // Obtener currency de metadata o usar COP por defecto
        $currency = data_get($product->metadata, 'currency', 'COP');
        
        // Obtener rating de metadata si existe
        $rating = data_get($product->metadata, 'rating');
        
        return [
            'id' => $product->id,
            'woocommerce_id' => $product->woocommerce_id,
            'name' => $product->name,
            'description' => $this->truncateDescription($product->description, 100),
            'short_description' => $product->short_description,
            'price' => (float) $product->price,
            'regular_price' => $product->regular_price ? (float) $product->regular_price : null,
            'sale_price' => $product->sale_price ? (float) $product->sale_price : null,
            'currency' => $currency,
            'formatted_price' => $product->formatted_price ?? $this->formatPrice((float) $product->price, $currency),
            'image_url' => $product->image_url ?? $this->getPlaceholderImage(),
            'product_url' => $product->product_url,
            'categories' => $categories,
            'category' => $firstCategory,
            'in_stock' => (bool) $product->in_stock,
            'stock_quantity' => $product->stock_quantity ?? 0,
            'rating' => $rating,
            'sku' => $product->sku,
            'buttons' => [
                [
                    'type' => 'link',
                    'label' => '🛒 Ver producto',
                    'url' => $product->product_url ?? '#',
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
    protected function formatPrice(float $price, string $currency = 'COP'): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'COP' => '$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        // Para COP, formatear sin decimales
        if ($currency === 'COP') {
            return $symbol . number_format($price, 0, ',', '.') . ' COP';
        }
        
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




