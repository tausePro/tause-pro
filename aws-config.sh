#!/bin/bash

# 🔧 AWS Configuration for MagicAI Deployment
# Update these values according to your AWS setup

# AWS Server Configuration
export AWS_HOST="34.207.248.220"        # Your EC2 instance IP
export AWS_USER="ubuntu"                # Common users: ec2-user, ubuntu, admin
export KEY_FILE="magicai-tause-key.pem"   # Your SSH key file
export PROJECT_PATH="/var/www/magicai"       # Path to your Laravel project

# PHP Configuration (adjust if needed)
export PHP_VERSION="8.2"                  # Your PHP version
export PHP_FPM_SERVICE="php8.2-fpm"      # PHP-FPM service name

# Web Server Configuration
export WEB_SERVER="nginx"                 # nginx or apache2
export WEB_USER="www-data"                # Web server user (www-data, nginx, apache)

# Database Configuration (if needed for migrations)
export DB_HOST="localhost"
export DB_NAME="magicai"
export DB_USER="magicai_user"

echo "🔧 AWS Configuration loaded:"
echo "   Host: $AWS_HOST"
echo "   User: $AWS_USER"
echo "   Key:  $KEY_FILE"
echo "   Path: $PROJECT_PATH"
echo "   PHP:  $PHP_VERSION"