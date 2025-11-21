#!/bin/bash

# 🔧 Script para Aplicar Fix de Nginx para Chatbot Frame
# Este script actualiza la configuración de Nginx para permitir embedding del chatbot frame

set -e

PROJECT_PATH="/var/www/magicai"
NGINX_CONFIG="/etc/nginx/sites-available/default"

echo "🔧 Aplicando fix de Nginx para chatbot frame..."
echo "================================================"

# Verificar que estamos en el servidor correcto
if [ ! -d "$PROJECT_PATH" ]; then
    echo "❌ Error: Directorio del proyecto no encontrado: $PROJECT_PATH"
    exit 1
fi

# Backup de la configuración actual
if [ -f "$NGINX_CONFIG" ]; then
    sudo cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
    echo "✅ Backup creado: ${NGINX_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Verificar si ya existe la excepción para chatbot frame
if grep -q "location ~ \^/chatbot/\[^/\]\+/frame\$" "$NGINX_CONFIG" 2>/dev/null; then
    echo "ℹ️  La excepción para chatbot frame ya existe en la configuración"
else
    echo "📝 Agregando excepción para chatbot frame..."
    
    # Crear un archivo temporal con la nueva configuración
    TEMP_CONFIG=$(mktemp)
    
    # Leer la configuración actual y agregar la excepción antes de "location /"
    awk '
    /^[[:space:]]*location[[:space:]]+\/[[:space:]]*\{/ {
        print "    # Excepción para chatbot frame: permitir embedding desde cualquier origen"
        print "    location ~ ^/chatbot/[^/]+/frame$ {"
        print "        # NO agregar X-Frame-Options aquí para permitir embedding cross-origin"
        print "        add_header X-Content-Type-Options \"nosniff\";"
        print "        add_header Content-Security-Policy \"frame-ancestors *\" always;"
        print "        try_files $uri $uri/ /index.php?$query_string;"
        print "    }"
        print ""
    }
    { print }
    ' "$NGINX_CONFIG" > "$TEMP_CONFIG"
    
    # Aplicar la nueva configuración
    sudo mv "$TEMP_CONFIG" "$NGINX_CONFIG"
    echo "✅ Excepción agregada a la configuración de Nginx"
fi

# Verificar y actualizar PHP-FPM a 8.3 si es necesario
if grep -q "php8.2-fpm.sock" "$NGINX_CONFIG"; then
    echo "🔄 Actualizando PHP-FPM de 8.2 a 8.3..."
    sudo sed -i 's/php8\.2-fpm\.sock/php8.3-fpm.sock/g' "$NGINX_CONFIG"
    echo "✅ PHP-FPM actualizado a 8.3"
fi

# Verificar configuración de Nginx
echo "🔍 Verificando configuración de Nginx..."
if sudo nginx -t; then
    echo "✅ Configuración válida"
    
    # Recargar Nginx
    echo "🔄 Recargando Nginx..."
    sudo systemctl reload nginx
    echo "✅ Nginx recargado exitosamente"
else
    echo "❌ Error en la configuración de Nginx"
    echo "📋 Últimas líneas del error:"
    sudo nginx -t 2>&1 | tail -10
    exit 1
fi

echo ""
echo "✅ Fix aplicado exitosamente!"
echo ""
echo "📋 Verificación:"
echo "   - Excepción para /chatbot/*/frame agregada"
echo "   - X-Frame-Options NO se aplicará a la ruta del chatbot frame"
echo "   - Content-Security-Policy: frame-ancestors * configurado"
echo ""
echo "🧪 Prueba el chatbot frame:"
echo "   curl -I https://test.tause.pro/chatbot/63140a24-9552-4751-b19b-e58802adb312/frame | grep -i frame"

