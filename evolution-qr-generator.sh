#!/bin/bash

# Configuración
EVOLUTION_URL="http://13.220.81.41:8080"
API_KEY="tause2024SecretKey"
INSTANCE_NAME="tausepro_main"

echo "🚀 Generando QR para WhatsApp..."
echo ""

# Paso 1: Crear instancia
echo "📱 Creando instancia: $INSTANCE_NAME"
CREATE_RESPONSE=$(curl -s -X POST "$EVOLUTION_URL/instance/create" \
  -H "Content-Type: application/json" \
  -H "apikey: $API_KEY" \
  -d "{
    \"instanceName\": \"$INSTANCE_NAME\",
    \"qrcode\": true,
    \"integration\": \"WHATSAPP-BAILEYS\"
  }")

echo "✅ Instancia creada"
echo "$CREATE_RESPONSE" | jq '.' 2>/dev/null || echo "$CREATE_RESPONSE"
echo ""

# Paso 2: Esperar 5 segundos
echo "⏳ Esperando 5 segundos para que se genere el QR..."
sleep 5

# Paso 3: Obtener QR
echo "📸 Obteniendo código QR..."
QR_RESPONSE=$(curl -s -X GET "$EVOLUTION_URL/instance/connect/$INSTANCE_NAME" \
  -H "apikey: $API_KEY")

echo "$QR_RESPONSE" | jq '.' 2>/dev/null || echo "$QR_RESPONSE"
echo ""

# Paso 4: Extraer base64 del QR
QR_BASE64=$(echo "$QR_RESPONSE" | jq -r '.qrcode.base64' 2>/dev/null)

if [ "$QR_BASE64" != "null" ] && [ -n "$QR_BASE64" ]; then
    echo "✅ QR generado exitosamente!"
    echo ""
    echo "🔗 Opciones para escanear:"
    echo "1. Abre este link en tu navegador:"
    echo "   https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=$QR_BASE64"
    echo ""
    echo "2. O guarda la imagen:"
    echo "$QR_BASE64" | base64 -d > whatsapp-qr.png
    echo "   Imagen guardada en: whatsapp-qr.png"
    echo ""
    echo "📱 Escanea el QR con WhatsApp:"
    echo "   WhatsApp > Configuración > Dispositivos vinculados > Vincular dispositivo"
else
    echo "❌ No se pudo generar el QR"
    echo "Respuesta completa:"
    echo "$QR_RESPONSE"
    echo ""
    echo "🔍 Verificando estado de la instancia..."
    curl -s -X GET "$EVOLUTION_URL/instance/fetchInstances?instanceName=$INSTANCE_NAME" \
      -H "apikey: $API_KEY" | jq '.'
fi

echo ""
echo "📊 Estado de la instancia:"
curl -s -X GET "$EVOLUTION_URL/instance/connectionState/$INSTANCE_NAME" \
  -H "apikey: $API_KEY" | jq '.'
