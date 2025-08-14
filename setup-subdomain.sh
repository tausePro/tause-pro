#!/bin/bash

# MagicAI Subdomain Setup Script
# Configures app.tause.pro for MagicAI deployment

set -e

SUBDOMAIN="app.tause.pro"
MAIN_DOMAIN="tause.pro"

echo "🌐 Setting up MagicAI on subdomain: $SUBDOMAIN"

# Update Nginx configuration with subdomain
echo "🔧 Updating Nginx configuration..."
sed -i "s/server_name _;/server_name $SUBDOMAIN;/" /etc/nginx/sites-available/magicai

# Test Nginx configuration
nginx -t

# Reload Nginx
systemctl reload nginx

# Update .env with subdomain URL
echo "⚙️ Updating .env with subdomain URL..."
cd /var/www/magicai
sed -i "s|APP_URL=.*|APP_URL=https://$SUBDOMAIN|" .env

# Clear config cache
sudo -u www-data php artisan config:cache

echo "✅ Subdomain configuration completed!"
echo "🌐 Your MagicAI will be available at: https://$SUBDOMAIN"
echo ""
echo "📋 Next steps:"
echo "1. Point $SUBDOMAIN to this server's IP in your DNS"
echo "2. Wait for DNS propagation (5-30 minutes)"
echo "3. Run SSL setup: ./setup-ssl.sh $SUBDOMAIN"
echo ""
echo "🔍 DNS Configuration needed:"
echo "Type: A Record"
echo "Name: app"
echo "Value: $(curl -s http://checkip.amazonaws.com/)"
echo "TTL: 300 (5 minutes)"