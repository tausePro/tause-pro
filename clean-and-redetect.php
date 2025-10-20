<?php

require_once 'vendor/autoload.php';

echo "=== Cleaning Old Products and Re-detecting ===\n\n";

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=magicai_local', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Delete old products for chatbot 5
    echo "1. Cleaning old products for chatbot 5...\n";
    
    // First delete pivot relationships
    $stmt = $pdo->prepare("DELETE FROM ext_chatbot_product_pivot WHERE chatbot_id = 5");
    $stmt->execute();
    echo "   Deleted pivot relationships: " . $stmt->rowCount() . "\n";
    
    // Then delete products that are no longer associated with any chatbot
    $stmt = $pdo->prepare("
        DELETE p FROM ext_chatbot_products p
        LEFT JOIN ext_chatbot_product_pivot pp ON p.id = pp.product_id
        WHERE pp.product_id IS NULL AND p.auto_detected = 1
    ");
    $stmt->execute();
    echo "   Deleted orphaned products: " . $stmt->rowCount() . "\n";
    
    echo "\n✅ Cleanup complete!\n";
    echo "\nNow go to the chatbot interface and:\n";
    echo "1. Go to Train step\n";
    echo "2. Enter URL: https://tez.com.co/productos/\n";
    echo "3. Check 'Detect Products'\n";
    echo "4. Select 'Single URL'\n";
    echo "5. Click refresh button\n";
    echo "6. Go to Products step to see the new products with images!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Cleanup Complete ===\n";