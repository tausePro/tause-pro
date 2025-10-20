#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

SERVER="ubuntu@34.207.248.220"
KEY="magicai-tause-key.pem"

echo -e "${BLUE}🔓 ACTIVANDO EXTENSIONES EN PRODUCCIÓN${NC}\n"

echo -e "${YELLOW}Paso 1: Actualizar versiones de extension.json${NC}"
ssh -i $KEY $SERVER 'bash -s' << 'ENDSSH'
cd /var/www/magicai/app/Extensions

for EXT in AIWebChat ChatProTempChat ChatSetting ChatShare Chatbot ChatbotAgent ChatbotMessenger ChatbotWhatsapp AIRealtimeImage AIVoiceIsolator AIWriterTemplates Announcement AzureOpenai AzureTTS CheckoutRegistration FluxPro Hubspot Introduction Mailchimp Maintenance Midjourney Mobile NanoBanana Newsletter OpenRouter Perplexity SeeDreamV4 Wordpress; do
    if [ -f "$EXT/extension.json" ]; then
        CURRENT=$(grep -o '"version": "[^"]*"' "$EXT/extension.json" | cut -d'"' -f4)
        NEW_VERSION=$(echo $CURRENT | awk -F. '{$2=$2+1; print $1"."$2"."$3}')
        sudo sed -i "s/\"version\": \"$CURRENT\"/\"version\": \"$NEW_VERSION\"/" "$EXT/extension.json"
        echo "  ✓ $EXT: $CURRENT → $NEW_VERSION"
    fi
done
ENDSSH

echo ""
echo -e "${YELLOW}Paso 2: Actualizar tabla extensions en BD${NC}"
ssh -i $KEY $SERVER << 'ENDSSH'
cd /var/www/magicai

# Actualizar todas las extensiones a licensed = 1
mysql -u tause_user -p'TausePro2025!' -D tause_pro << 'SQL'
UPDATE extensions SET licensed = 1 WHERE is_theme = 0;
SELECT CONCAT('✓ Total extensiones licenciadas: ', COUNT(*)) FROM extensions WHERE is_theme = 0 AND licensed = 1;
SQL

ENDSSH

echo ""
echo -e "${YELLOW}Paso 3: Limpiar caché${NC}"
ssh -i $KEY $SERVER << 'ENDSSH'
cd /var/www/magicai
sudo -u www-data php artisan cache:clear > /dev/null 2>&1
sudo -u www-data php artisan config:clear > /dev/null 2>&1
sudo -u www-data php artisan route:clear > /dev/null 2>&1
sudo -u www-data php artisan view:clear > /dev/null 2>&1
echo "  ✓ Caché limpiado"
ENDSSH

echo ""
echo -e "${GREEN}✅ EXTENSIONES ACTIVADAS EN PRODUCCIÓN!${NC}"
echo ""
echo -e "${BLUE}📋 Verifica en: https://app.tause.pro/dashboard/admin/settings/general${NC}"



