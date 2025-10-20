#!/bin/bash

# Script de ROLLBACK rápido
# Uso: ./rollback-chatbot-features.sh [NOMBRE_BACKUP]

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SERVER="ubuntu@34.207.248.220"
KEY="magicai-tause-key.pem"
REMOTE_PATH="/var/www/html"

echo -e "${RED}⚠️  ROLLBACK DE FUNCIONALIDADES CHATBOT${NC}\n"

# Listar backups disponibles
echo -e "${YELLOW}📦 Backups disponibles:${NC}"
ssh -i $KEY $SERVER "ls -lt $REMOTE_PATH/backups/ | grep backup_before_chatbot"

echo -e "\n${YELLOW}Ingresa el nombre del backup a restaurar:${NC}"
read BACKUP_NAME

if [ -z "$BACKUP_NAME" ]; then
    echo -e "${RED}❌ Debes especificar un backup${NC}"
    exit 1
fi

echo -e "\n${RED}⚠️  ADVERTENCIA: Esto revertirá los cambios${NC}"
echo -e "${YELLOW}¿Estás seguro? (yes/no):${NC}"
read CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Rollback cancelado${NC}"
    exit 0
fi

echo -e "\n${YELLOW}🔄 Iniciando rollback...${NC}"

ssh -i $KEY $SERVER << EOF
cd $REMOTE_PATH

echo "1. Restaurando archivos desde backup..."
cp -v backups/$BACKUP_NAME/Models/* app/Extensions/Chatbot/System/Models/ 2>/dev/null
cp -v backups/$BACKUP_NAME/Controllers/* app/Extensions/Chatbot/System/Http/Controllers/Api/ 2>/dev/null
cp -v backups/$BACKUP_NAME/Resources/* app/Extensions/Chatbot/System/Http/Resources/Api/ 2>/dev/null
cp -v backups/$BACKUP_NAME/ChatbotServiceProvider.php app/Extensions/Chatbot/System/ 2>/dev/null
cp -v backups/$BACKUP_NAME/extension.json app/Extensions/Chatbot/ 2>/dev/null

echo "2. Revirtiendo migraciones..."
php artisan migrate:rollback --step=2 --force

echo "3. Limpiando caché..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "4. Reiniciando servicios..."
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "✅ Rollback completado"
EOF

echo -e "\n${GREEN}✅ ROLLBACK COMPLETADO${NC}"
echo -e "${YELLOW}Verifica: https://app.tause.pro${NC}\n"

