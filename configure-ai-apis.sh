#!/bin/bash

# MagicAI API Configuration Script
# Configure AI service API keys

set -e

echo "🤖 Configuring AI APIs for MagicAI..."

cd /var/www/magicai

# Function to update .env file
update_env() {
    local key=$1
    local value=$2
    if grep -q "^$key=" .env; then
        sed -i "s|^$key=.*|$key=$value|" .env
    else
        echo "$key=$value" >> .env
    fi
}

echo "Please provide your API keys (press Enter to skip):"

# OpenAI API Key
read -p "🔑 OpenAI API Key: " OPENAI_KEY
if [ ! -z "$OPENAI_KEY" ]; then
    update_env "OPENAI_API_KEY" "$OPENAI_KEY"
    echo "✅ OpenAI API key configured"
fi

# Anthropic API Key
read -p "🔑 Anthropic API Key: " ANTHROPIC_KEY
if [ ! -z "$ANTHROPIC_KEY" ]; then
    update_env "ANTHROPIC_API_KEY" "$ANTHROPIC_KEY"
    echo "✅ Anthropic API key configured"
fi

# Google Gemini API Key
read -p "🔑 Google Gemini API Key: " GEMINI_KEY
if [ ! -z "$GEMINI_KEY" ]; then
    update_env "GEMINI_API_KEY" "$GEMINI_KEY"
    echo "✅ Gemini API key configured"
fi

# Stable Diffusion API Key
read -p "🔑 Stable Diffusion API Key: " SD_KEY
if [ ! -z "$SD_KEY" ]; then
    update_env "STABLE_DIFFUSION_API_KEY" "$SD_KEY"
    echo "✅ Stable Diffusion API key configured"
fi

# ElevenLabs API Key
read -p "🔑 ElevenLabs API Key: " ELEVEN_KEY
if [ ! -z "$ELEVEN_KEY" ]; then
    update_env "ELEVENLABS_API_KEY" "$ELEVEN_KEY"
    echo "✅ ElevenLabs API key configured"
fi

# AWS Configuration for S3 (optional)
read -p "🔑 AWS Access Key ID (for S3): " AWS_KEY
if [ ! -z "$AWS_KEY" ]; then
    update_env "AWS_ACCESS_KEY_ID" "$AWS_KEY"
    read -p "🔑 AWS Secret Access Key: " AWS_SECRET
    update_env "AWS_SECRET_ACCESS_KEY" "$AWS_SECRET"
    read -p "🔑 AWS Region (default: us-east-1): " AWS_REGION
    AWS_REGION=${AWS_REGION:-us-east-1}
    update_env "AWS_DEFAULT_REGION" "$AWS_REGION"
    read -p "🔑 S3 Bucket Name: " S3_BUCKET
    if [ ! -z "$S3_BUCKET" ]; then
        update_env "AWS_BUCKET" "$S3_BUCKET"
        update_env "FILESYSTEM_DISK" "s3"
        echo "✅ AWS S3 configured"
    fi
fi

# Email Configuration
echo ""
echo "📧 Email Configuration:"
echo "1. SMTP"
echo "2. AWS SES"
echo "3. Skip"
read -p "Choose email provider (1-3): " EMAIL_CHOICE

case $EMAIL_CHOICE in
    1)
        read -p "SMTP Host: " SMTP_HOST
        read -p "SMTP Port: " SMTP_PORT
        read -p "SMTP Username: " SMTP_USER
        read -p "SMTP Password: " SMTP_PASS
        update_env "MAIL_MAILER" "smtp"
        update_env "MAIL_HOST" "$SMTP_HOST"
        update_env "MAIL_PORT" "$SMTP_PORT"
        update_env "MAIL_USERNAME" "$SMTP_USER"
        update_env "MAIL_PASSWORD" "$SMTP_PASS"
        update_env "MAIL_ENCRYPTION" "tls"
        echo "✅ SMTP configured"
        ;;
    2)
        update_env "MAIL_MAILER" "ses"
        echo "✅ AWS SES configured (make sure AWS keys are set)"
        ;;
    *)
        echo "⏭️ Email configuration skipped"
        ;;
esac

# Payment Gateway Configuration
echo ""
echo "💳 Payment Gateway Configuration:"
echo "Configure at least one payment gateway:"

read -p "🔑 Stripe Secret Key: " STRIPE_SECRET
if [ ! -z "$STRIPE_SECRET" ]; then
    update_env "STRIPE_SECRET" "$STRIPE_SECRET"
    read -p "🔑 Stripe Publishable Key: " STRIPE_PUBLIC
    update_env "STRIPE_KEY" "$STRIPE_PUBLIC"
    echo "✅ Stripe configured"
fi

read -p "🔑 PayPal Client ID: " PAYPAL_CLIENT
if [ ! -z "$PAYPAL_CLIENT" ]; then
    update_env "PAYPAL_CLIENT_ID" "$PAYPAL_CLIENT"
    read -p "🔑 PayPal Client Secret: " PAYPAL_SECRET
    update_env "PAYPAL_CLIENT_SECRET" "$PAYPAL_SECRET"
    update_env "PAYPAL_MODE" "sandbox"  # Change to 'live' for production
    echo "✅ PayPal configured (sandbox mode)"
fi

# Clear config cache
echo "🧹 Clearing configuration cache..."
sudo -u www-data php artisan config:cache

echo ""
echo "✅ API configuration completed!"
echo "🔧 Next steps:"
echo "1. Test your AI integrations in the admin panel"
echo "2. Configure payment plans"
echo "3. Set up your first admin user"
echo "4. Customize your application settings"
echo ""
echo "🌐 Access your admin panel at: https://yourdomain.com/admin"