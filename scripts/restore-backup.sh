#!/bin/bash

# Script de restauración desde backup
# Uso: ./scripts/restore-backup.sh /backups/pre-deploy-YYYYMMDD-HHMMSS

set -e

if [ -z "$1" ]; then
    echo "❌ Error: Debes especificar el directorio de backup"
    echo "Uso: ./scripts/restore-backup.sh /backups/pre-deploy-YYYYMMDD-HHMMSS"
    exit 1
fi

BACKUP_DIR="$1"
PROJECT_ROOT="/var/www/tausepro9.4"  # Ajustar según tu configuración

if [ ! -d "$BACKUP_DIR" ]; then
    echo "❌ Error: El directorio de backup no existe: $BACKUP_DIR"
    exit 1
fi

echo "🔄 Iniciando restauración desde backup..."
echo "📁 Directorio de backup: ${BACKUP_DIR}"

# Confirmar restauración
read -p "⚠️  ¿Estás seguro de restaurar desde este backup? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Restauración cancelada"
    exit 1
fi

# Restaurar base de datos
if [ -f "${BACKUP_DIR}/database.sql.gz" ]; then
    echo "💾 Restaurando base de datos..."
    gunzip -c "${BACKUP_DIR}/database.sql.gz" | mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}"
    echo "✅ Base de datos restaurada"
fi

# Restaurar archivos
if [ -f "${BACKUP_DIR}/storage.tar.gz" ]; then
    echo "📦 Restaurando archivos..."
    cd "${PROJECT_ROOT}"
    tar -xzf "${BACKUP_DIR}/storage.tar.gz"
    echo "✅ Archivos restaurados"
fi

# Restaurar configuración (opcional, con confirmación)
read -p "¿Restaurar archivo .env? (yes/no): " restore_env
if [ "$restore_env" == "yes" ] && [ -f "${BACKUP_DIR}/.env.backup" ]; then
    cp "${BACKUP_DIR}/.env.backup" "${PROJECT_ROOT}/.env"
    echo "✅ Configuración restaurada"
fi

echo ""
echo "✅ Restauración completada exitosamente"
echo "📋 Información del backup restaurado:"
cat "${BACKUP_DIR}/backup_info.txt"

