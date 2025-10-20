#!/bin/bash

echo "🔄 ROLLBACK - Sales Agent System"
echo "================================"
echo ""

# Configuración
REMOTE_HOST="34.207.248.220"
REMOTE_USER="ubuntu"
KEY_FILE="magicai-tause-key.pem"
REMOTE_PATH="/var/www/magicai"

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "📋 1. Listando backups disponibles..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
echo "📦 Backups disponibles:"
ls -la /var/www/backups/ | tail -5
ENDSSH

echo ""
echo "📋 2. ¿Cuál backup restaurar? (Presiona Enter para el más reciente)"
read -p "Backup folder name (o Enter para auto): " BACKUP_FOLDER

if [ -z "$BACKUP_FOLDER" ]; then
    BACKUP_FOLDER=$(ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" "ls -t /var/www/backups/ | head -1")
    echo "🔄 Usando backup más reciente: $BACKUP_FOLDER"
fi

echo ""
echo "📋 3. Confirmando rollback..."
echo "⚠️  Esto restaurará:"
echo "  - Todos los archivos de la aplicación"
echo "  - La base de datos"
echo "  - Eliminará cambios del Sales Agent"
echo ""
read -p "¿Continuar con el rollback? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "❌ Rollback cancelado"
    exit 0
fi

echo ""
echo "📋 4. Ejecutando rollback..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << ENDSSH
cd /var/www

echo "🛑 Deteniendo servicios..."
sudo systemctl stop nginx
sudo systemctl stop php8.3-fpm

echo "📦 Restaurando archivos..."
if [ -d "backups/$BACKUP_FOLDER/magicai" ]; then
    sudo rm -rf magicai_backup_$(date +%Y%m%d_%H%M%S)
    sudo mv magicai magicai_backup_$(date +%Y%m%d_%H%M%S)
    sudo cp -r backups/$BACKUP_FOLDER/magicai ./
    echo "✅ Archivos restaurados"
else
    echo "❌ Backup no encontrado: backups/$BACKUP_FOLDER/magicai"
    exit 1
fi

echo "🗄️ Restaurando base de datos..."
if [ -f "backups/$BACKUP_FOLDER/database_backup.sql" ]; then
    sudo mysql -u tause_user -p'TausePro2025!' tause_pro < backups/$BACKUP_FOLDER/database_backup.sql
    echo "✅ Base de datos restaurada"
else
    echo "⚠️  No se encontró backup de BD, continuando..."
fi

echo "🔧 Ajustando permisos..."
sudo chown -R www-data:www-data /var/www/magicai/
sudo chown -R www-data:www-data /var/www/magicai/storage/
sudo chown -R www-data:www-data /var/www/magicai/bootstrap/cache/

echo "🧹 Limpiando caché..."
cd magicai
sudo -u www-data php artisan optimize:clear

echo "🚀 Reiniciando servicios..."
sudo systemctl start php8.3-fpm
sudo systemctl start nginx

echo "✅ Rollback completado"
ENDSSH

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error durante el rollback${NC}"
    exit 1
fi

echo ""
echo "📋 5. Verificando rollback..."
ssh -i "$KEY_FILE" "$REMOTE_USER@$REMOTE_HOST" << 'ENDSSH'
cd /var/www/magicai

echo "🔍 Verificaciones post-rollback:"
echo "  - Sitio responde: $(curl -s -o /dev/null -w "%{http_code}" https://app.tause.pro/login)"
echo "  - Migraciones: $(php artisan migrate:status | grep -c "Ran")"
echo "  - Extensiones: $(php artisan tinker --execute="echo DB::table('extensions')->where('installed', 1)->count();" 2>/dev/null | tail -1)"
ENDSSH

echo ""
echo "================================"
echo -e "${GREEN}✅ ROLLBACK COMPLETADO${NC}"
echo ""
echo "🌐 Sitio restaurado: https://app.tause.pro"
echo "📦 Backup usado: $BACKUP_FOLDER"
echo ""


