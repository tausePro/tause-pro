#!/bin/bash

# Upgrade to 9.6.0 Script
# Fecha: 22 de Octubre, 2025

echo "🚀 Actualizando de 8.5.0 a 9.6.0..."
echo ""

# 1. Verificar archivo
if [ ! -f "new-version9.60.zip" ]; then
    echo "❌ Error: No se encontró new-version9.60.zip"
    echo "Descargando desde el wizard..."
    exit 1
fi

echo "✅ Archivo encontrado: new-version9.60.zip"
echo ""

# 2. Modo mantenimiento
echo "🔒 Activando modo mantenimiento..."
php artisan down --retry=60
echo ""

# 3. Backup
echo "💾 Creando backup..."
BACKUP_NAME="backup-pre-9.6-$(date +%Y%m%d_%H%M%S).zip"
zip -r "$BACKUP_NAME" . -x "*.git*" "vendor/*" "node_modules/*" "storage/logs/*" "*.zip" > /dev/null 2>&1
echo "✅ Backup: $BACKUP_NAME"
echo ""

# 4. Extraer
echo "📦 Extrayendo versión 9.6.0..."
unzip -o new-version9.60.zip -d temp-update > /dev/null 2>&1
echo "✅ Extraído"
echo ""

# 5. Copiar archivos
echo "📁 Copiando archivos..."
rsync -av --exclude='storage/app' --exclude='storage/logs' --exclude='.env' --exclude='vendor' temp-update/ ./ > /dev/null 2>&1
echo "✅ Archivos copiados"
echo ""

# 6. Limpiar
echo "🧹 Limpiando temporales..."
rm -rf temp-update
echo ""

# 7. Composer
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader > /dev/null 2>&1
echo "✅ Dependencias instaladas"
echo ""

# 8. Migraciones
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force
echo ""

# 9. Actualizar version.txt
echo "📝 Actualizando version.txt..."
echo "9.60" > version.txt
echo "✅ Versión actualizada a 9.60"
echo ""

# 10. Cache
echo "🧹 Limpiando cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo "✅ Cache limpiado"
echo ""

# 11. Optimizar
echo "⚡ Optimizando..."
php artisan config:cache
php artisan route:cache
echo "✅ Optimizado"
echo ""

# 12. Salir de mantenimiento
echo "🔓 Desactivando modo mantenimiento..."
php artisan up
echo ""

echo "🎉 ¡Actualización a 9.6.0 completada!"
echo ""
echo "Versión actual:"
cat version.txt
echo ""
echo "✅ Todo listo!"
