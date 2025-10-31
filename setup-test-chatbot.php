<?php

// Script para habilitar Sales Agent en chatbot para pruebas

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Extensions\Chatbot\System\Models\Chatbot;

echo "🔧 CONFIGURACIÓN DE CHATBOT PARA PRUEBAS\n";
echo "=========================================\n\n";

// Buscar chatbots disponibles
$chatbots = Chatbot::all();

if ($chatbots->count() === 0) {
    echo "❌ No hay chatbots en la base de datos.\n";
    echo "   Crea uno primero desde el panel de administración.\n";
    exit(1);
}

echo "Chatbots disponibles:\n";
echo "--------------------\n";
foreach ($chatbots as $i => $chatbot) {
    echo ($i + 1) . ". ID: {$chatbot->id} | {$chatbot->name}\n";
    echo "   - Sales Agent: " . ($chatbot->sales_agent_enabled ? '✅' : '❌') . "\n";
    echo "   - WooCommerce: " . ($chatbot->woocommerce_enabled ? '✅' : '❌') . "\n";
    echo "   - Productos: {$chatbot->products()->count()}\n\n";
}

// Si ya hay uno habilitado, mostrar info
$enabledChatbot = Chatbot::where('sales_agent_enabled', true)->first();

if ($enabledChatbot) {
    echo "✅ Ya existe un chatbot con Sales Agent habilitado:\n";
    echo "   ID: {$enabledChatbot->id} | {$enabledChatbot->name}\n\n";
    
    echo "📋 Configuración actual:\n";
    echo "-----------------------\n";
    echo "WooCommerce: " . ($enabledChatbot->woocommerce_enabled ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
    echo "Wompi: " . ($enabledChatbot->wompi_enabled ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
    
    $keywords = $enabledChatbot->sales_agent_keywords ?? [];
    echo "Keywords: " . (empty($keywords) ? '⚠️ Ninguno' : count($keywords) . " configurados") . "\n";
    if (!empty($keywords)) {
        echo "  → " . implode(', ', $keywords) . "\n";
    }
    
    $productsCount = $enabledChatbot->products()->count();
    $productsInStock = $enabledChatbot->products()->where('in_stock', true)->count();
    echo "Productos: {$productsCount} total, {$productsInStock} en stock\n\n";
    
    if ($productsInStock > 0) {
        echo "✅ TODO LISTO PARA PROBAR!\n\n";
        echo "🧪 PASOS PARA PROBAR:\n";
        echo "-------------------\n";
        echo "1. Limpia caches (ver abajo)\n";
        echo "2. Abre el chatbot embebido en un navegador\n";
        echo "3. Escribe: '¿Tienen [nombre de un producto]?'\n";
        echo "4. Deberías ver el grid de productos automáticamente\n";
        echo "5. Click en 'Comprar' para probar el flujo completo\n\n";
        
        // Mostrar algunos productos de ejemplo
        $sampleProducts = $enabledChatbot->products()->where('in_stock', true)->limit(3)->get();
        if ($sampleProducts->count() > 0) {
            echo "📦 Productos de ejemplo para probar:\n";
            foreach ($sampleProducts as $product) {
                echo "   • {$product->name}\n";
                echo "     Mensaje: '¿Tienen {$product->name}?'\n";
            }
        }
    } else {
        echo "⚠️ No hay productos en stock.\n";
        echo "   Sincroniza con WooCommerce:\n";
        echo "   php artisan chatbot:sync-products {$enabledChatbot->id}\n";
    }
    
} else {
    echo "⚠️ No hay chatbots con Sales Agent habilitado.\n\n";
    
    // Buscar el primer chatbot con productos
    $chatbotWithProducts = null;
    foreach ($chatbots as $chatbot) {
        if ($chatbot->products()->count() > 0) {
            $chatbotWithProducts = $chatbot;
            break;
        }
    }
    
    if (!$chatbotWithProducts) {
        $chatbotWithProducts = $chatbots->first();
    }
    
    echo "💡 ¿Quieres habilitar Sales Agent en '{$chatbotWithProducts->name}' (ID: {$chatbotWithProducts->id})?\n";
    echo "\n";
    echo "Ejecuta este SQL:\n";
    echo "----------------\n";
    echo "UPDATE ext_chatbots SET \n";
    echo "  sales_agent_enabled = 1,\n";
    echo "  woocommerce_enabled = 1,\n";
    echo "  sales_agent_keywords = '[\"comprar\",\"precio\",\"producto\",\"catálogo\",\"ver productos\",\"busco\",\"necesito\",\"quiero\"]'\n";
    echo "WHERE id = {$chatbotWithProducts->id};\n\n";
    
    echo "O ejecuta este comando PHP:\n";
    echo "--------------------------\n";
    echo "php -r \"require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$kernel = \$app->make(Illuminate\\Contracts\\Console\\Kernel::class); \$kernel->bootstrap(); \$c = App\\Extensions\\Chatbot\\System\\Models\\Chatbot::find({$chatbotWithProducts->id}); \$c->update(['sales_agent_enabled' => true, 'woocommerce_enabled' => true, 'sales_agent_keywords' => ['comprar','precio','producto','catálogo','ver productos','busco','necesito','quiero']]); echo 'Sales Agent habilitado!';\" \n\n";
}

echo "\n";
echo "🧹 LIMPIAR CACHES:\n";
echo "-----------------\n";
echo "php artisan cache:clear\n";
echo "php artisan view:clear\n";
echo "php artisan config:clear\n\n";

echo "=========================================\n";
echo "Script completado: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n";




