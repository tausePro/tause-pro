#!/bin/bash

# SSL Setup Script for MagicAI
# Run this after your domain is pointing to the server

set -e

# Check if domain parameter is provided
if [ -z "$1" ]; then
    echo "❌ Usage: ./setup-ssl.sh your-domain.com"
    exit 1
fi

DOMAIN=$1

echo "🔒 Setting up SSL for domain: $DOMAIN"

# Update Nginx configuration with domain
echo "🌐 Updating Nginx configuration..."
if [[ $DOMAIN == *.* ]] && [[ $DOMAIN != www.* ]]; then
    # For subdomains like app.tause.pro, don't add www
    sed -i "s/server_name _;/server_name $DOMAIN;/" /etc/nginx/sites-available/magicai
else
    # For main domains, add www variant
    sed -i "s/server_name _;/server_name $DOMAIN www.$DOMAIN;/" /etc/nginx/sites-available/magicai
fi

# Test Nginx configuration
nginx -t

# Reload Nginx
systemctl reload nginx

# Obtain SSL certificate
echo "📜 Obtaining SSL certificate..."
if [[ $DOMAIN == *.* ]] && [[ $DOMAIN != www.* ]]; then
    # For subdomains, only get certificate for the subdomain
    certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
else
    # For main domains, get certificate for both domain and www
    certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
fi

# Update .env with HTTPS URL
echo "⚙️ Updating .env with HTTPS URL..."
cd /var/www/magicai
sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env

# Clear config cache
sudo -u www-data php artisan config:cache

# Set up auto-renewal
echo "🔄 Setting up SSL auto-renewal..."
(crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -

echo "✅ SSL setup completed!"
echo "🌐 Your site is now available at: https://$DOMAIN"
echo "🔒 SSL certificate will auto-renew"