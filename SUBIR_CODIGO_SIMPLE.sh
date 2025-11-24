#!/bin/bash

# 🚀 Subir código a staging - SIMPLE Y DIRECTO

STAGING_IP="3.220.198.180"
SSH_KEY="staging-tausepro-key.pem"
REMOTE_USER="ubuntu"
REMOTE_PATH="/var/www/magicai"

echo "🚀 Subiendo código a staging..."
echo "IP: $STAGING_IP"
echo ""

# Verificar conexión
echo "🔍 Verificando conexión..."
if ! ssh -4 -i $SSH_KEY -o ConnectTimeout=5 -o StrictHostKeyChecking=no $REMOTE_USER@$STAGING_IP "echo '✅ Conectado'" 2>/dev/null; then
    echo "❌ No se puede conectar vía SSH"
    echo ""
    echo "Usa EC2 Instance Connect desde AWS Console:"
    echo "1. EC2 → Instances → i-08158cf4fbb6ef880"
    echo "2. Connect → EC2 Instance Connect"
    echo "3. Ejecuta los comandos manualmente"
    exit 1
fi

echo "✅ Conexión OK"
echo ""

# Crear archivo de exclusión
cat > .rsync-exclude-temp << 'EOF'
*.md
*.MD
*.sh
*.bash
*.zip
*.tar
*.tar.gz
*.tgz
*.rar
*.7z
node_modules/
vendor/
.git/
.env
.env.*
!.env.example
*.log
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
bootstrap/cache/*
.DS_Store
.vscode/
.idea/
EOF

# Subir código
echo "📤 Subiendo código (esto puede tardar)..."
rsync -avz --progress --exclude-from=.rsync-exclude-temp \
    -e "ssh -4 -i $SSH_KEY -o StrictHostKeyChecking=no" \
    ./ $REMOTE_USER@$STAGING_IP:$REMOTE_PATH/

echo ""
echo "✅ Código subido"
echo ""

# Configurar en servidor
echo "⚙️ Configurando en servidor..."
ssh -4 -i $SSH_KEY -o StrictHostKeyChecking=no $REMOTE_USER@$STAGING_IP << 'REMOTE_SCRIPT'
cd /var/www/magicai

# Crear .env si no existe
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || cat > .env << 'ENVEOF'
APP_NAME=MagicAI
APP_ENV=staging
APP_KEY=
APP_DEBUG=true
APP_URL=http://test.tause.pro

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024
ENVEOF
fi

# Instalar dependencias si falta vendor
if [ ! -d vendor ]; then
    echo "Instalando dependencias..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Generar APP_KEY
php artisan key:generate --force

# Permisos
sudo mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# BD y migraciones
sudo mysql << 'MYSQL_EOF'
CREATE DATABASE IF NOT EXISTS magicai_staging;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
MYSQL_EOF

php artisan migrate --force

# Limpiar cache
php artisan config:clear
php artisan cache:clear

echo "✅ Configuración completada"
REMOTE_SCRIPT

echo ""
echo "✅ LISTO!"
echo "🌐 Prueba: http://test.tause.pro"


