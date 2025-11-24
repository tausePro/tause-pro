#!/bin/bash

# 🔒 Script para Anonimizar Datos Sensibles en Staging
# Este script anonimiza emails, teléfonos y otros datos sensibles

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

echo -e "${BLUE}🔒 Anonimización de Datos en Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó el host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 ./scripts/anonymize-staging-db.sh"
    exit 1
fi

# Solicitar contraseña
read -sp "Contraseña MySQL para $DB_USER: " DB_PASSWORD
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

echo -e "${YELLOW}⚠️  ADVERTENCIA:${NC}"
echo "Este script modificará datos en la base de datos de staging."
echo "Los cambios NO son reversibles."
read -p "¿Continuar? (s/N): " CONFIRM
if [[ ! $CONFIRM =~ ^[Ss]$ ]]; then
    echo "Cancelado."
    exit 0
fi

echo -e "${YELLOW}🔒 Anonimizando emails de usuarios...${NC}"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"UPDATE users SET email = CONCAT('user', id, '@staging.example.com') WHERE email NOT LIKE '%@staging.example.com';\""
echo -e "${GREEN}✅ Emails anonimizados${NC}"

echo -e "${YELLOW}🔒 Anonimizando teléfonos...${NC}"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"UPDATE users SET phone = CONCAT('+57300', LPAD(id, 7, '0')) WHERE phone IS NOT NULL;\" 2>/dev/null || echo 'No phone column'"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"UPDATE ext_chatbot_conversations SET phone = CONCAT('+57300', LPAD(id, 7, '0')) WHERE phone IS NOT NULL;\" 2>/dev/null || echo 'No conversations table'"
echo -e "${GREEN}✅ Teléfonos anonimizados${NC}"

echo -e "${YELLOW}🔒 Reseteando contraseñas a 'password'...${NC}"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"UPDATE users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';\""
echo -e "${GREEN}✅ Contraseñas reseteadas (password: 'password')${NC}"

echo -e "${YELLOW}🔒 Limpiando tokens de API...${NC}"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"UPDATE users SET api_token = NULL;\" 2>/dev/null || echo 'No api_token column'"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"DELETE FROM personal_access_tokens;\" 2>/dev/null || echo 'No tokens table'"
echo -e "${GREEN}✅ Tokens limpiados${NC}"

echo -e "${YELLOW}🔒 Anonimizando nombres...${NC}"
run_remote "mysql -u $DB_USER -p'$DB_PASSWORD' $DB_NAME -e \"UPDATE users SET name = CONCAT('Usuario ', id) WHERE name IS NOT NULL;\""
echo -e "${GREEN}✅ Nombres anonimizados${NC}"

echo ""
echo -e "${GREEN}🎉 Anonimización completada!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Resumen:${NC}"
echo "✅ Emails anonimizados (formato: user{id}@staging.example.com)"
echo "✅ Teléfonos anonimizados"
echo "✅ Contraseñas reseteadas a 'password'"
echo "✅ Tokens de API limpiados"
echo "✅ Nombres anonimizados"
echo ""
echo -e "${YELLOW}🔐 Credenciales de prueba:${NC}"
echo "Email: user1@staging.example.com"
echo "Password: password"
echo ""
echo -e "${BLUE}📝 Nota:${NC}"
echo "Puedes crear un usuario de prueba específico si lo necesitas."

