#!/bin/bash

# 🔍 Verificar Security Groups de Instancia Staging

INSTANCE_ID="i-0bbe91c13a1343538"
PUBLIC_IP="44.211.83.213"

echo "🔍 Verificando Security Groups para instancia $INSTANCE_ID"
echo "=================================================="
echo ""

# Verificar si AWS CLI está instalado
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI no está instalado"
    echo ""
    echo "Instala: brew install awscli"
    exit 1
fi

# Obtener Security Groups de la instancia
echo "📊 Security Groups asociados a la instancia:"
echo "----------------------------------------"
SG_INFO=$(aws ec2 describe-instances \
    --instance-ids $INSTANCE_ID \
    --query 'Reservations[0].Instances[0].SecurityGroups[*].[GroupId,GroupName]' \
    --output table 2>&1)

if [ $? -ne 0 ]; then
    echo "❌ Error al obtener Security Groups"
    echo "$SG_INFO"
    exit 1
fi

echo "$SG_INFO"
echo ""

# Obtener IDs de Security Groups
SG_IDS=$(aws ec2 describe-instances \
    --instance-ids $INSTANCE_ID \
    --query 'Reservations[0].Instances[0].SecurityGroups[*].GroupId' \
    --output text)

echo "🔐 Verificando reglas SSH (puerto 22) en cada Security Group:"
echo "----------------------------------------"

for SG_ID in $SG_IDS; do
    echo ""
    echo "Security Group: $SG_ID"
    echo "----------------------------------------"
    
    # Ver reglas SSH
    SSH_RULES=$(aws ec2 describe-security-groups \
        --group-ids $SG_ID \
        --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`]' \
        --output json 2>&1)
    
    if echo "$SSH_RULES" | grep -q '"FromPort": 22'; then
        echo "✅ Regla SSH encontrada:"
        echo "$SSH_RULES" | jq -r '.[] | "   Puerto: \(.FromPort)-\(.ToPort), Protocolo: \(.IpProtocol), CIDR: \(.IpRanges[].CidrIp // "N/A")"'
        
        # Verificar si hay regla desde 0.0.0.0/0
        if echo "$SSH_RULES" | grep -q "0.0.0.0/0"; then
            echo "   ✅ Tiene regla desde 0.0.0.0/0 (cualquier IP)"
        else
            echo "   ⚠️  NO tiene regla desde 0.0.0.0/0"
            echo ""
            echo "   💡 Para agregar regla SSH desde cualquier IP:"
            echo "   aws ec2 authorize-security-group-ingress \\"
            echo "     --group-id $SG_ID \\"
            echo "     --protocol tcp \\"
            echo "     --port 22 \\"
            echo "     --cidr 0.0.0.0/0"
        fi
    else
        echo "❌ NO tiene regla SSH (puerto 22)"
        echo ""
        echo "💡 Para agregar regla SSH:"
        echo "aws ec2 authorize-security-group-ingress \\"
        echo "  --group-id $SG_ID \\"
        echo "  --protocol tcp \\"
        echo "  --port 22 \\"
        echo "  --cidr 0.0.0.0/0"
    fi
done

echo ""
echo "🌐 Tu IP actual:"
MY_IP=$(curl -s ifconfig.me 2>/dev/null || echo "No disponible")
echo "   $MY_IP"
echo ""

echo "📋 Todas las reglas Inbound de los Security Groups:"
echo "----------------------------------------"
for SG_ID in $SG_IDS; do
    echo ""
    echo "Security Group: $SG_ID"
    aws ec2 describe-security-groups \
        --group-ids $SG_ID \
        --query 'SecurityGroups[0].IpPermissions[*].[FromPort,ToPort,IpProtocol,IpRanges[0].CidrIp]' \
        --output table
done


