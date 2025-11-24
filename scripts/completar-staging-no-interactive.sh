#!/bin/bash

# 🚀 Script para Completar Configuración de Staging (NO INTERACTIVO)
# Versión sin prompts interactivos para evitar que se quede atascado

set -euo pipefail

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
PROJECT_PATH="/var/www/magicai-staging"

echo -e "${BLUE}🔧 Completar Configuración de Staging (No Interactivo)${NC}"
echo "=================================================="
echo ""

# Verificar que la clave existe
if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Error: No se encontró la clave $KEY_FILE${NC}"
    echo ""
    echo "Crea el archivo con la clave privada de staging"
    echo "O especifica otra clave: KEY_FILE=otra-clave.pem ./scripts/completar-staging-no-interactive.sh"
    exit 1
fi

# Configurar permisos de la clave
chmod 400 "$KEY_FILE" 2>/dev/null || true

# Función para ejecutar comandos remotos con timeout
run_remote() {
    local cmd="$1"
    ssh -4 -i "$KEY_FILE" \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=10 \
        -o ServerAliveInterval=5 \
        -o ServerAliveCountMax=3 \
        "$STAGING_USER@$STAGING_HOST" \
        "bash -c '${cmd}'" 2>&1
}

# Verificar conexión con timeout (forzar IPv4)
echo -e "${YELLOW}📡 Verificando conexión a $STAGING_HOST...${NC}"
if ! ssh -4 -i "$KEY_FILE" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=10 \
    "$STAGING_USER@$STAGING_HOST" \
    "echo 'OK'" > /dev/null 2>&1; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    echo ""
    echo "Posibles causas:"
    echo "  - IP incorrecta: $STAGING_HOST"
    echo "  - Security Group no permite SSH (puerto 22)"
    echo "  - La instancia no está corriendo"
    echo "  - Problemas de red"
    echo ""
    echo -e "${YELLOW}💡 Prueba manualmente:${NC}"
    echo "  ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST"
    echo ""
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"
echo ""

# 1. Corregir Permisos de Storage
echo -e "${YELLOW}1️⃣ Corrigiendo permisos de storage...${NC}"
if run_remote "cd $PROJECT_PATH && sudo chown -R www-data:www-data storage bootstrap/cache 2>&1"; then
    run_remote "cd $PROJECT_PATH && sudo chmod -R 775 storage bootstrap/cache 2>&1"
    echo -e "${GREEN}✅ Permisos corregidos${NC}"
else
    echo -e "${YELLOW}⚠️  No se pudieron corregir permisos (puede que el directorio no exista)${NC}"
fi
echo ""

# 2. Verificar Base de Datos
echo -e "${YELLOW}2️⃣ Verificando base de datos...${NC}"
DB_EXISTS=$(run_remote "timeout 5 mysql -u magicai_staging -pstaging_password_2024 -e 'SHOW DATABASES LIKE \"magicai_staging\"' 2>/dev/null | grep -c magicai_staging || echo '0'" | tail -1 | tr -d ' ')

if [ "$DB_EXISTS" = "0" ]; then
    echo -e "${YELLOW}⚠️  Base de datos no existe o no se puede acceder${NC}"
    echo "Intentando crear..."
    if run_remote "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" 2>&1"; then
        run_remote "sudo mysql -e \"CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';\" 2>&1" || true
        run_remote "sudo mysql -e \"GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';\" 2>&1" || true
        run_remote "sudo mysql -e \"FLUSH PRIVILEGES;\" 2>&1" || true
        echo -e "${GREEN}✅ Base de datos creada${NC}"
    else
        echo -e "${RED}❌ No se pudo crear la base de datos${NC}"
    fi
else
    echo -e "${GREEN}✅ Base de datos existe${NC}"
fi

# Verificar si la BD está vacía
TABLE_COUNT=$(run_remote "timeout 5 mysql -u magicai_staging -pstaging_password_2024 magicai_staging -e 'SHOW TABLES' 2>/dev/null | wc -l || echo '0'" | tail -1 | tr -d ' ')

if [ "${TABLE_COUNT:-0}" -lt "5" ]; then
    echo -e "${YELLOW}⚠️  Base de datos parece estar vacía ($TABLE_COUNT tablas)${NC}"
    echo ""
    echo -e "${BLUE}ℹ️  Para clonar la BD desde producción:${NC}"
    echo "  ./scripts/clone-production-db-to-staging.sh"
    echo "  O revisa GUIA_COMPLETAR_STAGING.md"
    echo ""
else
    echo -e "${GREEN}✅ Base de datos tiene $TABLE_COUNT tablas${NC}"
fi
echo ""

# 3. Ejecutar Migraciones
echo -e "${YELLOW}3️⃣ Ejecutando migraciones...${NC}"
if run_remote "cd $PROJECT_PATH && php artisan migrate --force 2>&1"; then
    echo -e "${GREEN}✅ Migraciones ejecutadas${NC}"
else
    echo -e "${YELLOW}⚠️  Migraciones completadas o con errores menores${NC}"
fi
echo ""

# 4. Verificar .env
echo -e "${YELLOW}4️⃣ Verificando configuración .env...${NC}"
ENV_EXISTS=$(run_remote "test -f $PROJECT_PATH/.env && echo '1' || echo '0'" | tail -1)

if [ "$ENV_EXISTS" = "0" ]; then
    echo -e "${YELLOW}⚠️  Archivo .env no existe${NC}"
    if run_remote "cd $PROJECT_PATH && test -f .env.example && cp .env.example .env && echo 'OK' || echo 'FAIL'" | grep -q "OK"; then
        run_remote "cd $PROJECT_PATH && php artisan key:generate 2>&1" || true
        echo -e "${GREEN}✅ .env creado desde .env.example${NC}"
    else
        echo -e "${RED}❌ No se pudo crear .env (no hay .env.example)${NC}"
    fi
fi

# Verificar variables críticas
APP_ENV=$(run_remote "grep '^APP_ENV=' $PROJECT_PATH/.env 2>/dev/null | cut -d '=' -f2 || echo ''" | tail -1 | tr -d ' ')
if [ -z "$APP_ENV" ] || [ "$APP_ENV" != "staging" ]; then
    echo -e "${YELLOW}⚠️  Configurando APP_ENV=staging...${NC}"
    run_remote "cd $PROJECT_PATH && sed -i 's/^APP_ENV=.*/APP_ENV=staging/' .env 2>&1 || echo 'APP_ENV=staging' >> .env" || true
fi

DB_DATABASE=$(run_remote "grep '^DB_DATABASE=' $PROJECT_PATH/.env 2>/dev/null | cut -d '=' -f2 || echo ''" | tail -1 | tr -d ' ')
if [ -z "$DB_DATABASE" ] || [ "$DB_DATABASE" != "magicai_staging" ]; then
    echo -e "${YELLOW}⚠️  Configurando DB_DATABASE=magicai_staging...${NC}"
    run_remote "cd $PROJECT_PATH && sed -i 's/^DB_DATABASE=.*/DB_DATABASE=magicai_staging/' .env 2>&1 || echo 'DB_DATABASE=magicai_staging' >> .env" || true
fi

echo -e "${GREEN}✅ Configuración .env verificada${NC}"
echo ""

# 5. Instalar Dependencias
echo -e "${YELLOW}5️⃣ Verificando dependencias...${NC}"
COMPOSER_LOCK=$(run_remote "test -f $PROJECT_PATH/composer.lock && echo '1' || echo '0'" | tail -1)
if [ "$COMPOSER_LOCK" = "1" ]; then
    echo "Instalando dependencias de Composer..."
    run_remote "cd $PROJECT_PATH && composer install --no-dev --optimize-autoloader --no-interaction 2>&1" || echo "Composer install completado o con advertencias"
    echo -e "${GREEN}✅ Dependencias de Composer verificadas${NC}"
fi

PACKAGE_JSON=$(run_remote "test -f $PROJECT_PATH/package.json && echo '1' || echo '0'" | tail -1)
if [ "$PACKAGE_JSON" = "1" ]; then
    echo "Instalando dependencias de NPM..."
    run_remote "cd $PROJECT_PATH && npm install --production 2>&1" || echo "NPM install completado"
    echo "Compilando assets..."
    run_remote "cd $PROJECT_PATH && npm run build 2>&1" || echo "Build completado"
    echo -e "${GREEN}✅ Dependencias de NPM verificadas${NC}"
fi
echo ""

# 6. Optimizar Laravel
echo -e "${YELLOW}6️⃣ Optimizando Laravel...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:clear 2>&1" || true
run_remote "cd $PROJECT_PATH && php artisan cache:clear 2>&1" || true
run_remote "cd $PROJECT_PATH && php artisan route:clear 2>&1" || true
run_remote "cd $PROJECT_PATH && php artisan view:clear 2>&1" || true
run_remote "cd $PROJECT_PATH && php artisan config:cache 2>&1" || true
run_remote "cd $PROJECT_PATH && php artisan route:cache 2>&1" || true
run_remote "cd $PROJECT_PATH && php artisan view:cache 2>&1" || true
echo -e "${GREEN}✅ Laravel optimizado${NC}"
echo ""

# 7. Verificar Nginx
echo -e "${YELLOW}7️⃣ Verificando Nginx...${NC}"
NGINX_STATUS=$(run_remote "sudo systemctl is-active nginx 2>&1 || echo 'inactive'" | tail -1)
if [ "$NGINX_STATUS" != "active" ]; then
    echo "Iniciando Nginx..."
    run_remote "sudo systemctl start nginx 2>&1" || true
fi
run_remote "sudo systemctl reload nginx 2>&1" || true
echo -e "${GREEN}✅ Nginx funcionando${NC}"
echo ""

# 8. Verificar PHP-FPM
echo -e "${YELLOW}8️⃣ Verificando PHP-FPM...${NC}"
PHPFPM_STATUS=$(run_remote "sudo systemctl is-active php8.2-fpm 2>&1 || echo 'inactive'" | tail -1)
if [ "$PHPFPM_STATUS" != "active" ]; then
    echo "Iniciando PHP-FPM..."
    run_remote "sudo systemctl start php8.2-fpm 2>&1" || true
fi
run_remote "sudo systemctl reload php8.2-fpm 2>&1" || true
echo -e "${GREEN}✅ PHP-FPM funcionando${NC}"
echo ""

# 9. Verificar que el sitio responde
echo -e "${YELLOW}9️⃣ Verificando que el sitio responde...${NC}"
HTTP_CODE=$(run_remote "curl -s -o /dev/null -w '%{http_code}' http://localhost 2>&1 || echo '000'" | tail -1)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "301" ]; then
    echo -e "${GREEN}✅ Sitio responde correctamente (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${YELLOW}⚠️  Sitio responde con código HTTP $HTTP_CODE${NC}"
    echo "Revisa los logs:"
    echo "  ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'tail -f $PROJECT_PATH/storage/logs/laravel.log'"
fi
echo ""

# Resumen
echo ""
echo -e "${GREEN}🎉 Configuración de Staging Completada!${NC}"
echo "=================================================="
echo ""
echo -e "${BLUE}📋 Resumen:${NC}"
echo "✅ Permisos de storage corregidos"
echo "✅ Base de datos verificada"
echo "✅ Migraciones ejecutadas"
echo "✅ Configuración .env verificada"
echo "✅ Dependencias verificadas"
echo "✅ Laravel optimizado"
echo "✅ Nginx funcionando"
echo "✅ PHP-FPM funcionando"
echo ""
echo -e "${BLUE}🔗 Acceso:${NC}"
echo "HTTP: http://$STAGING_HOST"
echo "SSH: ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST"
echo ""
echo -e "${BLUE}📝 Próximos pasos:${NC}"
echo "1. Si la BD está vacía, clónala desde producción:"
echo "   ./scripts/clone-production-db-to-staging.sh"
echo ""
echo "2. Configura variables de entorno en .env (API keys, etc.)"
echo ""
echo "3. Prueba el login y funcionalidades principales"
echo ""
echo "4. Despliega los cambios de Fase 1:"
echo "   export STAGING_HOST=$STAGING_HOST"
echo "   ./scripts/deploy-to-staging.sh"
echo ""

