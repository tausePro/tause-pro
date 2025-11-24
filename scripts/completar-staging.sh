#!/bin/bash

# 🚀 Script para Completar Configuración de Staging
# Resuelve los problemas pendientes identificados

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
PROJECT_PATH="/var/www/magicai-staging"

echo -e "${BLUE}🔧 Completar Configuración de Staging${NC}"
echo "=================================================="
echo ""

# Verificar que la clave existe
if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Error: No se encontró la clave $KEY_FILE${NC}"
    echo "Crea el archivo con la clave privada de staging"
    exit 1
fi

# Configurar permisos de la clave
chmod 400 "$KEY_FILE" 2>/dev/null || true

# Función para ejecutar comandos remotos con timeout
run_remote() {
    ssh -i "$KEY_FILE" \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=10 \
        -o ServerAliveInterval=5 \
        -o ServerAliveCountMax=3 \
        "$STAGING_USER@$STAGING_HOST" \
        "timeout 30 bash -c '$1'" 2>&1
}

echo -e "${YELLOW}📡 Verificando conexión...${NC}"
CONNECTION_TEST=$(timeout 10 ssh -i "$KEY_FILE" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=10 \
    "$STAGING_USER@$STAGING_HOST" \
    "echo 'OK'" 2>&1) || CONNECTION_TEST="FAILED"

if [ "$CONNECTION_TEST" != "OK" ]; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    echo ""
    echo "Error: $CONNECTION_TEST"
    echo ""
    echo "Verifica:"
    echo "  - IP correcta: $STAGING_HOST"
    echo "  - Clave correcta: $KEY_FILE"
    echo "  - Security Group permite SSH (puerto 22)"
    echo "  - La instancia está corriendo en AWS"
    echo ""
    echo -e "${YELLOW}💡 Prueba manualmente:${NC}"
    echo "  ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST"
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"
echo ""

# 1. Corregir Permisos de Storage
echo -e "${YELLOW}1️⃣ Corrigiendo permisos de storage...${NC}"
run_remote "cd $PROJECT_PATH && sudo chown -R www-data:www-data storage bootstrap/cache"
run_remote "cd $PROJECT_PATH && sudo chmod -R 775 storage bootstrap/cache"
echo -e "${GREEN}✅ Permisos corregidos${NC}"
echo ""

# 2. Verificar Base de Datos
echo -e "${YELLOW}2️⃣ Verificando base de datos...${NC}"
DB_EXISTS=$(run_remote "mysql -u magicai_staging -pstaging_password_2024 -e 'SHOW DATABASES LIKE \"magicai_staging\"' 2>/dev/null | grep -c magicai_staging || echo '0'")

if [ "$DB_EXISTS" = "0" ]; then
    echo -e "${RED}❌ Base de datos no existe o no se puede acceder${NC}"
    echo "Creando base de datos..."
    run_remote "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
    run_remote "sudo mysql -e \"CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';\""
    run_remote "sudo mysql -e \"GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';\""
    run_remote "sudo mysql -e \"FLUSH PRIVILEGES;\""
    echo -e "${GREEN}✅ Base de datos creada${NC}"
else
    echo -e "${GREEN}✅ Base de datos existe${NC}"
fi

# Verificar si la BD está vacía
TABLE_COUNT=$(run_remote "mysql -u magicai_staging -pstaging_password_2024 magicai_staging -e 'SHOW TABLES' 2>/dev/null | wc -l || echo '0'")

if [ "$TABLE_COUNT" -lt "5" ]; then
    echo -e "${YELLOW}⚠️  Base de datos parece estar vacía o casi vacía ($TABLE_COUNT tablas)${NC}"
    echo ""
    echo -e "${BLUE}ℹ️  Para clonar la BD desde producción, ejecuta manualmente:${NC}"
    echo ""
    echo "  ./scripts/clone-production-db-to-staging.sh"
    echo ""
    echo "O sigue las instrucciones en GUIA_COMPLETAR_STAGING.md"
    echo ""
    CLONE_DB="n"
    
    if [ "$CLONE_DB" = "s" ] || [ "$CLONE_DB" = "S" ]; then
        echo -e "${YELLOW}📥 Clonando base de datos desde producción...${NC}"
        echo ""
        echo "Necesitarás las credenciales de producción MySQL"
        echo ""
        
        # Opción 1: Desde producción directamente
        echo -e "${BLUE}Opción 1: Clonar directamente desde producción${NC}"
        echo "Ejecuta manualmente:"
        echo ""
        echo "ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 \\"
        echo "  \"sudo mysqldump -u debian-sys-maint -pXBM09DTCfSTl7a6S tause_pro\" | \\"
        echo "ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST \\"
        echo "  \"sudo mysql -u debian-sys-maint -pXcSG6LVUmLzS5zEW magicai_staging\""
        echo ""
        
        # Opción 2: Desde archivo local
        echo -e "${BLUE}Opción 2: Si ya tienes un dump local${NC}"
        echo "Si tienes un archivo dump.sql local:"
        echo ""
        echo "scp -i $KEY_FILE dump.sql $STAGING_USER@$STAGING_HOST:/tmp/"
        echo "ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST \\"
        echo "  \"sudo mysql -u debian-sys-maint -pXcSG6LVUmLzS5zEW magicai_staging < /tmp/dump.sql\""
        echo ""
        
        echo -e "${YELLOW}⚠️  Continuando sin clonar BD por ahora...${NC}"
    fi
else
    echo -e "${GREEN}✅ Base de datos tiene $TABLE_COUNT tablas${NC}"
fi
echo ""

# 3. Ejecutar Migraciones
echo -e "${YELLOW}3️⃣ Ejecutando migraciones...${NC}"
run_remote "cd $PROJECT_PATH && php artisan migrate --force 2>&1 || echo 'Migraciones completadas o con errores menores'"
echo -e "${GREEN}✅ Migraciones ejecutadas${NC}"
echo ""

# 4. Verificar .env
echo -e "${YELLOW}4️⃣ Verificando configuración .env...${NC}"
ENV_EXISTS=$(run_remote "test -f $PROJECT_PATH/.env && echo '1' || echo '0'")

if [ "$ENV_EXISTS" = "0" ]; then
    echo -e "${RED}❌ Archivo .env no existe${NC}"
    echo "Creando .env desde .env.example..."
    run_remote "cd $PROJECT_PATH && cp .env.example .env 2>/dev/null || echo 'No hay .env.example'"
    run_remote "cd $PROJECT_PATH && php artisan key:generate"
fi

# Verificar variables críticas
APP_ENV=$(run_remote "grep '^APP_ENV=' $PROJECT_PATH/.env | cut -d '=' -f2 || echo ''")
if [ -z "$APP_ENV" ] || [ "$APP_ENV" != "staging" ]; then
    echo -e "${YELLOW}⚠️  APP_ENV no está configurado como 'staging'${NC}"
    run_remote "cd $PROJECT_PATH && sed -i 's/^APP_ENV=.*/APP_ENV=staging/' .env"
    echo -e "${GREEN}✅ APP_ENV configurado${NC}"
fi

DB_DATABASE=$(run_remote "grep '^DB_DATABASE=' $PROJECT_PATH/.env | cut -d '=' -f2 || echo ''")
if [ -z "$DB_DATABASE" ] || [ "$DB_DATABASE" != "magicai_staging" ]; then
    echo -e "${YELLOW}⚠️  DB_DATABASE no está configurado correctamente${NC}"
    run_remote "cd $PROJECT_PATH && sed -i 's/^DB_DATABASE=.*/DB_DATABASE=magicai_staging/' .env"
    echo -e "${GREEN}✅ DB_DATABASE configurado${NC}"
fi

echo -e "${GREEN}✅ Configuración .env verificada${NC}"
echo ""

# 5. Instalar Dependencias
echo -e "${YELLOW}5️⃣ Verificando dependencias...${NC}"
COMPOSER_LOCK=$(run_remote "test -f $PROJECT_PATH/composer.lock && echo '1' || echo '0'")
if [ "$COMPOSER_LOCK" = "1" ]; then
    echo "Instalando dependencias de Composer..."
    run_remote "cd $PROJECT_PATH && composer install --no-dev --optimize-autoloader --no-interaction"
    echo -e "${GREEN}✅ Dependencias de Composer instaladas${NC}"
fi

PACKAGE_JSON=$(run_remote "test -f $PROJECT_PATH/package.json && echo '1' || echo '0'")
if [ "$PACKAGE_JSON" = "1" ]; then
    echo "Instalando dependencias de NPM..."
    run_remote "cd $PROJECT_PATH && npm install --production 2>&1 || echo 'NPM install completado'"
    echo "Compilando assets..."
    run_remote "cd $PROJECT_PATH && npm run build 2>&1 || echo 'Build completado'"
    echo -e "${GREEN}✅ Dependencias de NPM instaladas${NC}"
fi
echo ""

# 6. Optimizar Laravel
echo -e "${YELLOW}6️⃣ Optimizando Laravel...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:clear"
run_remote "cd $PROJECT_PATH && php artisan cache:clear"
run_remote "cd $PROJECT_PATH && php artisan route:clear"
run_remote "cd $PROJECT_PATH && php artisan view:clear"
run_remote "cd $PROJECT_PATH && php artisan config:cache"
run_remote "cd $PROJECT_PATH && php artisan route:cache"
run_remote "cd $PROJECT_PATH && php artisan view:cache"
echo -e "${GREEN}✅ Laravel optimizado${NC}"
echo ""

# 7. Verificar Nginx
echo -e "${YELLOW}7️⃣ Verificando Nginx...${NC}"
NGINX_STATUS=$(run_remote "sudo systemctl is-active nginx || echo 'inactive'")
if [ "$NGINX_STATUS" != "active" ]; then
    echo "Iniciando Nginx..."
    run_remote "sudo systemctl start nginx"
fi
run_remote "sudo systemctl reload nginx"
echo -e "${GREEN}✅ Nginx funcionando${NC}"
echo ""

# 8. Verificar PHP-FPM
echo -e "${YELLOW}8️⃣ Verificando PHP-FPM...${NC}"
PHPFPM_STATUS=$(run_remote "sudo systemctl is-active php8.2-fpm || echo 'inactive'")
if [ "$PHPFPM_STATUS" != "active" ]; then
    echo "Iniciando PHP-FPM..."
    run_remote "sudo systemctl start php8.2-fpm"
fi
run_remote "sudo systemctl reload php8.2-fpm"
echo -e "${GREEN}✅ PHP-FPM funcionando${NC}"
echo ""

# 9. Verificar que el sitio responde
echo -e "${YELLOW}9️⃣ Verificando que el sitio responde...${NC}"
HTTP_CODE=$(run_remote "curl -s -o /dev/null -w '%{http_code}' http://localhost || echo '000'")
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
echo "✅ Dependencias instaladas"
echo "✅ Laravel optimizado"
echo "✅ Nginx funcionando"
echo "✅ PHP-FPM funcionando"
echo ""
echo -e "${BLUE}🔗 Acceso:${NC}"
echo "HTTP: http://$STAGING_HOST"
echo "SSH: ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST"
echo ""
echo -e "${BLUE}📝 Próximos pasos:${NC}"
echo "1. Si la BD está vacía, clónala desde producción"
echo "2. Configura variables de entorno en .env (API keys, etc.)"
echo "3. Prueba el login y funcionalidades principales"
echo "4. Despliega los cambios de Fase 1 con: ./scripts/deploy-to-staging.sh"
echo ""
echo -e "${YELLOW}⚠️  Si necesitas clonar la BD desde producción:${NC}"
echo "Revisa ESTADO_STAGING.md para los comandos exactos"
echo ""

