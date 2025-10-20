<?php

require_once 'vendor/autoload.php';

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;

// Test script to verify product detection and association flow

echo "=== Testing Product Detection and Association Flow ===\n\n";

// Find a test chatbot
$chatbot = Chatbot::first();
if (!$chatbot) {
    echo "❌ No chatbot found. Please create a chatbot first.\n";
    exit(1);
}

echo "✅ Found chatbot: {$chatbot->title} (ID: {$chatbot->id})\n";

// Check if chatbot has products
$products = $chatbot->products()->get();
echo "📦 Chatbot has " . $products->count() . " products associated\n\n";

if ($products->count() > 0) {
    echo "Products associated with this chatbot:\n";
    foreach ($products as $product) {
        echo "  - {$product->name} (${$product->price} {$product->currency})\n";
        echo "    URL: {$product->purchase_url}\n";
        echo "    Auto-detected: " . ($product->auto_detected ? 'Yes' : 'No') . "\n";
        echo "    Availability: {$product->availability}\n\n";
    }
} else {
    echo "ℹ️  No products associated with this chatbot yet.\n";
    echo "   To test, go to the chatbot training page and scan a URL with 'detect products' enabled.\n\n";
}

// Check pivot table
$pivotCount = DB::table('ext_chatbot_product_pivot')
    ->where('chatbot_id', $chatbot->id)
    ->count();

echo "🔗 Pivot table entries for this chatbot: {$pivotCount}\n";

// Check all products for this user
$allUserProducts = ChatbotProduct::where('user_id', $chatbot->user_id)->get();
echo "📊 Total products for user {$chatbot->user_id}: " . $allUserProducts->count() . "\n";

if ($allUserProducts->count() > 0) {
    echo "\nAll products for this user:\n";
    foreach ($allUserProducts as $product) {
        $associatedChatbots = $product->chatbots()->count();
        echo "  - {$product->name} (Associated with {$associatedChatbots} chatbots)\n";
    }
}

echo "\n=== Test Complete ===\n";