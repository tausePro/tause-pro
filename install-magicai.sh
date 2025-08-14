#!/bin/bash

# MagicAI Installation Script for AWS EC2
# Ubuntu 22.04 LTS - t3.small instance

set -e

echo "🚀 Starting MagicAI installation on AWS..."

# Update system
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install required packages
echo "🔧 Installing LEMP stack and dependencies..."
apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-curl \
php8.2-json php8.2-zip php8.2-gd php8.2-mbstring php8.2-xml \
php8.2-bcmath php8.2-intl php8.2-redis php8.2-imagick php8.2-soap \
unzip curl wget git supervisor certbot python3-certbot-nginx

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Node.js 18
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Configure MySQL
echo "🗄️ Configuring MySQL..."
mysql_secure_installation <<EOF

y
StrongPassword123!
StrongPassword123!
y
y
y
y
EOF

# Create database and user
echo "📊 Creating database..."
mysql -u root -pStrongPassword123! <<EOF
CREATE DATABASE magicai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'magicai'@'localhost' IDENTIFIED BY 'MagicAI2024!';
GRANT ALL PRIVILEGES ON magicai.* TO 'magicai'@'localhost';
FLUSH PRIVILEGES;
EOF

# Configure PHP
echo "⚙️ Configuring PHP..."
sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.2/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini

# Enable PHP OPcache
echo "zend_extension=opcache.so
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1" >> /etc/php/8.2/fpm/php.ini

# Create web directory
echo "📁 Creating web directory..."
mkdir -p /var/www/magicai
chown -R www-data:www-data /var/www/magicai

# Configure Nginx
echo "🌐 Configuring Nginx..."
cat > /etc/nginx/sites-available/magicai <<EOF
server {
    listen 80;
    server_name _;
    root /var/www/magicai/public;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/magicai /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
nginx -t

# Restart services
echo "🔄 Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx
systemctl restart mysql

# Enable services on boot
systemctl enable nginx
systemctl enable php8.2-fpm
systemctl enable mysql

echo "✅ Base system installation completed!"
echo "📝 Next steps:"
echo "1. Upload MagicAI code to /var/www/magicai"
echo "2. Run the Laravel setup script"
echo "3. Configure SSL with Let's Encrypt"
echo ""
echo "🔐 Database credentials:"
echo "Database: magicai"
echo "Username: magicai"
echo "Password: MagicAI2024!"
echo "Root password: StrongPassword123!"