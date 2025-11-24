#!/bin/bash

# 🔧 User Data para ARREGLAR SSH Bloqueado
# Este User Data NO habilita firewall, permitiendo conexión SSH inmediata

set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Configurando staging (SIN firewall inicial)..."
echo "=================================================="

# Deshabilitar firewall COMPLETAMENTE
ufw --force disable || true
iptables -F || true
iptables -X || true
iptables -P INPUT ACCEPT || true
iptables -P FORWARD ACCEPT || true
iptables -P OUTPUT ACCEPT || true

# Iniciar servicios
systemctl start ssh || true
systemctl enable ssh || true
systemctl start nginx || true
systemctl enable nginx || true
systemctl start php8.2-fpm || true
systemctl enable php8.2-fpm || true

# Permisos básicos
chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true
chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true

# Verificar servicios
systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH inactivo"
systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"

echo ""
echo "✅ Configuración base completada!"
echo "⚠️  Firewall NO habilitado - configurar manualmente después de conectar"
echo "📝 Para habilitar firewall después de conectar:"
echo "   sudo ufw allow 22/tcp"
echo "   sudo ufw allow 80/tcp"
echo "   sudo ufw allow 443/tcp"
echo "   sudo ufw enable"



