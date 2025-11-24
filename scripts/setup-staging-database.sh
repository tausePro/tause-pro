#!/bin/bash

# 🗄️ Script de Configuración de Base de Datos Staging
# Este script crea la base de datos y usuario para staging

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
DB_NAME="${DB_NAME:-magicai_staging}"
DB_USER="${DB_USER:-magicai_staging}"

echo -e "${BLUE}🗄️ Configuración de Base de Datos Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó el host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 ./scripts/setup-staging-database.sh"
    exit 1
fi

# Solicitar contraseña de MySQL root
read -sp "Contraseña de MySQL root: " MYSQL_ROOT_PASSWORD
echo ""

# Solicitar contraseña para usuario staging
read -sp "Contraseña para usuario $DB_USER: " DB_PASSWORD
echo ""

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

echo -e "${YELLOW}🗄️ Creando base de datos...${NC}"
run_remote "mysql -u root -p'$MYSQL_ROOT_PASSWORD' -e \"CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
echo -e "${GREEN}✅ Base de datos creada${NC}"

echo -e "${YELLOW}👤 Creando usuario...${NC}"
run_remote "mysql -u root -p'$MYSQL_ROOT_PASSWORD' -e \"CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';\""
run_remote "mysql -u root -p'$MYSQL_ROOT_PASSWORD' -e \"GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';\""
run_remote "mysql -u root -p'$MYSQL_ROOT_PASSWORD' -e \"FLUSH PRIVILEGES;\""
echo -e "${GREEN}✅ Usuario creado${NC}"

echo ""
echo -e "${GREEN}🎉 Base de datos configurada!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Información:${NC}"
echo "Base de datos: $DB_NAME"
echo "Usuario: $DB_USER"
echo "Contraseña: [la que ingresaste]"
echo ""
echo -e "${BLUE}📝 Guarda esta información en el archivo .env:${NC}"
echo "DB_DATABASE=$DB_NAME"
echo "DB_USERNAME=$DB_USER"
echo "DB_PASSWORD=[tu contraseña]"
echo ""
echo -e "${YELLOW}🔗 Próximo paso:${NC}"
echo "Clonar base de datos desde producción: ./scripts/clone-production-db-to-staging.sh"

