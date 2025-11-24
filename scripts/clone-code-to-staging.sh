#!/bin/bash

# 📂 Script para Clonar Código de Producción a Staging
# Este script copia el código desde producción a staging

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
PROD_HOST="${PROD_HOST:-34.207.248.220}"
PROD_USER="${PROD_USER:-ubuntu}"
STAGING_HOST="${STAGING_HOST:-}"
STAGING_USER="${STAGING_USER:-ubuntu}"
KEY_FILE="${KEY_FILE:-staging-tausepro-key.pem}"
PROD_PATH="${PROD_PATH:-/var/www/magicai}"
STAGING_PATH="${STAGING_PATH:-/var/www/magicai-staging}"

echo -e "${BLUE}📂 Clonar Código: Producción → Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó staging host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 ./scripts/clone-code-to-staging.sh"
    exit 1
fi

# Función para ejecutar comandos remotos
run_remote_prod() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$PROD_USER@$PROD_HOST" "$1"
}

run_remote_staging() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "$1"
}

echo -e "${YELLOW}📡 Verificando conexiones...${NC}"
if ! run_remote_prod "echo 'Producción OK'"; then
    echo -e "${RED}❌ No se pudo conectar a producción${NC}"
    exit 1
fi

if ! run_remote_staging "echo 'Staging OK'"; then
    echo -e "${RED}❌ No se pudo conectar a staging${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexiones establecidas${NC}"

echo -e "${YELLOW}📦 Creando directorio en staging...${NC}"
run_remote_staging "sudo mkdir -p $STAGING_PATH"
run_remote_staging "sudo chown -R $STAGING_USER:$STAGING_USER $STAGING_PATH"
echo -e "${GREEN}✅ Directorio creado${NC}"

echo -e "${YELLOW}📤 Copiando código desde producción...${NC}"
echo -e "${BLUE}   (Esto puede tardar varios minutos...)${NC}"

# Usar rsync para copiar archivos (excluyendo vendor, node_modules, storage)
run_remote_prod "rsync -avz --exclude 'vendor' --exclude 'node_modules' --exclude 'storage/logs' --exclude 'storage/framework/cache' --exclude 'storage/framework/sessions' --exclude 'storage/framework/views' --exclude '.git' $PROD_PATH/ $STAGING_USER@$STAGING_HOST:$STAGING_PATH/"

echo -e "${GREEN}✅ Código copiado${NC}"

echo -e "${YELLOW}📝 Creando archivo .env desde ejemplo...${NC}"
run_remote_staging "cd $STAGING_PATH && cp .env.example .env 2>/dev/null || echo 'No .env.example found, create .env manually'"
echo -e "${GREEN}✅ Archivo .env preparado${NC}"

echo ""
echo -e "${GREEN}🎉 Código clonado exitosamente!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Próximos pasos:${NC}"
echo "1. Configurar archivo .env en staging"
echo "2. Instalar dependencias: composer install && npm install"
echo "3. Generar APP_KEY: php artisan key:generate"
echo "4. Ejecutar migraciones: php artisan migrate"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
echo "- Revisa y configura el archivo .env con las credenciales de staging"
echo "- Asegúrate de usar claves de API de prueba/staging"
echo "- Configura APP_ENV=staging y APP_DEBUG=true"

