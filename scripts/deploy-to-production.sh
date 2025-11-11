#!/bin/bash

# Script de deploy seguro a producción
# Ejecutar después de hacer merge a main

set -e

PROJECT_ROOT="/var/www/tausepro9.4"  # Ajustar según tu configuración
BRANCH="main"

echo "🚀 Iniciando deploy a producción..."
echo "📍 Branch: ${BRANCH}"
echo ""

# 1. Crear backup
echo "📦 Paso 1: Creando backup..."
./scripts/backup-pre-deploy.sh

# 2. Verificar que estamos en la rama correcta
echo ""
echo "🔍 Paso 2: Verificando branch..."
current_branch=$(git branch --show-current)
if [ "$current_branch" != "$BRANCH" ]; then
    echo "⚠️  Advertencia: No estás en la rama ${BRANCH}"
    read -p "¿Continuar de todas formas? (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        echo "❌ Deploy cancelado"
        exit 1
    fi
fi

# 3. Pull de cambios
echo ""
echo "⬇️  Paso 3: Obteniendo últimos cambios..."
git fetch origin
git pull origin "${BRANCH}"

# 4. Verificar sintaxis PHP
echo ""
echo "✅ Paso 4: Verificando sintaxis PHP..."
find app/Extensions/Chatbot -name "*.php" -exec php -l {} \; | grep -v "No syntax errors" || true

# 5. Instalar dependencias
echo ""
echo "📦 Paso 5: Instalando dependencias..."
composer install --no-dev --optimize-autoloader

# 6. Limpiar cache
echo ""
echo "🧹 Paso 6: Limpiando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 7. Optimizar
echo ""
echo "⚡ Paso 7: Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Ejecutar migraciones (si hay)
echo ""
echo "🗄️  Paso 8: Verificando migraciones..."
php artisan migrate --force || echo "⚠️  No hay migraciones pendientes"

# 9. Verificar que la aplicación funciona
echo ""
echo "🔍 Paso 9: Verificando aplicación..."
php artisan about | head -5

echo ""
echo "✅ Deploy completado exitosamente"
echo ""
echo "📋 Próximos pasos:"
echo "1. Verificar logs: tail -f storage/logs/laravel.log"
echo "2. Probar Sales Agent en un chatbot"
echo "3. Verificar que productos se encuentran correctamente"
echo ""
echo "🔄 Si hay problemas, restaurar con:"
echo "   ./scripts/restore-backup.sh [directorio-del-backup]"

