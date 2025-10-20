<?php

// Test directo de la consulta de productos
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

try {
    // Simular la consulta que hace el controlador
    $chatbotId = 5;
    
    $products = \Illuminate\Support\Facades\DB::table('ext_chatbot_products')
        ->join('ext_chatbot_product_pivot', 'ext_chatbot_products.id', '=', 'ext_chatbot_product_pivot.product_id')
        ->where('ext_chatbot_product_pivot.chatbot_id', $chatbotId)
        ->select('ext_chatbot_products.*')
        ->orderBy('ext_chatbot_products.created_at', 'desc')
        ->get();

    echo "✅ Productos encontrados: " . $products->count() . "\n";
    
    if ($products->count() > 0) {
        echo "\n📦 Primeros 3 productos:\n";
        foreach ($products->take(3) as $product) {
            echo "- {$product->name}\n";
            echo "  Precio: {$product->price} {$product->currency}\n";
            echo "  Auto-detectado: " . ($product->auto_detected ? 'Sí' : 'No') . "\n";
            echo "  Creado: {$product->created_at}\n\n";
        }
    }
    
    // Simular respuesta JSON como la del controlador
    $response = [
        'products' => $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => html_entity_decode($product->name, ENT_QUOTES, 'UTF-8'),
                'description' => html_entity_decode($product->description ?? '', ENT_QUOTES, 'UTF-8'),
                'price' => $product->price,
                'currency' => $product->currency ?? 'COP',
                'formatted_price' => '$' . number_format($product->price, 0, ',', '.'),
                'image_url' => $product->image_url,
                'availability' => $product->availability ?? 'in_stock',
                'auto_detected' => (bool) $product->auto_detected,
                'created_at' => $product->created_at,
            ];
        })->toArray(),
        'total' => $products->count(),
    ];
    
    echo "✅ Respuesta JSON simulada:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}