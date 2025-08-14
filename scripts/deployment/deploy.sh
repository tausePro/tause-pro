#!/bin/bash

# Deployment script for Tause Pro
set -e

APP_DIR="/var/www/magicai"
BACKUP_DIR="/var/backups/tause-pro"

echo "🚀 Starting Tause Pro deployment..."

# Create backup
echo "📦 Creating backup..."
mkdir -p $BACKUP_DIR
tar -czf $BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz -C $APP_DIR .

# Pull latest code
echo "📥 Pulling latest code..."
cd $APP_DIR
git pull origin main

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Clear and cache config
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache

# Restart services
echo "🔄 Restarting services..."
systemctl reload nginx
systemctl restart php8.2-fpm

echo "✅ Deployment completed successfully!"
