<?php

require_once 'vendor/autoload.php';

use App\Extensions\Chatbot\System\Parsers\ProductExtractor;

echo "=== Testing Product Extraction ===\n\n";

$extractor = new ProductExtractor();

// Test URL
$testUrl = 'https://tez.com.co/productos/';

echo "Testing URL: $testUrl\n";

try {
    // Get HTML content
    $html = @file_get_contents($testUrl);
    
    if ($html === false) {
        echo "❌ Failed to fetch URL content\n";
        exit(1);
    }
    
    echo "✅ HTML content fetched (" . strlen($html) . " bytes)\n";
    
    // Check if it's a product page
    $isProductPage = $extractor->isProductPage($html, $testUrl);
    echo "Is product page: " . ($isProductPage ? "YES" : "NO") . "\n";
    
    if ($isProductPage) {
        // Extract product data
        $productData = $extractor->extractProductData($html, $testUrl);
        
        if ($productData) {
            echo "\n✅ Product detected:\n";
            echo "  Name: " . ($productData['name'] ?? 'N/A') . "\n";
            echo "  Price: " . ($productData['price'] ?? 'N/A') . " " . ($productData['currency'] ?? 'N/A') . "\n";
            echo "  Image: " . ($productData['image'] ? 'YES (' . $productData['image'] . ')' : 'NO') . "\n";
            echo "  Description: " . substr($productData['description'] ?? 'N/A', 0, 100) . "...\n";
            echo "  Platform: " . ($productData['platform'] ?? 'N/A') . "\n";
            echo "  Availability: " . ($productData['availability'] ?? 'N/A') . "\n";
        } else {
            echo "\n❌ No product data extracted\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";