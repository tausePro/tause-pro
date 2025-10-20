#!/bin/bash

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 DESPLEGANDO ACTUALIZACIÓN DE VERSIONES DE EXTENSIONES${NC}\n"

# Variables
SERVER="ubuntu@3.83.18.44"
KEY="magicai-tause-key.pem"
REMOTE_PATH="/var/www/html"

# 1. Copiar archivo extension.json actualizado
echo -e "${BLUE}📦 Copiando extension.json actualizado...${NC}"
scp -i $KEY app/Extensions/Chatbot/extension.json $SERVER:$REMOTE_PATH/app/Extensions/Chatbot/

# 2. Actualizar versiones en la base de datos de producción
echo -e "${BLUE}🗄️  Actualizando versiones en la base de datos...${NC}"
ssh -i $KEY $SERVER << 'EOF'
cd /var/www/html

# Actualizar External Chatbot a 4.6
php artisan tinker --execute="
\$chatbot = \App\Models\Extension::firstOrCreate(
    ['slug' => 'chatbot', 'is_theme' => false],
    ['version' => '4.6', 'installed' => true]
);
\$chatbot->update(['version' => '4.6']);
echo '✅ External Chatbot actualizado a v4.6\n';
"

# Registrar WhatsApp Chatbot con slug correcto
php artisan tinker --execute="
\$wa = \App\Models\Extension::where('slug', 'whatsapp-chatbot')->first();
if (\$wa) {
    \$wa->update(['slug' => 'chatbot-whatsapp']);
    echo '✅ WhatsApp slug corregido\n';
} else {
    \$wa = \App\Models\Extension::firstOrCreate(
        ['slug' => 'chatbot-whatsapp', 'is_theme' => false],
        ['version' => '1.4', 'installed' => true]
    );
    echo '✅ WhatsApp Chatbot registrado\n';
}
"

# Limpiar caché
php artisan cache:clear
php artisan config:clear

echo "✅ Versiones actualizadas correctamente"
EOF

echo -e "\n${GREEN}✅ DESPLIEGUE COMPLETADO${NC}"
echo -e "${BLUE}Las extensiones ya no mostrarán actualizaciones disponibles${NC}"


