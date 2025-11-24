#!/bin/bash

# 🚀 Script para Crear Instancia Limpia Staging

set -e

echo "🚀 Crear Instancia Limpia Staging"
echo "=================================================="
echo ""

# Configuración
INSTANCE_NAME="staging-tausepro-clean"
INSTANCE_TYPE="t3.small"
AMI_ID="ami-0c55b159cbfafe1f0"  # Ubuntu 22.04 LTS us-east-1 (verificar)
KEY_NAME="staging-tausepro-key"  # O crear nueva
SECURITY_GROUP_NAME="staging-clean-sg"

echo "📋 Configuración:"
echo "  Nombre: $INSTANCE_NAME"
echo "  Tipo: $INSTANCE_TYPE"
echo "  AMI: $AMI_ID"
echo "  Key: $KEY_NAME"
echo ""

read -p "¿Continuar? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelado."
    exit 1
fi

echo ""
echo "🔍 Verificando configuración..."

# Verificar AWS CLI
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI no está instalado"
    exit 1
fi

# Obtener VPC ID (usar default o especificar)
VPC_ID=$(aws ec2 describe-vpcs --filters "Name=isDefault,Values=true" --query 'Vpcs[0].VpcId' --output text 2>/dev/null || echo "")

if [ -z "$VPC_ID" ]; then
    echo "⚠️  No se encontró VPC default. Especifica VPC_ID manualmente."
    exit 1
fi

echo "✅ VPC: $VPC_ID"

# Obtener subnet pública
SUBNET_ID=$(aws ec2 describe-subnets \
    --filters "Name=vpc-id,Values=$VPC_ID" "Name=map-public-ip-on-launch,Values=true" \
    --query 'Subnets[0].SubnetId' \
    --output text 2>/dev/null || echo "")

if [ -z "$SUBNET_ID" ]; then
    echo "⚠️  No se encontró subnet pública. Especifica SUBNET_ID manualmente."
    exit 1
fi

echo "✅ Subnet: $SUBNET_ID"

# Crear Security Group
echo ""
echo "🔐 Creando Security Group..."
SG_ID=$(aws ec2 create-security-group \
    --group-name "$SECURITY_GROUP_NAME" \
    --description "Security group for staging clean instance" \
    --vpc-id "$VPC_ID" \
    --query 'GroupId' \
    --output text 2>&1)

if [ $? -ne 0 ]; then
    # Si ya existe, obtener ID
    SG_ID=$(aws ec2 describe-security-groups \
        --filters "Name=group-name,Values=$SECURITY_GROUP_NAME" "Name=vpc-id,Values=$VPC_ID" \
        --query 'SecurityGroups[0].GroupId' \
        --output text)
    echo "ℹ️  Security Group ya existe: $SG_ID"
else
    echo "✅ Security Group creado: $SG_ID"
fi

# Agregar reglas
echo "🔐 Agregando reglas al Security Group..."
aws ec2 authorize-security-group-ingress \
    --group-id "$SG_ID" \
    --protocol tcp \
    --port 22 \
    --cidr 0.0.0.0/0 2>/dev/null || echo "  SSH ya existe"

aws ec2 authorize-security-group-ingress \
    --group-id "$SG_ID" \
    --protocol tcp \
    --port 80 \
    --cidr 0.0.0.0/0 2>/dev/null || echo "  HTTP ya existe"

aws ec2 authorize-security-group-ingress \
    --group-id "$SG_ID" \
    --protocol tcp \
    --port 443 \
    --cidr 0.0.0.0/0 2>/dev/null || echo "  HTTPS ya existe"

# Crear instancia
echo ""
echo "🚀 Creando instancia..."
INSTANCE_ID=$(aws ec2 run-instances \
    --image-id "$AMI_ID" \
    --instance-type "$INSTANCE_TYPE" \
    --key-name "$KEY_NAME" \
    --subnet-id "$SUBNET_ID" \
    --security-group-ids "$SG_ID" \
    --associate-public-ip-address \
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=$INSTANCE_NAME}]" \
    --query 'Instances[0].InstanceId' \
    --output text)

echo "✅ Instancia creada: $INSTANCE_ID"

# Esperar a que esté running
echo ""
echo "⏳ Esperando a que instancia esté Running..."
aws ec2 wait instance-running --instance-ids "$INSTANCE_ID"

# Obtener IP pública
PUBLIC_IP=$(aws ec2 describe-instances \
    --instance-ids "$INSTANCE_ID" \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text)

echo "✅ Instancia Running"
echo "✅ IP Pública: $PUBLIC_IP"

echo ""
echo "📋 Próximos pasos:"
echo "1. Asociar Elastic IP 3.220.198.180 a esta instancia"
echo "2. Conectar: ssh -4 -i staging-tausepro-key.pem ubuntu@$PUBLIC_IP"
echo "3. Seguir guía: CREAR_INSTANCIA_LIMPIA_STAGING.md"
echo ""
echo "Instance ID: $INSTANCE_ID"
echo "Security Group: $SG_ID"


