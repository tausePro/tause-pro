#!/bin/bash

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🚀 DESPLEGANDO NUEVAS FUNCIONALIDADES DEL CHATBOT${NC}\n"
echo -e "${YELLOW}Funcionalidades:${NC}"
echo -e "  ✅ File Attachments (ya existía)"
echo -e "  ✅ GDPR Compliance"
echo -e "  ✅ CRM Integration\n"

# Variables
SERVER="ubuntu@3.83.18.44"
KEY="magicai-tause-key.pem"
REMOTE_PATH="/var/www/html"

# Verificar conexión
echo -e "${BLUE}📡 Verificando conexión con el servidor...${NC}"
if ! ssh -i $KEY -o ConnectTimeout=5 $SERVER "echo 'Conexión exitosa'" 2>/dev/null; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    echo -e "${YELLOW}Verifica que el servidor esté activo y la key sea correcta${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Conexión exitosa${NC}\n"

# 1. Copiar migraciones
echo -e "${BLUE}📦 Copiando migraciones...${NC}"
scp -i $KEY \
    app/Extensions/Chatbot/database/migrations/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/database/migrations/

scp -i $KEY \
    app/Extensions/Chatbot/database/migrations/2025_10_13_130000_create_chatbot_crm_webhooks_table.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/database/migrations/

# 2. Copiar modelos
echo -e "${BLUE}📦 Copiando modelos...${NC}"
scp -i $KEY \
    app/Extensions/Chatbot/System/Models/Chatbot.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Models/

scp -i $KEY \
    app/Extensions/Chatbot/System/Models/ChatbotCustomer.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Models/

scp -i $KEY \
    app/Extensions/Chatbot/System/Models/ChatbotCrmWebhook.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Models/

# 3. Copiar controllers
echo -e "${BLUE}📦 Copiando controllers...${NC}"
scp -i $KEY \
    app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Http/Controllers/Api/

scp -i $KEY \
    app/Extensions/Chatbot/System/Http/Controllers/ChatbotCrmController.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Http/Controllers/

# 4. Copiar resources
echo -e "${BLUE}📦 Copiando resources...${NC}"
scp -i $KEY \
    app/Extensions/Chatbot/System/Http/Resources/Api/ChatbotResource.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Http/Resources/Api/

# 5. Copiar service provider
echo -e "${BLUE}📦 Copiando service provider...${NC}"
scp -i $KEY \
    app/Extensions/Chatbot/System/ChatbotServiceProvider.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/

# 6. Copiar extension.json actualizado
echo -e "${BLUE}📦 Copiando extension.json...${NC}"
scp -i $KEY \
    app/Extensions/Chatbot/extension.json \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/

# 7. Ejecutar comandos en producción
echo -e "\n${BLUE}🔧 Ejecutando comandos en producción...${NC}"
ssh -i $KEY $SERVER << 'EOF'
cd /var/www/html

echo "📊 Ejecutando migraciones..."
php artisan migrate --path=app/Extensions/Chatbot/database/migrations/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php --force
php artisan migrate --path=app/Extensions/Chatbot/database/migrations/2025_10_13_130000_create_chatbot_crm_webhooks_table.php --force

echo "🧹 Limpiando caché..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "🔄 Reiniciando servicios..."
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "✅ Despliegue completado en producción"
EOF

echo -e "\n${GREEN}✅ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}\n"
echo -e "${BLUE}📋 FUNCIONALIDADES DISPONIBLES:${NC}"
echo -e "  1. ${GREEN}File Attachments${NC} - Ya estaba funcionando"
echo -e "  2. ${GREEN}GDPR Compliance${NC} - Configurar en Chatbot Settings"
echo -e "  3. ${GREEN}CRM Integration${NC} - Exportar leads:"
echo -e "     ${YELLOW}GET /dashboard/chatbot/{id}/leads/export?format=csv${NC}"
echo -e "     ${YELLOW}GET /dashboard/chatbot/{id}/leads/stats${NC}\n"

echo -e "${YELLOW}💡 PRÓXIMOS PASOS:${NC}"
echo -e "  1. Verifica que el chatbot funcione correctamente"
echo -e "  2. Activa GDPR en la configuración del chatbot"
echo -e "  3. Prueba la exportación de leads"
echo -e "  4. Configura webhooks CRM si es necesario\n"



