#!/bin/bash

echo "🔧 SOLUCIONANDO ERROR 504 - AUMENTANDO TIMEOUTS"
echo "================================================"
echo ""

# 1. Cambiar APP_ENV a local
echo "1️⃣ Configurando entorno para desarrollo..."
if grep -q "APP_ENV=production" .env; then
    sed -i '' 's/APP_ENV=production/APP_ENV=local/' .env
    echo "   ✅ APP_ENV cambiado a local"
else
    echo "   ℹ️  APP_ENV ya está en local o development"
fi

if grep -q "APP_DEBUG=false" .env; then
    sed -i '' 's/APP_DEBUG=false/APP_DEBUG=true/' .env
    echo "   ✅ APP_DEBUG activado"
else
    echo "   ℹ️  APP_DEBUG ya está activado"
fi

# 2. Verificar PHP-FPM de Valet
echo ""
echo "2️⃣ Configurando PHP-FPM para Valet..."

VALET_PHP_VERSION=$(php -v | head -1 | awk '{print $2}' | cut -d. -f1,2)
VALET_PHP_FPM_CONF="$HOME/.config/valet/php-fpm.d/www.conf"

if [ -f "$VALET_PHP_FPM_CONF" ]; then
    # Backup
    cp "$VALET_PHP_FPM_CONF" "$VALET_PHP_FPM_CONF.backup-$(date +%Y%m%d-%H%M%S)"
    
    # Aumentar timeouts
    if ! grep -q "request_terminate_timeout" "$VALET_PHP_FPM_CONF"; then
        echo "request_terminate_timeout = 300" >> "$VALET_PHP_FPM_CONF"
        echo "   ✅ Timeout de PHP-FPM aumentado a 300s"
    else
        echo "   ℹ️  Timeout ya configurado"
    fi
else
    echo "   ⚠️  No se encontró configuración de Valet PHP-FPM"
    echo "   Intentando con configuración del sistema..."
fi

# 3. Crear php.ini personalizado
echo ""
echo "3️⃣ Creando configuración PHP optimizada..."

cat > php-local-dev.ini << 'PHPINI'
; Configuración PHP para desarrollo local
max_execution_time = 300
max_input_time = 300
memory_limit = 1024M
post_max_size = 100M
upload_max_filesize = 100M
default_socket_timeout = 300
PHPINI

echo "   ✅ Archivo php-local-dev.ini creado"

# 4. Reiniciar servicios de Valet
echo ""
echo "4️⃣ Reiniciando servicios..."

if command -v valet &> /dev/null; then
    echo "   🔄 Reiniciando Valet..."
    valet restart 2>/dev/null
    echo "   ✅ Valet reiniciado"
else
    echo "   ⚠️  Valet no encontrado, reinicia PHP-FPM manualmente"
fi

# 5. Limpiar caches
echo ""
echo "5️⃣ Limpiando caches..."
php artisan cache:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
echo "   ✅ Caches limpiados"

# 6. Verificar configuración
echo ""
echo "6️⃣ Verificación de configuración:"
echo "   ----------------------------------"
php -i | grep -E "max_execution_time|memory_limit|max_input_time" | grep -v "ORIG" | sed 's/^/   /'

echo ""
echo "================================================"
echo "✅ CONFIGURACIÓN COMPLETADA"
echo "================================================"
echo ""
echo "📋 SIGUIENTE:"
echo "   1. Recarga la página del chatbot"
echo "   2. Si persiste el 504:"
echo "      - Usa: php artisan serve --host=0.0.0.0 --port=8001"
echo "      - Accede a: http://localhost:8001"
echo ""
echo "🔧 Para usar la configuración PHP personalizada:"
echo "   php -c php-local-dev.ini artisan serve --host=0.0.0.0 --port=8001"
echo ""




