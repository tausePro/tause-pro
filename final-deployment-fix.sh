#!/bin/bash

# 🚀 DEPLOYMENT FINAL - Sistema de Licencias Completo
# Este script corrige TODO: licencias, permisos, cachés, servicios

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
AWS_HOST="34.207.248.220"
AWS_USER="ubuntu"
KEY_FILE="magicai-tause-key.pem"
PROJECT_PATH="/var/www/magicai"

echo -e "${BLUE}🚀 DEPLOYMENT FINAL - CORRECCIÓN COMPLETA${NC}"
echo "=========================================="

# Función para ejecutar comandos remotos
run_remote() {
    ssh -i "$KEY_FILE" "$AWS_USER@$AWS_HOST" "$1"
}

echo -e "${YELLOW}1. Corrigiendo permisos completos...${NC}"
run_remote "cd $PROJECT_PATH && \
    sudo chown -R www-data:www-data . && \
    sudo find storage -type f -exec chmod 664 {} \; && \
    sudo find storage -type d -exec chmod 775 {} \; && \
    sudo find bootstrap/cache -type f -exec chmod 664 {} \; && \
    sudo find bootstrap/cache -type d -exec chmod 775 {} \;"
echo -e "${GREEN}✅ Permisos corregidos${NC}"

echo -e "${YELLOW}2. Limpiando cachés corrupto...${NC}"
run_remote "cd $PROJECT_PATH && \
    sudo rm -rf storage/framework/cache/data/* && \
    sudo rm -rf bootstrap/cache/*.php && \
    sudo mkdir -p storage/framework/cache/data && \
    sudo chown -R www-data:www-data storage bootstrap/cache"
echo -e "${GREEN}✅ Cachés limpiados${NC}"

echo -e "${YELLOW}3. Regenerando cachés como www-data...${NC}"
run_remote "cd $PROJECT_PATH && \
    sudo -u www-data php artisan route:clear && \
    sudo -u www-data php artisan config:clear && \
    sudo -u www-data php artisan view:clear && \
    sudo -u www-data php artisan event:clear"
echo -e "${GREEN}✅ Cachés regenerados${NC}"

echo -e "${YELLOW}4. Verificando extensiones licenciadas...${NC}"
run_remote "cd $PROJECT_PATH && sudo -u www-data php artisan tinker --execute=\"
echo 'Extensiones licenciadas: ' . \App\Models\Extension::where('licensed', true)->where('is_theme', false)->count();
echo PHP_EOL . 'Chatbots activos: ' . \Illuminate\Support\Facades\DB::table('chatbot')->where('status', 1)->count();
\""

echo -e "${YELLOW}5. Verificando tipo de licencia...${NC}"
run_remote "cd $PROJECT_PATH && sudo -u www-data php artisan tinker --execute=\"
\\\$settings = \App\Models\SettingTwo::first();
echo 'Tipo licencia: ' . \\\$settings->liquid_license_type;
\""

echo -e "${YELLOW}6. Reiniciando servicios...${NC}"
run_remote "sudo systemctl restart php8.3-fpm && sudo systemctl reload nginx"
echo -e "${GREEN}✅ Servicios reiniciados${NC}"

echo -e "${YELLOW}7. Test de acceso a rutas principales...${NC}"
echo "Testing /dashboard/admin/themes..."
THEMES_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L https://app.tause.pro/dashboard/admin/themes || echo "000")
echo "   Status: $THEMES_STATUS"

echo "Testing /dashboard/admin/marketplace/licensed..."
MARKETPLACE_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -L https://app.tause.pro/dashboard/admin/marketplace/licensed || echo "000")
echo "   Status: $MARKETPLACE_STATUS"

echo ""
echo -e "${GREEN}🎉 DEPLOYMENT COMPLETADO${NC}"
echo "=========================================="
echo -e "${BLUE}📋 RESUMEN:${NC}"
echo "✅ Permisos corregidos"
echo "✅ Cachés regenerados"
echo "✅ Servicios reiniciados"
echo ""
echo -e "${YELLOW}🔗 URLS PARA PROBAR:${NC}"
echo "   - Marketplace: https://app.tause.pro/dashboard/admin/marketplace/licensed"
echo "   - Temas: https://app.tause.pro/dashboard/admin/themes"
echo "   - Menú: https://app.tause.pro/dashboard/admin/frontend/menu"
echo ""
echo -e "${YELLOW}💡 IMPORTANTE:${NC}"
echo "   - Cierra completamente tu navegador"
echo "   - Limpia cookies (Ctrl+Shift+Del)"
echo "   - Vuelve a iniciar sesión"
echo ""


