#!/bin/bash

# ============================================
# Script de Verificación de Entorno Local
# ============================================

set -e

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🔍 VERIFICACIÓN DE ENTORNO LOCAL${NC}\n"

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

ERRORS=0
WARNINGS=0

# Función para verificar
check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ $1${NC}"
    else
        echo -e "${RED}❌ $1${NC}"
        ERRORS=$((ERRORS + 1))
    fi
}

warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
    WARNINGS=$((WARNINGS + 1))
}

echo -e "${YELLOW}📦 Verificando PHP...${NC}"
php -v > /dev/null 2>&1
check "PHP instalado"

PHP_VERSION=$(php -v | head -1 | cut -d ' ' -f2 | cut -d '.' -f1,2)
if [ "$(echo "$PHP_VERSION >= 8.2" | bc)" -eq 1 ]; then
    check "PHP versión >= 8.2 (actual: $PHP_VERSION)"
else
    warn "PHP versión < 8.2 (actual: $PHP_VERSION)"
fi

echo -e "\n${YELLOW}📦 Verificando Composer...${NC}"
composer --version > /dev/null 2>&1
check "Composer instalado"

echo -e "\n${YELLOW}📦 Verificando Node/NPM...${NC}"
if command -v node &> /dev/null; then
    check "Node.js instalado"
    node --version
else
    warn "Node.js no encontrado (opcional para frontend)"
fi

if command -v npm &> /dev/null; then
    check "NPM instalado"
    npm --version
else
    warn "NPM no encontrado (opcional para frontend)"
fi

echo -e "\n${YELLOW}📦 Verificando MySQL...${NC}"
if command -v mysql &> /dev/null; then
    check "MySQL cliente instalado"
    mysql --version
else
    warn "MySQL cliente no encontrado"
fi

echo -e "\n${YELLOW}📦 Verificando Archivos del Proyecto...${NC}"
[ -f ".env" ] && check ".env existe" || warn ".env no encontrado"
[ -f "composer.json" ] && check "composer.json existe" || warn "composer.json no encontrado"
[ -f "artisan" ] && check "artisan existe" || warn "artisan no encontrado"
[ -d "app" ] && check "Directorio app existe" || warn "Directorio app no encontrado"
[ -d "database" ] && check "Directorio database existe" || warn "Directorio database no encontrado"

echo -e "\n${YELLOW}📦 Verificando Dependencias...${NC}"
if [ -d "vendor" ]; then
    check "Vendor directory existe"
    [ -f "vendor/autoload.php" ] && check "Autoload de Composer existe" || warn "Autoload no encontrado"
else
    warn "Vendor directory no existe - ejecutar: composer install"
fi

echo -e "\n${YELLOW}📦 Verificando Laravel...${NC}"
if [ -f "artisan" ]; then
    php artisan --version > /dev/null 2>&1
    check "Laravel funciona"
    
    LARAVEL_VERSION=$(php artisan --version 2>/dev/null | cut -d ' ' -f3)
    echo "  Versión: $LARAVEL_VERSION"
    
    php artisan config:clear > /dev/null 2>&1
    check "Config cache se puede limpiar"
else
    warn "Laravel no disponible"
fi

echo -e "\n${YELLOW}📦 Verificando Base de Datos...${NC}"
if [ -f ".env" ]; then
    DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | xargs)
    DB_USER=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | xargs)
    DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | xargs)
    
    if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
        echo "  Base de datos: $DB_NAME"
        echo "  Usuario: $DB_USER"
        
        # Intentar conectar
        if [ -n "$DB_PASS" ]; then
            mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" > /dev/null 2>&1
        else
            mysql -u "$DB_USER" -e "USE $DB_NAME;" > /dev/null 2>&1
        fi
        
        if [ $? -eq 0 ]; then
            check "Conexión a BD exitosa"
            
            # Verificar tablas importantes
            TABLES=("ext_chatbots" "ext_chatbot_agents" "ext_chatbot_products")
            for table in "${TABLES[@]}"; do
                if [ -n "$DB_PASS" ]; then
                    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE '$table';" 2>/dev/null | grep -q "$table"
                else
                    mysql -u "$DB_USER" "$DB_NAME" -e "SHOW TABLES LIKE '$table';" 2>/dev/null | grep -q "$table"
                fi
                if [ $? -eq 0 ]; then
                    check "Tabla $table existe"
                else
                    warn "Tabla $table no existe"
                fi
            done
        else
            warn "No se pudo conectar a la BD"
        fi
    else
        warn "Credenciales de BD no encontradas en .env"
    fi
else
    warn ".env no encontrado - no se puede verificar BD"
fi

echo -e "\n${YELLOW}📦 Verificando Extensiones...${NC}"
if [ -d "app/Extensions/Chatbot" ]; then
    check "Extensión Chatbot existe"
    [ -f "app/Extensions/Chatbot/System/ChatbotServiceProvider.php" ] && check "ChatbotServiceProvider existe" || warn "ChatbotServiceProvider no encontrado"
else
    warn "Extensión Chatbot no encontrada"
fi

echo -e "\n${YELLOW}📦 Verificando Servicios...${NC}"
SERVICES=(
    "app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/WooCommerceService.php"
)

for service in "${SERVICES[@]}"; do
    if [ -f "$service" ]; then
        check "$(basename $service) existe"
    else
        warn "$(basename $service) no encontrado"
    fi
done

echo -e "\n${YELLOW}📦 Verificando Tests...${NC}"
if [ -d "tests" ]; then
    check "Directorio tests existe"
    TEST_COUNT=$(find tests -name "*.php" | wc -l)
    echo "  Tests encontrados: $TEST_COUNT"
else
    warn "Directorio tests no encontrado"
fi

echo -e "\n${YELLOW}📦 Verificando Git...${NC}"
if [ -d ".git" ]; then
    check "Repositorio Git inicializado"
    CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "N/A")
    echo "  Branch actual: $CURRENT_BRANCH"
    
    UNCOMMITTED=$(git status --porcelain 2>/dev/null | wc -l)
    if [ "$UNCOMMITTED" -eq 0 ]; then
        check "No hay cambios sin commitear"
    else
        warn "$UNCOMMITTED archivos modificados sin commitear"
    fi
else
    warn "No es un repositorio Git"
fi

# ============================================
# RESUMEN
# ============================================
echo -e "\n${BLUE}📋 RESUMEN:${NC}"
echo -e "  ❌ Errores: $ERRORS"
echo -e "  ⚠️  Advertencias: $WARNINGS\n"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ Entorno local listo para desarrollo!${NC}\n"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Entorno funcional pero con advertencias${NC}\n"
    exit 0
else
    echo -e "${RED}❌ Hay errores que deben corregirse antes de continuar${NC}\n"
    exit 1
fi



