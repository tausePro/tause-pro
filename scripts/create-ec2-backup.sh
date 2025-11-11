#!/bin/bash

# Script para crear snapshot/imagen de EC2 antes de deploy
# Ejecutar ANTES de cualquier cambio en producción
# Requiere AWS CLI configurado

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
INSTANCE_ID=$(curl -s http://169.254.169.254/latest/meta-data/instance-id 2>/dev/null || echo "")
REGION=$(curl -s http://169.254.169.254/latest/meta-data/placement/region 2>/dev/null || echo "us-east-1")

echo -e "${GREEN}📸 Creando snapshot/imagen de EC2 antes de deploy...${NC}"
echo ""

# Verificar que AWS CLI está instalado
if ! command -v aws &> /dev/null; then
    echo -e "${RED}❌ Error: AWS CLI no está instalado${NC}"
    echo "Instala con: sudo apt-get install awscli (o según tu sistema)"
    exit 1
fi

# Obtener Instance ID automáticamente (si estamos en EC2)
if [ -z "$INSTANCE_ID" ]; then
    echo -e "${YELLOW}⚠️  No se pudo detectar Instance ID automáticamente${NC}"
    read -p "Ingresa el Instance ID manualmente: " INSTANCE_ID
fi

if [ -z "$INSTANCE_ID" ]; then
    echo -e "${RED}❌ Error: Instance ID requerido${NC}"
    exit 1
fi

echo -e "${GREEN}🖥️  Instance ID: ${INSTANCE_ID}${NC}"
echo -e "${GREEN}🌍 Región: ${REGION}${NC}"
echo ""

# Obtener información de los volúmenes asociados
echo -e "${GREEN}📋 Obteniendo información de volúmenes...${NC}"
VOLUMES=$(aws ec2 describe-instances \
    --instance-ids "$INSTANCE_ID" \
    --region "$REGION" \
    --query 'Reservations[0].Instances[0].BlockDeviceMappings[*].Ebs.VolumeId' \
    --output text 2>/dev/null)

if [ -z "$VOLUMES" ] || [ "$VOLUMES" == "None" ]; then
    echo -e "${RED}❌ Error: No se pudieron obtener los volúmenes${NC}"
    echo "Verifica tus credenciales de AWS: aws configure"
    exit 1
fi

echo -e "${GREEN}💾 Volúmenes encontrados:${NC}"
echo "$VOLUMES" | tr '\t' '\n'
echo ""

# Crear snapshots de cada volumen
SNAPSHOT_IDS=()
for VOLUME_ID in $VOLUMES; do
    echo -e "${YELLOW}📸 Creando snapshot de volumen: ${VOLUME_ID}...${NC}"
    
    DESCRIPTION="Pre-deploy-backup-${TIMESTAMP}-volume-${VOLUME_ID}"
    TAGS="Key=Name,Value=Pre-Deploy-${TIMESTAMP} Key=Purpose,Value=Backup Key=Timestamp,Value=${TIMESTAMP}"
    
    SNAPSHOT_ID=$(aws ec2 create-snapshot \
        --volume-id "$VOLUME_ID" \
        --description "$DESCRIPTION" \
        --region "$REGION" \
        --tag-specifications "ResourceType=snapshot,Tags=[{Key=Name,Value=Pre-Deploy-${TIMESTAMP}},{Key=Purpose,Value=Backup},{Key=Timestamp,Value=${TIMESTAMP}}]" \
        --query 'SnapshotId' \
        --output text 2>/dev/null)
    
    if [ ! -z "$SNAPSHOT_ID" ] && [ "$SNAPSHOT_ID" != "None" ]; then
        SNAPSHOT_IDS+=("$SNAPSHOT_ID")
        echo -e "${GREEN}✅ Snapshot creado: ${SNAPSHOT_ID}${NC}"
    else
        echo -e "${RED}❌ Error al crear snapshot de ${VOLUME_ID}${NC}"
    fi
done

echo ""

# Crear AMI (imagen completa de la instancia)
echo -e "${YELLOW}🖼️  ¿Deseas crear una AMI (imagen completa) de la instancia? (yes/no)${NC}"
read -p "Respuesta: " CREATE_AMI

if [ "$CREATE_AMI" == "yes" ]; then
    AMI_NAME="Pre-Deploy-Backup-${TIMESTAMP}"
    AMI_DESCRIPTION="Backup completo antes de deploy - ${TIMESTAMP}"
    
    echo -e "${GREEN}🖼️  Creando AMI: ${AMI_NAME}...${NC}"
    
    AMI_ID=$(aws ec2 create-image \
        --instance-id "$INSTANCE_ID" \
        --name "$AMI_NAME" \
        --description "$AMI_DESCRIPTION" \
        --no-reboot \
        --region "$REGION" \
        --query 'ImageId' \
        --output text 2>/dev/null)
    
    if [ ! -z "$AMI_ID" ] && [ "$AMI_ID" != "None" ]; then
        echo -e "${GREEN}✅ AMI creada: ${AMI_ID}${NC}"
        
        # Agregar tags a la AMI
        aws ec2 create-tags \
            --resources "$AMI_ID" \
            --tags Key=Name,Value="$AMI_NAME" Key=Purpose,Value=Backup Key=Timestamp,Value="$TIMESTAMP" \
            --region "$REGION" 2>/dev/null || true
    else
        echo -e "${RED}❌ Error al crear AMI${NC}"
    fi
else
    AMI_ID=""
fi

# Guardar información del backup
BACKUP_INFO_FILE="/backups/ec2-backup-${TIMESTAMP}.txt"
mkdir -p /backups
cat > "$BACKUP_INFO_FILE" << EOF
========================================
EC2 BACKUP PRE-DEPLOY
========================================
Fecha: $(date)
Timestamp: ${TIMESTAMP}
Instance ID: ${INSTANCE_ID}
Región: ${REGION}

SNAPSHOTS CREADOS:
$(for id in "${SNAPSHOT_IDS[@]}"; do echo "- $id"; done)

AMI CREADA:
${AMI_ID:-"No creada"}

========================================
PARA RESTAURAR DESDE SNAPSHOT:
1. Crear nuevo volumen desde snapshot
2. Detener instancia
3. Desasociar volumen actual
4. Asociar nuevo volumen
5. Iniciar instancia

PARA RESTAURAR DESDE AMI:
1. Detener instancia actual
2. Crear nueva instancia desde AMI: ${AMI_ID}
3. O reemplazar root volume con AMI

COMANDOS ÚTILES:
# Ver snapshots:
aws ec2 describe-snapshots --snapshot-ids ${SNAPSHOT_IDS[@]} --region ${REGION}

# Ver AMI:
aws ec2 describe-images --image-ids ${AMI_ID} --region ${REGION}

# Eliminar snapshots (cuando ya no se necesiten):
aws ec2 delete-snapshot --snapshot-id [SNAPSHOT_ID] --region ${REGION}

# Eliminar AMI (cuando ya no se necesite):
aws ec2 deregister-image --image-id ${AMI_ID} --region ${REGION}
========================================
EOF

echo ""
echo -e "${GREEN}✅ Proceso completado${NC}"
echo -e "${GREEN}📋 Información guardada en: ${BACKUP_INFO_FILE}${NC}"
echo ""
echo -e "${YELLOW}📊 Resumen:${NC}"
echo "  - Snapshots creados: ${#SNAPSHOT_IDS[@]}"
if [ ! -z "$AMI_ID" ]; then
    echo "  - AMI creada: ${AMI_ID}"
fi
echo ""
echo -e "${YELLOW}⏳ Nota: Los snapshots pueden tardar varios minutos en completarse${NC}"
echo -e "${YELLOW}💡 Puedes verificar el estado con: aws ec2 describe-snapshots --snapshot-ids [ID] --region ${REGION}${NC}"

