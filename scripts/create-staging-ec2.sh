#!/bin/bash

# ☁️ Script para Crear Instancia EC2 para Staging
# Este script crea una nueva instancia EC2 usando AWS CLI

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}☁️ Crear Instancia EC2 para Staging${NC}"
echo "=================================================="

# Verificar que AWS CLI está instalado
if ! command -v aws &> /dev/null; then
    echo -e "${RED}❌ AWS CLI no está instalado${NC}"
    echo "Instala con: brew install awscli (macOS) o apt-get install awscli (Linux)"
    exit 1
fi

# Verificar configuración de AWS
if ! aws sts get-caller-identity &> /dev/null; then
    echo -e "${RED}❌ AWS CLI no está configurado${NC}"
    echo "Configura con: aws configure"
    exit 1
fi

# Configuración (ajustar según tu setup)
AMI_ID="${AMI_ID:-ami-0c55b159cbfafe1f0}"  # Ubuntu 24.04 LTS en us-east-1
INSTANCE_TYPE="${INSTANCE_TYPE:-t3.small}"
KEY_NAME="${KEY_NAME:-magicai-tause-key}"
SECURITY_GROUP="${SECURITY_GROUP:-staging-sg}"
SUBNET_ID="${SUBNET_ID:-}"  # Dejar vacío para usar default

echo -e "${YELLOW}📋 Configuración:${NC}"
echo "AMI: $AMI_ID"
echo "Tipo: $INSTANCE_TYPE"
echo "Key: $KEY_NAME"
echo "Security Group: $SECURITY_GROUP"
echo ""

read -p "¿Continuar con esta configuración? (s/N): " CONFIRM
if [[ ! $CONFIRM =~ ^[Ss]$ ]]; then
    echo "Cancelado."
    exit 0
fi

echo -e "${YELLOW}🔍 Verificando que el Security Group existe...${NC}"
if aws ec2 describe-security-groups --group-names "$SECURITY_GROUP" &> /dev/null; then
    echo -e "${GREEN}✅ Security Group encontrado${NC}"
else
    echo -e "${YELLOW}⚠️  Security Group no existe, creándolo...${NC}"
    
    # Obtener VPC ID por defecto
    VPC_ID=$(aws ec2 describe-vpcs --filters "Name=isDefault,Values=true" --query "Vpcs[0].VpcId" --output text)
    
    if [ "$VPC_ID" == "None" ] || [ -z "$VPC_ID" ]; then
        echo -e "${RED}❌ No se pudo encontrar VPC por defecto${NC}"
        echo "Por favor crea el Security Group manualmente desde la consola AWS"
        exit 1
    fi
    
    # Crear Security Group
    SG_ID=$(aws ec2 create-security-group \
        --group-name "$SECURITY_GROUP" \
        --description "Security group for staging environment" \
        --vpc-id "$VPC_ID" \
        --query 'GroupId' \
        --output text)
    
    echo -e "${GREEN}✅ Security Group creado: $SG_ID${NC}"
    
    # Agregar reglas
    echo -e "${YELLOW}🔧 Configurando reglas del Security Group...${NC}"
    aws ec2 authorize-security-group-ingress \
        --group-id "$SG_ID" \
        --protocol tcp \
        --port 22 \
        --cidr 0.0.0.0/0 &> /dev/null
    
    aws ec2 authorize-security-group-ingress \
        --group-id "$SG_ID" \
        --protocol tcp \
        --port 80 \
        --cidr 0.0.0.0/0 &> /dev/null
    
    aws ec2 authorize-security-group-ingress \
        --group-id "$SG_ID" \
        --protocol tcp \
        --port 443 \
        --cidr 0.0.0.0/0 &> /dev/null
    
    echo -e "${GREEN}✅ Reglas configuradas${NC}"
fi

# Obtener Security Group ID
SG_ID=$(aws ec2 describe-security-groups --group-names "$SECURITY_GROUP" --query "SecurityGroups[0].GroupId" --output text)

echo -e "${YELLOW}🚀 Creando instancia EC2...${NC}"

# Construir comando
LAUNCH_CMD="aws ec2 run-instances \
    --image-id $AMI_ID \
    --instance-type $INSTANCE_TYPE \
    --key-name $KEY_NAME \
    --security-group-ids $SG_ID \
    --associate-public-ip-address \
    --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=magicai-staging},{Key=Environment,Value=staging}]'"

if [ -n "$SUBNET_ID" ]; then
    LAUNCH_CMD="$LAUNCH_CMD --subnet-id $SUBNET_ID"
fi

# Crear instancia
INSTANCE_OUTPUT=$(eval $LAUNCH_CMD)
INSTANCE_ID=$(echo "$INSTANCE_OUTPUT" | grep -oP '"InstanceId":\s*"\K[^"]+')

echo -e "${GREEN}✅ Instancia creada: $INSTANCE_ID${NC}"

echo -e "${YELLOW}⏳ Esperando que la instancia esté corriendo...${NC}"
aws ec2 wait instance-running --instance-ids "$INSTANCE_ID"

# Obtener IP pública
PUBLIC_IP=$(aws ec2 describe-instances \
    --instance-ids "$INSTANCE_ID" \
    --query "Reservations[0].Instances[0].PublicIpAddress" \
    --output text)

echo ""
echo -e "${GREEN}🎉 Instancia EC2 creada exitosamente!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Información:${NC}"
echo "Instance ID: $INSTANCE_ID"
echo "IP Pública: $PUBLIC_IP"
echo "Security Group: $SECURITY_GROUP ($SG_ID)"
echo ""
echo -e "${BLUE}📝 Próximos pasos:${NC}"
echo "1. Exportar IP: export STAGING_HOST=$PUBLIC_IP"
echo "2. Configurar servidor: ./scripts/setup-staging-server.sh"
echo ""
echo -e "${YELLOW}⏱️  Espera 1-2 minutos antes de conectarte por SSH${NC}"
echo "Conectar: ssh -i $KEY_NAME.pem ubuntu@$PUBLIC_IP"



