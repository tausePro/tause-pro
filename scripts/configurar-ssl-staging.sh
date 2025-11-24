#!/bin/bash

# 🔒 Configurar SSL para test.tause.pro usando Let's Encrypt

DOMAIN="test.tause.pro"
EMAIL="admin@tause.pro"  # Cambiar si es necesario

echo "🔒 Configurando SSL para $DOMAIN"
echo "=================================================="
echo ""

# Instalar Certbot si no está instalado
if ! command -v certbot &> /dev/null; then
    echo "📦 Instalando Certbot..."
    sudo apt-get update -qq
    sudo apt-get install -y certbot python3-certbot-nginx
else
    echo "✅ Certbot ya está instalado"
fi

echo ""
echo "🔐 Obteniendo certificado SSL..."
sudo certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email $EMAIL --redirect

echo ""
echo "✅ SSL configurado!"
echo ""
echo "Verifica:"
echo "  https://$DOMAIN"
echo ""
echo "Para renovar automáticamente:"
echo "  sudo certbot renew --dry-run"


