#!/bin/bash

# Continuous Deployment Setup for Tause Pro
# Configures GitHub Actions and deployment automation

set -e

echo "🚀 Setting up Continuous Deployment for Tause Pro"
echo "================================================="

# Create deployment directory structure
mkdir -p .github/workflows
mkdir -p scripts/deployment

echo "📁 Creating GitHub Actions workflow..."

# Create main deployment workflow
cat > .github/workflows/deploy.yml << 'EOF'
name: Deploy Tause Pro

on:
  push:
    branches: [ main, production ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: tause_pro_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, mysql, gd, curl, zip
        coverage: none

    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache

    - name: Run Tests
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: tause_pro_test
        DB_USERNAME: root
        DB_PASSWORD: password
      run: |
        php artisan migrate
        php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main' || github.ref == 'refs/heads/production'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Deploy to Server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.PRIVATE_KEY }}
        script: |
          cd /var/www/magicai
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo systemctl reload nginx
          sudo systemctl restart php8.2-fpm
EOF

echo "✅ GitHub Actions workflow created"

# Create deployment script
cat > scripts/deployment/deploy.sh << 'EOF'
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
EOF

chmod +x scripts/deployment/deploy.sh

echo "✅ Deployment script created"

# Create environment-specific configs
cat > .env.production << 'EOF'
APP_NAME="Tause Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.tause.pro

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tause_pro
DB_USERNAME=tause_user
DB_PASSWORD=TausePro2025!

# Wompi Configuration
WOMPI_PUBLIC_KEY=
WOMPI_PRIVATE_KEY=
WOMPI_ENVIRONMENT=production
WOMPI_WEBHOOK_SECRET=

# Other payment gateways
STRIPE_KEY=
STRIPE_SECRET=
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
EOF

echo "✅ Production environment template created"

echo ""
echo "🎯 Next Steps for CI/CD:"
echo "1. Push code to GitHub repository"
echo "2. Add these secrets in GitHub repository settings:"
echo "   - HOST: app.tause.pro"
echo "   - USERNAME: ubuntu"
echo "   - PRIVATE_KEY: (content of magicai-tause-key.pem)"
echo "3. Configure webhook for automatic deployments"
echo ""
echo "📁 Files created:"
echo "   - .github/workflows/deploy.yml"
echo "   - scripts/deployment/deploy.sh"
echo "   - .env.production"