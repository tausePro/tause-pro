#!/bin/bash

# 🔧 Script para Arreglar Instancia Staging Actual
# Este script intenta conectarse y arreglar la instancia existente

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
INSTANCE_ID="${INSTANCE_ID:-i-0bbe91c13a1343538}"
STAGING_HOST="${STAGING_HOST:-44.211.83.213}"
STAGING_USER="${STAGING_USER:-ubuntu}"
KEY_FILE="${KEY_FILE:-staging-tausepro-key.pem}"
PROJECT_PATH="/var/www/magicai"

echo -e "${BLUE}🔧 Arreglando Instancia Staging Actual${NC}"
echo "=================================================="
echo ""
echo "Instance ID: $INSTANCE_ID"
echo "IP: $STAGING_HOST"
echo ""

# Función para ejecutar comandos remotos
run_remote() {
    ssh -4 -i "$KEY_FILE" \
        -o StrictHostKeyChecking=no \
        -o ConnectTimeout=10 \
        -o ServerAliveInterval=5 \
        -o ServerAliveCountMax=3 \
        "$STAGING_USER@$STAGING_HOST" \
        "$1" 2>&1
}

# Intentar conectar vía SSH primero
echo -e "${YELLOW}1️⃣ Intentando conectar vía SSH...${NC}"
if ssh -4 -i "$KEY_FILE" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=5 \
    "$STAGING_USER@$STAGING_HOST" \
    "echo 'OK'" > /dev/null 2>&1; then
    
    echo -e "${GREEN}✅ Conexión SSH establecida${NC}"
    echo ""
    
    # Deshabilitar firewall
    echo -e "${YELLOW}2️⃣ Deshabilitando firewall...${NC}"
    run_remote "sudo ufw --force disable || true"
    run_remote "sudo iptables -F || true"
    run_remote "sudo iptables -X || true"
    run_remote "sudo iptables -P INPUT ACCEPT || true"
    run_remote "sudo iptables -P FORWARD ACCEPT || true"
    run_remote "sudo iptables -P OUTPUT ACCEPT || true"
    echo -e "${GREEN}✅ Firewall deshabilitado${NC}"
    echo ""
    
    # Verificar servicios
    echo -e "${YELLOW}3️⃣ Verificando servicios...${NC}"
    run_remote "sudo systemctl start ssh || true"
    run_remote "sudo systemctl enable ssh || true"
    run_remote "sudo systemctl start nginx || true"
    run_remote "sudo systemctl enable nginx || true"
    run_remote "sudo systemctl start php8.2-fpm || true"
    run_remote "sudo systemctl enable php8.2-fpm || true"
    echo -e "${GREEN}✅ Servicios iniciados${NC}"
    echo ""
    
    # Permisos
    echo -e "${YELLOW}4️⃣ Configurando permisos...${NC}"
    run_remote "sudo chown -R www-data:www-data $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache || true"
    run_remote "sudo chmod -R 775 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache || true"
    run_remote "sudo mkdir -p $PROJECT_PATH/storage/framework/{cache,sessions,views,testing} $PROJECT_PATH/storage/logs || true"
    echo -e "${GREEN}✅ Permisos configurados${NC}"
    echo ""
    
    # Verificar HTTP
    echo -e "${YELLOW}5️⃣ Verificando HTTP...${NC}"
    HTTP_TEST=$(run_remote "curl -s -o /dev/null -w '%{http_code}' http://localhost" || echo "000")
    if [ "$HTTP_TEST" == "200" ] || [ "$HTTP_TEST" == "302" ]; then
        echo -e "${GREEN}✅ HTTP funciona (código: $HTTP_TEST)${NC}"
    else
        echo -e "${YELLOW}⚠️  HTTP responde con código: $HTTP_TEST${NC}"
        echo "Verificando Nginx..."
        run_remote "sudo nginx -t && sudo systemctl reload nginx"
    fi
    echo ""
    
    # Verificar puertos
    echo -e "${YELLOW}6️⃣ Verificando puertos...${NC}"
    SSH_PORT=$(run_remote "sudo ss -tlnp | grep :22 | wc -l" || echo "0")
    HTTP_PORT=$(run_remote "sudo ss -tlnp | grep :80 | wc -l" || echo "0")
    
    if [ "$SSH_PORT" -gt "0" ]; then
        echo -e "${GREEN}✅ Puerto 22 (SSH) escuchando${NC}"
    else
        echo -e "${RED}❌ Puerto 22 no escuchando${NC}"
    fi
    
    if [ "$HTTP_PORT" -gt "0" ]; then
        echo -e "${GREEN}✅ Puerto 80 (HTTP) escuchando${NC}"
    else
        echo -e "${RED}❌ Puerto 80 no escuchando${NC}"
    fi
    echo ""
    
    # Configurar firewall al final
    echo -e "${YELLOW}7️⃣ Configurando firewall correctamente...${NC}"
    run_remote "sudo ufw allow 22/tcp"
    run_remote "sudo ufw allow 80/tcp"
    run_remote "sudo ufw allow 443/tcp"
    run_remote "sudo ufw --force enable"
    echo -e "${GREEN}✅ Firewall configurado${NC}"
    echo ""
    
    echo -e "${BLUE}🎉 Instancia arreglada!${NC}"
    echo "=================================================="
    echo ""
    echo "Prueba ahora:"
    echo "  ssh -4 -i $KEY_FILE $STAGING_USER@$STAGING_HOST"
    echo "  curl http://test.tause.pro"
    echo ""
    
else
    echo -e "${RED}❌ No se pudo conectar vía SSH${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  Usa EC2 Instance Connect desde AWS Console:${NC}"
    echo ""
    echo "1. AWS Console → EC2 → Instances"
    echo "2. Selecciona: $INSTANCE_ID"
    echo "3. Connect → EC2 Instance Connect → Connect"
    echo "4. Ejecuta estos comandos manualmente:"
    echo ""
    echo "   sudo ufw --force disable"
    echo "   sudo iptables -F"
    echo "   sudo iptables -P INPUT ACCEPT"
    echo "   sudo systemctl start ssh"
    echo "   sudo systemctl start nginx"
    echo "   sudo systemctl start php8.2-fpm"
    echo "   sudo chown -R www-data:www-data /var/www/magicai/storage"
    echo "   sudo chmod -R 775 /var/www/magicai/storage"
    echo "   curl http://localhost"
    echo "   sudo ufw allow 22/tcp"
    echo "   sudo ufw allow 80/tcp"
    echo "   sudo ufw allow 443/tcp"
    echo "   sudo ufw enable"
    echo ""
    echo "O ver: ARREGLAR_INSTANCIA_ACTUAL.md"
    echo ""
    exit 1
fi



