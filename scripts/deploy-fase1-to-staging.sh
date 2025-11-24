#!/bin/bash

# 🚀 Script para Desplegar Cambios de Fase 1 a Staging (Sin Git)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
STAGING_HOST="${STAGING_HOST:-13.218.39.31}"
STAGING_USER="${STAGING_USER:-ubuntu}"
KEY_FILE="${KEY_FILE:-staging-tausepro-key.pem}"
PROJECT_PATH="/var/www/magicai"
LOCAL_PROJECT="/Users/tause/Documents/proyectos/tausepro9.4"

echo -e "${BLUE}🚀 Deployment Fase 1 a Staging${NC}"
echo "=================================================="

# Verificar conexión
echo -e "${YELLOW}📡 Verificando conexión...${NC}"
if ! ssh -4 -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "echo 'OK'" > /dev/null 2>&1; then
    echo -e "${RED}❌ No se pudo conectar${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"
echo ""

# Archivos a copiar (Fase 1)
FILES=(
    "app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/AgentIntelligenceService.php"
    "app/Extensions/Chatbot/System/Models/ChatbotAgent.php"
    "app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php"
    "app/Extensions/Chatbot/System/Http/Controllers/ChatbotController.php"
    "app/Extensions/Chatbot/System/ChatbotServiceProvider.php"
    "app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-agents.blade.php"
)

echo -e "${YELLOW}💾 Creando backup...${NC}"
BACKUP_DIR="$PROJECT_PATH/backups/fase1-$(date +%Y%m%d_%H%M%S)"
ssh -4 -i "$KEY_FILE" "$STAGING_USER@$STAGING_HOST" "mkdir -p $BACKUP_DIR"
for file in "${FILES[@]}"; do
    ssh -4 -i "$KEY_FILE" "$STAGING_USER@$STAGING_HOST" "
        if [ -f $PROJECT_PATH/$file ]; then
            mkdir -p $BACKUP_DIR/\$(dirname $file)
            cp $PROJECT_PATH/$file $BACKUP_DIR/$file
        fi
    " 2>/dev/null || true
done
echo -e "${GREEN}✅ Backup creado${NC}"
echo ""

echo -e "${YELLOW}📤 Copiando archivos...${NC}"
for file in "${FILES[@]}"; do
    if [ -f "$LOCAL_PROJECT/$file" ]; then
        echo "  Copiando: $file"
        scp -4 -i "$KEY_FILE" "$LOCAL_PROJECT/$file" "$STAGING_USER@$STAGING_HOST:$PROJECT_PATH/$file" > /dev/null 2>&1
    else
        echo -e "  ${YELLOW}⚠️  No encontrado: $file${NC}"
    fi
done
echo -e "${GREEN}✅ Archivos copiados${NC}"
echo ""

echo -e "${YELLOW}🧹 Limpiando cache...${NC}"
ssh -4 -i "$KEY_FILE" "$STAGING_USER@$STAGING_HOST" "cd $PROJECT_PATH && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear" > /dev/null 2>&1
echo -e "${GREEN}✅ Cache limpiado${NC}"
echo ""

echo -e "${YELLOW}⚡ Optimizando...${NC}"
ssh -4 -i "$KEY_FILE" "$STAGING_USER@$STAGING_HOST" "cd $PROJECT_PATH && php artisan config:cache && php artisan route:cache && php artisan view:cache" > /dev/null 2>&1
echo -e "${GREEN}✅ Optimizado${NC}"
echo ""

echo -e "${YELLOW}🔐 Configurando permisos...${NC}"
ssh -4 -i "$KEY_FILE" "$STAGING_USER@$STAGING_HOST" "cd $PROJECT_PATH && sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache" > /dev/null 2>&1
echo -e "${GREEN}✅ Permisos configurados${NC}"
echo ""

echo -e "${YELLOW}🔄 Recargando servicios...${NC}"
ssh -4 -i "$KEY_FILE" "$STAGING_USER@$STAGING_HOST" "sudo systemctl reload php8.2-fpm && sudo systemctl reload nginx" > /dev/null 2>&1
echo -e "${GREEN}✅ Servicios recargados${NC}"
echo ""

echo ""
echo -e "${GREEN}🎉 Deployment Fase 1 completado!${NC}"
echo "=================================================="
echo ""
echo -e "${BLUE}📋 Archivos desplegados:${NC}"
for file in "${FILES[@]}"; do
    echo "  ✅ $file"
done
echo ""
echo -e "${BLUE}🔗 Verificar:${NC}"
echo "  http://$STAGING_HOST"
echo ""
echo -e "${BLUE}📝 Ver logs:${NC}"
echo "  ssh -4 -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'tail -f $PROJECT_PATH/storage/logs/laravel.log | grep Agent'"
echo ""
echo -e "${YELLOW}🔄 Rollback (si es necesario):${NC}"
echo "  ssh -4 -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'cd $PROJECT_PATH && cp -r $BACKUP_DIR/* . && php artisan config:cache'"
echo ""

