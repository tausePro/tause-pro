#!/bin/bash

# Release 002 - Rollback Script
# Revierte los cambios en el sistema de inyección de URLs

set -e

echo "🔄 ROLLBACK - Release 002: Fix URL Injection System"
echo "=================================================="
echo ""

# Variables
BACKUP_FILE="deploy/releases/release-002-fix-url-injection/ChatbotApplicationController.php.backup"
TARGET_FILE="app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php"
REMOTE_TARGET="/var/www/magicai/app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php"

# Verificar que existe el backup
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ ERROR: No se encontró el archivo de backup"
    echo "   Esperado en: $BACKUP_FILE"
    exit 1
fi

echo "📦 Backup encontrado: $BACKUP_FILE"
echo ""

# Confirmar rollback
read -p "⚠️  ¿Confirmas que deseas hacer ROLLBACK? (si/no): " confirm
if [ "$confirm" != "si" ]; then
    echo "❌ Rollback cancelado"
    exit 0
fi

echo ""
echo "🔄 Iniciando rollback..."
echo ""

# 1. Subir backup al servidor
echo "1️⃣ Subiendo versión anterior al servidor..."
scp -i magicai-tause-key.pem "$BACKUP_FILE" ubuntu@34.207.248.220:/home/ubuntu/ChatbotApplicationController.php

if [ $? -ne 0 ]; then
    echo "❌ ERROR: Fallo al subir el backup"
    exit 1
fi

# 2. Reemplazar archivo en producción
echo "2️⃣ Restaurando versión anterior en producción..."
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 "sudo mv /home/ubuntu/ChatbotApplicationController.php $REMOTE_TARGET"

if [ $? -ne 0 ]; then
    echo "❌ ERROR: Fallo al restaurar archivo"
    exit 1
fi

# 3. Limpiar cache (opcional)
echo "3️⃣ Limpiando cache..."
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 "cd /var/www/magicai && php artisan optimize:clear" || true

echo ""
echo "✅ ROLLBACK COMPLETADO"
echo ""
echo "📋 Verificación recomendada:"
echo "   1. Probar chatbot en https://aliviate.com.co"
echo "   2. Revisar logs si es necesario"
echo ""
