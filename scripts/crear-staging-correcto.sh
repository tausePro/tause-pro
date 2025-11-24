#!/bin/bash

# 🚀 Script para Crear Staging Correctamente desde AMI
# Este script crea una instancia SIN User Data que habilite firewall

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
AMI_ID="${AMI_ID:-ami-00cee3e99312902ad}"  # "antes de actualizar agentes"
INSTANCE_TYPE="${INSTANCE_TYPE:-t3.small}"
KEY_NAME="${KEY_NAME:-staging-tausepro-key}"
SECURITY_GROUP="${SECURITY_GROUP:-sg-0933986b1aa1f35eb}"  # Ajustar según tu SG
SUBNET_ID="${SUBNET_ID:-subnet-0699318e89a95a034}"  # Ajustar según tu subnet

echo -e "${BLUE}🚀 Creando Staging desde AMI${NC}"
echo "=================================================="
echo ""
echo "Configuración:"
echo "  AMI: $AMI_ID"
echo "  Tipo: $INSTANCE_TYPE"
echo "  Key: $KEY_NAME"
echo "  Security Group: $SECURITY_GROUP"
echo "  Subnet: $SUBNET_ID"
echo ""

# Leer User Data (sin firewall)
USER_DATA_FILE="scripts/user-data-sin-firewall.sh"
if [ ! -f "$USER_DATA_FILE" ]; then
    echo -e "${RED}❌ Error: No se encontró $USER_DATA_FILE${NC}"
    exit 1
fi

USER_DATA=$(cat "$USER_DATA_FILE" | base64)

# Crear instancia
echo -e "${YELLOW}📦 Creando instancia...${NC}"
INSTANCE_OUTPUT=$(aws ec2 run-instances \
    --image-id "$AMI_ID" \
    --instance-type "$INSTANCE_TYPE" \
    --key-name "$KEY_NAME" \
    --security-group-ids "$SECURITY_GROUP" \
    --subnet-id "$SUBNET_ID" \
    --associate-public-ip-address \
    --user-data "$USER_DATA" \
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=staging-magicai-final},{Key=Environment,Value=staging}]" \
    --output json 2>&1)

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Error al crear instancia:${NC}"
    echo "$INSTANCE_OUTPUT"
    exit 1
fi

INSTANCE_ID=$(echo "$INSTANCE_OUTPUT" | jq -r '.Instances[0].InstanceId')

if [ -z "$INSTANCE_ID" ] || [ "$INSTANCE_ID" == "null" ]; then
    echo -e "${RED}❌ Error: No se pudo obtener Instance ID${NC}"
    echo "$INSTANCE_OUTPUT"
    exit 1
fi

echo -e "${GREEN}✅ Instancia creada: $INSTANCE_ID${NC}"
echo ""

# Esperar a que esté corriendo
echo -e "${YELLOW}⏳ Esperando a que la instancia esté corriendo...${NC}"
aws ec2 wait instance-running --instance-ids "$INSTANCE_ID"

# Obtener IP pública
echo -e "${YELLOW}📡 Obteniendo IP pública...${NC}"
PUBLIC_IP=$(aws ec2 describe-instances \
    --instance-ids "$INSTANCE_ID" \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text)

if [ -z "$PUBLIC_IP" ] || [ "$PUBLIC_IP" == "None" ]; then
    echo -e "${YELLOW}⚠️  No se obtuvo IP pública. Esperando 30 segundos más...${NC}"
    sleep 30
    PUBLIC_IP=$(aws ec2 describe-instances \
        --instance-ids "$INSTANCE_ID" \
        --query 'Reservations[0].Instances[0].PublicIpAddress' \
        --output text)
fi

if [ -z "$PUBLIC_IP" ] || [ "$PUBLIC_IP" == "None" ]; then
    echo -e "${RED}❌ Error: No se pudo obtener IP pública${NC}"
    echo "Instance ID: $INSTANCE_ID"
    exit 1
fi

echo ""
echo -e "${GREEN}✅ Instancia lista!${NC}"
echo "=================================================="
echo ""
echo "Instance ID: $INSTANCE_ID"
echo "IP Pública:  $PUBLIC_IP"
echo ""
echo -e "${BLUE}📋 Próximos pasos:${NC}"
echo ""
echo "1. Conectar vía SSH (debería funcionar inmediatamente):"
echo -e "   ${YELLOW}ssh -4 -i staging-tausepro-key.pem ubuntu@$PUBLIC_IP${NC}"
echo ""
echo "2. Una vez conectado, ejecutar configuración:"
echo "   Ver: GUIA_COMPLETA_CREAR_STAGING_DESDE_AMI.md"
echo ""
echo "3. O usar script de configuración automática:"
echo "   ./scripts/configurar-staging-manual.sh $PUBLIC_IP"
echo ""
echo -e "${GREEN}🎉 Instancia creada correctamente!${NC}"



