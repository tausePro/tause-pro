#!/bin/bash

# 🔧 Script para Configurar Nueva Instancia Staging

STAGING_IP="44.211.83.213"
KEY_FILE="staging-tausepro-key.pem"

echo "🔧 Configurando nueva instancia staging..."
echo "IP: $STAGING_IP"
echo ""

# Conectar y ejecutar comandos
ssh -4 -i "$KEY_FILE" -o StrictHostKeyChecking=no ubuntu@$STAGING_IP << 'EOF'

echo "1️⃣ Habilitando servicios..."
sudo systemctl enable ssh
sudo systemctl start ssh
sudo systemctl enable nginx
sudo systemctl start nginx
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm

echo ""
echo "2️⃣ Configurando firewall..."
sudo ufw --force disable
sudo iptables -F
sudo iptables -P INPUT ACCEPT
sudo ufw --force reset
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

echo ""
echo "3️⃣ Verificando servicios..."
sudo systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH inactivo"
sudo systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
sudo systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"

echo ""
echo "4️⃣ Verificando puertos..."
sudo netstat -tlnp | grep :22 && echo "✅ Puerto 22 escuchando" || echo "❌ Puerto 22 no escuchando"
sudo netstat -tlnp | grep :80 && echo "✅ Puerto 80 escuchando" || echo "❌ Puerto 80 no escuchando"

echo ""
echo "✅ Configuración completada!"

EOF

echo ""
echo "🌐 Probar HTTP:"
echo "curl http://$STAGING_IP"



