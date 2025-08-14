#!/bin/bash

# MagicAI Setup Verification Script for app.tause.pro
# Verifies that everything is configured correctly

set -e

SUBDOMAIN="app.tause.pro"
PUBLIC_IP=$(curl -s http://checkip.amazonaws.com/)

echo "🔍 Verifying MagicAI setup for $SUBDOMAIN"
echo "=========================================="

# Check if MagicAI directory exists
echo "📁 Checking MagicAI installation..."
if [ -d "/var/www/magicai" ] && [ -f "/var/www/magicai/composer.json" ]; then
    echo "✅ MagicAI code found"
else
    echo "❌ MagicAI code not found in /var/www/magicai"
    exit 1
fi

# Check services
echo ""
echo "🔧 Checking services..."
services=("nginx" "php8.2-fpm" "mysql" "supervisor")
for service in "${services[@]}"; do
    if systemctl is-active --quiet $service; then
        echo "✅ $service is running"
    else
        echo "❌ $service is not running"
    fi
done

# Check Nginx configuration
echo ""
echo "🌐 Checking Nginx configuration..."
if nginx -t &>/dev/null; then
    echo "✅ Nginx configuration is valid"
else
    echo "❌ Nginx configuration has errors"
fi

# Check if domain is configured
echo ""
echo "🔍 Checking domain configuration..."
if grep -q "$SUBDOMAIN" /etc/nginx/sites-available/magicai; then
    echo "✅ Domain $SUBDOMAIN configured in Nginx"
else
    echo "⚠️  Domain not configured yet"
fi

# Check database connection
echo ""
echo "🗄️ Checking database connection..."
cd /var/www/magicai
if sudo -u www-data php artisan migrate:status &>/dev/null; then
    echo "✅ Database connection working"
else
    echo "❌ Database connection failed"
fi

# Check .env configuration
echo ""
echo "⚙️ Checking .env configuration..."
if [ -f "/var/www/magicai/.env" ]; then
    echo "✅ .env file exists"
    
    # Check key configurations
    if grep -q "APP_KEY=base64:" /var/www/magicai/.env; then
        echo "✅ Application key is set"
    else
        echo "⚠️  Application key not set"
    fi
    
    if grep -q "DB_DATABASE=magicai" /var/www/magicai/.env; then
        echo "✅ Database configuration found"
    else
        echo "⚠️  Database not configured"
    fi
else
    echo "❌ .env file not found"
fi

# Check queue workers
echo ""
echo "⚡ Checking queue workers..."
if supervisorctl status magicai-worker:* &>/dev/null; then
    RUNNING_WORKERS=$(supervisorctl status magicai-worker:* | grep RUNNING | wc -l)
    echo "✅ $RUNNING_WORKERS queue workers running"
else
    echo "⚠️  Queue workers not configured yet"
fi

# Check SSL certificate
echo ""
echo "🔒 Checking SSL certificate..."
if [ -f "/etc/letsencrypt/live/$SUBDOMAIN/fullchain.pem" ]; then
    echo "✅ SSL certificate exists for $SUBDOMAIN"
    CERT_EXPIRY=$(openssl x509 -enddate -noout -in /etc/letsencrypt/live/$SUBDOMAIN/fullchain.pem | cut -d= -f2)
    echo "📅 Certificate expires: $CERT_EXPIRY"
else
    echo "⚠️  SSL certificate not installed yet"
fi

# Check DNS resolution
echo ""
echo "🌐 Checking DNS resolution..."
RESOLVED_IP=$(dig +short $SUBDOMAIN 2>/dev/null || echo "")
if [ "$RESOLVED_IP" = "$PUBLIC_IP" ]; then
    echo "✅ DNS correctly points to this server ($PUBLIC_IP)"
else
    echo "⚠️  DNS not configured or propagating"
    echo "   Current DNS: $RESOLVED_IP"
    echo "   Server IP: $PUBLIC_IP"
fi

# Check HTTP response
echo ""
echo "🌐 Checking HTTP response..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|302"; then
    echo "✅ Application responds to HTTP requests"
else
    echo "⚠️  Application not responding properly"
fi

# Check file permissions
echo ""
echo "🔐 Checking file permissions..."
if [ "$(stat -c %U /var/www/magicai)" = "www-data" ]; then
    echo "✅ Correct file ownership"
else
    echo "⚠️  File ownership needs fixing"
fi

if [ -w "/var/www/magicai/storage" ]; then
    echo "✅ Storage directory is writable"
else
    echo "❌ Storage directory is not writable"
fi

# Summary
echo ""
echo "📊 Setup Summary"
echo "================"
echo "🌐 Domain: $SUBDOMAIN"
echo "🖥️  Server IP: $PUBLIC_IP"
echo "📁 Installation: /var/www/magicai"
echo ""

# Next steps
echo "📋 Next Steps:"
if [ "$RESOLVED_IP" != "$PUBLIC_IP" ]; then
    echo "1. Configure DNS A record:"
    echo "   Type: A"
    echo "   Name: app"
    echo "   Value: $PUBLIC_IP"
    echo "   TTL: 300"
    echo ""
fi

if [ ! -f "/etc/letsencrypt/live/$SUBDOMAIN/fullchain.pem" ]; then
    echo "2. Setup SSL certificate:"
    echo "   sudo ./setup-ssl.sh $SUBDOMAIN"
    echo ""
fi

echo "3. Configure AI APIs:"
echo "   sudo ./configure-ai-apis.sh"
echo ""

echo "4. Access your application:"
if [ -f "/etc/letsencrypt/live/$SUBDOMAIN/fullchain.pem" ]; then
    echo "   https://$SUBDOMAIN"
    echo "   https://$SUBDOMAIN/admin"
else
    echo "   http://$PUBLIC_IP (temporary)"
    echo "   http://$PUBLIC_IP/admin (temporary)"
fi

echo ""
echo "🎯 Your MagicAI setup verification completed!"