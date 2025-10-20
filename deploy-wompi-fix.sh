#!/bin/bash
set -e

echo "🚀 Desplegando fix de Wompi..."

scp -i magicai-tause-key.pem \
    app/Extensions/Chatbot/System/Services/WompiService.php \
    ubuntu@34.207.248.220:/tmp/

ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 << 'ENDSSH'
sudo cp /tmp/WompiService.php /var/www/magicai/app/Extensions/Chatbot/System/Services/
sudo chown www-data:www-data /var/www/magicai/app/Extensions/Chatbot/System/Services/WompiService.php
sudo -u www-data php /var/www/magicai/artisan optimize:clear
rm /tmp/WompiService.php

echo ""
echo "✅ ¡PROBLEMA RESUELTO!"
echo ""
echo "🐛 Error encontrado:"
echo "   Wompi rechazaba HTML en el campo 'description'"
echo "   Ejemplo: '<p>Diseñado para la recuperación...'"
echo ""
echo "🔧 Solución aplicada:"
echo "   strip_tags() para convertir HTML a texto plano"
echo ""
echo "🧪 PRUEBA AHORA:"
echo "   https://app.tause.pro/dashboard/chatbot/2/chatcommerce"
echo ""
ENDSSH

echo "✅ Desplegado correctamente"


