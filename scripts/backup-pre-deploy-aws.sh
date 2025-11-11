#!/bin/bash

# Script de backup pre-deploy - Ejecutar DIRECTAMENTE en servidor AWS
# Este script crea backup completo antes de cualquier cambio

set -e

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_BASE_DIR="/backups"
BACKUP_DIR="${BACKUP_BASE_DIR}/pre-deploy-${TIMESTAMP}"
# AJUSTAR ESTA VARIABLE según tu configuración real en AWS
PROJECT_ROOT="/var/www/tausepro9.4"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Iniciando backup pre-deploy...${NC}"
echo -e "${YELLOW}📁 Directorio de backup: ${BACKUP_DIR}${NC}"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -d "$PROJECT_ROOT" ]; then
    echo -e "${RED}❌ Error: Directorio del proyecto no encontrado: ${PROJECT_ROOT}${NC}"
    echo "Por favor, ajusta la variable PROJECT_ROOT en el script"
    exit 1
fi

cd "$PROJECT_ROOT"

# Crear directorio de backup
mkdir -p "${BACKUP_DIR}"

# 1. Backup de base de datos
echo -e "${GREEN}💾 Paso 1/5: Haciendo backup de base de datos...${NC}"
if [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ] || [ -z "$DB_DATABASE" ]; then
    echo -e "${YELLOW}⚠️  Variables de BD no encontradas, intentando desde .env...${NC}"
    source .env 2>/dev/null || true
fi

if [ ! -z "$DB_USERNAME" ] && [ ! -z "$DB_DATABASE" ]; then
    mysqldump -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${BACKUP_DIR}/database.sql" 2>/dev/null || {
        echo -e "${YELLOW}⚠️  No se pudo hacer backup de BD (puede requerir credenciales)${NC}"
        echo "Ejecuta manualmente: mysqldump -u [user] -p [database] > ${BACKUP_DIR}/database.sql"
    }
    gzip "${BACKUP_DIR}/database.sql" 2>/dev/null || true
    echo -e "${GREEN}✅ Backup de base de datos completado${NC}"
else
    echo -e "${YELLOW}⚠️  No se pudo hacer backup de BD (variables no configuradas)${NC}"
fi

# 2. Backup de archivos críticos (storage)
echo -e "${GREEN}📦 Paso 2/5: Haciendo backup de archivos críticos...${NC}"
if [ -d "storage" ]; then
    tar -czf "${BACKUP_DIR}/storage.tar.gz" storage/app storage/logs 2>/dev/null || {
        echo -e "${YELLOW}⚠️  Error al hacer backup de storage${NC}"
    }
    echo -e "${GREEN}✅ Backup de storage completado${NC}"
else
    echo -e "${YELLOW}⚠️  Directorio storage no encontrado${NC}"
fi

# 3. Backup de configuración
echo -e "${GREEN}⚙️  Paso 3/5: Haciendo backup de configuración...${NC}"
if [ -f ".env" ]; then
    cp .env "${BACKUP_DIR}/.env.backup"
    echo -e "${GREEN}✅ Backup de configuración completado${NC}"
else
    echo -e "${YELLOW}⚠️  Archivo .env no encontrado${NC}"
fi

# 4. Backup de código (referencia del commit actual)
echo -e "${GREEN}📝 Paso 4/5: Guardando referencia del código actual...${NC}"
if [ -d ".git" ]; then
    git rev-parse HEAD > "${BACKUP_DIR}/commit_hash.txt" 2>/dev/null || echo "N/A" > "${BACKUP_DIR}/commit_hash.txt"
    git branch --show-current > "${BACKUP_DIR}/branch.txt" 2>/dev/null || echo "N/A" > "${BACKUP_DIR}/branch.txt"
    git log -1 --pretty=format:"%H|%an|%ae|%ad|%s" > "${BACKUP_DIR}/last_commit.txt" 2>/dev/null || echo "N/A" > "${BACKUP_DIR}/last_commit.txt"
    
    # Guardar diff de cambios recientes
    git diff HEAD~1 > "${BACKUP_DIR}/changes.patch" 2>/dev/null || echo "No changes" > "${BACKUP_DIR}/changes.patch"
    
    echo -e "${GREEN}✅ Referencia de código guardada${NC}"
else
    echo -e "${YELLOW}⚠️  No es un repositorio git${NC}"
fi

# 5. Crear archivo de información del backup
echo -e "${GREEN}📋 Paso 5/5: Creando información del backup...${NC}"
cat > "${BACKUP_DIR}/backup_info.txt" << EOF
========================================
BACKUP PRE-DEPLOY
========================================
Fecha: $(date)
Timestamp: ${TIMESTAMP}
Servidor: $(hostname)
Usuario: $(whoami)
Directorio proyecto: ${PROJECT_ROOT}

COMMIT ACTUAL:
$(cat "${BACKUP_DIR}/commit_hash.txt" 2>/dev/null || echo "N/A")

BRANCH:
$(cat "${BACKUP_DIR}/branch.txt" 2>/dev/null || echo "N/A")

ÚLTIMO COMMIT:
$(cat "${BACKUP_DIR}/last_commit.txt" 2>/dev/null || echo "N/A")

ARCHIVOS EN BACKUP:
- database.sql.gz (si existe)
- storage.tar.gz (si existe)
- .env.backup (si existe)
- commit_hash.txt
- branch.txt
- last_commit.txt
- changes.patch

ARCHIVOS MODIFICADOS RECIENTEMENTE:
$(git diff --name-only HEAD~1 2>/dev/null | head -20 || echo "N/A")

TAMAÑO DEL BACKUP:
$(du -sh "${BACKUP_DIR}" | cut -f1)

========================================
PARA RESTAURAR:
./scripts/restore-backup.sh ${BACKUP_DIR}
========================================
EOF

echo ""
echo -e "${GREEN}✅ Backup completado exitosamente${NC}"
echo -e "${GREEN}📍 Ubicación: ${BACKUP_DIR}${NC}"
echo ""
echo -e "${YELLOW}📊 Resumen:${NC}"
echo "  - Base de datos: $(ls -lh "${BACKUP_DIR}/database.sql.gz" 2>/dev/null | awk '{print $5}' || echo 'No disponible')"
echo "  - Storage: $(ls -lh "${BACKUP_DIR}/storage.tar.gz" 2>/dev/null | awk '{print $5}' || echo 'No disponible')"
echo "  - Configuración: $(ls -lh "${BACKUP_DIR}/.env.backup" 2>/dev/null | awk '{print $5}' || echo 'No disponible')"
echo "  - Tamaño total: $(du -sh "${BACKUP_DIR}" | cut -f1)"
echo ""
echo -e "${YELLOW}📋 Información guardada en: ${BACKUP_DIR}/backup_info.txt${NC}"

