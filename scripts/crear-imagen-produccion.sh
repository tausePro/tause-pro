#!/bin/bash

# 📸 Script para Crear Imagen de Producción

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PROD_IP="34.207.248.220"
IMAGE_NAME="produccion-magicai-$(date +%Y%m%d)-con-fase1"
IMAGE_DESCRIPTION="Backup con cambios Fase 1 - Agent Orchestrator mejorado - $(date '+%Y-%m-%d %H:%M')"

echo -e "${BLUE}📸 Crear Imagen de Producción${NC}"
echo "=================================================="
echo ""

# Obtener Instance ID
echo -e "${YELLOW}🔍 Obteniendo Instance ID...${NC}"
INSTANCE_ID=$(aws ec2 describe-instances \
  --filters "Name=ip-address,Values=$PROD_IP" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text 2>&1)

if [ -z "$INSTANCE_ID" ] || [ "$INSTANCE_ID" == "None" ]; then
    echo -e "${RED}❌ No se encontró la instancia de producción${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Instancia encontrada: $INSTANCE_ID${NC}"
echo ""

# Verificar estado
echo -e "${YELLOW}🔍 Verificando estado...${NC}"
STATE=$(aws ec2 describe-instances \
  --instance-ids $INSTANCE_ID \
  --query 'Reservations[0].Instances[0].State.Name' \
  --output text)

if [ "$STATE" != "running" ]; then
    echo -e "${RED}❌ La instancia no está corriendo (estado: $STATE)${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Instancia corriendo${NC}"
echo ""

# Crear imagen
echo -e "${YELLOW}📸 Creando imagen...${NC}"
echo "Nombre: $IMAGE_NAME"
echo "Descripción: $IMAGE_DESCRIPTION"
echo ""

IMAGE_ID=$(aws ec2 create-image \
  --instance-id $INSTANCE_ID \
  --name "$IMAGE_NAME" \
  --description "$IMAGE_DESCRIPTION" \
  --query 'ImageId' \
  --output text 2>&1)

if [ $? -eq 0 ] && [ -n "$IMAGE_ID" ]; then
    echo -e "${GREEN}✅ Imagen creada exitosamente!${NC}"
    echo ""
    echo -e "${BLUE}📋 Detalles:${NC}"
    echo "  Image ID: $IMAGE_ID"
    echo "  Nombre: $IMAGE_NAME"
    echo ""
    echo -e "${YELLOW}⏳ La imagen está siendo creada...${NC}"
    echo "  Esto puede tomar 10-15 minutos"
    echo ""
    echo -e "${BLUE}💡 Para verificar el estado:${NC}"
    echo "  aws ec2 describe-images --image-ids $IMAGE_ID --query 'Images[0].State' --output text"
    echo ""
    echo -e "${BLUE}💡 Para ver cuando esté lista:${NC}"
    echo "  aws ec2 wait image-available --image-ids $IMAGE_ID"
    echo ""
else
    echo -e "${RED}❌ Error al crear imagen${NC}"
    exit 1
fi



