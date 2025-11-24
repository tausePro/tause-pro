#!/bin/bash

# Script de backup antes de deploy
# Ejecutar manualmente antes de cada deploy a producción

set -e

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/backups/pre-deploy-${TIMESTAMP}"
PROJECT_ROOT="/var/www/tausepro9.4"  # Ajustar según tu configuración

echo "🚀 Iniciando backup pre-deploy..."
echo "📁 Directorio de backup: ${BACKUP_DIR}"

# Crear directorio de backup
mkdir -p "${BACKUP_DIR}"

# Backup de base de datos
echo "💾 Haciendo backup de base de datos..."
mysqldump -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" > "${BACKUP_DIR}/database.sql"
gzip "${BACKUP_DIR}/database.sql"
echo "✅ Backup de base de datos completado"

# Backup de archivos críticos
echo "📦 Haciendo backup de archivos críticos..."
cd "${PROJECT_ROOT}"
tar -czf "${BACKUP_DIR}/storage.tar.gz" storage/app storage/logs
echo "✅ Backup de storage completado"

# Backup de configuración
echo "⚙️  Haciendo backup de configuración..."
cp .env "${BACKUP_DIR}/.env.backup"
echo "✅ Backup de configuración completado"

# Backup de código (último commit)
echo "📝 Guardando referencia del commit actual..."
git rev-parse HEAD > "${BACKUP_DIR}/commit_hash.txt"
git diff HEAD~1 > "${BACKUP_DIR}/changes.patch" || true
echo "✅ Referencia de commit guardada"

# Crear archivo de información del backup
cat > "${BACKUP_DIR}/backup_info.txt" << EOF
Backup creado: $(date)
Commit: $(git rev-parse HEAD)
Branch: $(git branch --show-current)
Usuario: $(whoami)
Archivos modificados:
$(git diff --name-only HEAD~1 | head -20)
EOF

echo ""
echo "✅ Backup completado exitosamente"
echo "📍 Ubicación: ${BACKUP_DIR}"
echo ""
echo "Para restaurar este backup:"
echo "  ./scripts/restore-backup.sh ${BACKUP_DIR}"

