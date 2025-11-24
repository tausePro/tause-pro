#!/bin/bash

# Rollback script para Sales Agent Orchestrator
# Fecha de creación: $(date +%Y-%m-%d)

echo "🔄 Iniciando rollback del Sales Agent Orchestrator..."

# Buscar el backup más reciente
SCRIPT_BACKUP=$(ls -t app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-scripts.blade.php.backup-* 2>/dev/null | head -1)
COMPONENT_BACKUP=$(ls -t app/Extensions/ChatbotSalesAgent/resources/views/sales-agent-component.blade.php.backup-* 2>/dev/null | head -1)

if [ -z "$SCRIPT_BACKUP" ]; then
    echo "❌ No se encontró backup de frontend-ui-scripts.blade.php"
    exit 1
fi

if [ -z "$COMPONENT_BACKUP" ]; then
    echo "❌ No se encontró backup de sales-agent-component.blade.php"
    exit 1
fi

echo "📦 Restaurando desde:"
echo "   - $SCRIPT_BACKUP"
echo "   - $COMPONENT_BACKUP"

# Restaurar archivos
cp "$SCRIPT_BACKUP" app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-scripts.blade.php
cp "$COMPONENT_BACKUP" app/Extensions/ChatbotSalesAgent/resources/views/sales-agent-component.blade.php

echo "✅ Rollback completado exitosamente"
echo ""
echo "Para aplicar cambios en producción:"
echo "1. Limpiar cache: php artisan cache:clear"
echo "2. Limpiar vistas: php artisan view:clear"
echo "3. Recargar la página del chatbot"











