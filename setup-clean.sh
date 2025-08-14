#!/bin/bash

# Script de configuración limpio para MagicAI
set -e

echo "🔧 Configurando MySQL..."
# Configurar MySQL sin contraseña inicial
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'StrongPassword123';" || true
sudo mysql -u root -pStrongPassword123 -e "CREATE DATABASE IF NOT EXISTS magicai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -pStrongPassword123 -e "CREATE USER IF NOT EXISTS 'magicai'@'localhost' IDENTIFIED BY 'MagicAI2024';"
sudo mysql -u root -pStrongPassword123 -e "GRANT ALL PRIVILEGES ON magicai.* TO 'magicai'@'localhost';"
sudo mysql -u root -pStrongPassword123 -e "FLUSH PRIVILEGES;"

echo "⚙️ Configurando PHP..."
sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini

echo "📁 Creando directorio web..."
sudo mkdir -p /var/www/magicai
sudo chown -R www-data:www-data /var/www/magicai

echo "🌐 Configurando Nginx..."
sudo tee /etc/nginx/sites-available/magicai > /dev/null << 'NGINX_EOF'
server {
    listen 80;
    server_name _;
    root /var/www/magicai/public;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
NGINX_EOF

echo "🔗 Habilitando sitio..."
sudo ln -sf /etc/nginx/sites-available/magicai /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

echo "✅ Probando configuración..."
sudo nginx -t

echo "🔄 Reiniciando servicios..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql

echo "🎯 Habilitando servicios en arranque..."
sudo systemctl enable nginx php8.3-fpm mysql

echo "✅ Configuración completada!"
echo "🔐 Credenciales de base de datos:"
echo "  Database: magicai"
echo "  Username: magicai"
echo "  Password: MagicAI2024"
echo "  Root password: StrongPassword123"
