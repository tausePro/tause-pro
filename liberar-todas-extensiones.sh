#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

SERVER="ubuntu@34.207.248.220"
KEY="magicai-tause-key.pem"

echo -e "${BLUE}🔓 LIBERANDO TODAS LAS EXTENSIONES${NC}\n"

ssh -i $KEY $SERVER << 'ENDSSH'
cd /var/www/magicai

echo "📊 Actualizando versiones en BD..."

sudo -u www-data php artisan tinker --execute="
// Liberar TODAS las extensiones incrementando versión
DB::table('extensions')->where('is_theme', 0)->update(['licensed' => 1]);

// Actualizar versiones para bypass
\$extensions = [
    'ai-chat-pro' => '2.4',
    'social-media' => '5.0',
    'creative-suite' => '1.4',
    'chatbot-voice' => '2.0',
    'canvas' => '1.6',
    'url-to-video' => '1.4',
    'ai-viral-clips' => '1.2',
    'influencer-avatar' => '1.3',
    'marketing-bot' => '2.1',
    'live-customizer' => '1.4',
    'content-manager' => '1.2',
    'menu' => '2.3',
    'mega-menu' => '1.2',
    'advanced-image' => '2.0',
    'onboarding-pro' => '1.5',
    'openai-realtime-chat' => '1.8',
    'elevenlabs-voice-chat' => '1.3',
    'chatbot-telegram' => '1.5',
    'ai-persona' => '2.3',
    'ai-video-pro' => '2.5',
    'focus-mode' => '2.2',
    'ai-product-shot' => '2.4',
    'ai-avatar' => '2.3',
    'seo-tool' => '3.5',
    'cryptomus' => '3.3',
    'ai-social-media' => '4.6',
    'cloudflare-r2' => '3.2',
    'plagiarism' => '2.2',
    'ai-video-to-video' => '1.3',
    'ai-music' => '1.3',
    'xero' => '1.1',
    'migration' => '1.3',
    'discount-manager' => '1.4',
    'multi-model' => '1.3',
    'all-in-one-package' => '1.1',
    'ai-music-pro' => '1.1',
    'ai-chat-pro-file-chat' => '1.1',
];

foreach (\$extensions as \$slug => \$version) {
    DB::table('extensions')->where('slug', \$slug)->update([
        'version' => \$version,
        'licensed' => 1
    ]);
}

echo 'Total extensiones liberadas: ' . count(\$extensions);
"

echo ""
echo "🧹 Limpiando caché..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear

echo ""
echo "✅ TODAS LAS EXTENSIONES LIBERADAS!"
echo ""
echo "📋 Extensiones ahora disponibles:"
sudo -u www-data php artisan tinker --execute="
\$licensed = DB::table('extensions')->where('is_theme', 0)->where('licensed', 1)->count();
\$total = DB::table('extensions')->where('is_theme', 0)->count();
echo \"Licenciadas: \$licensed de \$total extensiones\";
"

ENDSSH

echo -e "\n${GREEN}✅ Proceso completado${NC}"



