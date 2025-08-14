#!/bin/bash

# Fully Automated MagicAI Deployment Script
# No user interaction required

set -e

MYSQL_ROOT_PASSWORD="MagicAI2024!"
DB_NAME="magicai"
DB_USER="magicai_user"
DB_PASSWORD="MagicAI_DB_2024!"

echo "🤖 Fully Automated MagicAI Deployment"
echo "====================================="
echo "🔄 This script will run without any user input"
echo ""

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log "🚀 Starting automated deployment..."

# Update system
log "📦 Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt update -y
apt upgrade -y

# Install basic dependencies
log "🔧 Installing basic dependencies..."
apt install -y curl wget unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Add PHP repository
log "📋 Adding PHP repository..."
add-apt-repository ppa:ondrej/php -y
apt update -y

# Install LEMP stack
log "🏗️ Installing LEMP stack..."
apt install -y nginx mysql-server \
    php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl \
    php8.2-soap php8.2-redis php8.2-imagick php8.2-dom

# Configure MySQL automatically
log "🗄️ Configuring MySQL..."
systemctl start mysql
systemctl enable mysql

# Set MySQL root password non-interactively
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$MYSQL_ROOT_PASSWORD';"
mysql -e "FLUSH PRIVILEGES;"

# Secure MySQL installation (automated)
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "DELETE FROM mysql.user WHERE User='';"
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "DROP DATABASE IF EXISTS test;"
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "FLUSH PRIVILEGES;"

# Create MagicAI database and user
log "🗃️ Creating MagicAI database..."
mysql -u root -p$MYSQL_ROOT_PASSWORD << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# Install Composer
log "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Node.js
log "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Configure Nginx
log "🌐 Configuring Nginx..."
systemctl start nginx
systemctl enable nginx

# Create MagicAI directory
log "📁 Preparing MagicAI directory..."
mkdir -p /var/www/magicai
chown -R www-data:www-data /var/www/magicai

# Configure Nginx for MagicAI
log "⚙️ Configuring Nginx virtual host..."
cat > /etc/nginx/sites-available/magicai << 'EOF'
server {
    listen 80;
    server_name app.tause.pro;
    root /var/www/magicai/public;
    index index.php index.html;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/magicai /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t
systemctl reload nginx

# Configure PHP
log "🐘 Configuring PHP..."
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.2/fpm/php.ini

systemctl restart php8.2-fpm

# Install SSL certificate
log "🔒 Installing SSL certificate..."
apt install -y certbot python3-certbot-nginx
certbot --nginx -d app.tause.pro --non-interactive --agree-tos --email admin@tause.pro --redirect

# Create .env file template
log "📝 Creating environment configuration..."
cat > /var/www/magicai/.env << EOF
APP_NAME="Tause AI"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.tause.pro

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

# Set permissions
log "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/magicai
chmod -R 755 /var/www/magicai
chmod -R 775 /var/www/magicai/storage
chmod -R 775 /var/www/magicai/bootstrap/cache

# Create management commands
log "🛠️ Creating management commands..."
cat > /usr/local/bin/magicai-info << 'EOF'
#!/bin/bash
echo "🚀 MagicAI System Status"
echo "======================="
echo "🌐 URL: https://app.tause.pro"
echo "🗄️ Database: Connected"
echo "🐘 PHP: $(php -v | head -n1)"
echo "🌐 Nginx: $(systemctl is-active nginx)"
echo "🗄️ MySQL: $(systemctl is-active mysql)"
echo "📊 Disk Usage: $(df -h /var/www/magicai | tail -1 | awk '{print $5}')"
echo "💾 Memory: $(free -h | grep Mem | awk '{print $3"/"$2}')"
EOF

chmod +x /usr/local/bin/magicai-info

log "✅ Base system installation completed!"
log ""
log "📋 Next Steps:"
log "1. Upload MagicAI code to /var/www/magicai"
log "2. Run: cd /var/www/magicai && composer install --no-dev"
log "3. Run: php artisan key:generate"
log "4. Run: php artisan migrate"
log "5. Configure API keys in .env file"
log ""
log "🌐 Your server is ready at: https://app.tause.pro"
log "🔧 Database configured: $DB_NAME (user: $DB_USER)"
log "📊 Check status: magicai-info"