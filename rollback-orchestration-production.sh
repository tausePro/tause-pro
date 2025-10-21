#!/bin/bash

# 🔄 Rollback Agent Orchestration System from Production
# Restaura backup completo si algo falla

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

AWS_HOST="34.207.248.220"
AWS_USER="ubuntu"
KEY_FILE="magicai-tause-key.pem"
PROJECT_PATH="/var/www/magicai"

echo -e "${RED}🔄 ROLLBACK: Agent Orchestration System${NC}"
echo "=================================================="
echo -e "${YELLOW}⚠️  This will restore the backup${NC}"
echo ""

# Verificar key file
if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Error: $KEY_FILE not found${NC}"
    exit 1
fi

chmod 400 "$KEY_FILE"

# Función para ejecutar comandos remotos
run_remote() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" "$1"
}

# Pedir confirmación
echo -e "${YELLOW}⚠️  Are you sure you want to rollback? (yes/no)${NC}"
read -r CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${BLUE}Rollback cancelled${NC}"
    exit 0
fi

# Listar backups disponibles
echo ""
echo -e "${BLUE}📦 Available backups:${NC}"
run_remote "ls -lh /tmp/backup-orchestration-* 2>/dev/null || echo 'No backups found'"

echo ""
echo -e "${YELLOW}Enter backup date (format: YYYYMMDD_HHMMSS) or press Enter for latest:${NC}"
read -r BACKUP_DATE

if [ -z "$BACKUP_DATE" ]; then
    BACKUP_DATE=$(run_remote "ls -t /tmp/backup-orchestration-*.sql 2>/dev/null | head -1 | grep -oP '\d{8}_\d{6}' || echo ''")
    if [ -z "$BACKUP_DATE" ]; then
        echo -e "${RED}❌ No backups found${NC}"
        exit 1
    fi
    echo -e "${BLUE}Using latest backup: $BACKUP_DATE${NC}"
fi

# ============================================
# PASO 1: ROLLBACK MIGRATION
# ============================================
echo ""
echo -e "${BLUE}🗄️  PASO 1: Rolling back migration...${NC}"
echo "=================================================="

run_remote "cd $PROJECT_PATH && php artisan migrate:rollback --step=1 --force" || {
    echo -e "${YELLOW}⚠️  Migration rollback failed or already rolled back${NC}"
}

echo -e "${GREEN}✅ Migration rolled back${NC}"

# ============================================
# PASO 2: RESTORE DATABASE (OPTIONAL)
# ============================================
echo ""
echo -e "${YELLOW}Do you want to restore the database backup? (yes/no)${NC}"
read -r RESTORE_DB

if [ "$RESTORE_DB" = "yes" ]; then
    echo -e "${BLUE}🗄️  PASO 2: Restoring database...${NC}"
    echo "=================================================="
    
    run_remote "mysql -u root magicai < /tmp/backup-orchestration-$BACKUP_DATE.sql" || {
        echo -e "${RED}❌ Database restore failed${NC}"
        exit 1
    }
    
    echo -e "${GREEN}✅ Database restored${NC}"
else
    echo -e "${BLUE}Skipping database restore${NC}"
fi

# ============================================
# PASO 3: RESTORE FILES
# ============================================
echo ""
echo -e "${BLUE}📂 PASO 3: Restoring files...${NC}"
echo "=================================================="

run_remote "
cd $PROJECT_PATH
if [ -f /tmp/backup-orchestration-files-$BACKUP_DATE.tar.gz ]; then
    sudo tar -xzf /tmp/backup-orchestration-files-$BACKUP_DATE.tar.gz
    sudo chown -R www-data:www-data app/Extensions/Chatbot/
    echo 'Files restored'
else
    echo 'No file backup found'
fi
"

echo -e "${GREEN}✅ Files restored${NC}"

# ============================================
# PASO 4: DELETE NEW FILES
# ============================================
echo ""
echo -e "${BLUE}🗑️  PASO 4: Removing new files...${NC}"
echo "=================================================="

run_remote "
cd $PROJECT_PATH
sudo rm -f app/Extensions/Chatbot/System/Models/ChatbotAgent.php
sudo rm -f app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php
sudo rm -f database/migrations/2025_10_20_202126_create_ext_chatbot_agents_table.php
sudo rm -f database/seeders/MigrateSalesAgentConfigSeeder.php
"

echo -e "${GREEN}✅ New files removed${NC}"

# ============================================
# PASO 5: CLEAR CACHES
# ============================================
echo ""
echo -e "${BLUE}🧹 PASO 5: Clearing caches...${NC}"
echo "=================================================="

run_remote "cd $PROJECT_PATH && php artisan config:clear"
run_remote "cd $PROJECT_PATH && php artisan route:clear"
run_remote "cd $PROJECT_PATH && php artisan view:clear"
run_remote "cd $PROJECT_PATH && composer dump-autoload --optimize"

echo -e "${GREEN}✅ Caches cleared${NC}"

# ============================================
# PASO 6: VERIFICATION
# ============================================
echo ""
echo -e "${BLUE}✅ PASO 6: Verification...${NC}"
echo "=================================================="

run_remote "cd $PROJECT_PATH && php artisan tinker --execute=\"
echo 'System check: OK';
\"" || {
    echo -e "${RED}❌ Verification failed!${NC}"
    exit 1
}

echo -e "${GREEN}✅ System verified${NC}"

# ============================================
# SUCCESS
# ============================================
echo ""
echo -e "${GREEN}🎉 ROLLBACK SUCCESSFUL!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Summary:${NC}"
echo "✅ Migration rolled back"
echo "✅ Files restored"
echo "✅ New files removed"
echo "✅ Caches cleared"
echo "✅ System verified"
echo ""
echo -e "${YELLOW}🔗 Test the system:${NC}"
echo "https://app.tause.pro"
echo ""
echo -e "${GREEN}Rollback completed at: $(date)${NC}"
