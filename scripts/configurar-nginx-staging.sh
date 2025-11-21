#!/bin/bash

# 🔧 Script para Configurar Nginx en Staging
# Ejecuta este script EN EL SERVIDOR STAGING (no localmente)

set -e

echo "🔧 Configurando Nginx para test.tause.pro"
echo "=========================================="

# Crear configuración
sudo tee /etc/nginx/sites-available/test.tause.pro > /dev/null << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name test.tause.pro;
    root /var/www/magicai-staging/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    # Excepción para chatbot frame: permitir embedding desde cualquier origen
    location ~ ^/chatbot/[^/]+/frame$ {
        # NO agregar X-Frame-Options aquí para permitir embedding cross-origin
        add_header X-Content-Type-Options "nosniff";
        add_header Content-Security-Policy "frame-ancestors *" always;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

echo "✅ Configuración creada"

# Activar sitio
if [ ! -L /etc/nginx/sites-enabled/test.tause.pro ]; then
    sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/
    echo "✅ Sitio activado"
else
    echo "ℹ️  Sitio ya estaba activado"
fi

# Verificar configuración
echo "🔍 Verificando configuración..."
if sudo nginx -t; then
    echo "✅ Configuración válida"
    
    # Recargar Nginx
    sudo systemctl reload nginx
    echo "✅ Nginx recargado"
else
    echo "❌ Error en configuración"
    exit 1
fi

# Verificar servicios
echo ""
echo "📊 Estado de servicios:"
sudo systemctl status nginx --no-pager | head -5
echo ""
sudo systemctl status php8.3-fpm --no-pager | head -5

echo ""
echo "✅ Configuración completada!"
echo "🌐 Prueba: curl http://test.tause.pro"



