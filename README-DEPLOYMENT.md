# 🚀 MagicAI AWS Deployment Guide

## Quick Start

### 1. Create EC2 Instance
1. Go to AWS Console → EC2 → Launch Instance
2. Choose **Ubuntu 22.04 LTS**
3. Select **t3.small** instance type
4. Create or select a key pair
5. Configure Security Group:
   - SSH (22): Your IP
   - HTTP (80): 0.0.0.0/0
   - HTTPS (443): 0.0.0.0/0
6. Set storage to **30GB gp3**
7. Launch instance

### 2. Connect to Server
```bash
ssh -i your-key.pem ubuntu@YOUR-EC2-IP
```

### 3. Upload Deployment Scripts
Upload all the `.sh` files to your server:
```bash
scp -i your-key.pem *.sh ubuntu@YOUR-EC2-IP:~/
```

### 4. Run Master Deployment Script
```bash
sudo ./deploy-magicai.sh
```

### 5. Upload MagicAI Code
When prompted, upload your MagicAI code to `/var/www/magicai`

## Manual Step-by-Step

If you prefer manual control:

### Step 1: Install Base System
```bash
sudo ./install-magicai.sh
```

### Step 2: Upload MagicAI Code
```bash
# Method 1: Git clone
cd /var/www
sudo git clone YOUR_REPO_URL magicai

# Method 2: Upload and extract
sudo unzip MagicAI.zip -d /var/www/
sudo mv /var/www/MagicAI-folder /var/www/magicai
```

### Step 3: Setup Laravel
```bash
sudo ./setup-laravel.sh
```

### Step 4: Configure SSL (Optional)
```bash
sudo ./setup-ssl.sh yourdomain.com
```

### Step 5: Configure APIs
```bash
sudo ./configure-ai-apis.sh
```

### Step 6: Setup Monitoring
```bash
sudo ./setup-monitoring.sh
```

## Configuration

### Required API Keys
- **OpenAI**: For GPT models
- **Anthropic**: For Claude models
- **Google**: For Gemini models
- **Stable Diffusion**: For image generation
- **ElevenLabs**: For text-to-speech

### Payment Gateways
Configure at least one:
- **Stripe**: Most popular
- **PayPal**: Widely accepted
- **Other**: Razorpay, Paystack, etc.

### Email Service
Choose one:
- **SMTP**: Any email provider
- **AWS SES**: Recommended for AWS

## Post-Deployment

### Create Admin User
1. Visit your site
2. Go to `/admin`
3. Create first admin account

### Configure Settings
1. Application settings
2. Payment plans
3. AI model settings
4. Email templates

## Monitoring Commands

```bash
# System information
magicai-info

# View live logs
magicai-logs

# Create backup
magicai-backup

# Restart all services
magicai-restart

# Check service status
magicai-status
```

## Troubleshooting

### Common Issues

**Permission Errors:**
```bash
sudo chown -R www-data:www-data /var/www/magicai
sudo chmod -R 755 /var/www/magicai
sudo chmod -R 775 /var/www/magicai/storage
```

**Database Connection:**
```bash
# Check MySQL status
sudo systemctl status mysql

# Reset database password
sudo mysql -u root -p
```

**Queue Not Working:**
```bash
# Check queue workers
sudo supervisorctl status magicai-worker:*

# Restart workers
sudo supervisorctl restart magicai-worker:*
```

**SSL Issues:**
```bash
# Check certificate
sudo certbot certificates

# Renew certificate
sudo certbot renew
```

## Scaling

### When to Scale
- **CPU > 80%** consistently
- **Memory > 90%** consistently
- **Response time > 3 seconds**
- **Queue backlog** growing

### Scaling Options
1. **Vertical**: Upgrade to t3.medium/large
2. **Horizontal**: Add load balancer + multiple instances
3. **Database**: Move to RDS
4. **Cache**: Add ElastiCache Redis

## Security

### Firewall Rules
```bash
# Only allow necessary ports
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
```

### Regular Updates
```bash
# Update system monthly
sudo apt update && sudo apt upgrade

# Update MagicAI when new versions available
cd /var/www/magicai
git pull origin main
composer install --no-dev
npm run build
php artisan migrate
```

## Backup Strategy

### Automated Backups
- **Daily**: Database + application files
- **Retention**: 7 days local
- **Location**: `/var/backups/magicai/`

### Manual Backup
```bash
magicai-backup
```

### Restore from Backup
```bash
# Restore database
mysql -u magicai -pMagicAI2024! magicai < backup.sql

# Restore files
tar -xzf app_backup.tar.gz -C /var/www/
```

## Support

### Log Files
- **Application**: `/var/www/magicai/storage/logs/laravel.log`
- **Nginx**: `/var/log/nginx/error.log`
- **PHP**: `/var/log/php8.2-fpm.log`
- **MySQL**: `/var/log/mysql/error.log`

### Performance Monitoring
```bash
# Real-time monitoring
htop
iotop
nethogs

# Check disk space
df -h

# Check memory
free -h
```

---

🎉 **Your MagicAI installation is now ready for production!**