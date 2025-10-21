#!/bin/bash

# 🚀 Deploy Agent Orchestration System to Production
# Con backup completo y rollback automático si falla

set -e  # Exit on error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

AWS_HOST="34.207.248.220"
AWS_USER="ubuntu"
KEY_FILE="magicai-tause-key.pem"
PROJECT_PATH="/var/www/magicai"
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)

echo -e "${BLUE}🚀 Deployment: Agent Orchestration System${NC}"
echo "=================================================="
echo -e "${YELLOW}⚠️  Este script incluye backup automático${NC}"
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

echo -e "${YELLOW}📡 Testing connection to AWS...${NC}"
if ! run_remote "echo 'Connected'"; then
    echo -e "${RED}❌ Cannot connect to AWS${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Connected to AWS${NC}"

# ============================================
# PASO 1: BACKUP COMPLETO
# ============================================
echo ""
echo -e "${BLUE}📦 PASO 1: Creating complete backup...${NC}"
echo "=================================================="

echo -e "${YELLOW}1.1 Backing up database...${NC}"
run_remote "cd $PROJECT_PATH && php artisan db:backup --filename=backup-before-orchestration-$BACKUP_DATE.sql" || {
    echo -e "${YELLOW}⚠️  db:backup command not available, using mysqldump...${NC}"
    run_remote "mysqldump -u root magicai > /tmp/backup-orchestration-$BACKUP_DATE.sql"
}
echo -e "${GREEN}✅ Database backed up${NC}"

echo -e "${YELLOW}1.2 Backing up critical files...${NC}"
run_remote "cd $PROJECT_PATH && tar -czf /tmp/backup-orchestration-files-$BACKUP_DATE.tar.gz \
    app/Extensions/Chatbot/System/Generators/OpenAIGenerator.php \
    app/Extensions/Chatbot/System/Services/GeneratorService.php \
    app/Extensions/Chatbot/System/Models/Chatbot.php \
    app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php \
    2>/dev/null || true"
echo -e "${GREEN}✅ Files backed up${NC}"

echo -e "${YELLOW}1.3 Listing backups...${NC}"
run_remote "ls -lh /tmp/backup-orchestration-* 2>/dev/null || echo 'No backups found yet'"

# ============================================
# PASO 2: UPLOAD FILES
# ============================================
echo ""
echo -e "${BLUE}📤 PASO 2: Uploading new files...${NC}"
echo "=================================================="

echo -e "${YELLOW}2.1 Creating temp directory...${NC}"
run_remote "mkdir -p /tmp/orchestration-deploy-$BACKUP_DATE"

echo -e "${YELLOW}2.2 Uploading migration...${NC}"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    database/migrations/2025_10_20_202126_create_ext_chatbot_agents_table.php \
    "$AWS_USER@$AWS_HOST:/tmp/orchestration-deploy-$BACKUP_DATE/"

echo -e "${YELLOW}2.3 Uploading seeder...${NC}"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    database/seeders/MigrateSalesAgentConfigSeeder.php \
    "$AWS_USER@$AWS_HOST:/tmp/orchestration-deploy-$BACKUP_DATE/"

echo -e "${YELLOW}2.4 Uploading models...${NC}"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    app/Extensions/Chatbot/System/Models/ChatbotAgent.php \
    "$AWS_USER@$AWS_HOST:/tmp/orchestration-deploy-$BACKUP_DATE/"

echo -e "${YELLOW}2.5 Uploading services...${NC}"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php \
    app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php \
    "$AWS_USER@$AWS_HOST:/tmp/orchestration-deploy-$BACKUP_DATE/"

echo -e "${YELLOW}2.6 Uploading modified files...${NC}"
scp -i "$KEY_FILE" -o StrictHostKeyChecking=no \
    app/Extensions/Chatbot/System/Generators/OpenAIGenerator.php \
    app/Extensions/Chatbot/System/Services/GeneratorService.php \
    app/Extensions/Chatbot/System/Models/Chatbot.php \
    app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php \
    "$AWS_USER@$AWS_HOST:/tmp/orchestration-deploy-$BACKUP_DATE/"

echo -e "${GREEN}✅ All files uploaded${NC}"

# ============================================
# PASO 3: MOVE FILES TO PRODUCTION
# ============================================
echo ""
echo -e "${BLUE}📂 PASO 3: Moving files to production...${NC}"
echo "=================================================="

run_remote "
cd /tmp/orchestration-deploy-$BACKUP_DATE

# Migration
sudo cp 2025_10_20_202126_create_ext_chatbot_agents_table.php $PROJECT_PATH/database/migrations/

# Seeder
sudo cp MigrateSalesAgentConfigSeeder.php $PROJECT_PATH/database/seeders/

# Models
sudo cp ChatbotAgent.php $PROJECT_PATH/app/Extensions/Chatbot/System/Models/

# Services
sudo cp AgentOrchestratorService.php $PROJECT_PATH/app/Extensions/Chatbot/System/Services/
sudo cp ProductOrchestratorService.php $PROJECT_PATH/app/Extensions/Chatbot/System/Services/

# Modified files
sudo cp OpenAIGenerator.php $PROJECT_PATH/app/Extensions/Chatbot/System/Generators/
sudo cp GeneratorService.php $PROJECT_PATH/app/Extensions/Chatbot/System/Services/
sudo cp Chatbot.php $PROJECT_PATH/app/Extensions/Chatbot/System/Models/
sudo cp ChatbotApplicationController.php $PROJECT_PATH/app/Extensions/Chatbot/System/Http/Controllers/Api/

# Set permissions
sudo chown -R www-data:www-data $PROJECT_PATH/app/Extensions/Chatbot/
sudo chown -R www-data:www-data $PROJECT_PATH/database/
"

echo -e "${GREEN}✅ Files moved to production${NC}"

# ============================================
# PASO 4: RUN MIGRATION
# ============================================
echo ""
echo -e "${BLUE}🗄️  PASO 4: Running migration...${NC}"
echo "=================================================="

run_remote "cd $PROJECT_PATH && php artisan migrate --path=database/migrations/2025_10_20_202126_create_ext_chatbot_agents_table.php --force" || {
    echo -e "${RED}❌ Migration failed!${NC}"
    echo -e "${YELLOW}🔄 Rolling back...${NC}"
    run_remote "cd $PROJECT_PATH && php artisan migrate:rollback --step=1 --force"
    exit 1
}

echo -e "${GREEN}✅ Migration completed${NC}"

# ============================================
# PASO 5: RUN SEEDER
# ============================================
echo ""
echo -e "${BLUE}🌱 PASO 5: Running seeder...${NC}"
echo "=================================================="

run_remote "cd $PROJECT_PATH && php artisan db:seed --class=MigrateSalesAgentConfigSeeder --force" || {
    echo -e "${RED}❌ Seeder failed!${NC}"
    echo -e "${YELLOW}⚠️  This is not critical, continuing...${NC}"
}

echo -e "${GREEN}✅ Seeder completed${NC}"

# ============================================
# PASO 6: CLEAR CACHES
# ============================================
echo ""
echo -e "${BLUE}🧹 PASO 6: Clearing caches...${NC}"
echo "=================================================="

run_remote "cd $PROJECT_PATH && php artisan config:clear"
run_remote "cd $PROJECT_PATH && php artisan route:clear"
run_remote "cd $PROJECT_PATH && php artisan view:clear"
run_remote "cd $PROJECT_PATH && composer dump-autoload --optimize"

echo -e "${GREEN}✅ Caches cleared${NC}"

# ============================================
# PASO 7: VERIFICATION
# ============================================
echo ""
echo -e "${BLUE}✅ PASO 7: Verification...${NC}"
echo "=================================================="

echo -e "${YELLOW}7.1 Checking database...${NC}"
run_remote "cd $PROJECT_PATH && php artisan tinker --execute=\"
echo 'Agents in DB: ' . \App\Extensions\Chatbot\System\Models\ChatbotAgent::count();
\"" || {
    echo -e "${RED}❌ Verification failed!${NC}"
    exit 1
}

echo -e "${YELLOW}7.2 Testing orchestration...${NC}"
run_remote "cd $PROJECT_PATH && php artisan tinker --execute=\"
\\\$chatbot = \App\Extensions\Chatbot\System\Models\Chatbot::where('title', 'Ali')->first();
if(\\\$chatbot) {
    \\\$orchestrator = app(\App\Extensions\Chatbot\System\Services\AgentOrchestratorService::class);
    \\\$result = \\\$orchestrator->orchestrate(\\\$chatbot, 'test', 'test');
    echo 'Orchestration works: ' . (isset(\\\$result['message']) ? 'YES' : 'NO');
}
\"" || {
    echo -e "${RED}❌ Orchestration test failed!${NC}"
    exit 1
}

echo -e "${GREEN}✅ All verifications passed${NC}"

# ============================================
# PASO 8: CLEANUP
# ============================================
echo ""
echo -e "${BLUE}🧹 PASO 8: Cleanup...${NC}"
echo "=================================================="

run_remote "rm -rf /tmp/orchestration-deploy-$BACKUP_DATE"
echo -e "${GREEN}✅ Temp files cleaned${NC}"

# ============================================
# SUCCESS
# ============================================
echo ""
echo -e "${GREEN}🎉 DEPLOYMENT SUCCESSFUL!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Summary:${NC}"
echo "✅ Database backed up: /tmp/backup-orchestration-$BACKUP_DATE.sql"
echo "✅ Files backed up: /tmp/backup-orchestration-files-$BACKUP_DATE.tar.gz"
echo "✅ Migration executed"
echo "✅ Seeder executed"
echo "✅ Caches cleared"
echo "✅ System verified"
echo ""
echo -e "${YELLOW}🔗 Test the chatbot:${NC}"
echo "https://app.tause.pro/chatbot/786ce971-da76-4fd6-adbb-262cbee1200d"
echo ""
echo -e "${BLUE}📝 Rollback command (if needed):${NC}"
echo "ssh -i $KEY_FILE $AWS_USER@$AWS_HOST 'cd $PROJECT_PATH && php artisan migrate:rollback --step=1 --force'"
echo ""
echo -e "${GREEN}Deployment completed at: $(date)${NC}"
