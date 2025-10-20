<?php

require_once 'vendor/autoload.php';

// Test the products endpoint directly
echo "=== Testing Products Endpoint ===\n\n";

// Simulate the controller logic
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Models\Chatbot;

$chatbotId = 5; // Known chatbot with products

echo "Testing for chatbot ID: $chatbotId\n";

try {
    $chatbot = Chatbot::find($chatbotId);
    if (!$chatbot) {
        echo "❌ Chatbot not found\n";
        exit(1);
    }
    
    echo "✅ Chatbot found: {$chatbot->title}\n";
    echo "   User ID: {$chatbot->user_id}\n";
    
    // Get products using the same logic as the controller
    $products = ChatbotProduct::where('user_id', $chatbot->user_id)
        ->whereHas('chatbots', function ($query) use ($chatbotId) {
            $query->where('chatbot_id', $chatbotId);
        })
        ->orderBy('created_at', 'desc')
        ->get();
    
    echo "✅ Found {$products->count()} products\n\n";
    
    if ($products->count() > 0) {
        echo "Products:\n";
        foreach ($products->take(3) as $product) {
            echo "- {$product->name}\n";
            echo "  Price: {$product->price} {$product->currency}\n";
            echo "  Image: " . ($product->image_url ? 'YES' : 'NO') . "\n";
            echo "  URL: {$product->purchase_url}\n\n";
        }
        
        // Test the mapping logic
        $mapped = $products->map(function ($product) {
            $imageUrl = null;
            if ($product->local_image_path && file_exists(storage_path('app/public/' . $product->local_image_path))) {
                $imageUrl = asset('storage/' . $product->local_image_path);
            } elseif ($product->image_url) {
                $imageUrl = $product->image_url;
            }
            
            $formattedPrice = null;
            if ($product->price) {
                $currency = $product->currency ?? 'COP';
                $symbol = match($currency) {
                    'COP' => '$',
                    'USD' => 'US$',
                    'EUR' => '€',
                    'GBP' => '£',
                    'MXN' => 'MX$',
                    default => $currency . ' '
                };
                
                if ($currency === 'COP') {
                    $formattedPrice = $symbol . number_format($product->price, 0, ',', '.');
                } else {
                    $formattedPrice = $symbol . number_format($product->price, 2);
                }
            }
            
            return [
                'id' => $product->id,
                'name' => html_entity_decode($product->name, ENT_QUOTES, 'UTF-8'),
                'description' => html_entity_decode($product->description ?? '', ENT_QUOTES, 'UTF-8'),
                'price' => $product->price,
                'currency' => $product->currency ?? 'COP',
                'formatted_price' => $formattedPrice,
                'image_url' => $imageUrl,
                'purchase_url' => $product->purchase_url ?? $product->url,
                'availability' => $product->availability ?? 'in_stock',
            ];
        });
        
        echo "✅ Mapping successful. Sample mapped product:\n";
        $sample = $mapped->first();
        echo "  Name: {$sample['name']}\n";
        echo "  Formatted Price: {$sample['formatted_price']}\n";
        echo "  Image URL: " . ($sample['image_url'] ?? 'None') . "\n";
        echo "  Purchase URL: {$sample['purchase_url']}\n";
        
        // Simulate the JSON response
        $response = [
            'products' => $mapped,
            'total' => $mapped->count(),
        ];
        
        echo "\n✅ Response structure ready with {$response['total']} products\n";
        
    } else {
        echo "❌ No products found for this chatbot\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";