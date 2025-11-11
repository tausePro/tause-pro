#!/bin/bash

# Script de verificación post-deploy - Ejecutar DIRECTAMENTE en servidor AWS
# Verifica que todo funciona correctamente después del deploy

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# AJUSTAR ESTA VARIABLE según tu configuración real en AWS
PROJECT_ROOT="/var/www/tausepro9.4"

echo -e "${GREEN}🔍 Verificando deployment...${NC}"
echo ""

cd "$PROJECT_ROOT"

ERRORS=0
WARNINGS=0

# 1. Verificar que la aplicación responde
echo -e "${GREEN}1️⃣  Verificando que la aplicación responde...${NC}"
if php artisan about > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Aplicación responde correctamente${NC}"
else
    echo -e "${RED}❌ Error: Aplicación no responde${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 2. Verificar sintaxis de archivos modificados
echo -e "${GREEN}2️⃣  Verificando sintaxis de archivos modificados...${NC}"
FILES_TO_CHECK=(
    "app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php"
    "app/Extensions/Chatbot/System/Services/ProductCardService.php"
    "app/Extensions/Chatbot/System/Services/ProductIntegrationService.php"
    "app/Extensions/Chatbot/System/Services/Traits/EnhancedKnowledgeBaseTrait.php"
)

for file in "${FILES_TO_CHECK[@]}"; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo -e "${GREEN}✅ $(basename $file)${NC}"
        else
            echo -e "${RED}❌ Error de sintaxis en $file${NC}"
            ERRORS=$((ERRORS + 1))
        fi
    else
        echo -e "${YELLOW}⚠️  Archivo no encontrado: $file${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
done
echo ""

# 3. Verificar que los servicios se pueden instanciar
echo -e "${GREEN}3️⃣  Verificando que los servicios se cargan correctamente...${NC}"
php artisan tinker --execute="
    try {
        \$service1 = new App\Extensions\Chatbot\System\Services\ProductOrchestratorService();
        echo '✅ ProductOrchestratorService OK\n';
    } catch (Exception \$e) {
        echo '❌ Error en ProductOrchestratorService: ' . \$e->getMessage() . '\n';
    }
    
    try {
        \$service2 = new App\Extensions\Chatbot\System\Services\AgentOrchestratorService();
        echo '✅ AgentOrchestratorService OK\n';
    } catch (Exception \$e) {
        echo '❌ Error en AgentOrchestratorService: ' . \$e->getMessage() . '\n';
    }
" 2>&1 | grep -E "✅|❌" || echo -e "${YELLOW}⚠️  No se pudo verificar servicios${NC}"
echo ""

# 4. Verificar logs recientes
echo -e "${GREEN}4️⃣  Verificando logs recientes (últimas 50 líneas)...${NC}"
if [ -f "storage/logs/laravel.log" ]; then
    RECENT_ERRORS=$(tail -n 50 storage/logs/laravel.log | grep -i "error\|exception\|fatal" | wc -l)
    if [ "$RECENT_ERRORS" -gt 0 ]; then
        echo -e "${YELLOW}⚠️  Se encontraron $RECENT_ERRORS errores en logs recientes${NC}"
        echo -e "${YELLOW}Últimos errores:${NC}"
        tail -n 50 storage/logs/laravel.log | grep -i "error\|exception\|fatal" | tail -3
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "${GREEN}✅ No hay errores críticos en logs recientes${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Archivo de logs no encontrado${NC}"
fi
echo ""

# 5. Verificar conexión a base de datos
echo -e "${GREEN}5️⃣  Verificando conexión a base de datos...${NC}"
DB_CHECK=$(php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch (Exception \$e) { echo 'ERROR'; }" 2>&1 | grep -E "OK|ERROR" || echo "UNKNOWN")
if [ "$DB_CHECK" == "OK" ]; then
    echo -e "${GREEN}✅ Conexión a BD OK${NC}"
else
    echo -e "${RED}❌ Error de conexión a BD${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 6. Verificar modelo ChatbotProduct y scopes
echo -e "${GREEN}6️⃣  Verificando modelo ChatbotProduct...${NC}"
PRODUCT_CHECK=$(php artisan tinker --execute="
    try {
        \$product = App\Extensions\Chatbot\System\Models\ChatbotProduct::first();
        if (method_exists(App\Extensions\Chatbot\System\Models\ChatbotProduct::class, 'scopeActive')) {
            echo 'SCOPES_OK';
        } else {
            echo 'SCOPES_MISSING';
        }
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
    }
" 2>&1 | grep -E "SCOPES_OK|SCOPES_MISSING|ERROR" || echo "UNKNOWN")

if [ "$PRODUCT_CHECK" == "SCOPES_OK" ]; then
    echo -e "${GREEN}✅ Modelo ChatbotProduct y scopes OK${NC}"
elif [ "$PRODUCT_CHECK" == "SCOPES_MISSING" ]; then
    echo -e "${RED}❌ Scopes faltantes en ChatbotProduct${NC}"
    ERRORS=$((ERRORS + 1))
else
    echo -e "${YELLOW}⚠️  No se pudo verificar modelo (puede ser normal si no hay productos)${NC}"
fi
echo ""

# 7. Verificar cache
echo -e "${GREEN}7️⃣  Verificando cache...${NC}"
php artisan config:cache > /dev/null 2>&1 && echo -e "${GREEN}✅ Cache de configuración OK${NC}" || echo -e "${YELLOW}⚠️  Problema con cache${NC}"
echo ""

# Resumen final
echo "=========================================="
echo -e "${GREEN}📊 RESUMEN DE VERIFICACIÓN${NC}"
echo "=========================================="
echo -e "Errores encontrados: ${RED}${ERRORS}${NC}"
echo -e "Advertencias: ${YELLOW}${WARNINGS}${NC}"
echo ""

if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✅ Verificación completada: Todo OK${NC}"
    echo ""
    echo -e "${GREEN}📋 Próximos pasos recomendados:${NC}"
    echo "1. Probar Sales Agent en un chatbot"
    echo "2. Verificar que productos se encuentran correctamente"
    echo "3. Monitorear logs por las próximas horas: tail -f storage/logs/laravel.log"
    exit 0
else
    echo -e "${RED}❌ Verificación completada: Se encontraron ${ERRORS} errores${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  Se recomienda revisar los errores antes de continuar${NC}"
    echo -e "${YELLOW}💡 Si es necesario, ejecuta rollback: ./scripts/restore-backup.sh [backup-dir]${NC}"
    exit 1
fi

