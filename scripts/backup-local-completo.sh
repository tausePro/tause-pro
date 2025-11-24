#!/bin/bash

# ============================================
# Script de Backup Completo para Desarrollo Local
# ============================================

set -e  # Salir si hay algún error

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🛡️  BACKUP COMPLETO - DESARROLLO LOCAL${NC}\n"

# Variables
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/local-backup-${TIMESTAMP}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$PROJECT_ROOT"

# Crear directorio de backup
mkdir -p "$BACKUP_DIR"
mkdir -p "$BACKUP_DIR/database"
mkdir -p "$BACKUP_DIR/files"
mkdir -p "$BACKUP_DIR/config"

echo -e "${YELLOW}📦 PASO 1/5: Backup de Base de Datos${NC}"

# Obtener credenciales de .env
if [ ! -f .env ]; then
    echo -e "${RED}❌ Archivo .env no encontrado${NC}"
    exit 1
fi

DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    echo -e "${RED}❌ No se pudieron obtener credenciales de BD${NC}"
    exit 1
fi

# Backup de base de datos
echo "Creando backup de BD: $DB_NAME"
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database/backup_${TIMESTAMP}.sql" 2>/dev/null || {
    echo -e "${YELLOW}⚠️  Intentando sin contraseña...${NC}"
    mysqldump -u "$DB_USER" "$DB_NAME" > "$BACKUP_DIR/database/backup_${TIMESTAMP}.sql" 2>/dev/null || {
        echo -e "${RED}❌ Error al crear backup de BD${NC}"
        exit 1
    }
}

# Comprimir backup de BD
gzip "$BACKUP_DIR/database/backup_${TIMESTAMP}.sql"
echo -e "${GREEN}✅ Backup de BD creado: $BACKUP_DIR/database/backup_${TIMESTAMP}.sql.gz${NC}"

echo -e "\n${YELLOW}📦 PASO 2/5: Backup de Archivos Críticos${NC}"

# Backup de archivos modificados recientemente
echo "Copiando archivos críticos..."

# Extensiones Chatbot (donde haremos cambios)
if [ -d "app/Extensions/Chatbot" ]; then
    mkdir -p "$BACKUP_DIR/files/app/Extensions/Chatbot"
    cp -r app/Extensions/Chatbot/* "$BACKUP_DIR/files/app/Extensions/Chatbot/" 2>/dev/null || true
    echo "✅ Backup de Chatbot extension"
fi

# Services relacionados
if [ -d "app/Services/Chatbot" ]; then
    mkdir -p "$BACKUP_DIR/files/app/Services/Chatbot"
    cp -r app/Services/Chatbot/* "$BACKUP_DIR/files/app/Services/Chatbot/" 2>/dev/null || true
    echo "✅ Backup de Chatbot Services"
fi

# Migraciones
if [ -d "database/migrations" ]; then
    mkdir -p "$BACKUP_DIR/files/database/migrations"
    cp -r database/migrations/* "$BACKUP_DIR/files/database/migrations/" 2>/dev/null || true
    echo "✅ Backup de Migraciones"
fi

# Configuraciones
cp .env "$BACKUP_DIR/config/.env.backup" 2>/dev/null || true
cp composer.json "$BACKUP_DIR/config/composer.json.backup" 2>/dev/null || true
cp composer.lock "$BACKUP_DIR/config/composer.lock.backup" 2>/dev/null || true
echo "✅ Backup de Configuraciones"

echo -e "\n${YELLOW}📦 PASO 3/5: Backup de Base de Datos de Extensiones${NC}"

# Backup específico de tablas de extensiones
TABLES=(
    "ext_chatbot_agents"
    "ext_chatbot_products"
    "ext_chatbots"
    "ext_chatbot_conversations"
    "ext_chatbot_histories"
    "ext_chatbot_customers"
)

for table in "${TABLES[@]}"; do
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" "$table" > "$BACKUP_DIR/database/${table}_${TIMESTAMP}.sql" 2>/dev/null || {
        mysqldump -u "$DB_USER" "$DB_NAME" "$table" > "$BACKUP_DIR/database/${table}_${TIMESTAMP}.sql" 2>/dev/null || true
    }
done

echo -e "${GREEN}✅ Backup de tablas de extensiones creado${NC}"

echo -e "\n${YELLOW}📦 PASO 4/5: Información del Sistema${NC}"

# Crear archivo con información del sistema
cat > "$BACKUP_DIR/info.txt" << EOF
BACKUP CREADO: $(date)
PROYECTO: tause Pro
VERSIÓN: $(cat version.txt 2>/dev/null || echo "N/A")
PHP VERSIÓN: $(php -v | head -1)
LARAVEL VERSIÓN: $(php artisan --version 2>/dev/null || echo "N/A")
GIT BRANCH: $(git branch --show-current 2>/dev/null || echo "N/A")
GIT COMMIT: $(git rev-parse HEAD 2>/dev/null || echo "N/A")
BACKUP DIR: $BACKUP_DIR
EOF

echo -e "${GREEN}✅ Información del sistema guardada${NC}"

echo -e "\n${YELLOW}📦 PASO 5/5: Verificación de Integridad${NC}"

# Verificar que los backups existen
if [ -f "$BACKUP_DIR/database/backup_${TIMESTAMP}.sql.gz" ]; then
    SIZE=$(du -h "$BACKUP_DIR/database/backup_${TIMESTAMP}.sql.gz" | cut -f1)
    echo -e "${GREEN}✅ Backup de BD verificado: $SIZE${NC}"
else
    echo -e "${RED}❌ Error: Backup de BD no encontrado${NC}"
    exit 1
fi

# ============================================
# RESUMEN
# ============================================
echo -e "\n${GREEN}✅ BACKUP COMPLETADO EXITOSAMENTE${NC}\n"
echo -e "${BLUE}📋 RESUMEN:${NC}"
echo -e "  📁 Directorio: $BACKUP_DIR"
echo -e "  💾 Base de Datos: $BACKUP_DIR/database/backup_${TIMESTAMP}.sql.gz"
echo -e "  📂 Archivos: $BACKUP_DIR/files/"
echo -e "  ⚙️  Config: $BACKUP_DIR/config/"
echo -e "  ℹ️  Info: $BACKUP_DIR/info.txt\n"

echo -e "${YELLOW}🔄 PARA RESTAURAR:${NC}"
echo -e "  # Restaurar BD:"
echo -e "  gunzip $BACKUP_DIR/database/backup_${TIMESTAMP}.sql.gz"
echo -e "  mysql -u $DB_USER -p $DB_NAME < $BACKUP_DIR/database/backup_${TIMESTAMP}.sql"
echo -e ""
echo -e "  # Restaurar archivos:"
echo -e "  cp -r $BACKUP_DIR/files/* ./\n"

echo -e "${GREEN}🎉 Backup listo para desarrollo seguro!${NC}\n"



