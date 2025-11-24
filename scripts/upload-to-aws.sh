#!/bin/bash

# Script para subir archivos directamente a AWS (sin GitHub)
# Usa SCP o SFTP para transferir solo los archivos modificados

set -e

# CONFIGURACIÓN - AJUSTAR SEGÚN TU SETUP
AWS_HOST="tu-servidor-aws.com"  # O IP
AWS_USER="ubuntu"  # O tu usuario
AWS_KEY_PATH="~/.ssh/tu-key.pem"  # Ruta a tu clave SSH
AWS_REMOTE_PATH="/var/www/tausepro9.4"  # Ruta en el servidor
LOCAL_PROJECT_PATH="/Users/tause/Documents/proyectos/tausepro9.4"

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}📤 Subiendo archivos directamente a AWS...${NC}"
echo ""

# Archivos modificados para Sales Agent
FILES_TO_UPLOAD=(
    "app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/ProductCardService.php"
    "app/Extensions/Chatbot/System/Services/ProductIntegrationService.php"
    "app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php"
)

echo -e "${YELLOW}Archivos a subir:${NC}"
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$LOCAL_PROJECT_PATH/$file" ]; then
        echo "  ✅ $file"
    else
        echo -e "  ${RED}❌ No encontrado: $file${NC}"
    fi
done
echo ""

# Confirmar
read -p "¿Continuar con la subida? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Cancelado"
    exit 1
fi

# Subir archivos uno por uno
echo -e "${GREEN}📤 Subiendo archivos...${NC}"
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$LOCAL_PROJECT_PATH/$file" ]; then
        echo -n "  Subiendo $(basename $file)... "
        
        # Crear directorio remoto si no existe
        REMOTE_DIR=$(dirname "$AWS_REMOTE_PATH/$file")
        ssh -i "$AWS_KEY_PATH" "$AWS_USER@$AWS_HOST" "mkdir -p $REMOTE_DIR" 2>/dev/null
        
        # Subir archivo
        scp -i "$AWS_KEY_PATH" "$LOCAL_PROJECT_PATH/$file" "$AWS_USER@$AWS_HOST:$AWS_REMOTE_PATH/$file" > /dev/null 2>&1
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅${NC}"
        else
            echo -e "${RED}❌ Error${NC}"
        fi
    fi
done

echo ""
echo -e "${GREEN}✅ Archivos subidos correctamente${NC}"
echo ""
echo -e "${YELLOW}📋 Próximos pasos en el servidor AWS:${NC}"
echo "1. Verificar sintaxis: find app/Extensions/Chatbot/System/Services -name '*.php' -exec php -l {} \;"
echo "2. Limpiar cache: php artisan config:clear && php artisan cache:clear"
echo "3. Regenerar cache: php artisan config:cache && php artisan route:cache"
echo "4. Verificar: ./scripts/verify-deployment-aws.sh"

