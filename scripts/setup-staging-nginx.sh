#!/bin/bash

# 🌐 Script de Configuración de Nginx para Staging
# Este script crea la configuración de Nginx para staging

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
DOMAIN="${DOMAIN:-staging.magicai.com}"

echo -e "${BLUE}🌐 Configuración de Nginx para Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó el host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 DOMAIN=staging.magicai.com ./scripts/setup-staging-nginx.sh"
    exit 1
fi

# Función para ejecutar comandos remotos
run_remote() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "$1"
}

echo -e "${YELLOW}📡 Verificando conexión...${NC}"
if ! run_remote "echo 'Conexión exitosa'"; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"

echo -e "${YELLOW}📝 Creando configuración de Nginx...${NC}"

# Crear configuración de Nginx
NGINX_CONFIG=$(cat <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    root $PROJECT_PATH/public;
    index index.php index.html;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Logs
    access_log /var/log/nginx/staging-access.log;
    error_log /var/log/nginx/staging-error.log;
}
EOF
)

# Crear archivo temporal local
TEMP_CONFIG="/tmp/nginx-staging-config.conf"
echo "$NGINX_CONFIG" > "$TEMP_CONFIG"

# Subir configuración
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    "$TEMP_CONFIG" \
    "$STAGING_USER@$STAGING_HOST:/tmp/nginx-staging-config.conf"

# Mover a ubicación correcta en servidor
run_remote "sudo mv /tmp/nginx-staging-config.conf /etc/nginx/sites-available/$PROJECT_NAME"
run_remote "sudo chown root:root /etc/nginx/sites-available/$PROJECT_NAME"
run_remote "sudo chmod 644 /etc/nginx/sites-available/$PROJECT_NAME"

# Eliminar archivo temporal local
rm -f "$TEMP_CONFIG"

echo -e "${GREEN}✅ Configuración creada${NC}"

echo -e "${YELLOW}🔗 Activando sitio...${NC}"
run_remote "sudo ln -sf /etc/nginx/sites-available/$PROJECT_NAME /etc/nginx/sites-enabled/$PROJECT_NAME"
run_remote "sudo rm -f /etc/nginx/sites-enabled/default"
echo -e "${GREEN}✅ Sitio activado${NC}"

echo -e "${YELLOW}✅ Verificando configuración de Nginx...${NC}"
if run_remote "sudo nginx -t"; then
    echo -e "${GREEN}✅ Configuración válida${NC}"
else
    echo -e "${RED}❌ Error en configuración de Nginx${NC}"
    exit 1
fi

echo -e "${YELLOW}🔄 Recargando Nginx...${NC}"
run_remote "sudo systemctl reload nginx"
echo -e "${GREEN}✅ Nginx recargado${NC}"

echo ""
echo -e "${GREEN}🎉 Nginx configurado exitosamente!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Información:${NC}"
echo "Dominio: $DOMAIN"
echo "Path: $PROJECT_PATH"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
echo "1. Configura el DNS para que $DOMAIN apunte a $STAGING_HOST"
echo "2. O accede temporalmente por IP: http://$STAGING_HOST"
echo ""
echo -e "${BLUE}🔍 Verificar logs:${NC}"
echo "ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'sudo tail -f /var/log/nginx/staging-error.log'"

