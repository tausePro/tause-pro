#!/bin/bash
set -e

STAGING_IP="3.220.198.180"
USER="ubuntu"
KEY="staging-tausepro-key.pem"

echo "🚀 Upgrading Staging Server to PHP 8.3..."

ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "
    # 1. Add repository
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt-get update

    # 2. Install PHP 8.3 and extensions
    echo '📦 Installing PHP 8.3...'
    sudo apt-get install -y php8.3 php8.3-cli php8.3-fpm \
        php8.3-mysql php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip \
        php8.3-bcmath php8.3-intl php8.3-gd php8.3-gmp php8.3-imagick

    # 3. Configure PHP
    echo '⚙️ Configuring PHP...'
    # Set as default
    sudo update-alternatives --set php /usr/bin/php8.3
    
    # Configure FPM
    sudo sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
    sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.3/fpm/php.ini
    sudo sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.3/fpm/php.ini
    sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini

    # 4. Update Nginx Config
    echo '🔧 Updating Nginx...'
    # Backup current config
    sudo cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.bak
    # Replace php8.2-fpm with php8.3-fpm in nginx config
    sudo sed -i 's/php8.2-fpm.sock/php8.3-fpm.sock/g' /etc/nginx/sites-available/default
    
    # 5. Restart Services
    echo '🔄 Restarting services...'
    sudo systemctl stop php8.2-fpm
    sudo systemctl disable php8.2-fpm
    sudo systemctl enable php8.3-fpm
    sudo systemctl restart php8.3-fpm
    sudo systemctl restart nginx

    # 6. Install Composer (ensure it's up to date)
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer

    echo '✅ PHP 8.3 Upgrade Complete!'
    php -v
"

