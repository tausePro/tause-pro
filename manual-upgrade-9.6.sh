#!/bin/bash

# Manual Upgrade Script to 9.6.0
# Fecha: 22 de Octubre, 2025

echo "🚀 Iniciando actualización manual a 9.6.0..."
echo ""

# 1. Verificar que existe el archivo descargado
if [ ! -f "new-version9.50.zip" ]; then
    echo "❌ Error: No se encontró el archivo new-version9.50.zip"
    exit 1
fi

echo "✅ Archivo de actualización encontrado: new-version9.50.zip"
echo ""

# 2. Poner en modo mantenimiento
echo "🔒 Activando modo mantenimiento..."
php artisan down
echo ""

# 3. Hacer backup adicional (por seguridad)
echo "💾 Creando backup de seguridad..."
BACKUP_NAME="manual-backup-$(date +%Y%m%d_%H%M%S).zip"
zip -r "$BACKUP_NAME" . -x "*.git*" "vendor/*" "node_modules/*" "storage/logs/*" "*.zip" > /dev/null 2>&1
echo "✅ Backup creado: $BACKUP_NAME"
echo ""

# 4. Extraer nueva versión
echo "📦 Extrayendo nueva versión..."
unzip -o new-version9.50.zip -d temp-update > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo "❌ Error al extraer el archivo"
    php artisan up
    exit 1
fi

echo "✅ Archivos extraídos"
echo ""

# 5. Copiar archivos (excluyendo algunos directorios)
echo "📁 Copiando archivos nuevos..."
rsync -av --exclude='storage/app' --exclude='storage/logs' --exclude='.env' --exclude='vendor' temp-update/ ./ > /dev/null 2>&1
echo "✅ Archivos copiados"
echo ""

# 6. Limpiar directorio temporal
echo "🧹 Limpiando archivos temporales..."
rm -rf temp-update
echo ""

# 7. Instalar dependencias
echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader > /dev/null 2>&1
echo "✅ Dependencias instaladas"
echo ""

# 8. Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force
echo ""

# 9. Limpiar cache
echo "🧹 Limpiando cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo "✅ Cache limpiado"
echo ""

# 10. Optimizar
echo "⚡ Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Optimización completada"
echo ""

# 11. Salir de modo mantenimiento
echo "🔓 Desactivando modo mantenimiento..."
php artisan up
echo ""

# 12. Verificar versión
echo "🎉 Actualización completada!"
echo ""
echo "Versión actual:"
php artisan --version
echo ""
echo "✅ Todo listo!"
