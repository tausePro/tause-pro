#!/bin/bash

# 🔧 Script para Configurar Staging Manualmente
# Ejecutar DESPUÉS de conectar vía SSH

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

STAGING_HOST="${1:-}"
KEY_FILE="${KEY_FILE:-staging-tausepro-key.pem}"
PROJECT_PATH="/var/www/magicai"

if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: Debes proporcionar la IP de staging${NC}"
    echo ""
    echo "Uso: $0 <IP_STAGING>"
    echo ""
    echo "Ejemplo:"
    echo "  $0 44.211.83.213"
    exit 1
fi

echo -e "${BLUE}🔧 Configurando Staging en $STAGING_HOST${NC}"
echo "=================================================="
echo ""

# Función para ejecutar comandos remotos
run_remote() {
    ssh -4 -i "$KEY_FILE" \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=10 \
        "$STAGING_USER@$STAGING_HOST" \
        "$1" 2>&1
}

STAGING_USER="ubuntu"

# Verificar conexión
echo -e "${YELLOW}1️⃣ Verificando conexión...${NC}"
if ! ssh -4 -i "$KEY_FILE" -o ConnectTimeout=5 -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "echo 'OK'" > /dev/null 2>&1; then
    echo -e "${RED}❌ No se pudo conectar a $STAGING_HOST${NC}"
    echo ""
    echo "Verifica:"
    echo "  - IP correcta: $STAGING_HOST"
    echo "  - Key correcta: $KEY_FILE"
    echo "  - Security Group permite SSH"
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"
echo ""

# 1. Verificar servicios
echo -e "${YELLOW}2️⃣ Verificando servicios...${NC}"
run_remote "sudo systemctl status ssh --no-pager | head -3"
run_remote "sudo systemctl status nginx --no-pager | head -3"
run_remote "sudo systemctl status php8.2-fpm --no-pager | head -3"
echo ""

# 2. Configurar .env
echo -e "${YELLOW}3️⃣ Configurando .env...${NC}"
run_remote "cd $PROJECT_PATH && sudo cp .env .env.production.backup"
run_remote "cd $PROJECT_PATH && sudo sed -i 's/^APP_ENV=.*/APP_ENV=staging/' .env"
run_remote "cd $PROJECT_PATH && sudo sed -i 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env"
run_remote "cd $PROJECT_PATH && sudo sed -i 's|^APP_URL=.*|APP_URL=http://test.tause.pro|' .env"
run_remote "cd $PROJECT_PATH && sudo sed -i 's/^DB_DATABASE=.*/DB_DATABASE=magicai_staging/' .env"
run_remote "cd $PROJECT_PATH && sudo sed -i 's/^DB_USERNAME=.*/DB_USERNAME=magicai_staging/' .env"
run_remote "cd $PROJECT_PATH && sudo sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=staging_password_2024/' .env"
echo -e "${GREEN}✅ .env configurado${NC}"
echo ""

# 3. Crear base de datos
echo -e "${YELLOW}4️⃣ Creando base de datos...${NC}"
run_remote "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
run_remote "sudo mysql -e \"CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';\""
run_remote "sudo mysql -e \"GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';\""
run_remote "sudo mysql -e \"FLUSH PRIVILEGES;\""
echo -e "${GREEN}✅ Base de datos creada${NC}"
echo ""

# 4. Configurar Nginx
echo -e "${YELLOW}5️⃣ Configurando Nginx...${NC}"
NGINX_CONF="server {
    listen 80;
    listen [::]:80;
    server_name test.tause.pro;
    root $PROJECT_PATH/public;
    index index.php index.html;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    charset utf-8;

    # Excepción para chatbot frame: permitir embedding desde cualquier origen
    location ~ ^/chatbot/[^/]+/frame\$ {
        # NO agregar X-Frame-Options aquí para permitir embedding cross-origin
        add_header X-Content-Type-Options \"nosniff\";
        add_header Content-Security-Policy \"frame-ancestors *\" always;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php\$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}"

run_remote "echo '$NGINX_CONF' | sudo tee /etc/nginx/sites-available/test.tause.pro > /dev/null"
run_remote "sudo ln -sf /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/"
run_remote "sudo nginx -t && sudo systemctl reload nginx"
echo -e "${GREEN}✅ Nginx configurado${NC}"
echo ""

# 5. Permisos
echo -e "${YELLOW}6️⃣ Configurando permisos...${NC}"
run_remote "sudo chown -R www-data:www-data $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache"
run_remote "sudo chmod -R 775 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache"
run_remote "sudo mkdir -p $PROJECT_PATH/storage/framework/{cache,sessions,views,testing} $PROJECT_PATH/storage/logs"
echo -e "${GREEN}✅ Permisos configurados${NC}"
echo ""

# 6. Cache Laravel
echo -e "${YELLOW}7️⃣ Actualizando cache Laravel...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear"
run_remote "cd $PROJECT_PATH && php artisan config:cache && php artisan route:cache && php artisan view:cache"
echo -e "${GREEN}✅ Cache actualizado${NC}"
echo ""

# 7. Verificar que funciona
echo -e "${YELLOW}8️⃣ Verificando que funciona...${NC}"
HTTP_TEST=$(run_remote "curl -s -o /dev/null -w '%{http_code}' http://localhost" || echo "000")
if [ "$HTTP_TEST" == "200" ] || [ "$HTTP_TEST" == "302" ]; then
    echo -e "${GREEN}✅ HTTP responde correctamente (código: $HTTP_TEST)${NC}"
else
    echo -e "${YELLOW}⚠️  HTTP responde con código: $HTTP_TEST${NC}"
fi
echo ""

# 8. Configurar firewall (AL FINAL)
echo -e "${YELLOW}9️⃣ Configurando firewall...${NC}"
run_remote "sudo ufw allow 22/tcp"
run_remote "sudo ufw allow 80/tcp"
run_remote "sudo ufw allow 443/tcp"
run_remote "sudo ufw --force enable"
echo -e "${GREEN}✅ Firewall configurado${NC}"
echo ""

echo -e "${BLUE}🎉 Configuración completada!${NC}"
echo "=================================================="
echo ""
echo "Staging está listo en: http://test.tause.pro"
echo ""
echo "Próximos pasos:"
echo "  1. Verificar que test.tause.pro apunta a $STAGING_HOST"
echo "  2. Desplegar cambios de Fase 1: ./scripts/deploy-fase1-to-staging.sh"
echo "  3. Probar en navegador: http://test.tause.pro"
echo ""



