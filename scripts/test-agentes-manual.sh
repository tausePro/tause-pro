#!/bin/bash

# Script de Testing Manual para Agentes
# Ejecutar después de activar Sales Agent en un chatbot

echo "🧪 Testing Manual de Agentes"
echo "=============================="
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: No se encontró artisan. Ejecuta desde la raíz del proyecto.${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Checklist de Validación:${NC}"
echo ""

# 1. Verificar que las migraciones están ejecutadas
echo "1. Verificando migraciones..."
if php artisan migrate:status | grep -q "ext_chatbot_agents"; then
    echo -e "${GREEN}✅ Tabla ext_chatbot_agents existe${NC}"
else
    echo -e "${RED}❌ Tabla ext_chatbot_agents NO existe. Ejecuta: php artisan migrate${NC}"
    exit 1
fi

# 2. Verificar que los servicios existen
echo ""
echo "2. Verificando servicios..."
if [ -f "app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php" ]; then
    echo -e "${GREEN}✅ AgentOrchestratorService existe${NC}"
else
    echo -e "${RED}❌ AgentOrchestratorService NO existe${NC}"
    exit 1
fi

if [ -f "app/Extensions/Chatbot/System/Services/AgentIntelligenceService.php" ]; then
    echo -e "${GREEN}✅ AgentIntelligenceService existe${NC}"
else
    echo -e "${RED}❌ AgentIntelligenceService NO existe${NC}"
    exit 1
fi

# 3. Verificar sintaxis PHP
echo ""
echo "3. Verificando sintaxis PHP..."
if php -l app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php > /dev/null 2>&1; then
    echo -e "${GREEN}✅ AgentOrchestratorService - Sintaxis OK${NC}"
else
    echo -e "${RED}❌ AgentOrchestratorService - Error de sintaxis${NC}"
    php -l app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php
    exit 1
fi

if php -l app/Extensions/Chatbot/System/Services/AgentIntelligenceService.php > /dev/null 2>&1; then
    echo -e "${GREEN}✅ AgentIntelligenceService - Sintaxis OK${NC}"
else
    echo -e "${RED}❌ AgentIntelligenceService - Error de sintaxis${NC}"
    php -l app/Extensions/Chatbot/System/Services/AgentIntelligenceService.php
    exit 1
fi

# 4. Verificar rutas
echo ""
echo "4. Verificando rutas..."
if php artisan route:list | grep -q "agents.get"; then
    echo -e "${GREEN}✅ Ruta /dashboard/chatbot/{chatbot}/agents existe${NC}"
else
    echo -e "${YELLOW}⚠️  Ruta de agentes no encontrada. Ejecuta: php artisan route:clear${NC}"
fi

# 5. Verificar que el modelo existe
echo ""
echo "5. Verificando modelo ChatbotAgent..."
if php artisan tinker --execute="echo class_exists('App\Extensions\Chatbot\System\Models\ChatbotAgent') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✅ Modelo ChatbotAgent existe${NC}"
else
    echo -e "${RED}❌ Modelo ChatbotAgent NO existe${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Validaciones básicas completadas${NC}"
echo ""
echo -e "${YELLOW}📝 Próximos pasos manuales:${NC}"
echo ""
echo "1. Ve al dashboard y activa Sales Agent en un chatbot"
echo "2. Verifica en la BD que se creó un External Agent:"
echo "   SELECT * FROM ext_chatbot_agents WHERE chatbot_id = [TU_CHATBOT_ID];"
echo ""
echo "3. Prueba el chatbot embebido con estos mensajes:"
echo "   - 'Quiero ver productos'"
echo "   - '¿Qué productos tienen?'"
echo "   - 'Quiero comprar algo'"
echo ""
echo "4. Verifica los logs en storage/logs/laravel.log"
echo "   Busca: 'Agent Orchestration'"
echo ""
echo "5. Verifica que los productos se muestren cuando corresponde"
echo ""
echo -e "${YELLOW}💡 Para ver logs en tiempo real:${NC}"
echo "   tail -f storage/logs/laravel.log | grep 'Agent'"
echo ""



