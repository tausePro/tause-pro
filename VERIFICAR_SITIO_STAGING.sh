#!/bin/bash

# 🔍 Script para Verificar Sitio Staging

STAGING_IP="3.220.198.180"
STAGING_HOST="test.tause.pro"
SSH_KEY="staging-tausepro-key.pem"

echo "🔍 Verificando Sitio Staging"
echo "=================================================="
echo ""

echo "1. Verificando servicios en servidor..."
ssh -4 -i $SSH_KEY ubuntu@$STAGING_IP << 'EOF'
echo "Nginx:"
sudo systemctl is-active nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
echo ""
echo "PHP-FPM:"
sudo systemctl is-active php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"
echo ""
echo "Puertos escuchando:"
sudo ss -tlnp | grep -E '(:80|:443)'
EOF

echo ""
echo "2. Verificando configuración Nginx..."
ssh -4 -i $SSH_KEY ubuntu@$STAGING_IP << 'EOF'
echo "Test configuración:"
sudo nginx -t
echo ""
echo "Sitios configurados:"
ls -la /etc/nginx/sites-enabled/
EOF

echo ""
echo "3. Probando HTTP desde servidor..."
ssh -4 -i $SSH_KEY ubuntu@$STAGING_IP << 'EOF'
echo "HTTP localhost:"
curl -I http://localhost 2>&1 | head -5
echo ""
echo "HTTP test.tause.pro:"
curl -I http://test.tause.pro 2>&1 | head -5
EOF

echo ""
echo "4. Probando desde fuera..."
echo "HTTP directo (IP):"
curl -I http://$STAGING_IP 2>&1 | head -5
echo ""
echo "HTTP dominio:"
curl -I http://$STAGING_HOST 2>&1 | head -5

echo ""
echo "5. Verificando logs de error..."
ssh -4 -i $SSH_KEY ubuntu@$STAGING_IP << 'EOF'
echo "Últimas 10 líneas de error.log:"
sudo tail -10 /var/log/nginx/error.log 2>/dev/null || echo "No hay errores"
EOF


