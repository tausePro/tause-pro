#!/bin/bash

# 🎯 Script Maestro: Configuración Completa de Staging
# Este script guía todo el proceso de configuración de staging

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🎯 Configuración Completa de Staging${NC}"
echo "=================================================="
echo ""

# Verificar configuración
if [ -z "$STAGING_HOST" ]; then
    echo -e "${RED}❌ STAGING_HOST no está configurado${NC}"
    echo ""
    echo -e "${YELLOW}Opciones:${NC}"
    echo "1. Crear nueva instancia EC2"
    echo "2. Usar instancia existente"
    echo ""
    read -p "Selecciona opción (1/2): " OPTION
    
    if [ "$OPTION" == "1" ]; then
        echo -e "${BLUE}☁️ Creando nueva instancia EC2...${NC}"
        ./scripts/create-staging-ec2.sh
        read -p "Ingresa la IP pública de la nueva instancia: " STAGING_HOST
        export STAGING_HOST
    else
        read -p "Ingresa la IP de la instancia existente: " STAGING_HOST
        export STAGING_HOST
    fi
fi

echo ""
echo -e "${GREEN}✅ Usando STAGING_HOST=$STAGING_HOST${NC}"
echo ""

# Menú de opciones
echo -e "${BLUE}📋 Selecciona qué configurar:${NC}"
echo ""
echo "1. Configurar servidor (PHP, MySQL, Nginx, etc.)"
echo "2. Configurar base de datos"
echo "3. Clonar código desde producción"
echo "4. Clonar base de datos desde producción"
echo "5. Configurar Nginx"
echo "6. Anonimizar datos de staging"
echo "7. Todo (configuración completa)"
echo "8. Salir"
echo ""
read -p "Selecciona opción (1-8): " MENU_OPTION

case $MENU_OPTION in
    1)
        echo -e "${BLUE}🔧 Configurando servidor...${NC}"
        ./scripts/setup-staging-server.sh
        ;;
    2)
        echo -e "${BLUE}🗄️ Configurando base de datos...${NC}"
        ./scripts/setup-staging-database.sh
        ;;
    3)
        echo -e "${BLUE}📂 Clonando código...${NC}"
        ./scripts/clone-code-to-staging.sh
        ;;
    4)
        echo -e "${BLUE}📥 Clonando base de datos...${NC}"
        ./scripts/clone-production-db-to-staging.sh
        ;;
    5)
        echo -e "${BLUE}🌐 Configurando Nginx...${NC}"
        read -p "Dominio para staging (ej: staging.magicai.com): " DOMAIN
        export DOMAIN
        ./scripts/setup-staging-nginx.sh
        ;;
    6)
        echo -e "${BLUE}🔒 Anonimizando datos...${NC}"
        ./scripts/anonymize-staging-db.sh
        ;;
    7)
        echo -e "${BLUE}🚀 Configuración completa...${NC}"
        echo ""
        echo -e "${YELLOW}Paso 1/6: Configurando servidor...${NC}"
        ./scripts/setup-staging-server.sh
        
        echo ""
        echo -e "${YELLOW}Paso 2/6: Configurando base de datos...${NC}"
        ./scripts/setup-staging-database.sh
        
        echo ""
        echo -e "${YELLOW}Paso 3/6: Clonando código...${NC}"
        ./scripts/clone-code-to-staging.sh
        
        echo ""
        echo -e "${YELLOW}Paso 4/6: Clonando base de datos...${NC}"
        ./scripts/clone-production-db-to-staging.sh
        
        echo ""
        read -p "Dominio para staging (ej: staging.magicai.com): " DOMAIN
        export DOMAIN
        echo -e "${YELLOW}Paso 5/6: Configurando Nginx...${NC}"
        ./scripts/setup-staging-nginx.sh
        
        echo ""
        read -p "¿Anonimizar datos? (s/N): " ANON
        if [[ $ANON =~ ^[Ss]$ ]]; then
            echo -e "${YELLOW}Paso 6/6: Anonimizando datos...${NC}"
            ./scripts/anonymize-staging-db.sh
        fi
        
        echo ""
        echo -e "${GREEN}🎉 Configuración completa finalizada!${NC}"
        echo ""
        echo -e "${BLUE}📋 Próximos pasos manuales:${NC}"
        echo "1. Configurar archivo .env en staging"
        echo "2. Instalar dependencias: composer install && npm install"
        echo "3. Generar APP_KEY: php artisan key:generate"
        echo "4. Ejecutar migraciones: php artisan migrate"
        echo "5. Configurar DNS para el dominio"
        ;;
    8)
        echo "Saliendo..."
        exit 0
        ;;
    *)
        echo -e "${RED}❌ Opción inválida${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}✅ Proceso completado!${NC}"



