#!/bin/bash

# 🚀 Comandos Rápidos para Staging
# Ejecuta estos comandos manualmente uno por uno

echo "🔧 Comandos para Ejecutar en Staging"
echo "======================================"
echo ""
echo "1. Conectar a staging:"
echo "   ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31"
echo ""
echo "2. Verificar Nginx:"
echo "   sudo cat /etc/nginx/sites-available/test.tause.pro"
echo ""
echo "3. Si no existe, crear configuración (ver INSTRUCCIONES_MANUALES_STAGING.md)"
echo ""
echo "4. Activar sitio:"
echo "   sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/"
echo "   sudo nginx -t"
echo "   sudo systemctl reload nginx"
echo ""
echo "5. Verificar servicios:"
echo "   sudo systemctl status nginx"
echo "   sudo systemctl status php8.2-fpm"
echo ""
echo "6. Actualizar cache Laravel:"
echo "   cd /var/www/magicai-staging"
echo "   php artisan config:clear && php artisan cache:clear"
echo "   php artisan config:cache && php artisan route:cache"
echo ""
echo "7. Verificar permisos:"
echo "   sudo chown -R www-data:www-data storage bootstrap/cache"
echo "   sudo chmod -R 775 storage bootstrap/cache"
echo ""
echo "8. Probar localmente:"
echo "   curl http://localhost"
echo ""
echo "9. Ver logs si hay errores:"
echo "   sudo tail -f /var/log/nginx/error.log"
echo "   tail -f storage/logs/laravel.log"
echo ""



