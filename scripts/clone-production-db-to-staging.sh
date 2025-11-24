#!/bin/bash

# 📥 Script para Clonar Base de Datos de Producción a Staging
# Este script exporta la BD de producción e importa en staging

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
PROD_DB_NAME="${PROD_DB_NAME:-magicai}"
STAGING_DB_NAME="${STAGING_DB_NAME:-magicai_staging}"
STAGING_DB_USER="${STAGING_DB_USER:-magicai_staging}"

echo -e "${BLUE}📥 Clonar Base de Datos: Producción → Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó staging host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 ./scripts/clone-production-db-to-staging.sh"
    exit 1
fi

# Solicitar contraseñas
read -sp "Contraseña MySQL producción (usuario root): " PROD_MYSQL_PASSWORD
echo ""
read -sp "Contraseña MySQL staging (usuario $STAGING_DB_USER): " STAGING_DB_PASSWORD
echo ""

# Función para ejecutar comandos remotos
run_remote_prod() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$PROD_USER@$PROD_HOST" "$1"
}

run_remote_staging() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "$1"
}

DUMP_FILE="/tmp/magicai_prod_$(date +%Y%m%d_%H%M%S).sql"

echo -e "${YELLOW}📤 Exportando base de datos de producción...${NC}"
run_remote_prod "mysqldump -u root -p'$PROD_MYSQL_PASSWORD' $PROD_DB_NAME > $DUMP_FILE"
echo -e "${GREEN}✅ Base de datos exportada${NC}"

echo -e "${YELLOW}📥 Descargando dump...${NC}"
LOCAL_DUMP="./magicai_prod_$(date +%Y%m%d_%H%M%S).sql"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    "$PROD_USER@$PROD_HOST:$DUMP_FILE" \
    "$LOCAL_DUMP"
echo -e "${GREEN}✅ Dump descargado${NC}"

echo -e "${YELLOW}📤 Subiendo dump a staging...${NC}"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    "$LOCAL_DUMP" \
    "$STAGING_USER@$STAGING_HOST:/tmp/magicai_prod.sql"
echo -e "${GREEN}✅ Dump subido${NC}"

echo -e "${YELLOW}🗄️ Importando en staging...${NC}"
run_remote_staging "mysql -u $STAGING_DB_USER -p'$STAGING_DB_PASSWORD' $STAGING_DB_NAME < /tmp/magicai_prod.sql"
echo -e "${GREEN}✅ Base de datos importada${NC}"

echo -e "${YELLOW}🧹 Limpiando archivos temporales...${NC}"
run_remote_prod "rm -f $DUMP_FILE"
run_remote_staging "rm -f /tmp/magicai_prod.sql"
rm -f "$LOCAL_DUMP"
echo -e "${GREEN}✅ Archivos temporales eliminados${NC}"

echo ""
echo -e "${GREEN}🎉 Base de datos clonada exitosamente!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Próximos pasos:${NC}"
echo "1. (Opcional) Anonimizar datos: ./scripts/anonymize-staging-db.sh"
echo "2. Verificar que la aplicación funciona"
echo ""
echo -e "${YELLOW}⚠️  ADVERTENCIA:${NC}"
echo "Los datos de staging ahora son una copia exacta de producción."
echo "Considera anonimizar datos sensibles antes de usar staging."

