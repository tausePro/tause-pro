#!/bin/bash

# 🔧 Script para Aplicar Fix de Nginx para Chatbot Frame
# Este script actualiza la configuración de Nginx para permitir embedding del chatbot frame

# NO usar set -e para evitar que falle el deploy si hay problemas menores
set +e

PROJECT_PATH="/var/www/magicai"
NGINX_CONFIG="/etc/nginx/sites-available/default"

echo "🔧 Aplicando fix de Nginx para chatbot frame..."
echo "================================================"

# Verificar que estamos en el servidor correcto
if [ ! -d "$PROJECT_PATH" ]; then
    echo "⚠️ Warning: Directorio del proyecto no encontrado: $PROJECT_PATH"
    echo "⚠️ Continuando sin aplicar fix de Nginx..."
    exit 0  # Salir con éxito para no romper el deploy
fi

# Verificar que el archivo de configuración existe
if [ ! -f "$NGINX_CONFIG" ]; then
    echo "⚠️ Warning: Archivo de configuración de Nginx no encontrado: $NGINX_CONFIG"
    echo "⚠️ Continuando sin aplicar fix de Nginx..."
    exit 0  # Salir con éxito para no romper el deploy
fi

# Backup de la configuración actual
BACKUP_FILE="${NGINX_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
if sudo cp "$NGINX_CONFIG" "$BACKUP_FILE" 2>/dev/null; then
    echo "✅ Backup creado: $BACKUP_FILE"
else
    echo "⚠️ Warning: No se pudo crear backup de Nginx config (puede ser un problema de permisos)"
    echo "⚠️ Continuando sin aplicar fix de Nginx..."
    exit 0  # Salir con éxito para no romper el deploy
fi

# Verificar si ya existe la excepción para chatbot frame
if grep -q "location ~ \^/chatbot/\[^/\]\+/frame\$" "$NGINX_CONFIG" 2>/dev/null; then
    echo "ℹ️  La excepción para chatbot frame ya existe en la configuración"
else
    echo "📝 Agregando excepción para chatbot frame..."
    
    # Crear un archivo temporal con la nueva configuración
    TEMP_CONFIG=$(mktemp)
    if [ -z "$TEMP_CONFIG" ] || [ ! -f "$TEMP_CONFIG" ]; then
        echo "⚠️ Warning: No se pudo crear archivo temporal"
        echo "⚠️ Continuando sin aplicar fix de Nginx..."
        exit 0
    fi
    
    # Leer la configuración actual y agregar la excepción antes de "location /"
    if ! awk '
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
    ' "$NGINX_CONFIG" > "$TEMP_CONFIG" 2>/dev/null; then
        echo "⚠️ Warning: Error al procesar configuración de Nginx con awk"
        rm -f "$TEMP_CONFIG"
        echo "⚠️ Continuando sin aplicar fix de Nginx..."
        exit 0
    fi
    
    # Verificar que el archivo temporal tiene contenido
    if [ ! -s "$TEMP_CONFIG" ]; then
        echo "⚠️ Warning: El archivo temporal está vacío"
        rm -f "$TEMP_CONFIG"
        echo "⚠️ Continuando sin aplicar fix de Nginx..."
        exit 0
    fi
    
    # Aplicar la nueva configuración
    if sudo mv "$TEMP_CONFIG" "$NGINX_CONFIG" 2>/dev/null; then
        echo "✅ Excepción agregada a la configuración de Nginx"
    else
        echo "⚠️ Warning: No se pudo aplicar la nueva configuración de Nginx (puede ser un problema de permisos)"
        rm -f "$TEMP_CONFIG"
        echo "⚠️ Continuando sin aplicar fix de Nginx..."
        exit 0
    fi
fi

# Verificar y actualizar PHP-FPM a 8.3 si es necesario
if grep -q "php8.2-fpm.sock" "$NGINX_CONFIG"; then
    echo "🔄 Actualizando PHP-FPM de 8.2 a 8.3..."
    sudo sed -i 's/php8\.2-fpm\.sock/php8.3-fpm.sock/g' "$NGINX_CONFIG"
    echo "✅ PHP-FPM actualizado a 8.3"
fi

# Verificar configuración de Nginx
echo "🔍 Verificando configuración de Nginx..."
NGINX_TEST_OUTPUT=$(sudo nginx -t 2>&1)
NGINX_TEST_EXIT_CODE=$?

if [ $NGINX_TEST_EXIT_CODE -eq 0 ]; then
    echo "✅ Configuración válida"
    
    # Recargar Nginx
    echo "🔄 Recargando Nginx..."
    if sudo systemctl reload nginx 2>/dev/null; then
        echo "✅ Nginx recargado exitosamente"
    else
        echo "⚠️ Warning: No se pudo recargar Nginx (puede requerir reinicio manual)"
        echo "⚠️ La configuración es válida pero no se aplicó automáticamente"
    fi
else
    echo "⚠️ Warning: Error en la configuración de Nginx"
    echo "📋 Últimas líneas del error:"
    echo "$NGINX_TEST_OUTPUT" | tail -10
    
    # Restaurar backup si existe
    if [ -f "$BACKUP_FILE" ]; then
        echo "🔄 Restaurando configuración anterior desde backup..."
        if sudo mv "$BACKUP_FILE" "$NGINX_CONFIG" 2>/dev/null; then
            echo "✅ Configuración restaurada desde backup"
        else
            echo "⚠️ Warning: No se pudo restaurar el backup"
        fi
    fi
    
    echo "⚠️ Continuando sin aplicar fix de Nginx (el sitio debería seguir funcionando)"
    exit 0  # Salir con éxito para no romper el deploy
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

