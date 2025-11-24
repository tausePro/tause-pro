#!/bin/bash

# 🔧 Script para Arreglar Base de Datos de Staging

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

STAGING_HOST="13.218.39.31"
STAGING_USER="ubuntu"
KEY_FILE="staging-tausepro-key.pem"
PROJECT_PATH="/var/www/magicai-staging"

echo -e "${BLUE}🔧 Arreglar Base de Datos de Staging${NC}"
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

echo -e "${YELLOW}1️⃣ Verificando usuarios MySQL existentes...${NC}"
run_remote "sudo mysql -e 'SELECT User, Host FROM mysql.user' 2>&1 | grep -E 'User|staging|magicai|root|debian'"
echo ""

echo -e "${YELLOW}2️⃣ Verificando bases de datos existentes...${NC}"
run_remote "sudo mysql -e 'SHOW DATABASES' 2>&1 | grep -E 'Database|staging|magicai'"
echo ""

echo -e "${YELLOW}3️⃣ Creando base de datos y usuario...${NC}"

# Crear base de datos
run_remote "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\" 2>&1"

# Crear usuario y dar permisos
run_remote "sudo mysql -e \"
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
\" 2>&1"

echo -e "${GREEN}✅ Base de datos y usuario creados${NC}"
echo ""

echo -e "${YELLOW}4️⃣ Verificando conexión con nuevo usuario...${NC}"
if run_remote "mysql -u magicai_staging -pstaging_password_2024 -e 'SELECT 1' 2>&1 | grep -q '1'"; then
    echo -e "${GREEN}✅ Conexión exitosa${NC}"
else
    echo -e "${YELLOW}⚠️  Verificando error...${NC}"
    run_remote "mysql -u magicai_staging -pstaging_password_2024 -e 'SELECT 1' 2>&1"
fi
echo ""

echo -e "${YELLOW}5️⃣ Verificando .env en staging...${NC}"
ENV_DB=$(run_remote "grep '^DB_DATABASE=' $PROJECT_PATH/.env 2>/dev/null | cut -d '=' -f2 || echo ''" | tail -1 | tr -d ' ')
ENV_USER=$(run_remote "grep '^DB_USERNAME=' $PROJECT_PATH/.env 2>/dev/null | cut -d '=' -f2 || echo ''" | tail -1 | tr -d ' ')
ENV_PASS=$(run_remote "grep '^DB_PASSWORD=' $PROJECT_PATH/.env 2>/dev/null | cut -d '=' -f2 || echo ''" | tail -1 | tr -d ' ')

echo "DB_DATABASE en .env: ${ENV_DB:-No encontrado}"
echo "DB_USERNAME en .env: ${ENV_USER:-No encontrado}"
echo "DB_PASSWORD en .env: ${ENV_PASS:+Configurado}"

if [ "$ENV_DB" != "magicai_staging" ] || [ "$ENV_USER" != "magicai_staging" ]; then
    echo ""
    echo -e "${YELLOW}⚠️  Actualizando .env...${NC}"
    run_remote "cd $PROJECT_PATH && sed -i 's/^DB_DATABASE=.*/DB_DATABASE=magicai_staging/' .env 2>&1 || echo 'DB_DATABASE=magicai_staging' >> .env"
    run_remote "cd $PROJECT_PATH && sed -i 's/^DB_USERNAME=.*/DB_USERNAME=magicai_staging/' .env 2>&1 || echo 'DB_USERNAME=magicai_staging' >> .env"
    run_remote "cd $PROJECT_PATH && sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=staging_password_2024/' .env 2>&1 || echo 'DB_PASSWORD=staging_password_2024' >> .env"
    echo -e "${GREEN}✅ .env actualizado${NC}"
fi
echo ""

echo -e "${GREEN}🎉 Base de datos configurada!${NC}"
echo ""
echo "Próximos pasos:"
echo "  1. Ejecutar migraciones: ssh -4 -i $KEY_FILE $STAGING_USER@$STAGING_HOST 'cd $PROJECT_PATH && php artisan migrate --force'"
echo "  2. O continuar con: ./scripts/completar-staging-no-interactive.sh"
echo ""



