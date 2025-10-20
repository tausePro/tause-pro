<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Extensions\Chatbot\System\Models\Chatbot;

$chatbot = Chatbot::find(2);

if (!$chatbot) {
    echo "❌ Chatbot no encontrado\n";
    exit(1);
}

echo "🤖 Chatbot: {$chatbot->title}\n";
echo "📋 ID: {$chatbot->id}\n";
echo "\n";
echo "🛒 WooCommerce Configuration:\n";
echo "   URL: " . ($chatbot->woocommerce_url ?? 'NULL') . "\n";
echo "   Key: " . ($chatbot->woocommerce_key ? substr($chatbot->woocommerce_key, 0, 20) . '...' : 'NULL') . "\n";
echo "   Secret: " . ($chatbot->woocommerce_secret ? substr($chatbot->woocommerce_secret, 0, 20) . '...' : 'NULL') . "\n";
echo "   Enabled: " . ($chatbot->woocommerce_enabled ? 'TRUE ✅' : 'FALSE ❌') . "\n";
echo "\n";
echo "💳 Wompi Configuration:\n";
echo "   Public Key: " . ($chatbot->wompi_public_key ? substr($chatbot->wompi_public_key, 0, 20) . '...' : 'NULL') . "\n";
echo "   Private Key: " . ($chatbot->wompi_private_key ? substr($chatbot->wompi_private_key, 0, 20) . '...' : 'NULL') . "\n";
echo "   Environment: " . ($chatbot->wompi_environment ?? 'NULL') . "\n";
echo "   Enabled: " . ($chatbot->wompi_enabled ? 'TRUE ✅' : 'FALSE ❌') . "\n";
echo "\n";
echo "🤖 Sales Agent Configuration:\n";
echo "   Enabled: " . ($chatbot->sales_agent_enabled ? 'TRUE ✅' : 'FALSE ❌') . "\n";
echo "   Keywords: " . json_encode($chatbot->sales_agent_keywords ?? []) . "\n";


