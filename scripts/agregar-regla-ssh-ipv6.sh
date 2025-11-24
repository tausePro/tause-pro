#!/bin/bash

# 🔐 Script para Agregar Regla SSH IPv6 al Security Group

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SECURITY_GROUP_ID="sg-0933986b1aa1f35eb"

echo -e "${BLUE}🔐 Agregar Regla SSH IPv6 al Security Group${NC}"
echo "=================================================="
echo ""

# Verificar AWS CLI
if ! command -v aws &> /dev/null; then
    echo -e "${RED}❌ AWS CLI no está instalado${NC}"
    exit 1
fi

# Obtener tu IP IPv6
echo -e "${YELLOW}🌐 Obteniendo tu IP IPv6...${NC}"
MY_IPV6=$(curl -6 -s ifconfig.me 2>/dev/null || echo "")

if [ -z "$MY_IPV6" ]; then
    echo -e "${RED}❌ No se pudo obtener tu IP IPv6${NC}"
    echo ""
    echo "Usando ::/0 (permite desde cualquier IPv6)"
    CIDR_V6="::/0"
    DESCRIPTION="SSH from anywhere IPv6 (staging)"
else
    echo -e "${GREEN}✅ Tu IP IPv6: $MY_IPV6${NC}"
    echo ""
    echo "Opciones:"
    echo "1. Agregar regla solo desde tu IP IPv6 ($MY_IPV6/128) - Más seguro"
    echo "2. Agregar regla desde cualquier IPv6 (::/0) - Menos seguro pero funcional"
    echo ""
    read -p "Selecciona opción (1/2) [2]: " OPTION
    OPTION=${OPTION:-2}
    
    if [ "$OPTION" = "1" ]; then
        CIDR_V6="$MY_IPV6/128"
        DESCRIPTION="SSH from $MY_IPV6"
    else
        CIDR_V6="::/0"
        DESCRIPTION="SSH from anywhere IPv6 (staging)"
    fi
fi

echo ""
echo -e "${YELLOW}📝 Agregando regla SSH IPv6...${NC}"
echo "Security Group: $SECURITY_GROUP_ID"
echo "CIDR IPv6: $CIDR_V6"
echo ""

# Agregar regla IPv6
RESULT=$(aws ec2 authorize-security-group-ingress \
    --group-id $SECURITY_GROUP_ID \
    --ip-permissions IpProtocol=tcp,FromPort=22,ToPort=22,Ipv6Ranges="[{CidrIpv6=$CIDR_V6,Description=\"$DESCRIPTION\"}]" \
    2>&1)

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Regla SSH IPv6 agregada exitosamente${NC}"
    echo ""
    echo "Reglas SSH actuales (IPv4):"
    aws ec2 describe-security-groups \
        --group-ids $SECURITY_GROUP_ID \
        --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`].[FromPort,ToPort,IpRanges[0].CidrIp]' \
        --output table
    echo ""
    echo "Reglas SSH actuales (IPv6):"
    aws ec2 describe-security-groups \
        --group-ids $SECURITY_GROUP_ID \
        --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`].[FromPort,ToPort,Ipv6Ranges[0].CidrIpv6]' \
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
echo -e "${BLUE}💡 Nota:${NC}"
echo "Si aún no puedes conectar, prueba usar IPv4:"
echo "  ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31"
echo ""



