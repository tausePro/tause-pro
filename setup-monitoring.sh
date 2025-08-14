#!/bin/bash

# Basic Monitoring Setup for MagicAI
# Sets up log rotation, basic monitoring, and health checks

set -e

echo "📊 Setting up basic monitoring for MagicAI..."

# Install monitoring tools
apt install -y htop iotop nethogs

# Set up log rotation for Laravel logs
echo "📝 Setting up log rotation..."
cat > /etc/logrotate.d/magicai <<EOF
/var/www/magicai/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
EOF

# Create monitoring script
echo "🔍 Creating monitoring script..."
cat > /usr/local/bin/magicai-monitor.sh <<'EOF'
#!/bin/bash

# MagicAI Health Check Script
LOG_FILE="/var/log/magicai-monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Function to log messages
log_message() {
    echo "[$DATE] $1" >> $LOG_FILE
}

# Check if Nginx is running
if ! systemctl is-active --quiet nginx; then
    log_message "ERROR: Nginx is not running"
    systemctl restart nginx
    log_message "INFO: Nginx restarted"
fi

# Check if PHP-FPM is running
if ! systemctl is-active --quiet php8.2-fpm; then
    log_message "ERROR: PHP-FPM is not running"
    systemctl restart php8.2-fpm
    log_message "INFO: PHP-FPM restarted"
fi

# Check if MySQL is running
if ! systemctl is-active --quiet mysql; then
    log_message "ERROR: MySQL is not running"
    systemctl restart mysql
    log_message "INFO: MySQL restarted"
fi

# Check disk space
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    log_message "WARNING: Disk usage is ${DISK_USAGE}%"
fi

# Check memory usage
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ $MEMORY_USAGE -gt 90 ]; then
    log_message "WARNING: Memory usage is ${MEMORY_USAGE}%"
fi

# Check if MagicAI is responding
if ! curl -f -s http://localhost > /dev/null; then
    log_message "ERROR: MagicAI is not responding"
fi

# Check queue workers
QUEUE_WORKERS=$(supervisorctl status magicai-worker:* | grep RUNNING | wc -l)
if [ $QUEUE_WORKERS -eq 0 ]; then
    log_message "ERROR: No queue workers running"
    supervisorctl restart magicai-worker:*
    log_message "INFO: Queue workers restarted"
fi

log_message "INFO: Health check completed"
EOF

chmod +x /usr/local/bin/magicai-monitor.sh

# Set up cron job for monitoring
echo "⏰ Setting up monitoring cron job..."
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/magicai-monitor.sh") | crontab -

# Create system info script
cat > /usr/local/bin/magicai-info.sh <<'EOF'
#!/bin/bash

echo "🚀 MagicAI System Information"
echo "================================"
echo "Date: $(date)"
echo "Uptime: $(uptime -p)"
echo ""

echo "💾 Memory Usage:"
free -h

echo ""
echo "💿 Disk Usage:"
df -h /

echo ""
echo "🔧 Service Status:"
echo "Nginx: $(systemctl is-active nginx)"
echo "PHP-FPM: $(systemctl is-active php8.2-fpm)"
echo "MySQL: $(systemctl is-active mysql)"
echo "Supervisor: $(systemctl is-active supervisor)"

echo ""
echo "⚡ Queue Workers:"
supervisorctl status magicai-worker:*

echo ""
echo "📊 Top Processes:"
ps aux --sort=-%cpu | head -10

echo ""
echo "🌐 Network Connections:"
netstat -tuln | grep -E ':80|:443|:3306'

echo ""
echo "📝 Recent Errors (last 10):"
tail -10 /var/www/magicai/storage/logs/laravel.log 2>/dev/null || echo "No errors found"
EOF

chmod +x /usr/local/bin/magicai-info.sh

# Create backup script
echo "💾 Creating backup script..."
cat > /usr/local/bin/magicai-backup.sh <<'EOF'
#!/bin/bash

BACKUP_DIR="/var/backups/magicai"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/magicai"

# Create backup directory
mkdir -p $BACKUP_DIR

echo "🗄️ Creating database backup..."
mysqldump -u magicai -pMagicAI2024! magicai > $BACKUP_DIR/database_$DATE.sql

echo "📁 Creating application backup..."
tar -czf $BACKUP_DIR/app_$DATE.tar.gz -C /var/www magicai --exclude=node_modules --exclude=vendor

echo "🧹 Cleaning old backups (keeping last 7 days)..."
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "✅ Backup completed: $DATE"
EOF

chmod +x /usr/local/bin/magicai-backup.sh

# Set up daily backup
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/magicai-backup.sh >> /var/log/magicai-backup.log 2>&1") | crontab -

# Create useful aliases
echo "🔧 Creating useful aliases..."
cat >> /home/ubuntu/.bashrc <<'EOF'

# MagicAI aliases
alias magicai-logs='tail -f /var/www/magicai/storage/logs/laravel.log'
alias magicai-info='/usr/local/bin/magicai-info.sh'
alias magicai-backup='/usr/local/bin/magicai-backup.sh'
alias magicai-restart='systemctl restart nginx php8.2-fpm mysql && supervisorctl restart magicai-worker:*'
alias magicai-status='systemctl status nginx php8.2-fpm mysql'
EOF

echo "✅ Monitoring setup completed!"
echo ""
echo "📊 Available commands:"
echo "  magicai-info     - Show system information"
echo "  magicai-logs     - View live application logs"
echo "  magicai-backup   - Create manual backup"
echo "  magicai-restart  - Restart all services"
echo "  magicai-status   - Check service status"
echo ""
echo "📝 Log files:"
echo "  Application: /var/www/magicai/storage/logs/laravel.log"
echo "  Monitor: /var/log/magicai-monitor.log"
echo "  Backup: /var/log/magicai-backup.log"
echo ""
echo "💾 Backups: /var/backups/magicai/"