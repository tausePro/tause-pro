#!/bin/bash

# Remote deployment script for MagicAI on AWS
# Deploys to app.tause.pro using magicai--tause-key.pem

set -e

KEY_FILE="magicai-tause-key.pem"
SERVER="app.tause.pro"
USER="ubuntu"

echo "🚀 Remote MagicAI Deployment to AWS"
echo "==================================="
echo "🔑 Key: $KEY_FILE"
echo "🌐 Server: $SERVER"
echo "👤 User: $USER"
echo ""

# Check if key file exists
if [ ! -f "$KEY_FILE" ]; then
    echo "❌ Key file $KEY_FILE not found!"
    echo "Make sure magicai--tause-key.pem is in the current directory"
    exit 1
fi

# Set correct permissions
chmod 400 $KEY_FILE

# Test connection
echo "🔍 Testing connection to AWS server..."
if ! ssh -i $KEY_FILE -o ConnectTimeout=10 $USER@$SERVER "echo 'Connection successful'"; then
    echo "❌ Cannot connect to server. Check:"
    echo "   - Key file is correct"
    echo "   - Server is running"
    echo "   - Security group allows SSH (port 22)"
    echo "   - DNS is pointing to correct IP"
    exit 1
fi

echo "✅ Connection successful!"
echo ""

# Upload scripts
echo "📤 Uploading deployment scripts..."
scp -i $KEY_FILE *.sh $USER@$SERVER:~/
echo "✅ Scripts uploaded"

# Upload MagicAI code if exists locally
if [ -d "magicai_original" ]; then
    echo "📤 Uploading MagicAI code..."
    scp -i $KEY_FILE -r magicai_original $USER@$SERVER:~/
    echo "✅ MagicAI code uploaded"
elif [ -f "magicai.zip" ]; then
    echo "📤 Uploading MagicAI ZIP..."
    scp -i $KEY_FILE magicai.zip $USER@$SERVER:~/
    echo "✅ MagicAI ZIP uploaded"
else
    echo "⚠️  No MagicAI code found locally"
    echo "   You'll need to upload it manually or clone from git"
fi

# Execute deployment on server
echo ""
echo "🚀 Starting remote deployment..."
echo "================================"

ssh -i $KEY_FILE $USER@$SERVER << 'ENDSSH'
    # Make scripts executable
    chmod +x *.sh
    
    # Move MagicAI code to correct location if uploaded
    if [ -d "magicai_original" ]; then
        sudo mkdir -p /var/www
        sudo mv magicai_original /var/www/magicai
        sudo chown -R www-data:www-data /var/www/magicai
    elif [ -f "magicai.zip" ]; then
        sudo mkdir -p /var/www
        cd /var/www
        sudo unzip ~/magicai.zip
        sudo mv MagicAI* magicai 2>/dev/null || sudo mv magicai* magicai 2>/dev/null || echo "Directory already named correctly"
        sudo chown -R www-data:www-data /var/www/magicai
        cd ~
    fi
    
    # Run deployment
    sudo ./deploy-tause-magicai.sh
ENDSSH

echo ""
echo "🎉 Remote deployment completed!"
echo "==============================="
echo ""
echo "🌐 Your MagicAI should be available at:"
echo "   https://app.tause.pro"
echo "   https://app.tause.pro/admin"
echo ""
echo "🔧 To manage the server:"
echo "   ssh -i $KEY_FILE $USER@$SERVER"
echo ""
echo "📊 Check status:"
echo "   ssh -i $KEY_FILE $USER@$SERVER 'sudo ./verify-tause-setup.sh'"