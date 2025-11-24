#!/bin/bash

# 🔍 Script para Verificar Estado de Instancia Staging

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTANCE_ID="i-06113402909fd6b57"
SECURITY_GROUP_ID="sg-0933986b1aa1f35eb"

echo -e "${BLUE}🔍 Verificando Estado de Instancia Staging${NC}"
echo "=================================================="
echo ""

# Verificar si AWS CLI está instalado
if ! command -v aws &> /dev/null; then
    echo -e "${RED}❌ AWS CLI no está instalado${NC}"
    echo ""
    echo "Instala AWS CLI:"
    echo "  brew install awscli"
    echo ""
    echo "O descarga desde: https://aws.amazon.com/cli/"
    exit 1
fi

echo -e "${YELLOW}📊 Estado de la Instancia${NC}"
echo "----------------------------------------"

# Obtener información de la instancia
INSTANCE_INFO=$(aws ec2 describe-instances \
    --instance-ids $INSTANCE_ID \
    --query 'Reservations[0].Instances[0].[State.Name,PublicIpAddress,PrivateIpAddress,InstanceType]' \
    --output text 2>&1)

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error al obtener información de la instancia${NC}"
    echo "$INSTANCE_INFO"
    echo ""
    echo "Verifica:"
    echo "  - AWS CLI está configurado: aws configure"
    echo "  - Tienes permisos para EC2"
    echo "  - Instance ID es correcto: $INSTANCE_ID"
    exit 1
fi

# Parsear información
STATE=$(echo $INSTANCE_INFO | awk '{print $1}')
PUBLIC_IP=$(echo $INSTANCE_INFO | awk '{print $2}')
PRIVATE_IP=$(echo $INSTANCE_INFO | awk '{print $3}')
INSTANCE_TYPE=$(echo $INSTANCE_INFO | awk '{print $4}')

echo "Instance ID: $INSTANCE_ID"
echo "Estado: $STATE"
echo "IP Pública: ${PUBLIC_IP:-N/A}"
echo "IP Privada: ${PRIVATE_IP:-N/A}"
echo "Tipo: $INSTANCE_TYPE"
echo ""

# Verificar estado
if [ "$STATE" = "running" ]; then
    echo -e "${GREEN}✅ Instancia está corriendo${NC}"
    
    if [ -z "$PUBLIC_IP" ] || [ "$PUBLIC_IP" = "None" ]; then
        echo -e "${YELLOW}⚠️  No tiene IP pública asignada${NC}"
        echo "Asigna Elastic IP o verifica configuración de red"
    else
        echo -e "${GREEN}✅ IP pública: $PUBLIC_IP${NC}"
        
        # Verificar si es diferente a la esperada
        EXPECTED_IP="13.218.39.31"
        if [ "$PUBLIC_IP" != "$EXPECTED_IP" ]; then
            echo -e "${YELLOW}⚠️  IP pública cambió!${NC}"
            echo "Esperada: $EXPECTED_IP"
            echo "Actual: $PUBLIC_IP"
            echo ""
            echo "Actualiza STAGING_HOST en los scripts:"
            echo "  export STAGING_HOST=$PUBLIC_IP"
        fi
    fi
elif [ "$STATE" = "stopped" ]; then
    echo -e "${RED}❌ Instancia está detenida${NC}"
    echo ""
    echo "Para iniciarla:"
    echo "  aws ec2 start-instances --instance-ids $INSTANCE_ID"
    echo "  aws ec2 wait instance-running --instance-ids $INSTANCE_ID"
elif [ "$STATE" = "stopping" ] || [ "$STATE" = "pending" ]; then
    echo -e "${YELLOW}⏳ Instancia está $STATE${NC}"
    echo "Espera a que termine..."
else
    echo -e "${RED}❌ Estado desconocido: $STATE${NC}"
fi

echo ""
echo -e "${YELLOW}🔐 Reglas del Security Group${NC}"
echo "----------------------------------------"

# Obtener reglas del Security Group
SG_RULES=$(aws ec2 describe-security-groups \
    --group-ids $SECURITY_GROUP_ID \
    --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`]' \
    --output json 2>&1)

if echo "$SG_RULES" | grep -q "22"; then
    echo -e "${GREEN}✅ Regla SSH (puerto 22) encontrada${NC}"
    echo ""
    echo "Reglas SSH:"
    aws ec2 describe-security-groups \
        --group-ids $SECURITY_GROUP_ID \
        --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`].[FromPort,ToPort,IpRanges[0].CidrIp]' \
        --output table
else
    echo -e "${RED}❌ No se encontró regla SSH (puerto 22)${NC}"
    echo ""
    echo "Agrega regla SSH:"
    echo "  aws ec2 authorize-security-group-ingress \\"
    echo "    --group-id $SECURITY_GROUP_ID \\"
    echo "    --protocol tcp \\"
    echo "    --port 22 \\"
    echo "    --cidr 0.0.0.0/0"
fi

echo ""
echo -e "${YELLOW}🌐 Tu IP Actual${NC}"
echo "----------------------------------------"
MY_IP=$(curl -s ifconfig.me 2>/dev/null || echo "No disponible")
echo "Tu IP: $MY_IP"
echo ""

if [ "$MY_IP" != "No disponible" ]; then
    echo -e "${BLUE}💡 Para agregar regla SSH solo desde tu IP (más seguro):${NC}"
    echo ""
    echo "aws ec2 authorize-security-group-ingress \\"
    echo "  --group-id $SECURITY_GROUP_ID \\"
    echo "  --protocol tcp \\"
    echo "  --port 22 \\"
    echo "  --cidr $MY_IP/32"
    echo ""
fi

echo -e "${BLUE}💡 Para agregar regla SSH desde cualquier IP (staging):${NC}"
echo ""
echo "aws ec2 authorize-security-group-ingress \\"
echo "  --group-id $SECURITY_GROUP_ID \\"
echo "  --protocol tcp \\"
echo "  --port 22 \\"
echo "  --cidr 0.0.0.0/0"
echo ""

# Resumen
echo ""
echo -e "${BLUE}📋 Resumen${NC}"
echo "=================================================="
if [ "$STATE" = "running" ] && [ -n "$PUBLIC_IP" ] && [ "$PUBLIC_IP" != "None" ]; then
    echo -e "${GREEN}✅ Instancia lista para conectar${NC}"
    echo ""
    echo "Prueba conexión:"
    echo "  ssh -i staging-tausepro-key.pem ubuntu@$PUBLIC_IP"
    echo ""
    echo "O completa staging:"
    echo "  export STAGING_HOST=$PUBLIC_IP"
    echo "  ./scripts/completar-staging-no-interactive.sh"
else
    echo -e "${YELLOW}⚠️  Instancia no está lista${NC}"
    echo ""
    if [ "$STATE" != "running" ]; then
        echo "1. Inicia la instancia primero"
    fi
    if [ -z "$PUBLIC_IP" ] || [ "$PUBLIC_IP" = "None" ]; then
        echo "2. Asigna IP pública o Elastic IP"
    fi
fi
echo ""



