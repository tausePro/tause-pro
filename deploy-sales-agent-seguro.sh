#!/bin/bash

echo "🚀 DEPLOY SEGURO - Sales Agent System"
echo "====================================="
echo ""

# Configuración
REMOTE_HOST="34.207.248.220"
REMOTE_USER="ubuntu"
KEY_FILE="magicai-tause-key.pem"
REMOTE_PATH="/var/www/magicai"
BACKUP_DIR="/var/www/backups/$(date +%Y%m%d_%H%M%S)"

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "📋 1. Verificando archivos locales..."
if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Archivo de clave no encontrado: $KEY_FILE${NC}"
    exit 1
fi

echo "✅ Clave SSH encontrada"

echo ""
echo "📋 2. Creando backup completo en producción..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
cd /var/www/magicai

echo "📦 Creando backup de archivos..."
sudo mkdir -p /var/www/backups/$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/www/backups/$(date +%Y%m%d_%H%M%S)"
sudo cp -r /var/www/magicai "$BACKUP_DIR/"

echo "🗄️ Creando backup de base de datos..."
sudo -u www-data php artisan db:backup --destination="$BACKUP_DIR/database_backup.sql"

echo "✅ Backup creado en: $BACKUP_DIR"
ENDSSH

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error creando backup${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Backup completado${NC}"

echo ""
echo "📋 3. Verificando estado actual de producción..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
cd /var/www/magicai

echo "📊 Estado actual:"
echo "  - Migraciones: $(php artisan migrate:status | grep -c "Ran")"
echo "  - Productos en BD: $(php artisan tinker --execute="echo DB::table('ext_chatbot_products')->count();" 2>/dev/null | tail -1)"
echo "  - Extensiones: $(php artisan tinker --execute="echo DB::table('extensions')->where('installed', 1)->count();" 2>/dev/null | tail -1)"
ENDSSH

echo ""
echo "📋 4. Deployando archivos nuevos..."

# Archivos a deployar
FILES_TO_DEPLOY=(
    "app/Extensions/Chatbot/System/Models/ChatbotProduct.php"
    "app/Extensions/Chatbot/System/Services/WooCommerceService.php"
    "app/Extensions/Chatbot/System/Services/WompiService.php"
    "app/Extensions/Chatbot/System/Http/Controllers/ChatbotEcommerceController.php"
    "app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php"
    "app/Extensions/Chatbot/System/ChatbotServiceProvider.php"
    "app/Extensions/Chatbot/resources/views/ecommerce/index.blade.php"
    "app/Extensions/Chatbot/database/migrations/2025_10_14_100000_create_chatbot_products_table.php"
    "app/Extensions/Chatbot/database/migrations/2025_10_14_110000_add_woocommerce_fields_to_chatbots.php"
    "SALES_AGENT_IMPLEMENTATION.md"
)

echo "📤 Subiendo archivos..."
for file in "${FILES_TO_DEPLOY[@]}"; do
    if [ -f "$file" ]; then
        echo "  📄 $file"
        scp -i "$KEY_FILE" "$file" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/$file"
        if [ $? -ne 0 ]; then
            echo -e "${RED}❌ Error subiendo $file${NC}"
            exit 1
        fi
    else
        echo -e "${YELLOW}⚠️  Archivo no encontrado: $file${NC}"
    fi
done

echo -e "${GREEN}✅ Archivos subidos${NC}"

echo ""
echo "📋 5. Ejecutando migraciones en producción..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
cd /var/www/magicai

echo "🔄 Ejecutando migraciones..."
sudo -u www-data php artisan migrate --force

echo "🧹 Limpiando cachés..."
sudo -u www-data php artisan optimize:clear

echo "🔧 Ajustando permisos..."
sudo chown -R www-data:www-data app/Extensions/Chatbot/
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/

echo "✅ Migraciones y caché completados"
ENDSSH

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error en migraciones${NC}"
    echo "🔄 Iniciando rollback..."
    ./rollback-sales-agent.sh
    exit 1
fi

echo ""
echo "📋 6. Verificando deploy..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
cd /var/www/magicai

echo "🔍 Verificaciones post-deploy:"
echo "  - Modelo ChatbotProduct: $(php artisan tinker --execute="echo class_exists('App\Extensions\Chatbot\System\Models\ChatbotProduct') ? 'OK' : 'ERROR';" 2>/dev/null | tail -1)"
echo "  - Servicio WooCommerce: $(php artisan tinker --execute="echo class_exists('App\Extensions\Chatbot\System\Services\WooCommerceService') ? 'OK' : 'ERROR';" 2>/dev/null | tail -1)"
echo "  - Servicio Wompi: $(php artisan tinker --execute="echo class_exists('App\Extensions\Chatbot\System\Services\WompiService') ? 'OK' : 'ERROR';" 2>/dev/null | tail -1)"
echo "  - Productos en BD: $(php artisan tinker --execute="echo DB::table('ext_chatbot_products')->count();" 2>/dev/null | tail -1)"

echo "🌐 Probando respuesta HTTP..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://app.tause.pro/login)
echo "  - Login page: HTTP $HTTP_CODE"

if [ "$HTTP_CODE" -eq 200 ] || [ "$HTTP_CODE" -eq 302 ]; then
    echo "✅ Sitio respondiendo correctamente"
else
    echo "❌ Problema con el sitio"
fi
ENDSSH

echo ""
echo "📋 7. Verificando rutas de E-commerce..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
cd /var/www/magicai

echo "🛣️  Verificando rutas:"
php artisan route:list --name=ecommerce 2>/dev/null | head -5 || echo "  ⚠️  Comando no disponible en esta versión"
php artisan route:list --name=products 2>/dev/null | head -5 || echo "  ⚠️  Comando no disponible en esta versión"
ENDSSH

echo ""
echo "====================================="
echo -e "${GREEN}🎉 DEPLOY COMPLETADO EXITOSAMENTE${NC}"
echo ""
echo "📝 Próximos pasos:"
echo "  1. Acceder a: https://app.tause.pro/login"
echo "  2. Ir a: https://app.tause.pro/dashboard/chatbot/{id}/ecommerce"
echo "  3. Configurar WooCommerce y Wompi"
echo "  4. Activar Sales Agent"
echo ""
echo "📚 Documentación: https://app.tause.pro/SALES_AGENT_IMPLEMENTATION.md"
echo ""
echo "🔄 En caso de problemas, ejecutar: ./rollback-sales-agent.sh"
echo ""


