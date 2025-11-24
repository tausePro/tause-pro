#!/bin/bash

# 🔧 Script de Configuración Inicial del Servidor Staging
# Este script configura un servidor Ubuntu para Laravel (staging)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración (actualizar según tu setup)
STAGING_HOST="${STAGING_HOST:-13.218.39.31}"
STAGING_USER="${STAGING_USER:-ubuntu}"
KEY_FILE="${KEY_FILE:-staging-tausepro-key.pem}"
PROJECT_NAME="${PROJECT_NAME:-magicai-staging}"
PROJECT_PATH="/var/www/${PROJECT_NAME}"

echo -e "${BLUE}🚀 Configuración Inicial del Servidor Staging${NC}"
echo "=================================================="

# Verificar que se proporcionó el host
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ Error: STAGING_HOST no está configurado${NC}"
    echo "Uso: STAGING_HOST=1.2.3.4 ./scripts/setup-staging-server.sh"
    echo "O exportar: export STAGING_HOST=1.2.3.4"
    exit 1
fi

# Verificar que existe la key
if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Error: $KEY_FILE no encontrado${NC}"
    exit 1
fi

chmod 400 "$KEY_FILE"

# Función para ejecutar comandos remotos
run_remote() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$STAGING_USER@$STAGING_HOST" "$1"
}

# Función para copiar archivos
copy_to_remote() {
    scp -i "$KEY_FILE" -o StrictHostKeyChecking=no "$1" "$STAGING_USER@$STAGING_HOST:$2"
}

echo -e "${YELLOW}📡 Verificando conexión...${NC}"
if ! run_remote "echo 'Conexión exitosa'"; then
    echo -e "${RED}❌ No se pudo conectar al servidor${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Conexión establecida${NC}"

echo -e "${YELLOW}📦 Actualizando sistema...${NC}"
run_remote "sudo apt-get update -y"
run_remote "sudo apt-get upgrade -y"
echo -e "${GREEN}✅ Sistema actualizado${NC}"

echo -e "${YELLOW}🔧 Instalando dependencias básicas...${NC}"
run_remote "sudo apt-get install -y software-properties-common curl wget git unzip"
echo -e "${GREEN}✅ Dependencias básicas instaladas${NC}"

echo -e "${YELLOW}🐘 Instalando PHP 8.2...${NC}"
run_remote "sudo add-apt-repository ppa:ondrej/php -y"
run_remote "sudo apt-get update -y"
run_remote "sudo apt-get install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl"
echo -e "${GREEN}✅ PHP 8.2 instalado${NC}"

echo -e "${YELLOW}📦 Instalando Composer...${NC}"
run_remote "curl -sS https://getcomposer.org/installer | php"
run_remote "sudo mv composer.phar /usr/local/bin/composer"
run_remote "sudo chmod +x /usr/local/bin/composer"
echo -e "${GREEN}✅ Composer instalado${NC}"

echo -e "${YELLOW}🗄️ Instalando MySQL...${NC}"
run_remote "sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password temp_password'"
run_remote "sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password temp_password'"
run_remote "sudo apt-get install -y mysql-server"
echo -e "${GREEN}✅ MySQL instalado${NC}"
echo -e "${YELLOW}⚠️  IMPORTANTE: Configura la contraseña de MySQL después${NC}"

echo -e "${YELLOW}🌐 Instalando Nginx...${NC}"
run_remote "sudo apt-get install -y nginx"
run_remote "sudo systemctl enable nginx"
run_remote "sudo systemctl start nginx"
echo -e "${GREEN}✅ Nginx instalado${NC}"

echo -e "${YELLOW}📁 Creando estructura de directorios...${NC}"
run_remote "sudo mkdir -p $PROJECT_PATH"
run_remote "sudo chown -R $STAGING_USER:$STAGING_USER $PROJECT_PATH"
echo -e "${GREEN}✅ Directorios creados${NC}"

echo -e "${YELLOW}🔐 Configurando permisos de PHP-FPM...${NC}"
run_remote "sudo sed -i 's/user = www-data/user = $STAGING_USER/' /etc/php/8.2/fpm/pool.d/www.conf"
run_remote "sudo sed -i 's/group = www-data/group = $STAGING_USER/' /etc/php/8.2/fpm/pool.d/www.conf"
run_remote "sudo systemctl restart php8.2-fpm"
echo -e "${GREEN}✅ PHP-FPM configurado${NC}"

echo -e "${YELLOW}🔧 Configurando PHP...${NC}"
run_remote "sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' /etc/php/8.2/fpm/php.ini"
run_remote "sudo sed -i 's/post_max_size = 8M/post_max_size = 50M/' /etc/php/8.2/fpm/php.ini"
run_remote "sudo sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/8.2/fpm/php.ini"
run_remote "sudo systemctl restart php8.2-fpm"
echo -e "${GREEN}✅ PHP configurado${NC}"

echo -e "${YELLOW}📝 Instalando Node.js y NPM...${NC}"
run_remote "curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -"
run_remote "sudo apt-get install -y nodejs"
echo -e "${GREEN}✅ Node.js instalado${NC}"

echo ""
echo -e "${GREEN}🎉 Configuración del servidor completada!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Próximos pasos:${NC}"
echo "1. Configurar base de datos: ./scripts/setup-staging-database.sh"
echo "2. Clonar código: ./scripts/clone-code-to-staging.sh"
echo "3. Configurar Nginx: ./scripts/setup-staging-nginx.sh"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
echo "- Configura la contraseña de MySQL root"
echo "- Crea la base de datos staging"
echo "- Configura el archivo .env"
echo ""
echo -e "${BLUE}🔗 Conectar al servidor:${NC}"
echo "ssh -i $KEY_FILE $STAGING_USER@$STAGING_HOST"

