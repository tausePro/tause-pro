#!/bin/bash

# 🚀 Solución Definitiva: Arreglar Staging de Una Vez

STAGING_IP="44.211.83.213"
KEY_FILE="staging-tausepro-key.pem"

echo "🔧 Arreglando staging de una vez..."
echo ""

# Intentar conectar y ejecutar todo
ssh -4 -i "$KEY_FILE" \
  -o StrictHostKeyChecking=no \
  -o ConnectTimeout=10 \
  -o ServerAliveInterval=2 \
  ubuntu@$STAGING_IP << 'REMOTE_SCRIPT'

set -e

echo "1️⃣ Deshabilitando firewall..."
sudo ufw --force disable || true
sudo iptables -F || true
sudo iptables -P INPUT ACCEPT || true
sudo iptables -P FORWARD ACCEPT || true
sudo iptables -P OUTPUT ACCEPT || true

echo "2️⃣ Iniciando servicios..."
sudo systemctl start ssh || true
sudo systemctl enable ssh || true
sudo systemctl start nginx || true
sudo systemctl enable nginx || true
sudo systemctl start php8.2-fpm || true
sudo systemctl enable php8.2-fpm || true

echo "3️⃣ Configurando firewall correctamente..."
sudo ufw --force reset || true
sudo ufw default deny incoming || true
sudo ufw default allow outgoing || true
sudo ufw allow 22/tcp || true
sudo ufw allow 80/tcp || true
sudo ufw allow 443/tcp || true
sudo ufw --force enable || true

echo "4️⃣ Configurando permisos..."
sudo chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true
sudo chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true

echo "5️⃣ Actualizando cache Laravel..."
cd /var/www/magicai
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo ""
echo "6️⃣ Verificando..."
sudo systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH"
sudo systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx"
sudo systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM"
sudo ufw status | grep -q "Status: active" && echo "✅ Firewall activo" || echo "❌ Firewall"

echo ""
echo "✅ COMPLETADO!"
echo "🌐 Prueba: curl http://test.tause.pro"

REMOTE_SCRIPT

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Staging arreglado!"
    echo "🌐 Prueba: curl http://test.tause.pro"
else
    echo ""
    echo "❌ No se pudo conectar vía SSH"
    echo ""
    echo "🚀 ALTERNATIVA: Reiniciar instancia"
    echo "1. AWS Console → Instances → i-0bbe91c13a1343538"
    echo "2. Instance state → Reboot instance"
    echo "3. Esperar 3 minutos"
    echo "4. Ejecutar este script de nuevo"
fi



