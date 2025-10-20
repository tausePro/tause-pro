#!/bin/bash

echo "🔍 VERIFICACIÓN PRE-DEPLOY"
echo "=========================="
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "📋 1. Verificando archivos locales..."
ERRORS=0

# Archivos críticos
CRITICAL_FILES=(
    "app/Extensions/Chatbot/System/Models/ChatbotProduct.php"
    "app/Extensions/Chatbot/System/Services/WooCommerceService.php"
    "app/Extensions/Chatbot/System/Services/WompiService.php"
    "app/Extensions/Chatbot/System/Http/Controllers/ChatbotEcommerceController.php"
    "app/Extensions/Chatbot/database/migrations/2025_10_14_100000_create_chatbot_products_table.php"
    "app/Extensions/Chatbot/database/migrations/2025_10_14_110000_add_woocommerce_fields_to_chatbots.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  ✅ $file"
    else
        echo -e "  ${RED}❌ $file${NC}"
        ((ERRORS++))
    fi
done

echo ""
echo "📋 2. Verificando sintaxis PHP..."
for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo -e "  ✅ $file"
        else
            echo -e "  ${RED}❌ $file (syntax error)${NC}"
            ((ERRORS++))
        fi
    fi
done

echo ""
echo "📋 3. Verificando migraciones..."
MIGRATION_ERRORS=0

# Verificar que las migraciones no tengan errores
for migration in app/Extensions/Chatbot/database/migrations/2025_10_14_*.php; do
    if [ -f "$migration" ]; then
        if php -l "$migration" > /dev/null 2>&1; then
            echo -e "  ✅ $(basename "$migration")"
        else
            echo -e "  ${RED}❌ $(basename "$migration") (syntax error)${NC}"
            ((MIGRATION_ERRORS++))
        fi
    fi
done

echo ""
echo "📋 4. Verificando conexión SSH..."
if [ -f "magicai-tause-key.pem" ]; then
    chmod 600 magicai-tause-key.pem
    if ssh -i magicai-tause-key.pem -o ConnectTimeout=10 -o BatchMode=yes ubuntu@34.207.248.220 "echo 'SSH OK'" > /dev/null 2>&1; then
        echo -e "  ✅ Conexión SSH exitosa"
    else
        echo -e "  ${RED}❌ Error de conexión SSH${NC}"
        ((ERRORS++))
    fi
else
    echo -e "  ${RED}❌ Archivo de clave SSH no encontrado${NC}"
    ((ERRORS++))
fi

echo ""
echo "📋 5. Verificando estado de producción..."
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 << 'ENDSSH' 2>/dev/null
cd /var/www/magicai

echo "  📊 Estado actual:"
echo "    - Migraciones ejecutadas: $(php artisan migrate:status | grep -c "Ran")"
echo "    - Productos en BD: $(php artisan tinker --execute="echo DB::table('ext_chatbot_products')->count();" 2>/dev/null | tail -1)"
echo "    - Sitio responde: $(curl -s -o /dev/null -w "%{http_code}" https://app.tause.pro/login)"

# Verificar si ya existen las tablas
echo "    - Tabla productos existe: $(php artisan tinker --execute="echo Schema::hasTable('ext_chatbot_products') ? 'Sí' : 'No';" 2>/dev/null | tail -1)"
echo "    - Campos WooCommerce: $(php artisan tinker --execute="echo Schema::hasColumn('ext_chatbots', 'woocommerce_enabled') ? 'Sí' : 'No';" 2>/dev/null | tail -1)"
ENDSSH

echo ""
echo "📋 6. Verificando espacio en disco remoto..."
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 "df -h /var/www" 2>/dev/null | tail -1

echo ""
echo "=========================="
if [ $ERRORS -eq 0 ] && [ $MIGRATION_ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ VERIFICACIÓN EXITOSA${NC}"
    echo ""
    echo "🚀 Listo para deploy. Ejecutar:"
    echo "   ./deploy-sales-agent-seguro.sh"
    echo ""
else
    echo -e "${RED}❌ VERIFICACIÓN FALLIDA${NC}"
    echo ""
    echo "⚠️  Errores encontrados:"
    echo "   - Archivos faltantes: $ERRORS"
    echo "   - Errores de migración: $MIGRATION_ERRORS"
    echo ""
    echo "🔧 Corregir errores antes de continuar"
    exit 1
fi


