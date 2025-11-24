#!/bin/bash

# Script para verificar el chatbot embebido en staging
# UUID: 63140a24-9552-4751-b19b-e58802adb312

CHATBOT_UUID="63140a24-9552-4751-b19b-e58802adb312"
PROJECT_PATH="/var/www/magicai"

echo "🔍 Verificando chatbot embebido en staging..."
echo "UUID: $CHATBOT_UUID"
echo ""

cd $PROJECT_PATH || exit 1

# 1. Verificar que el archivo external-chatbot.js existe
echo "1️⃣ Verificando archivo external-chatbot.js..."
if [ -f "public/vendor/chatbot/js/external-chatbot.js" ]; then
    echo "   ✅ Archivo existe: public/vendor/chatbot/js/external-chatbot.js"
    ls -lh public/vendor/chatbot/js/external-chatbot.js
else
    echo "   ❌ Archivo NO existe: public/vendor/chatbot/js/external-chatbot.js"
    echo "   📦 Publicando assets..."
    php8.3 artisan vendor:publish --tag=extension --force
    if [ -f "public/vendor/chatbot/js/external-chatbot.js" ]; then
        echo "   ✅ Assets publicados correctamente"
    else
        echo "   ❌ Error al publicar assets"
    fi
fi
echo ""

# 2. Verificar permisos del archivo
echo "2️⃣ Verificando permisos..."
if [ -f "public/vendor/chatbot/js/external-chatbot.js" ]; then
    stat public/vendor/chatbot/js/external-chatbot.js | grep -E "Access|Uid|Gid"
fi
echo ""

# 3. Verificar que el chatbot existe en la base de datos
echo "3️⃣ Verificando chatbot en base de datos..."
php8.3 artisan tinker --execute="
\$chatbot = \App\Extensions\Chatbot\System\Models\Chatbot::where('uuid', '$CHATBOT_UUID')->first();
if (\$chatbot) {
    echo '   ✅ Chatbot encontrado:' . PHP_EOL;
    echo '      - ID: ' . \$chatbot->id . PHP_EOL;
    echo '      - Título: ' . \$chatbot->title . PHP_EOL;
    echo '      - Activo: ' . (\$chatbot->active ? 'Sí' : 'No') . PHP_EOL;
    echo '      - User ID: ' . \$chatbot->user_id . PHP_EOL;
} else {
    echo '   ❌ Chatbot NO encontrado con UUID: $CHATBOT_UUID' . PHP_EOL;
}
"
echo ""

# 4. Verificar que la API responde correctamente
echo "4️⃣ Verificando API del chatbot..."
API_RESPONSE=$(curl -s "https://test.tause.pro/api/v2/chatbot/$CHATBOT_UUID" 2>&1)
if echo "$API_RESPONSE" | grep -q '"uuid"'; then
    echo "   ✅ API responde correctamente"
    echo "$API_RESPONSE" | head -20
else
    echo "   ❌ API no responde correctamente"
    echo "$API_RESPONSE"
fi
echo ""

# 5. Verificar que el frame route funciona
echo "5️⃣ Verificando frame route..."
FRAME_RESPONSE=$(curl -s -I "https://test.tause.pro/chatbot/$CHATBOT_UUID/frame" 2>&1 | head -5)
if echo "$FRAME_RESPONSE" | grep -q "200\|302"; then
    echo "   ✅ Frame route responde correctamente"
    echo "$FRAME_RESPONSE"
else
    echo "   ❌ Frame route no responde correctamente"
    echo "$FRAME_RESPONSE"
fi
echo ""

# 6. Verificar que el script JS es accesible públicamente
echo "6️⃣ Verificando acceso público al script JS..."
SCRIPT_RESPONSE=$(curl -s -I "https://test.tause.pro/vendor/chatbot/js/external-chatbot.js" 2>&1 | head -5)
if echo "$SCRIPT_RESPONSE" | grep -q "200"; then
    echo "   ✅ Script JS es accesible públicamente"
    echo "$SCRIPT_RESPONSE"
else
    echo "   ❌ Script JS NO es accesible públicamente"
    echo "$SCRIPT_RESPONSE"
fi
echo ""

# 7. Verificar logs recientes
echo "7️⃣ Verificando logs recientes..."
tail -50 storage/logs/laravel.log | grep -i "chatbot\|$CHATBOT_UUID\|external-chatbot" | tail -10 || echo "   No hay logs recientes relacionados"
echo ""

echo "✅ Verificación completada"

