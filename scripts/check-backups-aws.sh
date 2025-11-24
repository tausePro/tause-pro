#!/bin/bash

# Script para revisar backups automáticos en AWS
# Ejecutar DESPUÉS de conectarse a AWS

set -e

echo "🔍 Revisando backups automáticos en AWS..."
echo ""

# 1. Verificar backups de RDS (si usas RDS)
echo "📊 1. Backups de RDS (si aplica):"
if command -v aws &> /dev/null; then
    echo "Buscando snapshots de RDS..."
    aws rds describe-db-snapshots \
        --query 'DBSnapshots[*].[DBSnapshotIdentifier,Status,SnapshotCreateTime]' \
        --output table 2>/dev/null | head -20 || echo "No se encontraron snapshots de RDS"
else
    echo "AWS CLI no disponible"
fi
echo ""

# 2. Verificar backups locales comunes
echo "📁 2. Backups locales comunes:"

BACKUP_DIRS=(
    "/backups"
    "/var/backups"
    "/home/ubuntu/backups"
    "/opt/backups"
    "/var/www/backups"
)

for dir in "${BACKUP_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✅ Encontrado: $dir"
        echo "   Últimos backups de BD:"
        find "$dir" -name "*.sql*" -type f -mtime -2 -exec ls -lh {} \; 2>/dev/null | head -5 || echo "   No hay backups recientes"
        echo ""
    fi
done

# 3. Verificar cron jobs de backup
echo "⏰ 3. Cron jobs de backup:"
echo "Crontab del usuario actual:"
crontab -l 2>/dev/null | grep -i backup || echo "No hay backups en crontab del usuario"
echo ""

echo "Cron jobs del sistema:"
if [ -d "/etc/cron.daily" ]; then
    grep -r "backup\|mysql\|dump" /etc/cron.daily/ 2>/dev/null | head -5 || echo "No hay scripts de backup diarios"
fi
echo ""

# 4. Verificar último backup de BD
echo "📅 4. Último backup de base de datos encontrado:"
LAST_BACKUP=$(find /backups /var/backups /home/ubuntu/backups /opt/backups /var/www/backups \
    -name "*.sql*" -type f -mtime -1 2>/dev/null | head -1)

if [ ! -z "$LAST_BACKUP" ]; then
    echo "✅ Último backup: $LAST_BACKUP"
    ls -lh "$LAST_BACKUP"
    echo "Fecha: $(stat -c %y "$LAST_BACKUP" 2>/dev/null || stat -f "%Sm" "$LAST_BACKUP" 2>/dev/null)"
else
    echo "⚠️  No se encontró backup reciente (últimas 24 horas)"
    echo "Buscando backups más antiguos..."
    find /backups /var/backups /home/ubuntu/backups /opt/backups /var/www/backups \
        -name "*.sql*" -type f -mtime -7 2>/dev/null | head -3 || echo "No se encontraron backups"
fi
echo ""

# 5. Verificar espacio disponible
echo "💾 5. Espacio disponible:"
df -h / | tail -1
echo ""

# 6. Resumen
echo "=========================================="
echo "📋 RESUMEN:"
echo "=========================================="
if [ ! -z "$LAST_BACKUP" ]; then
    echo "✅ Backup encontrado: $LAST_BACKUP"
    echo "✅ Fecha: $(stat -c %y "$LAST_BACKUP" 2>/dev/null || stat -f "%Sm" "$LAST_BACKUP" 2>/dev/null)"
    echo ""
    echo "✅ Puedes proceder con el deploy (hay backup reciente)"
else
    echo "⚠️  No se encontró backup reciente"
    echo ""
    echo "💡 Recomendación: Crear backup manual antes de deploy"
    echo "   mysqldump -u [user] -p [database] > /backups/manual-backup-$(date +%Y%m%d-%H%M%S).sql"
fi

