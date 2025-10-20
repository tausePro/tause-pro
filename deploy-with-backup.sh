#!/bin/bash

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🚀 DESPLIEGUE SEGURO CON BACKUP${NC}\n"

# Variables
SERVER="ubuntu@34.207.248.220"
KEY="magicai-tause-key.pem"
REMOTE_PATH="/var/www/magicai"
BACKUP_DIR="backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="backup_before_chatbot_features_${TIMESTAMP}"

# Crear directorio de backups local
mkdir -p $BACKUP_DIR

echo -e "${BLUE}📡 Verificando conexión...${NC}"
if ! ssh -i $KEY -o ConnectTimeout=5 $SERVER "echo 'OK'" 2>/dev/null; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexión exitosa${NC}\n"

# ============================================
# PASO 1: BACKUP DE BASE DE DATOS
# ============================================
echo -e "${YELLOW}📦 PASO 1/5: Backup de Base de Datos${NC}"
ssh -i $KEY $SERVER << 'EOF'
cd /var/www/html

# Obtener credenciales de .env
DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2)

echo "Creando backup de la base de datos: $DB_NAME"
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > /tmp/db_backup_$(date +%Y%m%d_%H%M%S).sql

echo "✅ Backup de BD creado en /tmp/"
ls -lh /tmp/db_backup_*.sql | tail -1
EOF

# ============================================
# PASO 2: BACKUP DE ARCHIVOS
# ============================================
echo -e "\n${YELLOW}📦 PASO 2/5: Backup de Archivos Modificados${NC}"
ssh -i $KEY $SERVER << EOF
cd $REMOTE_PATH

# Crear directorio de backup
mkdir -p backups/$BACKUP_NAME

echo "Copiando archivos a backup..."

# Backup de modelos
mkdir -p backups/$BACKUP_NAME/Models
cp -v app/Extensions/Chatbot/System/Models/Chatbot.php backups/$BACKUP_NAME/Models/ 2>/dev/null || echo "Chatbot.php no existe"
cp -v app/Extensions/Chatbot/System/Models/ChatbotCustomer.php backups/$BACKUP_NAME/Models/ 2>/dev/null || echo "ChatbotCustomer.php no existe"

# Backup de controllers
mkdir -p backups/$BACKUP_NAME/Controllers
cp -v app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php backups/$BACKUP_NAME/Controllers/ 2>/dev/null || echo "ChatbotApplicationController.php no existe"

# Backup de resources
mkdir -p backups/$BACKUP_NAME/Resources
cp -v app/Extensions/Chatbot/System/Http/Resources/Api/ChatbotResource.php backups/$BACKUP_NAME/Resources/ 2>/dev/null || echo "ChatbotResource.php no existe"

# Backup de ServiceProvider
cp -v app/Extensions/Chatbot/System/ChatbotServiceProvider.php backups/$BACKUP_NAME/ 2>/dev/null || echo "ChatbotServiceProvider.php no existe"

# Backup de extension.json
cp -v app/Extensions/Chatbot/extension.json backups/$BACKUP_NAME/ 2>/dev/null || echo "extension.json no existe"

echo "✅ Backup de archivos creado en: $REMOTE_PATH/backups/$BACKUP_NAME"
ls -lh backups/$BACKUP_NAME/
EOF

# ============================================
# PASO 3: DESPLIEGUE DE NUEVOS ARCHIVOS
# ============================================
echo -e "\n${YELLOW}📦 PASO 3/5: Desplegando Nuevos Archivos${NC}"

echo "Copiando migraciones..."
scp -i $KEY \
    app/Extensions/Chatbot/database/migrations/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/database/migrations/

scp -i $KEY \
    app/Extensions/Chatbot/database/migrations/2025_10_13_130000_create_chatbot_crm_webhooks_table.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/database/migrations/

echo "Copiando modelos..."
scp -i $KEY \
    app/Extensions/Chatbot/System/Models/Chatbot.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Models/

scp -i $KEY \
    app/Extensions/Chatbot/System/Models/ChatbotCustomer.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Models/

scp -i $KEY \
    app/Extensions/Chatbot/System/Models/ChatbotCrmWebhook.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Models/

echo "Copiando controllers..."
scp -i $KEY \
    app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Http/Controllers/Api/

scp -i $KEY \
    app/Extensions/Chatbot/System/Http/Controllers/ChatbotCrmController.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Http/Controllers/

echo "Copiando resources..."
scp -i $KEY \
    app/Extensions/Chatbot/System/Http/Resources/Api/ChatbotResource.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/Http/Resources/Api/

echo "Copiando service provider..."
scp -i $KEY \
    app/Extensions/Chatbot/System/ChatbotServiceProvider.php \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/System/

scp -i $KEY \
    app/Extensions/Chatbot/extension.json \
    $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/

echo -e "${GREEN}✅ Archivos copiados${NC}"

# ============================================
# PASO 4: EJECUTAR MIGRACIONES
# ============================================
echo -e "\n${YELLOW}📦 PASO 4/5: Ejecutando Migraciones${NC}"
ssh -i $KEY $SERVER << 'EOF'
cd /var/www/html

echo "Ejecutando migración GDPR..."
php artisan migrate --path=app/Extensions/Chatbot/database/migrations/2025_10_13_120000_add_gdpr_fields_to_chatbots_and_customers.php --force

echo "Ejecutando migración CRM..."
php artisan migrate --path=app/Extensions/Chatbot/database/migrations/2025_10_13_130000_create_chatbot_crm_webhooks_table.php --force

echo "✅ Migraciones ejecutadas"
EOF

# ============================================
# PASO 5: LIMPIAR CACHÉ Y REINICIAR
# ============================================
echo -e "\n${YELLOW}📦 PASO 5/5: Limpiando Caché y Reiniciando${NC}"
ssh -i $KEY $SERVER << 'EOF'
cd /var/www/html

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "✅ Caché limpiado y servicios reiniciados"
EOF

# ============================================
# RESUMEN
# ============================================
echo -e "\n${GREEN}✅ DESPLIEGUE COMPLETADO EXITOSAMENTE${NC}\n"
echo -e "${BLUE}📋 RESUMEN:${NC}"
echo -e "  ✅ Backup de BD creado en servidor: /tmp/db_backup_*.sql"
echo -e "  ✅ Backup de archivos: $REMOTE_PATH/backups/$BACKUP_NAME"
echo -e "  ✅ Nuevos archivos desplegados"
echo -e "  ✅ Migraciones ejecutadas"
echo -e "  ✅ Caché limpiado\n"

echo -e "${YELLOW}🔄 PARA RESTAURAR EN CASO DE PROBLEMAS:${NC}"
echo -e "  ssh -i $KEY $SERVER"
echo -e "  cd $REMOTE_PATH"
echo -e "  # Restaurar archivos:"
echo -e "  cp -r backups/$BACKUP_NAME/* app/Extensions/Chatbot/System/"
echo -e "  # Restaurar BD:"
echo -e "  mysql -u USER -p DATABASE < /tmp/db_backup_*.sql"
echo -e "  # Revertir migraciones:"
echo -e "  php artisan migrate:rollback --step=2\n"

echo -e "${GREEN}🎉 Todo listo! Verifica: https://app.tause.pro${NC}\n"

