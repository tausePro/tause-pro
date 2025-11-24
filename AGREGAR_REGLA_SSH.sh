#!/bin/bash

# 🔧 Agregar Regla SSH a Security Group

INSTANCE_ID="i-0bbe91c13a1343538"

echo "🔧 Agregando regla SSH a Security Groups de instancia $INSTANCE_ID"
echo "=================================================="
echo ""

# Obtener Security Groups
SG_IDS=$(aws ec2 describe-instances \
    --instance-ids $INSTANCE_ID \
    --query 'Reservations[0].Instances[0].SecurityGroups[*].GroupId' \
    --output text 2>&1)

if [ $? -ne 0 ]; then
    echo "❌ Error al obtener Security Groups"
    echo "$SG_IDS"
    exit 1
fi

echo "Security Groups encontrados: $SG_IDS"
echo ""

for SG_ID in $SG_IDS; do
    echo "Agregando regla SSH a $SG_ID..."
    
    # Intentar agregar regla SSH desde 0.0.0.0/0
    RESULT=$(aws ec2 authorize-security-group-ingress \
        --group-id $SG_ID \
        --protocol tcp \
        --port 22 \
        --cidr 0.0.0.0/0 2>&1)
    
    if [ $? -eq 0 ]; then
        echo "✅ Regla SSH agregada a $SG_ID"
    else
        if echo "$RESULT" | grep -q "already exists"; then
            echo "ℹ️  Regla SSH ya existe en $SG_ID"
        else
            echo "❌ Error al agregar regla: $RESULT"
        fi
    fi
    echo ""
done

echo "✅ Proceso completado"
echo ""
echo "Prueba SSH ahora:"
echo "ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213"


