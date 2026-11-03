#!/bin/bash

##############################################################################
# Post-Deployment Verification & Quick Reference
# 
# This script helps verify the deployment is working correctly
# Usage: sudo bash post-deployment-check.sh
#
##############################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

APP_PATH="/var/www/ciblerh"
APP_DOMAIN="${APP_DOMAIN:-yourdomain.com}"

log_pass() {
    echo -e "${GREEN}✓${NC} $1"
}

log_fail() {
    echo -e "${RED}✗${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}!${NC} $1"
}

log_info() {
    echo -e "${BLUE}»${NC} $1"
}

print_header() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║  $1"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
}

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    echo "This script must be run as root"
    exit 1
fi

print_header "Application Environment"

# Check PHP
if command -v php >/dev/null 2>&1; then
    PHP_VERSION=$(php -v | head -1 | cut -d' ' -f2)
    log_pass "PHP installed: $PHP_VERSION"
else
    log_fail "PHP not found"
fi

# Check Composer
if command -v composer >/dev/null 2>&1; then
    log_pass "Composer installed"
else
    log_fail "Composer not found"
fi

# Check Node.js
if command -v node >/dev/null 2>&1; then
    NODE_VERSION=$(node -v)
    log_pass "Node.js installed: $NODE_VERSION"
else
    log_fail "Node.js not found"
fi

# Check npm
if command -v npm >/dev/null 2>&1; then
    NPM_VERSION=$(npm -v)
    log_pass "npm installed: $NPM_VERSION"
else
    log_fail "npm not found"
fi

print_header "Web Services"

# Check PHP-FPM
if systemctl is-active --quiet php8.4-fpm; then
    FPM_WORKERS=$(ps aux | grep -c "[p]hp-fpm:" || true)
    log_pass "PHP-FPM is running ($FPM_WORKERS workers)"
else
    log_fail "PHP-FPM is NOT running"
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    log_pass "Nginx is running"
else
    log_fail "Nginx is NOT running"
fi

# Check if site is accessible
if curl -sk "https://$APP_DOMAIN" -w "\n%{http_code}\n" -o /dev/null | grep -q "200\|301\|302"; then
    log_pass "Site is accessible at https://$APP_DOMAIN"
else
    log_warn "Site may not be accessible or SSL not configured"
fi

print_header "Database & Cache"

# Check MySQL/MariaDB
if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null; then
    log_pass "MySQL is running"
    
    # Test database connection
    if mysql -u ciblerh -p$(grep DB_PASSWORD $APP_PATH/.env | cut -d'=' -f2) -e "SELECT 1" >/dev/null 2>&1; then
        log_pass "Database connection works"
    else
        log_fail "Cannot connect to database (check credentials)"
    fi
else
    log_fail "MySQL is NOT running"
fi

# Check Redis
if systemctl is-active --quiet redis-server; then
    log_pass "Redis is running"
    
    # Test Redis connection
    if redis-cli ping | grep -q "PONG"; then
        log_pass "Redis connection works"
    else
        log_fail "Cannot connect to Redis"
    fi
else
    log_fail "Redis is NOT running"
fi

print_header "Queue Workers"

# Check Supervisor
if command -v supervisorctl >/dev/null 2>&1; then
    RUNNING=$(supervisorctl status laravel-queues:* 2>/dev/null | grep -c "RUNNING" || true)
    TOTAL=$(supervisorctl status laravel-queues:* 2>/dev/null | wc -l || true)
    
    if [ "$RUNNING" -gt 0 ]; then
        log_pass "Queue workers running: $RUNNING/$TOTAL"
    else
        log_warn "No queue workers are running"
        log_info "Start them with: supervisorctl start laravel-queues:*"
    fi
else
    log_fail "Supervisor not installed"
fi

print_header "Application"

# Check .env file
if [ -f "$APP_PATH/.env" ]; then
    log_pass "Environment file exists"
    
    if grep -q "^APP_KEY=base64:" "$APP_PATH/.env"; then
        log_pass "Application key is set"
    else
        log_fail "Application key is NOT set"
    fi
else
    log_fail "Environment file not found at $APP_PATH/.env"
fi

# Check application key
if php -r "require '$APP_PATH/bootstrap/app.php';" >/dev/null 2>&1; then
    log_pass "Application can be bootstrapped"
else
    log_fail "Cannot bootstrap application"
fi

# Check storage directory
if [ -d "$APP_PATH/storage" ] && [ -w "$APP_PATH/storage" ]; then
    log_pass "Storage directory is writable"
else
    log_fail "Storage directory is NOT writable"
fi

# Check if database tables exist
TABLE_COUNT=$(mysql -u ciblerh -p$(grep DB_PASSWORD $APP_PATH/.env | cut -d'=' -f2) ciblerh -e "SELECT COUNT(*) FROM information_schema.TABLES;" 2>/dev/null | tail -1 || echo 0)

if [ "$TABLE_COUNT" -gt 10 ]; then
    log_pass "Database appears to have data ($TABLE_COUNT tables)"
else
    log_warn "Database may not have been migrated yet"
    log_info "Run migrations: php artisan migrate"
fi

print_header "SSL Certificate"

# Check SSL certificate
if [ -f "/etc/letsencrypt/live/$APP_DOMAIN/fullchain.pem" ]; then
    EXPIRY=$(openssl x509 -in "/etc/letsencrypt/live/$APP_DOMAIN/fullchain.pem" -noout -enddate | cut -d'=' -f2)
    log_pass "SSL Certificate found, expires: $EXPIRY"
else
    log_warn "SSL certificate not found. Check ENABLE_SSL configuration."
fi

print_header "System Resources"

# Check Disk Space
DISK_USAGE=$(df -h "$APP_PATH" | tail -1 | awk '{print $5}')
DISK_AVAILABLE=$(df -h "$APP_PATH" | tail -1 | awk '{print $4}')
log_info "Disk space: $DISK_AVAILABLE available, $DISK_USAGE used"

# Check Memory
MEMORY=$(free -h | awk '/^Mem:/ {print $3 "/" $2}')
log_info "Memory usage: $MEMORY"

# Check CPU Load
LOAD=$(uptime | awk -F'average:' '{print $2}')
log_info "CPU load:$LOAD"

print_header "Logs & Troubleshooting"

# Check for recent errors
if [ -f "$APP_PATH/storage/logs/laravel.log" ]; then
    ERROR_COUNT=$(grep -c "ERROR\|Exception" "$APP_PATH/storage/logs/laravel.log" 2>/dev/null | wc -l || echo 0)
    if [ "$ERROR_COUNT" -gt 0 ]; then
        log_warn "Found $ERROR_COUNT error entries in logs"
        log_info "View with: tail -f $APP_PATH/storage/logs/laravel.log"
    else
        log_pass "No recent errors in logs"
    fi
else
    log_warn "Application log file not found"
fi

print_header "Quick Reference"

cat << 'EOF'

📋 USEFUL COMMANDS:

System Status:
  sudo monitor-ciblerh                    # Overall system status

Services:
  systemctl status php8.4-fpm             # Check PHP-FPM
  systemctl status nginx                  # Check Nginx
  systemctl status redis-server           # Check Redis
  systemctl status mysql                  # Check MySQL

Queues:
  supervisorctl status laravel-queues:*   # Check queue workers
  supervisorctl restart laravel-queues:*  # Restart all workers
  supervisorctl restart queue-emails:*    # Restart specific queue

Logs:
  tail -f /var/www/ciblerh/storage/logs/laravel.log
  tail -f /var/log/nginx/error.log
  supervisorctl tail queue-emails -f

Database:
  mysql -u ciblerh -p ciblerh
  php artisan migrate
  php artisan db:seed

Cache & Optimization:
  php artisan optimize:clear
  php artisan config:cache

Deployment Updates:
  cd /var/www/ciblerh
  git pull origin main
  composer install --no-dev
  npm install && npm run build
  php artisan migrate --force
  supervisorctl restart laravel-queues:*

Monitoring:
  htop                                    # System monitor
  ps aux | grep php                       # List PHP processes
  redis-cli INFO                          # Redis statistics

📁 IMPORTANT PATHS:

  Application:     /var/www/ciblerh
  Configuration:   /var/www/ciblerh/.env
  Logs:            /var/www/ciblerh/storage/logs/
  Web Root:        /var/www/ciblerh/public
  Nginx config:    /etc/nginx/sites-available/yourdomain.com
  Supervisor:      /etc/supervisor/conf.d/laravel-queues.conf
  PHP-FPM config:  /etc/php/8.4/fpm/pool.d/ciblerh.conf

🔐 SECURITY REMINDERS:

  ✓ Change default admin password
  ✓ Configure SMTP credentials
  ✓ Enable firewall (UFW)
  ✓ Set up regular backups
  ✓ Monitor logs regularly
  ✓ Keep system updated
  ✓ Use strong database passwords
  ✓ Restrict access to /admin paths

📞 SUPPORT:

  Logs:     tail -f /var/www/ciblerh/storage/logs/laravel.log
  Issues:   Check UBUNTU_DEPLOYMENT_GUIDE.md
  Status:   sudo monitor-ciblerh

EOF

print_header "Verification Complete"

echo -e "${GREEN}Deployment verification finished!${NC}"
echo ""
echo "If any items are marked with ✗, review the UBUNTU_DEPLOYMENT_GUIDE.md"
echo "or run: tail -f /var/www/ciblerh/storage/logs/laravel.log"
echo ""
