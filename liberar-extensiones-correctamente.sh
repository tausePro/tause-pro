#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

SERVER="ubuntu@34.207.248.220"
KEY="magicai-tause-key.pem"

echo -e "${BLUE}🔓 LIBERANDO EXTENSIONES (Método correcto)${NC}\n"

# Array de extensiones a liberar con sus nuevas versiones
declare -A EXTENSIONS=(
    ["AIWebChat"]="2.9.0"
    ["ChatProTempChat"]="1.3.0"
    ["ChatSetting"]="3.3.0"
    ["ChatShare"]="2.6.0"
    ["Chatbot"]="4.7.0"
    ["ChatbotAgent"]="2.1.0"
    ["ChatbotMessenger"]="1.2.0"
    ["ChatbotWhatsapp"]="1.5.0"
    ["AIRealtimeImage"]="1.7.0"
    ["AIVoiceIsolator"]="2.2.0"
    ["AIWriterTemplates"]="2.1.0"
    ["Announcement"]="1.4.0"
    ["AzureOpenai"]="1.1.0"
    ["AzureTTS"]="2.2.0"
    ["CheckoutRegistration"]="1.5.0"
    ["FluxPro"]="2.3.0"
    ["Hubspot"]="2.1.0"
    ["Introduction"]="2.1.0"
    ["Mailchimp"]="2.1.0"
    ["Maintenance"]="2.1.0"
    ["Midjourney"]="2.4.0"
    ["Mobile"]="3.2.0"
    ["NanoBanana"]="1.1.0"
    ["Newsletter"]="2.1.0"
    ["OpenRouter"]="1.2.0"
    ["Perplexity"]="1.1.0"
    ["SeeDreamV4"]="1.2.0"
    ["Wordpress"]="3.1.0"
)

for EXT_NAME in "${!EXTENSIONS[@]}"; do
    NEW_VERSION="${EXTENSIONS[$EXT_NAME]}"
    
    echo -e "${YELLOW}📦 Liberando: $EXT_NAME → v$NEW_VERSION${NC}"
    
    ssh -i $KEY $SERVER << ENDSSH
cd /var/www/magicai/app/Extensions/$EXT_NAME

# Backup del extension.json original
sudo cp extension.json extension.json.backup 2>/dev/null || true

# Actualizar versión en extension.json
sudo sed -i 's/"version": "[^"]*"/"version": "'$NEW_VERSION'"/' extension.json

# Verificar el cambio
echo "  ✓ $(grep version extension.json)"
ENDSSH

done

echo ""
echo -e "${BLUE}🧹 Limpiando caché...${NC}"

ssh -i $KEY $SERVER << 'ENDSSH'
cd /var/www/magicai
sudo -u www-data php artisan cache:clear > /dev/null 2>&1
sudo -u www-data php artisan config:clear > /dev/null 2>&1
sudo -u www-data php artisan route:clear > /dev/null 2>&1
echo "  ✓ Caché limpiado"
ENDSSH

echo ""
echo -e "${GREEN}✅ TODAS LAS EXTENSIONES LIBERADAS!${NC}"
echo ""
echo -e "${BLUE}📋 Verifica en: https://app.tause.pro/dashboard/admin/settings/general${NC}"



