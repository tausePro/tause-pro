<?php

// Debug script to check products in database
echo "=== Product Detection Debug ===\n\n";

// Check if products table exists and has data
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=magicai_local', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check products table
    echo "1. Checking ext_chatbot_products table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ext_chatbot_products");
    $result = $stmt->fetch();
    echo "   Total products: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "\n   Recent products:\n";
        $stmt = $pdo->query("SELECT id, name, price, currency, auto_detected, created_at FROM ext_chatbot_products ORDER BY created_at DESC LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - ID: {$row['id']}, Name: " . substr($row['name'], 0, 50) . "..., Price: {$row['price']} {$row['currency']}, Auto: {$row['auto_detected']}, Created: {$row['created_at']}\n";
        }
    }
    
    // Check pivot table
    echo "\n2. Checking ext_chatbot_product_pivot table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ext_chatbot_product_pivot");
    $result = $stmt->fetch();
    echo "   Total associations: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "\n   Recent associations:\n";
        $stmt = $pdo->query("SELECT chatbot_id, product_id, created_at FROM ext_chatbot_product_pivot ORDER BY created_at DESC LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - Chatbot: {$row['chatbot_id']}, Product: {$row['product_id']}, Created: {$row['created_at']}\n";
        }
    }
    
    // Check chatbots
    echo "\n3. Checking ext_chatbots table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ext_chatbots");
    $result = $stmt->fetch();
    echo "   Total chatbots: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "\n   Recent chatbots:\n";
        $stmt = $pdo->query("SELECT id, title, user_id, created_at FROM ext_chatbots ORDER BY created_at DESC LIMIT 3");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - ID: {$row['id']}, Title: {$row['title']}, User: {$row['user_id']}, Created: {$row['created_at']}\n";
        }
    }
    
    // Check products for specific chatbot
    echo "\n4. Products for chatbot ID 1:\n";
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.currency 
        FROM ext_chatbot_products p
        JOIN ext_chatbot_product_pivot pp ON p.id = pp.product_id
        WHERE pp.chatbot_id = 1
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "   No products found for chatbot ID 1\n";
        
        // Check if chatbot 1 exists
        $stmt = $pdo->prepare("SELECT id, title FROM ext_chatbots WHERE id = 1");
        $stmt->execute();
        $chatbot = $stmt->fetch();
        
        if ($chatbot) {
            echo "   Chatbot 1 exists: {$chatbot['title']}\n";
        } else {
            echo "   Chatbot 1 does not exist\n";
        }
    } else {
        foreach ($products as $product) {
            echo "   - {$product['name']} ({$product['price']} {$product['currency']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";