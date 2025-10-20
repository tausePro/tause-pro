#!/bin/bash

# 🚀 MagicAI Product Detection Deployment Script
# This script deploys the new product detection functionality to AWS

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration (UPDATE THESE VALUES)
AWS_HOST="34.207.248.220"  # Your AWS IP
AWS_USER="ubuntu"          # or ubuntu, depending on your AMI
KEY_FILE="magicai-tause-key.pem"
PROJECT_PATH="/var/www/magicai"  # Update if different

echo -e "${BLUE}🚀 Starting MagicAI Product Detection Deployment${NC}"
echo "=================================================="

# Check if key file exists
if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Error: $KEY_FILE not found${NC}"
    exit 1
fi

# Set correct permissions for key file
chmod 400 "$KEY_FILE"
echo -e "${GREEN}✅ Key file permissions set${NC}"

# Function to run commands on remote server
run_remote() {
    ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$AWS_USER@$AWS_HOST" "$1"
}

# Function to copy files to remote server
copy_to_remote() {
    scp -i "$KEY_FILE" -o StrictHostKeyChecking=no "$1" "$AWS_USER@$AWS_HOST:/tmp/"
    filename=$(basename "$1")
    run_remote "sudo mv /tmp/$filename $2$filename"
}

echo -e "${YELLOW}📡 Testing connection to AWS server...${NC}"
if run_remote "echo 'Connection successful'"; then
    echo -e "${GREEN}✅ Connected to AWS server${NC}"
else
    echo -e "${RED}❌ Failed to connect to AWS server${NC}"
    echo "Please check:"
    echo "1. AWS_HOST IP address is correct"
    echo "2. Security group allows SSH (port 22)"
    echo "3. Key file is correct"
    exit 1
fi

echo -e "${YELLOW}📦 Creating backup of current system...${NC}"
run_remote "cd $PROJECT_PATH && cp -r app/Extensions/Chatbot/System app/Extensions/Chatbot/System.backup.$(date +%Y%m%d_%H%M%S)"
echo -e "${GREEN}✅ Backup created${NC}"

echo -e "${YELLOW}📤 Uploading new ProductExtractor...${NC}"
copy_to_remote "app/Extensions/Chatbot/System/Parsers/ProductExtractor.php" "$PROJECT_PATH/app/Extensions/Chatbot/System/Parsers/"
echo -e "${GREEN}✅ ProductExtractor uploaded${NC}"

echo -e "${YELLOW}📤 Uploading updated LinkParser...${NC}"
copy_to_remote "app/Extensions/Chatbot/System/Parsers/LinkParser.php" "$PROJECT_PATH/app/Extensions/Chatbot/System/Parsers/"
echo -e "${GREEN}✅ LinkParser uploaded${NC}"

echo -e "${YELLOW}📤 Uploading updated ChatbotTrainController...${NC}"
copy_to_remote "app/Extensions/Chatbot/System/Http/Controllers/ChatbotTrainController.php" "$PROJECT_PATH/app/Extensions/Chatbot/System/Http/Controllers/"
echo -e "${GREEN}✅ ChatbotTrainController uploaded${NC}"

echo -e "${YELLOW}📤 Uploading updated TrainUrlRequest...${NC}"
copy_to_remote "app/Extensions/Chatbot/System/Http/Requests/Train/TrainUrlRequest.php" "$PROJECT_PATH/app/Extensions/Chatbot/System/Http/Requests/Train/"
echo -e "${GREEN}✅ TrainUrlRequest uploaded${NC}"

echo -e "${YELLOW}📤 Uploading updated ChatbotServiceProvider...${NC}"
copy_to_remote "app/Extensions/Chatbot/System/ChatbotServiceProvider.php" "$PROJECT_PATH/app/Extensions/Chatbot/System/"
echo -e "${GREEN}✅ ChatbotServiceProvider uploaded${NC}"

echo -e "${YELLOW}🔧 Setting correct file permissions...${NC}"
run_remote "cd $PROJECT_PATH && sudo chown -R www-data:www-data app/Extensions/Chatbot/System/ 2>/dev/null || true && sudo chmod -R 755 app/Extensions/Chatbot/System/ 2>/dev/null || true"
echo -e "${GREEN}✅ Permissions set${NC}"

echo -e "${YELLOW}🧹 Clearing Laravel caches...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:clear"
run_remote "cd $PROJECT_PATH && php artisan route:clear"
run_remote "cd $PROJECT_PATH && php artisan view:clear"
echo -e "${GREEN}✅ Caches cleared${NC}"

echo -e "${YELLOW}📚 Updating autoloader...${NC}"
run_remote "cd $PROJECT_PATH && composer dump-autoload --optimize"
echo -e "${GREEN}✅ Autoloader updated${NC}"

echo -e "${YELLOW}⚡ Optimizing for production...${NC}"
run_remote "cd $PROJECT_PATH && php artisan config:cache"
run_remote "cd $PROJECT_PATH && php artisan route:cache"
echo -e "${GREEN}✅ Production optimization complete${NC}"

echo -e "${YELLOW}🔄 Restarting services...${NC}"
run_remote "sudo systemctl reload nginx"
run_remote "sudo systemctl restart php8.2-fpm"  # Adjust PHP version if needed
echo -e "${GREEN}✅ Services restarted${NC}"

echo -e "${YELLOW}🧪 Testing deployment...${NC}"
if run_remote "cd $PROJECT_PATH && php artisan tinker --execute=\"echo 'Testing ProductExtractor...'; \\\$extractor = new \\\App\\\Extensions\\\Chatbot\\\System\\\Parsers\\\ProductExtractor(); echo 'SUCCESS: ProductExtractor loaded!';\""; then
    echo -e "${GREEN}✅ Deployment test passed${NC}"
else
    echo -e "${RED}❌ Deployment test failed${NC}"
    echo -e "${YELLOW}Rolling back...${NC}"
    run_remote "cd $PROJECT_PATH && rm -rf app/Extensions/Chatbot/System && mv app/Extensions/Chatbot/System.backup.* app/Extensions/Chatbot/System"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 DEPLOYMENT SUCCESSFUL!${NC}"
echo "=================================================="
echo -e "${BLUE}📋 Summary:${NC}"
echo "✅ ProductExtractor deployed"
echo "✅ LinkParser updated with product detection"
echo "✅ ChatbotTrainController updated"
echo "✅ New routes registered"
echo "✅ All caches cleared and optimized"
echo "✅ Services restarted"
echo ""
echo -e "${YELLOW}🔗 Next steps:${NC}"
echo "1. Test the product detection in the admin panel"
echo "2. Try training a chatbot with detect_products=true"
echo "3. Monitor logs for any issues"
echo ""
echo -e "${BLUE}📝 Rollback command (if needed):${NC}"
echo "ssh -i $KEY_FILE $AWS_USER@$AWS_HOST 'cd $PROJECT_PATH && rm -rf app/Extensions/Chatbot/System && mv app/Extensions/Chatbot/System.backup.* app/Extensions/Chatbot/System'"