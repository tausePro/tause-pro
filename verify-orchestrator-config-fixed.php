<?php

// Verificación de configuración del Sales Agent Orchestrator (CORREGIDO)

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Extensions\Chatbot\System\Models\Chatbot;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICACIÓN DEL SALES AGENT ORCHESTRATOR\n";
echo "=============================================\n\n";

// 1. Verificar chatbots con Sales Agent habilitado
echo "1️⃣ Chatbots con Sales Agent habilitado:\n";
echo "----------------------------------------\n";

$chatbots = Chatbot::where('sales_agent_enabled', true)->get();

if ($chatbots->count() === 0) {
    echo "⚠️  No hay chatbots con Sales Agent habilitado\n\n";
} else {
    foreach ($chatbots as $chatbot) {
        echo "✅ ID: {$chatbot->id} | Nombre: " . ($chatbot->name ?: 'Sin nombre') . "\n";
        echo '   - WooCommerce: ' . ($chatbot->woocommerce_enabled ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
        echo '   - Wompi: ' . ($chatbot->wompi_enabled ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";

        // Keywords
        $keywords = $chatbot->sales_agent_keywords ?? [];
        echo '   - Keywords: ' . (empty($keywords) ? '⚠️  Ninguno configurado' : count($keywords) . ' configurados') . "\n";
        if (! empty($keywords)) {
            echo '     ' . implode(', ', array_slice($keywords, 0, 5)) . "\n";
        }

        // Productos (usando availability en lugar de in_stock)
        $productsCount = DB::table('ext_chatbot_products')
            ->where('chatbot_id', $chatbot->id)
            ->count();
        $productsAvailable = DB::table('ext_chatbot_products')
            ->where('chatbot_id', $chatbot->id)
            ->where('availability', 'in_stock')
            ->count();

        echo "   - Productos: {$productsCount} total, {$productsAvailable} disponibles\n";

        if ($productsAvailable === 0) {
            echo "   ⚠️  No hay productos disponibles. Sincroniza con WooCommerce o verifica availability.\n";
        }

        echo "\n";
    }
}

// 2. Verificar archivos modificados
echo "\n2️⃣ Archivos del Orquestador:\n";
echo "----------------------------\n";

$frontendFile = 'app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-scripts.blade.php';
$salesAgentFile = 'app/Extensions/ChatbotSalesAgent/resources/views/sales-agent-component.blade.php';

if (file_exists($frontendFile)) {
    $content = file_get_contents($frontendFile);
    $hasOrchestrator = strpos($content, 'ORQUESTADOR') !== false;
    echo "✅ frontend-ui-scripts.blade.php\n";
    echo '   Orquestador: ' . ($hasOrchestrator ? '✅ Presente' : '❌ Ausente') . "\n";
    echo '   Tamaño: ' . number_format(filesize($frontendFile)) . " bytes\n\n";
} else {
    echo "❌ frontend-ui-scripts.blade.php - No encontrado\n\n";
}

if (file_exists($salesAgentFile)) {
    $content = file_get_contents($salesAgentFile);
    $hasCommercialPhrases = strpos($content, 'commercialPhrases') !== false;
    echo "✅ sales-agent-component.blade.php\n";
    echo '   Frases comerciales: ' . ($hasCommercialPhrases ? '✅ Presente' : '❌ Ausente') . "\n";
    echo '   Tamaño: ' . number_format(filesize($salesAgentFile)) . " bytes\n\n";
} else {
    echo "❌ sales-agent-component.blade.php - No encontrado\n\n";
}

// 3. Test de productos disponibles
if ($chatbots->count() > 0) {
    echo "\n3️⃣ Productos de Prueba:\n";
    echo "----------------------\n";

    $testChatbot = $chatbots->first();
    $testProducts = DB::table('ext_chatbot_products')
        ->where('chatbot_id', $testChatbot->id)
        ->where('availability', 'in_stock')
        ->limit(5)
        ->get();

    if ($testProducts->count() > 0) {
        echo "Productos del chatbot ID {$testChatbot->id}:\n\n";
        foreach ($testProducts as $product) {
            $price = number_format($product->price, 0, ',', '.');
            echo "  📦 {$product->name}\n";
            echo "     Precio: \${price} {$product->currency}\n";
            echo "     SKU: {$product->sku}\n";
            echo "     Availability: {$product->availability}\n\n";

            echo "     🧪 Mensajes de prueba:\n";
            echo "        • \"¿Tienen {$product->name}?\"\n";
            echo "        • \"Quiero comprar {$product->name}\"\n";
            echo '        • "Cuál es el precio de ' . strtolower($product->name) . "?\"\n\n";
        }
    } else {
        echo "⚠️  No hay productos disponibles para probar\n\n";
    }
}

// 4. Instrucciones finales
echo "\n4️⃣ PRÓXIMOS PASOS:\n";
echo "-------------------\n";

if ($chatbots->count() > 0) {
    $chatbot = $chatbots->first();
    $productsAvailable = DB::table('ext_chatbot_products')
        ->where('chatbot_id', $chatbot->id)
        ->where('availability', 'in_stock')
        ->count();

    if ($productsAvailable > 0) {
        echo "✅ TODO CONFIGURADO CORRECTAMENTE!\n\n";
        echo "1. Limpia caches:\n";
        echo "   php artisan cache:clear\n";
        echo "   php artisan view:clear\n";
        echo "   php artisan config:clear\n\n";
        echo "2. Abre el chatbot embebido (ID: {$chatbot->id}) en un navegador\n\n";
        echo "3. Abre la consola del navegador (F12 → Console)\n\n";
        echo "4. Escribe uno de los mensajes de prueba arriba\n\n";
        echo "5. Deberías ver en consola:\n";
        echo "   🛍️ Sales Agent: Loading products database...\n";
        echo "   ✅ Sales Agent: Loaded X products\n";
        echo "   🎯 Orquestador: Evaluando si activar Sales Agent...\n";
        echo "   ✅ Orquestador: Activando Sales Agent - productos detectados!\n\n";
        echo "6. Deberías ver el grid de productos automáticamente debajo del mensaje\n\n";
        echo "7. Click en 'Comprar' para probar el flujo completo\n\n";
    } else {
        echo "⚠️  No hay productos disponibles.\n";
        echo "   Configura productos con availability = 'in_stock'\n\n";
    }
} else {
    echo "⚠️  Habilita Sales Agent en al menos un chatbot primero\n\n";
}

echo "=============================================\n";
echo 'Verificación completada: ' . date('Y-m-d H:i:s') . "\n";
echo "=============================================\n";
