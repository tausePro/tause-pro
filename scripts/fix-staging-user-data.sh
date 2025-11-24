#!/bin/bash

# 🔧 Script de User Data para Arreglar Staging Automáticamente
# Este script se ejecuta al iniciar la instancia

set -e

# Log todo
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Iniciando configuración automática de staging..."
echo "=================================================="

# 1. Deshabilitar firewall temporalmente
echo "1️⃣ Deshabilitando firewall..."
ufw --force disable || true
iptables -F || true
iptables -X || true
iptables -t nat -F || true
iptables -t nat -X || true
iptables -t mangle -F || true
iptables -t mangle -X || true
iptables -P INPUT ACCEPT || true
iptables -P FORWARD ACCEPT || true
iptables -P OUTPUT ACCEPT || true

# 2. Asegurar que servicios están corriendo
echo "2️⃣ Iniciando servicios..."
systemctl start ssh || true
systemctl enable ssh || true
systemctl start nginx || true
systemctl enable nginx || true
systemctl start php8.2-fpm || true
systemctl enable php8.2-fpm || true

# 3. Verificar permisos
echo "3️⃣ Configurando permisos..."
chown -R www-data:www-data /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true
chmod -R 775 /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true

# 4. Configurar firewall correctamente
echo "4️⃣ Configurando firewall..."
ufw --force reset || true
ufw default deny incoming || true
ufw default allow outgoing || true
ufw allow 22/tcp || true
ufw allow 80/tcp || true
ufw allow 443/tcp || true
ufw --force enable || true

# 5. Verificar que todo está bien
echo "5️⃣ Verificando servicios..."
systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH inactivo"
systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"

# 6. Verificar puertos
echo "6️⃣ Verificando puertos..."
netstat -tlnp | grep :22 && echo "✅ Puerto 22 escuchando" || echo "❌ Puerto 22 no escuchando"
netstat -tlnp | grep :80 && echo "✅ Puerto 80 escuchando" || echo "❌ Puerto 80 no escuchando"

echo ""
echo "✅ Configuración completada!"
echo "📝 Logs guardados en /var/log/user-data.log"



