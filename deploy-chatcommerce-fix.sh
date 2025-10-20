#!/bin/bash
scp -i magicai-tause-key.pem app/Extensions/Chatbot/resources/views/chatcommerce/index.blade.php ubuntu@34.207.248.220:/tmp/
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 << 'ENDSSH'
sudo cp /tmp/index.blade.php /var/www/magicai/app/Extensions/Chatbot/resources/views/chatcommerce/
sudo chown www-data:www-data /var/www/magicai/app/Extensions/Chatbot/resources/views/chatcommerce/index.blade.php
sudo -u www-data php /var/www/magicai/artisan view:clear
rm /tmp/index.blade.php
echo "✅ Desplegado - Ahora con logging para debug"
ENDSSH


