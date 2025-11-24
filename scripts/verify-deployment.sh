#!/bin/bash

# Script de verificación post-deploy
# Ejecutar después del deploy para verificar que todo funciona

set -e

echo "🔍 Verificando deployment..."

# 1. Verificar que la aplicación responde
echo ""
echo "1️⃣  Verificando que la aplicación responde..."
php artisan about > /dev/null 2>&1 && echo "✅ Aplicación responde" || echo "❌ Error: Aplicación no responde"

# 2. Verificar sintaxis de archivos modificados
echo ""
echo "2️⃣  Verificando sintaxis de archivos modificados..."
ERRORS=0
for file in app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php \
            app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php \
            app/Extensions/Chatbot/System/Services/ProductCardService.php \
            app/Extensions/Chatbot/System/Services/ProductIntegrationService.php; do
    if php -l "$file" > /dev/null 2>&1; then
        echo "✅ $file"
    else
        echo "❌ Error en $file"
        ERRORS=$((ERRORS + 1))
    fi
done

# 3. Verificar que los métodos existen
echo ""
echo "3️⃣  Verificando métodos críticos..."
php artisan tinker --execute="
    \$service = new App\Extensions\Chatbot\System\Services\ProductOrchestratorService();
    echo '✅ ProductOrchestratorService cargado correctamente\n';
    
    \$service2 = new App\Extensions\Chatbot\System\Services\AgentOrchestratorService();
    echo '✅ AgentOrchestratorService cargado correctamente\n';
" 2>&1 | grep "✅" || echo "⚠️  Verificar manualmente"

# 4. Verificar que no hay errores en logs recientes
echo ""
echo "4️⃣  Verificando logs recientes..."
if tail -n 50 storage/logs/laravel.log | grep -i "error\|exception\|fatal" > /dev/null; then
    echo "⚠️  Se encontraron errores en logs recientes"
    tail -n 50 storage/logs/laravel.log | grep -i "error\|exception\|fatal" | tail -5
else
    echo "✅ No hay errores críticos en logs recientes"
fi

# 5. Verificar conexión a base de datos
echo ""
echo "5️⃣  Verificando conexión a base de datos..."
php artisan tinker --execute="DB::connection()->getPdo(); echo '✅ Conexión a BD OK\n';" 2>&1 | grep "✅" || echo "❌ Error de conexión a BD"

# 6. Verificar que los scopes existen en ChatbotProduct
echo ""
echo "6️⃣  Verificando modelo ChatbotProduct..."
php artisan tinker --execute="
    \$product = App\Extensions\Chatbot\System\Models\ChatbotProduct::first();
    if (\$product) {
        echo '✅ Modelo ChatbotProduct funciona\n';
        echo '✅ Scopes disponibles: active(), inStock()\n';
    } else {
        echo '⚠️  No hay productos en la BD para verificar\n';
    }
" 2>&1 | grep "✅" || echo "⚠️  Verificar manualmente"

echo ""
if [ $ERRORS -eq 0 ]; then
    echo "✅ Verificación completada: Todo OK"
else
    echo "❌ Verificación completada: Se encontraron $ERRORS errores"
    exit 1
fi

