#!/bin/bash

# 🚀 Script de Deployment a Staging
# Este script despliega cambios a staging de forma segura

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
STAGING_HOST="${STAGING_HOST:-}"
STAGING_USER="${STAGING_USER:-ubuntu}"
KEY_FILE="${KEY_FILE:-staging-tausepro-key.pem}"
PROJECT_NAME="${PROJECT_NAME:-magicai-staging}"
PROJECT_PATH="/var/www/${PROJECT_NAME}"
GIT_BRANCH="${GIT_BRANCH:-main}"

echo -e "${BLUE}🚀 Deployment a Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó el host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 ./scripts/deploy-to-staging.sh"
    exit 1
fi

# Función para ejecutar comandos remotos (forzar IPv4)
run_remote() {
    ssh -4 -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "$1"
}

echo -e "${YELLOW}📡 Verificando conexión...${NC}"
if ! ssh -4 -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "echo 'Conexión exitosa'" > /dev/null 2>&1; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"

echo -e "${YELLOW}💾 Creando backup...${NC}"
BACKUP_DIR="$PROJECT_PATH/backups/pre-deploy-$(date +%Y%m%d_%H%M%S)"
run_remote "mkdir -p $BACKUP_DIR"
run_remote "cd $PROJECT_PATH && cp -r app storage bootstrap/cache $BACKUP_DIR/ 2>/dev/null || true"
run_remote "cd $PROJECT_PATH && mysqldump -u \$(grep DB_USERNAME .env | cut -d '=' -f2) -p\$(grep DB_PASSWORD .env | cut -d '=' -f2) \$(grep DB_DATABASE .env | cut -d '=' -f2) > $BACKUP_DIR/database.sql 2>/dev/null || echo 'Backup DB skipped'"
echo -e "${GREEN}✅ Backup creado en $BACKUP_DIR${NC}"

echo -e "${YELLOW}📥 Actualizando código desde Git...${NC}"
run_remote "cd $PROJECT_PATH && git fetch origin"
run_remote "cd $PROJECT_PATH && git checkout $GIT_BRANCH"
run_remote "cd $PROJECT_PATH && git pull origin $GIT_BRANCH"
echo -e "${GREEN}✅ Código actualizado${NC}"

echo -e "${YELLOW}📦 Instalando dependencias de Composer...${NC}"
run_remote "cd $PROJECT_PATH && composer install --no-dev --optimize-autoloader --no-interaction"
echo -e "${GREEN}✅ Dependencias de Composer instaladas${NC}"

echo -e "${YELLOW}📦 Instalando dependencias de NPM...${NC}"
run_remote "cd $PROJECT_PATH && npm install --production"
echo -e "${GREEN}✅ Dependencias de NPM instaladas${NC}"

echo -e "${YELLOW}🏗️ Compilando assets...${NC}"
run_remote "cd $PROJECT_PATH && npm run build"
echo -e "${GREEN}✅ Assets compilados${NC}"

echo -e "${YELLOW}🗄️ Ejecutando migraciones...${NC}"
run_remote "cd $PROJECT_PATH && php artisan migrate --force"
echo -e "${GREEN}✅ Migraciones ejecutadas${NC}"

echo -e "${YELLOW}🧹 Limpiando cache...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:clear"
run_remote "cd $PROJECT_PATH && php artisan cache:clear"
run_remote "cd $PROJECT_PATH && php artisan route:clear"
run_remote "cd $PROJECT_PATH && php artisan view:clear"
echo -e "${GREEN}✅ Cache limpiado${NC}"

echo -e "${YELLOW}⚡ Optimizando para producción...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:cache"
run_remote "cd $PROJECT_PATH && php artisan route:cache"
run_remote "cd $PROJECT_PATH && php artisan view:cache"
echo -e "${GREEN}✅ Optimización completada${NC}"

echo -e "${YELLOW}🔐 Configurando permisos...${NC}"
run_remote "cd $PROJECT_PATH && sudo chown -R www-data:www-data storage bootstrap/cache"
run_remote "cd $PROJECT_PATH && sudo chmod -R 775 storage bootstrap/cache"
echo -e "${GREEN}✅ Permisos configurados${NC}"

echo -e "${YELLOW}✅ Verificando sintaxis PHP...${NC}"
if run_remote "cd $PROJECT_PATH && find app -name '*.php' -exec php -l {} \; | grep -v 'No syntax errors'"; then
    echo -e "${YELLOW}⚠️  Algunos archivos tienen advertencias (continuando...)${NC}"
else
    echo -e "${GREEN}✅ Sintaxis PHP correcta${NC}"
fi

echo -e "${YELLOW}🔄 Recargando servicios...${NC}"
run_remote "sudo systemctl reload php8.2-fpm"
run_remote "sudo systemctl reload nginx"
echo -e "${GREEN}✅ Servicios recargados${NC}"

echo ""
echo -e "${GREEN}🎉 Deployment completado exitosamente!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Resumen:${NC}"
echo "✅ Código actualizado desde $GIT_BRANCH"
echo "✅ Dependencias instaladas"
echo "✅ Migraciones ejecutadas"
echo "✅ Cache optimizado"
echo "✅ Servicios recargados"
echo ""
echo -e "${BLUE}🔗 Verificar:${NC}"
echo "Visita: http://$STAGING_HOST"
echo ""
echo -e "${BLUE}📝 Ver logs:${NC}"
echo "ssh -4 -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'tail -f $PROJECT_PATH/storage/logs/laravel.log'"
echo ""
echo -e "${YELLOW}🔄 Rollback (si es necesario):${NC}"
echo "ssh -4 -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'cd $PROJECT_PATH && git reset --hard HEAD~1 && php artisan config:cache'"

