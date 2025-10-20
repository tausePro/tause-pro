#!/bin/bash

echo "🧪 Testing Sales Agent Implementation - LOCAL"
echo "=============================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Base URL
BASE_URL="http://tausepro.test"

echo "📋 1. Verificando migraciones..."
php artisan migrate:status | grep -E "chatbot_products|woocommerce_fields"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones OK${NC}"
else
    echo -e "${RED}❌ Migraciones faltantes${NC}"
fi
echo ""

echo "📋 2. Verificando modelos..."
php artisan tinker --execute="echo class_exists('App\Extensions\Chatbot\System\Models\ChatbotProduct') ? '✅ ChatbotProduct exists' : '❌ ChatbotProduct missing';"
echo ""

echo "📋 3. Verificando rutas de E-commerce..."
php artisan route:list --name=ecommerce --compact
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Rutas de E-commerce registradas${NC}"
else
    echo -e "${RED}❌ Rutas no encontradas${NC}"
fi
echo ""

echo "📋 4. Verificando rutas API de productos..."
php artisan route:list --name=products --compact
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Rutas API registradas${NC}"
else
    echo -e "${RED}❌ Rutas API no encontradas${NC}"
fi
echo ""

echo "📋 5. Verificando servicios..."
php artisan tinker --execute="
\$woo = app('App\Extensions\Chatbot\System\Services\WooCommerceService');
\$wompi = app('App\Extensions\Chatbot\System\Services\WompiService');
echo '✅ WooCommerceService: ' . get_class(\$woo) . PHP_EOL;
echo '✅ WompiService: ' . get_class(\$wompi) . PHP_EOL;
"
echo ""

echo "📋 6. Verificando tabla de productos..."
php artisan tinker --execute="
\$count = DB::table('ext_chatbot_products')->count();
echo '📦 Productos en BD: ' . \$count . PHP_EOL;
"
echo ""

echo "📋 7. Verificando campos de Chatbot..."
php artisan tinker --execute="
\$chatbot = App\Extensions\Chatbot\System\Models\Chatbot::first();
if (\$chatbot) {
    echo '✅ woocommerce_enabled: ' . (\$chatbot->woocommerce_enabled ? 'true' : 'false') . PHP_EOL;
    echo '✅ wompi_enabled: ' . (\$chatbot->wompi_enabled ? 'true' : 'false') . PHP_EOL;
    echo '✅ sales_agent_enabled: ' . (\$chatbot->sales_agent_enabled ? 'true' : 'false') . PHP_EOL;
} else {
    echo '⚠️  No hay chatbots en la BD' . PHP_EOL;
}
"
echo ""

echo "📋 8. Test de conexión HTTP..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL)
if [ $HTTP_CODE -eq 200 ] || [ $HTTP_CODE -eq 302 ]; then
    echo -e "${GREEN}✅ Sitio local respondiendo (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}❌ Sitio no responde (HTTP $HTTP_CODE)${NC}"
fi
echo ""

echo "=============================================="
echo "🎉 Testing completado!"
echo ""
echo "📝 Próximos pasos manuales:"
echo "  1. Acceder a: $BASE_URL/dashboard/chatbot/{id}/ecommerce"
echo "  2. Configurar WooCommerce (URL + Keys)"
echo "  3. Sincronizar productos"
echo "  4. Configurar Wompi (Keys)"
echo "  5. Activar Sales Agent"
echo ""
echo "📚 Ver documentación completa en: SALES_AGENT_IMPLEMENTATION.md"
echo ""

