#!/bin/bash

# MagicAI Complete Deployment Script
# Master script that runs all installation steps

set -e

echo "🚀 MagicAI Complete Deployment Starting..."
echo "=========================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run as root (use sudo)"
    exit 1
fi

# Make all scripts executable
chmod +x *.sh

echo "📋 Deployment Steps:"
echo "1. Install base system (LEMP stack)"
echo "2. Setup Laravel application"
echo "3. Configure SSL (optional)"
echo "4. Configure AI APIs"
echo "5. Setup monitoring"
echo ""

read -p "🤔 Do you want to proceed with full installation? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Installation cancelled"
    exit 1
fi

# Step 1: Install base system
echo ""
echo "🔧 Step 1: Installing base system..."
./install-magicai.sh

echo ""
echo "✅ Base system installed successfully!"
echo ""
echo "📁 Next, you need to upload your MagicAI code to /var/www/magicai"
echo "You can use one of these methods:"
echo ""
echo "Method 1 - Git Clone (if you have a repository):"
echo "  cd /var/www"
echo "  git clone YOUR_REPO_URL magicai"
echo ""
echo "Method 2 - Upload ZIP file:"
echo "  # Upload your MagicAI.zip to the server"
echo "  cd /var/www"
echo "  unzip MagicAI.zip"
echo "  mv MagicAI-folder magicai"
echo ""
echo "Method 3 - SCP/SFTP:"
echo "  scp -r local-magicai-folder ubuntu@YOUR-IP:/var/www/magicai"
echo ""

read -p "📤 Have you uploaded the MagicAI code? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "⏸️ Please upload the code and then run: ./setup-laravel.sh"
    exit 0
fi

# Check if code exists
if [ ! -d "/var/www/magicai" ] || [ ! -f "/var/www/magicai/composer.json" ]; then
    echo "❌ MagicAI code not found in /var/www/magicai"
    echo "Please upload the code and run: ./setup-laravel.sh"
    exit 1
fi

# Step 2: Setup Laravel
echo ""
echo "🎨 Step 2: Setting up Laravel application..."
./setup-laravel.sh

# Step 3: SSL Setup for app.tause.pro
echo ""
read -p "🔒 Do you want to setup SSL for app.tause.pro? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    DOMAIN="app.tause.pro"
    echo "🌐 Setting up SSL for: $DOMAIN"
    ./setup-ssl.sh $DOMAIN
fi

# Step 4: Configure APIs
echo ""
read -p "🤖 Do you want to configure AI APIs now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    ./configure-ai-apis.sh
fi

# Step 5: Setup monitoring
echo ""
echo "📊 Step 5: Setting up monitoring..."
./setup-monitoring.sh

# Final steps
echo ""
echo "🎉 MagicAI Deployment Completed!"
echo "================================"
echo ""
echo "🌐 Your application is now available at:"
if [ ! -z "$DOMAIN" ]; then
    echo "   https://$DOMAIN"
    echo "   (Make sure app.tause.pro points to this server's IP)"
else
    PUBLIC_IP=$(curl -s http://checkip.amazonaws.com/)
    echo "   http://$PUBLIC_IP"
    echo "   (Configure app.tause.pro to point to: $PUBLIC_IP)"
fi
echo ""
echo "🔧 Admin Panel:"
if [ ! -z "$DOMAIN" ]; then
    echo "   https://$DOMAIN/admin"
else
    echo "   http://$PUBLIC_IP/admin"
fi
echo ""
echo "📋 Next Steps:"
echo "1. Create your first admin user"
echo "2. Configure application settings"
echo "3. Set up payment plans"
echo "4. Test AI integrations"
echo "5. Customize your branding"
echo ""
echo "📊 Useful Commands:"
echo "   magicai-info     - System information"
echo "   magicai-logs     - View logs"
echo "   magicai-backup   - Create backup"
echo "   magicai-restart  - Restart services"
echo ""
echo "📝 Important Files:"
echo "   Config: /var/www/magicai/.env"
echo "   Logs: /var/www/magicai/storage/logs/"
echo "   Backups: /var/backups/magicai/"
echo ""
echo "🎯 Your MagicAI installation is ready for production!"