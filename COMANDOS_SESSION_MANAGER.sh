#!/bin/bash

# 🔧 Comandos para Ejecutar en Staging vía Session Manager
# Copia y pega estos comandos uno por uno en Session Manager

echo "🔍 Diagnóstico de Staging"
echo "========================"
echo ""
echo "Ejecuta estos comandos uno por uno:"
echo ""

cat << 'EOF'

# 1. Verificar firewall
sudo ufw status verbose

# 2. Si ufw está activo, deshabilitarlo temporalmente
sudo ufw disable

# 3. Verificar iptables
sudo iptables -L -n -v

# 4. Verificar servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status ssh

# 5. Iniciar servicios si están detenidos
sudo systemctl start nginx
sudo systemctl start php8.2-fpm
sudo systemctl start ssh

# 6. Habilitar servicios para que inicien automáticamente
sudo systemctl enable nginx
sudo systemctl enable php8.2-fpm
sudo systemctl enable ssh

# 7. Verificar que puertos están escuchando
sudo netstat -tlnp | grep :22
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443

# 8. Ver logs de Nginx
sudo tail -20 /var/log/nginx/error.log

# 9. Ver logs de SSH
sudo journalctl -u ssh -n 20

# 10. Probar HTTP localmente
curl http://localhost

# 11. Si ufw estaba bloqueando, habilitarlo con reglas correctas
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

EOF

echo ""
echo "✅ Después de ejecutar estos comandos, prueba SSH desde tu máquina:"
echo "   ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31"



