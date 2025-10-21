#!/bin/bash

# 🔄 Script de Rollback - Limpieza de NeuronAI
# Ejecutar si algo sale mal durante la limpieza

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

BACKUP_DIR="backups/neuronai-cleanup-20251020-144924"

echo -e "${YELLOW}🔄 Iniciando rollback de limpieza de NeuronAI...${NC}"

if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}❌ Error: Directorio de backup no encontrado: $BACKUP_DIR${NC}"
    exit 1
fi

echo -e "${YELLOW}📂 Restaurando archivos desde backup...${NC}"

# Restaurar archivos
cp "$BACKUP_DIR/ProductAgent.php" app/Agents/
cp "$BACKUP_DIR/SalesAgent.php" app/Agents/
cp "$BACKUP_DIR/ChatcommerceWorkflow.php" app/Workflows/
cp "$BACKUP_DIR/TestController.php" app/Http/Controllers/
cp "$BACKUP_DIR/neuron.php" config/

echo -e "${GREEN}✅ Archivos restaurados${NC}"

# Limpiar cachés
echo -e "${YELLOW}🧹 Limpiando cachés...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}✅ Rollback completado exitosamente${NC}"
echo -e "${YELLOW}📝 Los archivos han sido restaurados a su estado original${NC}"
