<?php

require 'vendor/autoload.php';

use App\Extensions\Chatbot\System\Parsers\ProductExtractor;

// Test the new price extraction
$extractor = new ProductExtractor();

// Simulate HTML from tez.com.co with real prices
$testHtml = '
<div class="product">
    <h2 class="woocommerce-loop-product__title">Bio retinol & bandas antiarrugas | Tez</h2>
    <span class="price">
        <span class="woocommerce-Price-amount amount">
            <bdi>$89.000</bdi>
        </span>
    </span>
</div>

<div class="product">
    <h2 class="woocommerce-loop-product__title">Kit Cuidado Facial Anti-Brillo</h2>
    <span class="price">
        <span class="woocommerce-Price-amount amount">
            <bdi>$125.500</bdi>
        </span>
    </span>
</div>

<div class="product">
    <h2 class="woocommerce-loop-product__title">Mascarilla Soft Peeling</h2>
    <span class="price">
        <span class="woocommerce-Price-amount amount">
            <bdi>$45.000</bdi>
        </span>
    </span>
</div>
';

echo "🧪 Probando nueva extracción de precios...\n\n";

// Test price extraction
$reflection = new ReflectionClass($extractor);
$method = $reflection->getMethod('extractPrice');
$method->setAccessible(true);

$selectors = '.woocommerce-Price-amount.amount, .price .woocommerce-Price-amount, .price ins .amount, .price .amount';

$price = $method->invoke($extractor, $testHtml, $selectors);

echo "💰 Precio extraído: " . ($price ? $price : 'No encontrado') . "\n";

// Test full product extraction
$productData = $extractor->extractProductData($testHtml, 'https://tez.com.co/productos/bio-retinol');

if ($productData) {
    echo "\n📦 Producto completo extraído:\n";
    echo "- Nombre: " . ($productData['name'] ?? 'N/A') . "\n";
    echo "- Precio: " . ($productData['price'] ?? 'N/A') . "\n";
    echo "- Moneda: " . ($productData['currency'] ?? 'N/A') . "\n";
    echo "- Plataforma: " . ($productData['platform'] ?? 'N/A') . "\n";
} else {
    echo "\n❌ No se pudo extraer el producto\n";
}

echo "\n✅ Prueba completada\n";