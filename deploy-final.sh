#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🚀 DESPLIEGUE CHATBOT FEATURES${NC}\n"

SERVER="ubuntu@34.207.248.220"
KEY="magicai-tause-key.pem"
REMOTE_PATH="/var/www/magicai"
TMP_DIR="/tmp/chatbot_deploy_$(date +%s)"

echo -e "${BLUE}1. Copiando archivos a servidor...${NC}"

# Copiar archivos a /tmp primero
scp -i $KEY app/Extensions/Chatbot/database/migrations/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/database/migrations/2025_10_13_130000_create_chatbot_crm_webhooks_table.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/Models/Chatbot.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/Models/ChatbotCustomer.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/Models/ChatbotCrmWebhook.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/Http/Controllers/ChatbotCrmController.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/Http/Resources/Api/ChatbotResource.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/System/ChatbotServiceProvider.php $SERVER:$TMP_DIR/
scp -i $KEY app/Extensions/Chatbot/extension.json $SERVER:$TMP_DIR/

echo -e "${GREEN}✅ Archivos copiados a $TMP_DIR${NC}\n"

echo -e "${BLUE}2. Moviendo archivos y ejecutando migraciones...${NC}"

ssh -i $KEY $SERVER << EOF
cd $REMOTE_PATH

echo "📦 Creando backup..."
sudo mkdir -p backups/before_features_\$(date +%Y%m%d_%H%M%S)
sudo cp app/Extensions/Chatbot/System/Models/Chatbot.php backups/before_features_\$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true
sudo cp app/Extensions/Chatbot/System/Models/ChatbotCustomer.php backups/before_features_\$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true

echo "📁 Moviendo archivos..."
sudo mv $TMP_DIR/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php app/Extensions/Chatbot/database/migrations/
sudo mv $TMP_DIR/2025_10_13_130000_create_chatbot_crm_webhooks_table.php app/Extensions/Chatbot/database/migrations/
sudo mv $TMP_DIR/Chatbot.php app/Extensions/Chatbot/System/Models/
sudo mv $TMP_DIR/ChatbotCustomer.php app/Extensions/Chatbot/System/Models/
sudo mv $TMP_DIR/ChatbotCrmWebhook.php app/Extensions/Chatbot/System/Models/
sudo mv $TMP_DIR/ChatbotApplicationController.php app/Extensions/Chatbot/System/Http/Controllers/Api/
sudo mv $TMP_DIR/ChatbotCrmController.php app/Extensions/Chatbot/System/Http/Controllers/
sudo mv $TMP_DIR/ChatbotResource.php app/Extensions/Chatbot/System/Http/Resources/Api/
sudo mv $TMP_DIR/ChatbotServiceProvider.php app/Extensions/Chatbot/System/
sudo mv $TMP_DIR/extension.json app/Extensions/Chatbot/

echo "🔧 Ajustando permisos..."
sudo chown -R www-data:www-data app/Extensions/Chatbot/
sudo chmod -R 755 app/Extensions/Chatbot/

echo "📊 Ejecutando migraciones..."
sudo -u www-data php artisan migrate --path=app/Extensions/Chatbot/database/migrations/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php --force
sudo -u www-data php artisan migrate --path=app/Extensions/Chatbot/database/migrations/2025_10_13_130000_create_chatbot_crm_webhooks_table.php --force

echo "🧹 Limpiando caché..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

echo "🔄 Reiniciando servicios..."
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "🗑️  Limpiando temporal..."
rm -rf $TMP_DIR

echo "✅ Despliegue completado!"
EOF

echo -e "\n${GREEN}✅ DESPLIEGUE EXITOSO${NC}"
echo -e "${YELLOW}Verifica: https://app.tause.pro${NC}\n"



