#!/bin/bash

# MagicAI Deployment Script for app.tause.pro
# Optimized deployment for Tause's MagicAI instance

set -e

SUBDOMAIN="app.tause.pro"
MAIN_DOMAIN="tause.pro"

echo "🚀 MagicAI Deployment for Tause"
echo "==============================="
echo "🌐 Target: $SUBDOMAIN"
echo "🖥️  Server: $(curl -s http://checkip.amazonaws.com/)"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run as root (use sudo)"
    exit 1
fi

# Make all scripts executable
chmod +x *.sh

echo "📋 Deployment Plan:"
echo "1. ✅ Install LEMP stack and dependencies"
echo "2. ✅ Upload and configure MagicAI Laravel app"
echo "3. ✅ Configure subdomain app.tause.pro"
echo "4. ✅ Setup SSL certificate"
echo "5. ✅ Configure AI APIs and payment gateways"
echo "6. ✅ Setup monitoring and backups"
echo ""

read -p "🤔 Ready to deploy MagicAI for Tause? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Deployment cancelled"
    exit 1
fi

# Step 1: Install base system
echo ""
echo "🔧 Step 1: Installing base system (LEMP stack)..."
echo "================================================"
./install-magicai.sh

echo ""
echo "✅ Base system installed successfully!"
echo ""
echo "📁 Next: Upload MagicAI code to /var/www/magicai"
echo ""
echo "🔄 Upload Methods:"
echo "1. Git Clone (recommended):"
echo "   cd /var/www && git clone YOUR_MAGICAI_REPO magicai"
echo ""
echo "2. Upload ZIP:"
echo "   scp -i key.pem MagicAI.zip ubuntu@$(curl -s http://checkip.amazonaws.com/):/tmp/"
echo "   cd /var/www && unzip /tmp/MagicAI.zip && mv MagicAI-* magicai"
echo ""
echo "3. Direct upload:"
echo "   scp -r -i key.pem local-magicai-folder ubuntu@$(curl -s http://checkip.amazonaws.com/):/var/www/magicai"
echo ""

read -p "📤 Have you uploaded the MagicAI code? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "⏸️ Upload the code and then run: sudo ./deploy-tause-magicai.sh"
    echo "Or continue manually with: sudo ./setup-laravel.sh"
    exit 0
fi

# Verify code exists
if [ ! -d "/var/www/magicai" ] || [ ! -f "/var/www/magicai/composer.json" ]; then
    echo "❌ MagicAI code not found in /var/www/magicai"
    echo "Please upload the code first"
    exit 1
fi

# Step 2: Setup Laravel
echo ""
echo "🎨 Step 2: Setting up Laravel application..."
echo "============================================"
./setup-laravel.sh

# Step 3: Configure subdomain
echo ""
echo "🌐 Step 3: Configuring subdomain app.tause.pro..."
echo "================================================="
./setup-subdomain.sh

# Step 4: DNS and SSL Setup
echo ""
echo "🔒 Step 4: DNS and SSL Configuration"
echo "===================================="
echo ""
echo "📋 DNS Configuration Required:"
echo "Login to your DNS provider and add:"
echo ""
echo "Type: A"
echo "Name: app"
echo "Value: $(curl -s http://checkip.amazonaws.com/)"
echo "TTL: 300 (5 minutes)"
echo ""
echo "This will make app.tause.pro point to this server."
echo ""

read -p "✅ Have you configured the DNS A record? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "⏳ Waiting 30 seconds for DNS propagation..."
    sleep 30
    
    # Check DNS resolution
    echo "🔍 Checking DNS resolution..."
    RESOLVED_IP=$(dig +short $SUBDOMAIN 2>/dev/null || echo "")
    SERVER_IP=$(curl -s http://checkip.amazonaws.com/)
    
    if [ "$RESOLVED_IP" = "$SERVER_IP" ]; then
        echo "✅ DNS correctly resolved!"
        echo "🔒 Setting up SSL certificate..."
        ./setup-ssl.sh $SUBDOMAIN
    else
        echo "⚠️  DNS not fully propagated yet"
        echo "   Resolved IP: $RESOLVED_IP"
        echo "   Server IP: $SERVER_IP"
        echo "⏸️ You can setup SSL later with: sudo ./setup-ssl.sh $SUBDOMAIN"
    fi
else
    echo "⏸️ Setup SSL later with: sudo ./setup-ssl.sh $SUBDOMAIN"
fi

# Step 5: Configure APIs
echo ""
echo "🤖 Step 5: Configure AI APIs and Payment Gateways"
echo "=================================================="
echo ""
echo "Configure your API keys for:"
echo "• OpenAI (GPT models)"
echo "• Anthropic (Claude models)"
echo "• Google Gemini"
echo "• Stable Diffusion"
echo "• ElevenLabs (Voice)"
echo "• Payment gateways (Stripe, PayPal)"
echo ""

read -p "🔑 Configure APIs now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    ./configure-ai-apis.sh
else
    echo "⏸️ Configure APIs later with: sudo ./configure-ai-apis.sh"
fi

# Step 6: Setup monitoring
echo ""
echo "📊 Step 6: Setting up monitoring and backups..."
echo "==============================================="
./setup-monitoring.sh

# Final verification
echo ""
echo "🔍 Step 7: Verifying installation..."
echo "===================================="
./verify-tause-setup.sh

# Final summary
echo ""
echo "🎉 MagicAI Deployment for Tause Completed!"
echo "=========================================="
echo ""
echo "🌐 Your MagicAI instance:"
if [ -f "/etc/letsencrypt/live/$SUBDOMAIN/fullchain.pem" ]; then
    echo "   🔒 https://$SUBDOMAIN"
    echo "   🔧 https://$SUBDOMAIN/admin"
else
    echo "   🌐 http://$(curl -s http://checkip.amazonaws.com/) (temporary)"
    echo "   🔧 http://$(curl -s http://checkip.amazonaws.com/)/admin (temporary)"
    echo "   🔒 Setup SSL: sudo ./setup-ssl.sh $SUBDOMAIN"
fi
echo ""
echo "📊 Management Commands:"
echo "   magicai-info     - System status"
echo "   magicai-logs     - View logs"
echo "   magicai-backup   - Create backup"
echo "   magicai-restart  - Restart services"
echo ""
echo "📁 Important Locations:"
echo "   Config: /var/www/magicai/.env"
echo "   Logs: /var/www/magicai/storage/logs/"
echo "   Backups: /var/backups/magicai/"
echo ""
echo "🎯 Next Steps:"
echo "1. Access admin panel and create first admin user"
echo "2. Configure application settings"
echo "3. Set up payment plans"
echo "4. Test AI integrations"
echo "5. Customize branding"
echo ""
echo "🚀 Your MagicAI SaaS platform is ready!"