#!/bin/bash

# Configuración
EVOLUTION_IP="13.220.81.41"
EVOLUTION_URL="http://$EVOLUTION_IP:8080"
API_KEY="tause2024SecretKey"

echo "🔍 Verificando upgrade de Evolution API..."
echo ""

# Esperar a que la instancia esté online
echo "⏳ Esperando a que la instancia esté online..."
MAX_ATTEMPTS=30
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if ping -c 1 -W 2 $EVOLUTION_IP > /dev/null 2>&1; then
        echo "✅ Instancia online!"
        break
    fi
    ATTEMPT=$((ATTEMPT + 1))
    echo "   Intento $ATTEMPT/$MAX_ATTEMPTS..."
    sleep 10
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "❌ Timeout esperando la instancia"
    exit 1
fi

echo ""
echo "⏳ Esperando a que Evolution API esté listo..."
sleep 15

# Verificar recursos
echo ""
echo "📊 RECURSOS DEL SISTEMA:"
ssh -i evolution-key.pem ubuntu@$EVOLUTION_IP "
echo '=== MEMORIA ==='
free -h
echo ''
echo '=== CPU ==='
nproc
echo ''
echo '=== TIPO DE INSTANCIA ==='
curl -s http://169.254.169.254/latest/meta-data/instance-type
echo ''
"

echo ""
echo "🐳 ESTADO DE DOCKER:"
ssh -i evolution-key.pem ubuntu@$EVOLUTION_IP "docker ps"

echo ""
echo "📊 USO DE RECURSOS:"
ssh -i evolution-key.pem ubuntu@$EVOLUTION_IP "docker stats --no-stream"

echo ""
echo "🌐 VERIFICANDO API:"
RESPONSE=$(curl -s -w "\n%{http_code}" $EVOLUTION_URL)
HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
BODY=$(echo "$RESPONSE" | head -n -1)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Evolution API funcionando correctamente"
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
else
    echo "❌ Evolution API no responde correctamente (HTTP $HTTP_CODE)"
fi

echo ""
echo "✅ VERIFICACIÓN COMPLETA"
echo ""
echo "📝 PRÓXIMOS PASOS:"
echo "1. Crear instancia de WhatsApp"
echo "2. Generar QR"
echo "3. Conectar con TausePro"
