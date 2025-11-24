#!/bin/bash

# 🔧 User Data CORRECTO: NO habilita firewall
# Este script se ejecuta al iniciar la instancia
# NO habilita firewall para permitir conexión SSH inmediata

set -e

# Log todo
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Configurando staging (sin firewall inicial)..."
echo "=================================================="

# 1. Deshabilitar firewall COMPLETAMENTE
echo "1️⃣ Deshabilitando firewall..."
ufw --force disable || true
iptables -F || true
iptables -X || true
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

# 3. Configurar permisos básicos
echo "3️⃣ Configurando permisos..."
chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true
chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true

# 4. Verificar servicios
echo "4️⃣ Verificando servicios..."
systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH inactivo"
systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"

echo ""
echo "✅ Configuración base completada!"
echo "⚠️  Firewall NO habilitado - configurar manualmente después de verificar"
echo "📝 Para habilitar firewall después:"
echo "   sudo ufw allow 22/tcp"
echo "   sudo ufw allow 80/tcp"
echo "   sudo ufw allow 443/tcp"
echo "   sudo ufw enable"



