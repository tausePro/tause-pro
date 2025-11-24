#!/bin/bash
set -e

PEM="staging-tausepro-key.pem"
HOST="ubuntu@3.220.198.180"
REMOTE_DIR="/var/www/magicai"
LOCAL_SQL="/Users/tause/Downloads/codecanyon-EQwvWb8c-magicai-openai-content-text-image-chat-code-generator-as-saas/magicai.sql"

echo "🚀 Subiendo magicai.sql..."
scp -i "$PEM" "$LOCAL_SQL" "$HOST:$REMOTE_DIR/magicai.sql"

echo "🔧 Ejecutando reset en el servidor..."
ssh -i "$PEM" "$HOST" << 'EOF'
set -e
cd /var/www/magicai

echo "📦 Backup BD actual (por seguridad)..."
sudo mysqldump magicai_staging > backup_magicai_staging_$(date +%Y%m%d%H%M).sql || true

echo "🗄️ Drop + create BD y usuario..."
sudo mysql << 'SQL_EOF'
DROP DATABASE IF EXISTS magicai_staging;
CREATE DATABASE magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
SQL_EOF

echo "📥 Importando magicai.sql..."
sudo mysql magicai_staging < magicai.sql

echo "📝 Ajustando permisos storage/bootstrap..."
sudo mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "⚙️ Limpiando config..."
php artisan config:clear

echo "🚀 Ejecutando migraciones..."
php artisan migrate --force

echo "👑 Creando/actualizando super admin felipe@tause.co ..."
php artisan tinker --execute="
use App\Models\User;
use App\Enums\Roles;
User::updateOrCreate(
    ['email' => 'felipe@tause.co'],
    [
        'name' => 'Felipe',
        'surname' => 'Tause',
        'phone' => '0000000000',
        'type' => Roles::SUPER_ADMIN,
        'password' => bcrypt('Rafa0314\$'),
        'status' => 1,
    ]
);
"

echo "✅ Reset completado"
EOF

echo "🧪 Probando HTTP local..."
ssh -i "$PEM" "$HOST" "curl -I http://localhost || true"

echo "✅ Listo. Prueba ahora https://test.tause.pro y https://test.tause.pro/login"
