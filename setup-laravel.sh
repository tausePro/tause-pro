#!/bin/bash

# MagicAI Laravel Setup Script
# Run this after uploading the MagicAI code

set -e

echo "🎨 Setting up MagicAI Laravel application..."

# Navigate to project directory
cd /var/www/magicai

# Set proper permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/magicai
chmod -R 755 /var/www/magicai
chmod -R 775 storage bootstrap/cache

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
sudo -u www-data composer install --optimize-autoloader --no-dev

# Create .env file
echo "⚙️ Creating .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate application key
echo "🔑 Generating application key..."
sudo -u www-data php artisan key:generate

# Configure .env file
echo "📝 Configuring environment variables..."
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=magicai/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=magicai/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=MagicAI2024!/' .env

# Set cache and session drivers
sed -i 's/CACHE_DRIVER=.*/CACHE_DRIVER=file/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/' .env

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
sudo -u www-data npm install

# Build assets
echo "🏗️ Building assets..."
sudo -u www-data npm run build

# Run database migrations
echo "🗄️ Running database migrations..."
sudo -u www-data php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
sudo -u www-data php artisan db:seed --force

# Clear and cache config
echo "🧹 Optimizing Laravel..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Create storage link
sudo -u www-data php artisan storage:link

# Set up queue worker with Supervisor
echo "⚡ Setting up queue worker..."
cat > /etc/supervisor/conf.d/magicai-worker.conf <<EOF
[program:magicai-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/magicai/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/magicai/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Start supervisor
supervisorctl reread
supervisorctl update
supervisorctl start magicai-worker:*

# Set up cron job
echo "⏰ Setting up cron job..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/magicai && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Final permissions check
chown -R www-data:www-data /var/www/magicai
chmod -R 755 /var/www/magicai
chmod -R 775 storage bootstrap/cache

echo "✅ MagicAI Laravel setup completed!"
echo "🌐 Your application should now be accessible via your server IP"
echo "📊 Admin panel: http://YOUR-IP/admin"
echo "🔐 Don't forget to:"
echo "1. Configure your domain in APP_URL"
echo "2. Set up SSL with Let's Encrypt"
echo "3. Configure your AI API keys"
echo "4. Set up email settings"