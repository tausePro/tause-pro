#!/bin/bash

# 🔐 Script para Agregar Regla SSH al Security Group de Staging

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SECURITY_GROUP_ID="sg-0933986b1aa1f35eb"

echo -e "${BLUE}🔐 Agregar Regla SSH al Security Group${NC}"
echo "=================================================="
echo ""

# Verificar AWS CLI
if ! command -v aws &> /dev/null; then
    echo -e "${RED}❌ AWS CLI no está instalado${NC}"
    exit 1
fi

# Obtener tu IP actual
echo -e "${YELLOW}🌐 Obteniendo tu IP actual...${NC}"
MY_IP=$(curl -s ifconfig.me 2>/dev/null || echo "")

if [ -z "$MY_IP" ]; then
    echo -e "${RED}❌ No se pudo obtener tu IP${NC}"
    echo "Usando 0.0.0.0/0 (permite desde cualquier IP)"
    CIDR="0.0.0.0/0"
    DESCRIPTION="SSH from anywhere (staging)"
else
    echo -e "${GREEN}✅ Tu IP: $MY_IP${NC}"
    echo ""
    echo "Opciones:"
    echo "1. Agregar regla solo desde tu IP ($MY_IP/32) - Más seguro"
    echo "2. Agregar regla desde cualquier IP (0.0.0.0/0) - Menos seguro pero funcional"
    echo ""
    read -p "Selecciona opción (1/2) [2]: " OPTION
    OPTION=${OPTION:-2}
    
    if [ "$OPTION" = "1" ]; then
        CIDR="$MY_IP/32"
        DESCRIPTION="SSH from $MY_IP"
    else
        CIDR="0.0.0.0/0"
        DESCRIPTION="SSH from anywhere (staging)"
    fi
fi

echo ""
echo -e "${YELLOW}📝 Agregando regla SSH...${NC}"
echo "Security Group: $SECURITY_GROUP_ID"
echo "CIDR: $CIDR"
echo ""

# Verificar si la regla ya existe
EXISTING_RULES=$(aws ec2 describe-security-groups \
    --group-ids $SECURITY_GROUP_ID \
    --query "SecurityGroups[0].IpPermissions[?FromPort==\`22\` && ToPort==\`22\` && Protocol==\`tcp\`]" \
    --output json 2>&1)

if echo "$EXISTING_RULES" | grep -q "$CIDR"; then
    echo -e "${YELLOW}⚠️  La regla SSH desde $CIDR ya existe${NC}"
    echo ""
    echo "Reglas SSH actuales:"
    aws ec2 describe-security-groups \
        --group-ids $SECURITY_GROUP_ID \
        --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`].[FromPort,ToPort,IpRanges[0].CidrIp]' \
        --output table
    echo ""
    echo -e "${GREEN}✅ No es necesario agregar la regla${NC}"
    exit 0
fi

# Agregar regla
RESULT=$(aws ec2 authorize-security-group-ingress \
    --group-id $SECURITY_GROUP_ID \
    --ip-permissions IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges="[{CidrIp=$CIDR,Description=\"$DESCRIPTION\"}]" \
    2>&1)

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Regla SSH agregada exitosamente${NC}"
    echo ""
    echo "Reglas SSH actuales:"
    aws ec2 describe-security-groups \
        --group-ids $SECURITY_GROUP_ID \
        --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`].[FromPort,ToPort,IpRanges[0].CidrIp]' \
        --output table
else
    if echo "$RESULT" | grep -q "already exists"; then
        echo -e "${YELLOW}⚠️  La regla ya existe${NC}"
    else
        echo -e "${RED}❌ Error al agregar regla:${NC}"
        echo "$RESULT"
        exit 1
    fi
fi

echo ""
echo -e "${GREEN}🎉 Listo!${NC}"
echo ""
echo "Ahora puedes probar la conexión:"
echo "  ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31"
echo ""



