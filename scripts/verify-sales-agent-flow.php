<?php

/**
 * Script de verificación del flujo de Sales Agent
 * 
 * Verifica:
 * 1. Configuración del chatbot (sales_agent_enabled, keywords)
 * 2. Existencia del Sales Agent en BD
 * 3. Productos sincronizados y activos
 * 4. Funcionamiento del orquestador con un ejemplo
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotAgent;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Services\AgentOrchestratorService;
use Illuminate\Support\Facades\Log;

echo "🔍 Verificando flujo de Sales Agent...\n\n";

// 1. Buscar chatbot con productos sincronizados
// Buscar específicamente el chatbot de Tez o el que tenga más productos
$chatbot = Chatbot::whereHas('products')
    ->withCount('products')
    ->orderBy('products_count', 'desc')
    ->first();

if (!$chatbot) {
    echo "❌ No se encontró ningún chatbot con productos sincronizados\n";
    exit(1);
}

$productsCount = $chatbot->products()->count();
echo "✅ Chatbot encontrado: ID {$chatbot->id} - {$chatbot->name}\n";
echo "   - URL WooCommerce: " . ($chatbot->woocommerce_url ?? 'N/A') . "\n";
echo "   - Total productos sincronizados: {$productsCount}\n";

// Mostrar algunos productos para verificar (sin woocommerce_id si no existe)
$columns = \DB::select("SHOW COLUMNS FROM ext_chatbot_products");
$hasWooCommerceId = false;
foreach ($columns as $column) {
    $fieldName = is_object($column) ? $column->Field : (is_array($column) ? $column['Field'] : null);
    if ($fieldName === 'woocommerce_id') {
        $hasWooCommerceId = true;
        break;
    }
}

$selectFields = ['id', 'name', 'price'];
if ($hasWooCommerceId) {
    $selectFields[] = 'woocommerce_id';
}

$sampleProducts = $chatbot->products()->take(5)->get($selectFields);
echo "\n📦 Muestra de productos:\n";
foreach ($sampleProducts as $product) {
    $wooId = $hasWooCommerceId ? " (WooCommerce ID: {$product->woocommerce_id})" : "";
    echo "   - {$product->name}{$wooId} - $" . number_format($product->price, 0, ',', '.') . "\n";
}

// 2. Verificar configuración del Sales Agent
echo "\n📋 Configuración del Sales Agent:\n";
echo "   - sales_agent_enabled: " . ($chatbot->sales_agent_enabled ? '✅ true' : '❌ false') . "\n";
echo "   - woocommerce_enabled: " . ($chatbot->woocommerce_enabled ? '✅ true' : '❌ false') . "\n";
echo "   - sales_agent_keywords: " . json_encode($chatbot->sales_agent_keywords ?? []) . "\n";

if (!$chatbot->sales_agent_enabled) {
    echo "\n⚠️  Sales Agent no está habilitado. Habilitándolo...\n";
    $chatbot->update(['sales_agent_enabled' => true]);
    echo "✅ Sales Agent habilitado\n";
}

// 3. Verificar/Crear Sales Agent
echo "\n🤖 Verificando Sales Agent en BD:\n";
$salesAgent = ChatbotAgent::where('chatbot_id', $chatbot->id)
    ->where('agent_type', 'sales')
    ->first();

if (!$salesAgent) {
    echo "⚠️  Sales Agent no existe. Creándolo...\n";
    $chatbotService = app(\App\Extensions\Chatbot\System\Services\ChatbotService::class);
    $chatbotService->ensureSalesAgent($chatbot);
    $salesAgent = ChatbotAgent::where('chatbot_id', $chatbot->id)
        ->where('agent_type', 'sales')
        ->first();
}

if ($salesAgent) {
    echo "✅ Sales Agent encontrado:\n";
    echo "   - ID: {$salesAgent->id}\n";
    echo "   - Nombre: {$salesAgent->name}\n";
    echo "   - Habilitado: " . ($salesAgent->is_enabled ? '✅' : '❌') . "\n";
    echo "   - Prioridad: {$salesAgent->priority}\n";
    echo "   - Triggers: " . json_encode($salesAgent->triggers ?? []) . "\n";
    
    if (!$salesAgent->is_enabled) {
        echo "⚠️  Sales Agent está deshabilitado. Habilitándolo...\n";
        $salesAgent->update(['is_enabled' => true]);
        echo "✅ Sales Agent habilitado\n";
    }
} else {
    echo "❌ No se pudo crear el Sales Agent\n";
    exit(1);
}

// 4. Verificar productos
echo "\n📦 Verificando productos:\n";
$totalProducts = ChatbotProduct::where('chatbot_id', $chatbot->id)->count();

// Verificar qué columnas existen realmente
$columns = \DB::select("SHOW COLUMNS FROM ext_chatbot_products");
$columnNames = [];
foreach ($columns as $column) {
    $fieldName = is_object($column) ? $column->Field : (is_array($column) ? $column['Field'] : null);
    if ($fieldName) {
        $columnNames[] = $fieldName;
    }
}

$hasIsActiveColumn = in_array('is_active', $columnNames);
$hasInStockColumn = in_array('in_stock', $columnNames);

echo "   - Columnas disponibles: " . implode(', ', array_slice($columnNames, 0, 10)) . "...\n";
echo "   - Columna is_active existe: " . ($hasIsActiveColumn ? '✅' : '❌') . "\n";
echo "   - Columna in_stock existe: " . ($hasInStockColumn ? '✅' : '❌') . "\n";

// Contar productos según las columnas disponibles
$query = \DB::table('ext_chatbot_products')->where('chatbot_id', $chatbot->id);
$activeProducts = $query->count();

$inStockQuery = \DB::table('ext_chatbot_products')->where('chatbot_id', $chatbot->id);
if ($hasInStockColumn) {
    $inStockProducts = $inStockQuery->where('in_stock', true)->count();
} else {
    // Si no existe in_stock, contar todos los productos
    $inStockProducts = $inStockQuery->count();
}

echo "   - Total productos: {$totalProducts}\n";
echo "   - Productos activos: {$activeProducts}\n";
echo "   - Productos en stock: {$inStockProducts}\n";

if ($inStockProducts === 0 && $totalProducts > 0) {
    echo "\n⚠️  No hay productos disponibles. Verificando estructura...\n";
    // No intentar actualizar si las columnas no existen
    if ($hasInStockColumn) {
        \DB::table('ext_chatbot_products')
            ->where('chatbot_id', $chatbot->id)
            ->update(['in_stock' => true]);
        if ($hasIsActiveColumn) {
            \DB::table('ext_chatbot_products')
                ->where('chatbot_id', $chatbot->id)
                ->update(['is_active' => true]);
        }
        $inStockProducts = \DB::table('ext_chatbot_products')
            ->where('chatbot_id', $chatbot->id)
            ->where('in_stock', true)
            ->count();
        echo "✅ Productos activados. Total en stock: {$inStockProducts}\n";
    } else {
        echo "⚠️  La columna in_stock no existe. Usando todos los productos disponibles.\n";
        $inStockProducts = $totalProducts;
    }
}

if ($inStockProducts === 0) {
    echo "\n❌ No hay productos disponibles para mostrar\n";
    exit(1);
}

// 5. Probar el orquestador con un ejemplo
echo "\n🧪 Probando orquestador con query de ejemplo:\n";
$testQueries = [
    "Quiero comprar productos",
    "¿Qué precios tienen?",
    "Muéstrame el catálogo",
    "Busco productos de cuidado personal"
];

$orchestrator = app(AgentOrchestratorService::class);
$aiResponse = "Tenemos varios productos disponibles en nuestro catálogo. Te puedo ayudar a encontrar lo que necesitas.";

foreach ($testQueries as $index => $userQuery) {
    echo "\n   Test " . ($index + 1) . ": \"{$userQuery}\"\n";
    
    try {
        $orchestration = $orchestrator->orchestrate(
            chatbot: $chatbot,
            userQuery: $userQuery,
            aiResponse: $aiResponse,
            context: []
        );
        
        $agentsActivated = $orchestration['agents_activated'] ?? [];
        $salesAgentActivated = collect($agentsActivated)->firstWhere('agent_type', 'sales');
        
        if ($salesAgentActivated) {
            $products = $salesAgentActivated['data']['products'] ?? [];
            $productsCount = count($products);
            
            echo "      ✅ Sales Agent activado\n";
            echo "      ✅ Productos encontrados: {$productsCount}\n";
            
            if ($productsCount > 0) {
                echo "      📦 Primeros productos:\n";
                foreach (array_slice($products, 0, 3) as $product) {
                    echo "         - {$product['name']} - {$product['formatted_price']}\n";
                }
            }
        } else {
            echo "      ⚠️  Sales Agent NO se activó\n";
            echo "      Intent: " . json_encode($orchestration['orchestration_metadata']['intent'] ?? []) . "\n";
        }
    } catch (\Exception $e) {
        echo "      ❌ Error: {$e->getMessage()}\n";
        echo "      Trace: " . substr($e->getTraceAsString(), 0, 200) . "...\n";
    }
}

echo "\n\n✅ Verificación completada!\n";
echo "\n📝 Resumen:\n";
echo "   - Chatbot ID: {$chatbot->id}\n";
echo "   - Sales Agent: " . ($salesAgent->is_enabled ? '✅ Habilitado' : '❌ Deshabilitado') . "\n";
echo "   - Productos disponibles: {$inStockProducts}\n";
echo "   - Keywords configurados: " . count($chatbot->sales_agent_keywords ?? []) . "\n";

