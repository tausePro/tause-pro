#!/bin/bash
set -e

STAGING_IP="3.220.198.180"
USER="ubuntu"
KEY="staging-tausepro-key.pem"
REMOTE_DIR="/var/www/magicai"

echo "🚀 Starting Staging Deployment to $STAGING_IP"

# 1. Build Assets
echo "🎨 Building assets locally..."
# Assuming npm install is already done or fast
npm install
npm run build

# 2. Sync Files
echo "📡 Syncing files to server..."
# Create temp directory on server
ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "mkdir -p ~/magicai_deploy"

# Rsync to temp directory (user ubuntu owns this)
rsync -avz -e "ssh -i $KEY -o StrictHostKeyChecking=no" \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/logs/*' \
    ./ $USER@$STAGING_IP:~/magicai_deploy/

# Move files to final destination using sudo
echo "🚚 Moving files to final destination..."
ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "
    sudo rsync -av ~/magicai_deploy/ $REMOTE_DIR/
    rm -rf ~/magicai_deploy
    # Fix permissions
    sudo chown -R www-data:www-data $REMOTE_DIR
    sudo chmod -R 775 $REMOTE_DIR/storage $REMOTE_DIR/bootstrap/cache
    # Ensure artisan is executable
    sudo chmod +x $REMOTE_DIR/artisan
"

# 3. Database Setup
echo "🗄️ Configuring Database..."
ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS magicai_staging; CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'password'; GRANT ALL ON magicai_staging.* TO 'magicai_staging'@'localhost'; FLUSH PRIVILEGES;\""

# 4. Environment Setup
echo "🔧 Configuring .env..."
ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "
    cd $REMOTE_DIR
    if [ ! -f .env ]; then
        sudo cp .env.example .env
        echo 'Created .env from example'
    fi
    # Fix permissions for .env so we can edit it
    sudo chown $USER:www-data .env
    sudo chmod 664 .env

    # Update .env values using sudo sed just in case, though chown should fix it
    sudo sed -i 's/APP_ENV=local/APP_ENV=staging/' .env
    sudo sed -i 's/APP_DEBUG=true/APP_DEBUG=true/' .env
    sudo sed -i 's|APP_URL=http://localhost|APP_URL=http://test.tause.pro|' .env
    sudo sed -i 's/DB_DATABASE=laravel/DB_DATABASE=magicai_staging/' .env
    sudo sed -i 's/DB_USERNAME=root/DB_USERNAME=magicai_staging/' .env
    sudo sed -i 's/DB_PASSWORD=/DB_PASSWORD=password/' .env
    
    # Set final permissions
    sudo chown www-data:www-data .env
"

# 5. Dependencies & Permissions
echo "📦 Installing dependencies & Fixing permissions..."
ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "
    cd $REMOTE_DIR
    
    # Create vendor dir if missing and take ownership to allow composer to write
    if [ ! -d vendor ]; then
        sudo mkdir vendor
    fi
    sudo chown -R $USER:www-data vendor
    sudo chmod -R 775 vendor
    
    # Also ensure we own the composer.lock file if it exists
    if [ -f composer.lock ]; then
        sudo chown $USER:www-data composer.lock
    fi

    # Fix storage permissions BEFORE running composer (scripts need to write logs)
    sudo chown -R $USER:www-data storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
    
    # Clear stale cache and logs that might cause permission or discovery issues
    rm -f bootstrap/cache/*.php
    rm -f storage/logs/*.log

    export COMPOSER_ALLOW_SUPERUSER=1
    # Install WITH dev dependencies to avoid "Class not found" for Debugbar in staging
    composer install --optimize-autoloader
    
    # Final permissions fix
    sudo chown -R www-data:www-data $REMOTE_DIR
    sudo chmod -R 775 storage bootstrap/cache
"

# 6. Migrations & Optimization
echo "🔄 Running migrations and optimizing..."
ssh -i $KEY -o StrictHostKeyChecking=no $USER@$STAGING_IP "
    cd $REMOTE_DIR
    php artisan key:generate --force # Ensure key exists if new .env
    php artisan migrate --force
    php artisan storage:link
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
"

echo "✅ Deployment Complete! Visit http://test.tause.pro"

