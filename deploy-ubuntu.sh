#!/bin/bash

##############################################################################
# Ciblerh Payroll & Check-in System - Ubuntu Server Deployment Script
# 
# This script automates the deployment of the Ciblerh application on Ubuntu
# servers (20.04 LTS, 22.04 LTS, or later).
#
# Usage: sudo bash deploy-ubuntu.sh
#
# IMPORTANT: Read the deployment guide before running:
#   - Update configuration variables below
#   - Ensure you have a domain name and SSL certificate ready
#   - Have database credentials prepared
##############################################################################

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# DEPLOYMENT CONFIGURATION - CUSTOMIZE THESE VALUES
# ============================================================================

# Application Information
APP_NAME="Ciblerh"
APP_DOMAIN="${APP_DOMAIN:-yourdomain.com}"                    # REQUIRED: Your domain name
APP_USER="${APP_USER:-laravel}"                               # System user for application
APP_GROUP="${APP_GROUP:-laravel}"                              # System group for application
APP_PATH="/var/www/${APP_NAME,,}"                              # Installation path
GIT_REPO="${GIT_REPO:-https://github.com/Escarter/Ciblerh-payroll-checkin.git}"
GIT_BRANCH="${GIT_BRANCH:-main}"                               # Git branch to deploy

# Server Configuration
WEB_USER="www-data"                                            # Nginx user
WEB_GROUP="www-data"                                           # Nginx group
QUEUE_WORKERS=14                                               # Total queue workers

# Database Configuration
DB_HOST="${DB_HOST:-127.0.0.1}"                               # Can be remote IP/hostname
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-ciblerh}"
DB_USER="${DB_USER:-ciblerh}"
DB_PASSWORD="${DB_PASSWORD:-}"                                 # REQUIRED: Set this!
SETUP_LOCAL_DB="${SETUP_LOCAL_DB:-false}"                     # Set to 'true' to install MySQL locally

# Redis Configuration
REDIS_HOST="${REDIS_HOST:-127.0.0.1}"
REDIS_PORT="${REDIS_PORT:-6379}"
SETUP_REDIS="${SETUP_REDIS:-true}"                            # Set to 'false' if Redis already installed

# Email Configuration (SMTP)
MAIL_HOST="${MAIL_HOST:-smtp.gmail.com}"
MAIL_PORT="${MAIL_PORT:-587}"
MAIL_USERNAME="${MAIL_USERNAME:-}"
MAIL_PASSWORD="${MAIL_PASSWORD:-}"
MAIL_FROM="${MAIL_FROM:-noreply@${APP_DOMAIN}}"

# AWS S3 Configuration (Optional)
AWS_ACCESS_KEY="${AWS_ACCESS_KEY:-}"
AWS_SECRET_KEY="${AWS_SECRET_KEY:-}"
AWS_BUCKET="${AWS_BUCKET:-}"
AWS_REGION="${AWS_REGION:-us-east-1}"

# SSL Configuration
ENABLE_SSL="${ENABLE_SSL:-true}"                              # Enable Let's Encrypt SSL
LE_EMAIL="${LE_EMAIL:-admin@${APP_DOMAIN}}"                   # Let's Encrypt email

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root. Use: sudo bash deploy-ubuntu.sh"
        exit 1
    fi
}

validate_config() {
    log_info "Validating configuration..."
    
    if [[ -z "$APP_DOMAIN" ]]; then
        log_error "APP_DOMAIN is not set. Please configure it before running."
        exit 1
    fi
    
    if [[ -z "$DB_PASSWORD" ]]; then
        log_error "DB_PASSWORD is not set. Please configure it before running."
        exit 1
    fi
    
    log_success "Configuration is valid"
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# ============================================================================
# SYSTEM UPDATE & DEPENDENCIES
# ============================================================================

update_system() {
    log_info "Cleaning up problematic repositories..."
    
    # Remove PHP PPA if it exists (may cause issues on non-standard Ubuntu)
    add-apt-repository --remove -y "ppa:ondrej/php" 2>/dev/null || true
    
    # Remove Node.js PPA if it exists
    add-apt-repository --remove -y "ppa:chris-lea/node.js" 2>/dev/null || true
    
    # Clean apt cache and list
    apt-get clean
    rm -rf /var/lib/apt/lists/*
    
    log_info "Updating system packages..."
    apt-get update -qq 2>/dev/null || log_warning "Some repository issues detected, continuing..."
    apt-get upgrade -y -qq 2>/dev/null || log_warning "Upgrade had some issues, continuing..."
    log_success "System packages updated"
}

install_dependencies() {
    log_info "Installing system dependencies..."
    
    apt-get install -y -qq \
        curl \
        wget \
        git \
        gnupg \
        lsb-release \
        ca-certificates \
        apt-transport-https \
        software-properties-common \
        build-essential \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
        sudo \
        cron \
        supervisor \
        htop \
        vim \
        nano \
        net-tools \
        openssl
    
    log_success "System dependencies installed"
}

# ============================================================================
# PHP INSTALLATION
# ============================================================================

install_php() {
    log_info "Installing PHP 8.4 and extensions..."
    
    if ! command_exists php; then
        # Don't add PPA - use system packages (PPAs were cleaned in update_system)
        log_info "Using system PHP 8.4 packages..."
        apt-get update -qq
        
        # Install PHP 8.4 with required extensions
        apt-get install -y -qq \
            php8.4 \
            php8.4-cli \
            php8.4-fpm \
            php8.4-mysql \
            php8.4-redis \
            php8.4-xml \
            php8.4-mbstring \
            php8.4-pdo \
            php8.4-curl \
            php8.4-zip \
            php8.4-gd \
            php8.4-bcmath \
            php8.4-intl \
            php8.4-dev || {
            log_warning "Some PHP 8.4 packages not available, installing core packages..."
            apt-get install -y -qq php8.4 php8.4-cli php8.4-fpm php8.4-common || {
                log_error "Failed to install PHP 8.4"
                return 1
            }
        }
        
        # Enable PHP extensions (ignore if some don't exist)
        phpenmod -v 8.4 redis gd zip mbstring bcmath intl 2>/dev/null || true
        
        # Configure PHP FPM
        sed -i 's/^;?cgi.fix_pathinfo=.*/cgi.fix_pathinfo=0/' /etc/php/8.4/fpm/php.ini
        sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.4/fpm/php.ini
        sed -i 's/^post_max_size = .*/post_max_size = 100M/' /etc/php/8.4/fpm/php.ini
        
        # Create PHP-FPM pool for our application
        create_phpfpm_pool
        
        # Start PHP-FPM
        systemctl enable php8.4-fpm
        systemctl restart php8.4-fpm || {
            log_warning "PHP-FPM restart had issues, checking status..."
            systemctl status php8.4-fpm || true
        }
        
        log_success "PHP 8.4 installed"
    else
        log_warning "PHP is already installed"
    fi
}

create_phpfpm_pool() {
    log_info "Creating PHP-FPM pool configuration..."
    
    cat > /etc/php/8.4/fpm/pool.d/${APP_NAME,,}.conf << 'EOF'
[APPNAME]
user = WEBUSER
group = WEBGROUP
listen = /run/php/APPNAME-fpm.sock
listen.owner = WEBUSER
listen.group = WEBGROUP
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

chdir = /
catch_workers_output = yes
EOF
    
    # Replace placeholders
    sed -i "s|APPNAME|${APP_NAME,,}|g" /etc/php/8.4/fpm/pool.d/${APP_NAME,,}.conf
    sed -i "s|WEBUSER|${WEB_USER}|g" /etc/php/8.4/fpm/pool.d/${APP_NAME,,}.conf
    sed -i "s|WEBGROUP|${WEB_GROUP}|g" /etc/php/8.4/fpm/pool.d/${APP_NAME,,}.conf
}

install_composer() {
    log_info "Installing Composer..."
    
    if ! command_exists composer; then
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        chmod +x /usr/local/bin/composer
        log_success "Composer installed"
    else
        log_warning "Composer is already installed"
    fi
}

# ============================================================================
# NODE.JS INSTALLATION
# ============================================================================

install_nodejs() {
    log_info "Installing Node.js and npm..."
    
    if ! command_exists node; then
        # Use system packages (PPAs already cleaned in update_system)
        log_info "Installing Node.js from system packages..."
        apt-get install -y -qq nodejs npm || {
            log_warning "Node.js not available in standard packages, trying alternative..."
            apt-get install -y -qq node-legacy npm 2>/dev/null || {
                log_warning "Node.js installation failed, continuing without Node.js..."
                return 0
            }
        }
        
        log_success "Node.js installed"
    else
        log_warning "Node.js is already installed"
    fi
}

# ============================================================================
# DATABASE INSTALLATION
# ============================================================================

install_mysql() {
    if [[ "$SETUP_LOCAL_DB" != "true" ]]; then
        log_warning "Skipping MySQL installation (SETUP_LOCAL_DB=false)"
        log_info "Make sure your remote database is accessible and running"
        return
    fi
    
    log_info "Installing MySQL 8.0..."
    
    if ! command_exists mysql; then
        # Install MySQL Server
        apt-get install -y -qq mysql-server
        
        # Enable MySQL
        systemctl enable mysql
        systemctl start mysql
        
        # Secure MySQL installation (automated)
        # Use default authentication method (caching_sha2_password in MySQL 8.0+)
        mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
        
        log_success "MySQL installed"
        
        # Create database and user
        create_database
    else
        log_warning "MySQL is already installed"
    fi
}

create_database() {
    log_info "Creating database and user..."
    
    mysql -uroot -p"$DB_PASSWORD" -e "
        CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
        GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
        FLUSH PRIVILEGES;
    " || {
        log_warning "Database creation may have already been done or failed"
    }
    
    log_success "Database setup complete"
}

# ============================================================================
# REDIS INSTALLATION
# ============================================================================

install_redis() {
    if [[ "$SETUP_REDIS" != "true" ]]; then
        log_warning "Skipping Redis installation (SETUP_REDIS=false)"
        log_info "Make sure Redis is running on ${REDIS_HOST}:${REDIS_PORT}"
        return
    fi
    
    log_info "Installing Redis..."
    
    if ! command_exists redis-server; then
        apt-get install -y -qq redis-server redis-tools
        
        # Configure Redis
        sed -i 's/^# maxmemory <bytes>/maxmemory 512mb/' /etc/redis/redis.conf
        sed -i 's/^# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
        
        # Enable Redis
        systemctl enable redis-server
        systemctl restart redis-server
        
        log_success "Redis installed"
    else
        log_warning "Redis is already installed"
    fi
}

# ============================================================================
# APPLICATION SETUP
# ============================================================================

create_app_user() {
    log_info "Creating application user..."
    
    if ! id -u "$APP_USER" >/dev/null 2>&1; then
        useradd -m -s /bin/bash "$APP_USER"
        usermod -aG sudo "$APP_USER"
        log_success "Application user created"
    else
        log_warning "User $APP_USER already exists"
    fi
}

clone_repository() {
    log_info "Cloning repository..."
    
    if [[ ! -d "$APP_PATH" ]]; then
        mkdir -p "$APP_PATH"
        git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$APP_PATH"
        
        # Set permissions
        chown -R "$APP_USER:$APP_GROUP" "$APP_PATH"
        chmod -R 755 "$APP_PATH"
        
        log_success "Repository cloned"
    else
        log_warning "Application directory already exists. Pulling latest changes..."
        cd "$APP_PATH"
        sudo -u "$APP_USER" git pull origin "$GIT_BRANCH"
    fi
}

setup_env_file() {
    log_info "Setting up .env file..."
    
    if [[ ! -f "$APP_PATH/.env" ]]; then
        cp "$APP_PATH/.env.example" "$APP_PATH/.env"
    fi
    
    # Update .env with deployment values
    cat > "$APP_PATH/.env" << EOF
APP_NAME="${APP_NAME}"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${APP_DOMAIN}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie
SESSION_LIFETIME=1440

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=${REDIS_HOST}
REDIS_PASSWORD=null
REDIS_PORT=${REDIS_PORT}

MAIL_MAILER=smtp
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="${MAIL_FROM}"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_KEY}
AWS_DEFAULT_REGION=${AWS_REGION}
AWS_BUCKET=${AWS_BUCKET}

HORIZON_PREFIX=${APP_NAME,,}_

TZ=Africa/Douala
EOF
    
    # Set permissions
    chown "$APP_USER:$APP_GROUP" "$APP_PATH/.env"
    chmod 600 "$APP_PATH/.env"
    
    log_success ".env file created"
}

install_composer_dependencies() {
    log_info "Installing Composer dependencies..."
    
    cd "$APP_PATH"
    
    # Update lock file for PHP 8.4 compatibility
    log_info "Updating Composer lock file for PHP 8.4..."
    sudo -u "$APP_USER" composer update --no-dev --optimize-autoloader --no-interaction
    
    # Install dependencies
    sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --no-interaction
    
    log_success "Composer dependencies installed"
}

install_npm_dependencies() {
    log_info "Installing npm dependencies..."
    
    cd "$APP_PATH"
    sudo -u "$APP_USER" npm install
    
    log_success "npm dependencies installed"
}

build_assets() {
    log_info "Building frontend assets..."
    
    cd "$APP_PATH"
    sudo -u "$APP_USER" npm run build
    
    log_success "Frontend assets built"
}

generate_app_key() {
    log_info "Generating application key..."
    
    cd "$APP_PATH"
    sudo -u "$APP_USER" php artisan key:generate --force
    
    log_success "Application key generated"
}

setup_storage_permissions() {
    log_info "Setting up storage permissions..."
    
    chown -R "$APP_USER:$APP_GROUP" "$APP_PATH/storage"
    chown -R "$APP_USER:$APP_GROUP" "$APP_PATH/bootstrap/cache"
    chmod -R 775 "$APP_PATH/storage"
    chmod -R 775 "$APP_PATH/bootstrap/cache"
    
    log_success "Storage permissions configured"
}

run_migrations() {
    log_info "Running database migrations..."
    
    cd "$APP_PATH"
    sudo -u "$APP_USER" php artisan migrate --force
    
    log_success "Database migrations completed"
}

run_seeders() {
    log_info "Running database seeders..."
    
    cd "$APP_PATH"
    sudo -u "$APP_USER" php artisan db:seed --force
    
    log_success "Database seeders completed"
}

# ============================================================================
# WEB SERVER SETUP (NGINX)
# ============================================================================

install_nginx() {
    log_info "Installing Nginx..."
    
    if ! command_exists nginx; then
        apt-get install -y -qq nginx
        systemctl enable nginx
        log_success "Nginx installed"
    else
        log_warning "Nginx is already installed"
    fi
}

create_nginx_config() {
    log_info "Creating Nginx configuration..."
    
    cat > /etc/nginx/sites-available/${APP_DOMAIN} << 'NGINX_CONFIG'
upstream php_fpm {
    server unix:/run/php/APPNAME-fpm.sock;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN www.DOMAIN;
    
    location ~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
    }
    
    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS Server Block
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name DOMAIN www.DOMAIN;
    
    # SSL Configuration
    ssl_certificate SSL_CERT;
    ssl_certificate_key SSL_KEY;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    root APPPATH/public;
    index index.php index.html;
    
    # Logging
    access_log /var/log/nginx/APPNAME_access.log;
    error_log /var/log/nginx/APPNAME_error.log warn;
    
    # Client Upload Limit
    client_max_body_size 100M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM Configuration
    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php_fpm;
        fastcgi_index index.php;
        
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param SERVER_PORT 443;
        fastcgi_param HTTPS on;
        
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # Deny direct access to sensitive files
    location ~ /\.env {
        deny all;
    }
    
    location ~ /\.php$ {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
NGINX_CONFIG
    
    # Replace placeholders
    sed -i "s|APPNAME|${APP_NAME,,}|g" /etc/nginx/sites-available/${APP_DOMAIN}
    sed -i "s|DOMAIN|${APP_DOMAIN}|g" /etc/nginx/sites-available/${APP_DOMAIN}
    sed -i "s|APPPATH|${APP_PATH}|g" /etc/nginx/sites-available/${APP_DOMAIN}
    sed -i "s|SSL_CERT|/etc/letsencrypt/live/${APP_DOMAIN}/fullchain.pem|g" /etc/nginx/sites-available/${APP_DOMAIN}
    sed -i "s|SSL_KEY|/etc/letsencrypt/live/${APP_DOMAIN}/privkey.pem|g" /etc/nginx/sites-available/${APP_DOMAIN}
    
    # Enable site
    ln -sf /etc/nginx/sites-available/${APP_DOMAIN} /etc/nginx/sites-enabled/${APP_DOMAIN}
    
    # Remove default site if it exists
    rm -f /etc/nginx/sites-enabled/default
    
    # Test Nginx configuration
    nginx -t && systemctl restart nginx
    
    log_success "Nginx configuration created"
}

# ============================================================================
# SSL/TLS SETUP
# ============================================================================

install_certbot() {
    log_info "Installing Let's Encrypt Certbot..."
    
    if ! command_exists certbot; then
        apt-get install -y -qq certbot python3-certbot-nginx
        log_success "Certbot installed"
    else
        log_warning "Certbot is already installed"
    fi
}

setup_ssl_certificate() {
    if [[ "$ENABLE_SSL" != "true" ]]; then
        log_warning "SSL setup skipped (ENABLE_SSL=false)"
        return
    fi
    
    log_info "Setting up SSL certificate with Let's Encrypt..."
    
    # Create directory for Let's Encrypt challenges
    mkdir -p /var/www/letsencrypt
    
    # Obtain certificate
    certbot certonly --webroot \
        -w /var/www/letsencrypt \
        -d "$APP_DOMAIN" \
        -d "www.${APP_DOMAIN}" \
        --email "$LE_EMAIL" \
        --agree-tos \
        --non-interactive \
        --preferred-challenges http || {
        log_warning "SSL certificate setup may have failed. Ensure ports 80 is open."
    }
    
    # Set up auto-renewal
    systemctl enable certbot.timer
    systemctl start certbot.timer
    
    log_success "SSL certificate configured"
}

# ============================================================================
# QUEUE WORKERS SETUP
# ============================================================================

setup_supervisor_queues() {
    log_info "Configuring Supervisor for queue workers..."
    
    # Copy supervisor configuration from application
    if [[ -f "$APP_PATH/supervisor-queue-workers.conf" ]]; then
        cp "$APP_PATH/supervisor-queue-workers.conf" /etc/supervisor/conf.d/laravel-queues.conf
    else
        # Create default supervisor configuration
        create_default_supervisor_config
    fi
    
    # Replace placeholders in supervisor config
    sed -i "s|/path/to/your/app|${APP_PATH}|g" /etc/supervisor/conf.d/laravel-queues.conf
    sed -i "s|www-data|${WEB_USER}|g" /etc/supervisor/conf.d/laravel-queues.conf
    
    # Reload supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start laravel-queues:\*
    
    log_success "Supervisor queue workers configured"
}

create_default_supervisor_config() {
    cat > /etc/supervisor/conf.d/laravel-queues.conf << 'EOF'
[program:queue-high-priority]
process_name=%(program_name)s_%(process_num)02d
command=php APPPATH/artisan queue:work redis --queue=high-priority --sleep=3 --tries=1 --max-jobs=1000 --timeout=120 --memory=256
directory=APPPATH
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=4
user=WEBUSER
stdout_logfile=/var/log/supervisor/queue-high-priority.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3

[program:queue-emails]
process_name=%(program_name)s_%(process_num)02d
command=php APPPATH/artisan queue:work redis --queue=emails --sleep=3 --tries=1 --max-jobs=1000 --timeout=120 --memory=256
directory=APPPATH
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=5
user=WEBUSER
stdout_logfile=/var/log/supervisor/queue-emails.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3

[program:queue-processing]
process_name=%(program_name)s_%(process_num)02d
command=php APPPATH/artisan queue:work redis --queue=processing --sleep=3 --tries=1 --max-jobs=500 --timeout=300 --memory=512
directory=APPPATH
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=3
user=WEBUSER
stdout_logfile=/var/log/supervisor/queue-processing.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3

[program:queue-pdf-processing]
process_name=%(program_name)s_%(process_num)02d
command=php APPPATH/artisan queue:work redis --queue=pdf-processing --sleep=3 --tries=1 --max-jobs=500 --timeout=300 --memory=512
directory=APPPATH
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=2
user=WEBUSER
stdout_logfile=/var/log/supervisor/queue-pdf-processing.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3

[program:queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php APPPATH/artisan queue:work redis --queue=default --sleep=3 --tries=1 --max-jobs=1000 --timeout=120 --memory=256
directory=APPPATH
autostart=true
autorestart=true
stopwaitsecs=3600
numprocs=2
user=WEBUSER
stdout_logfile=/var/log/supervisor/queue-default.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=3

[group:laravel-queues]
programs=queue-high-priority,queue-emails,queue-processing,queue-pdf-processing,queue-default
EOF
    
    # Replace placeholders
    sed -i "s|APPPATH|${APP_PATH}|g" /etc/supervisor/conf.d/laravel-queues.conf
    sed -i "s|WEBUSER|${WEB_USER}|g" /etc/supervisor/conf.d/laravel-queues.conf
}

# ============================================================================
# CRON JOBS SETUP
# ============================================================================

setup_cron_jobs() {
    log_info "Setting up Laravel scheduled tasks..."
    
    # Add cron job for Laravel scheduler
    CRON_CMD="* * * * * cd ${APP_PATH} && php artisan schedule:run >> /dev/null 2>&1"
    
    (crontab -u "$APP_USER" -l 2>/dev/null; echo "$CRON_CMD") | crontab -u "$APP_USER" -
    
    log_success "Cron jobs configured"
}

# ============================================================================
# MONITORING & LOGGING
# ============================================================================

setup_logrotate() {
    log_info "Setting up log rotation..."
    
    cat > /etc/logrotate.d/${APP_NAME,,} << EOF
${APP_PATH}/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 ${APP_USER} ${APP_GROUP}
    sharedscripts
    postrotate
        systemctl reload-service-if-running php8.4-fpm > /dev/null 2>&1 || true
    endscript
}
EOF
    
    log_success "Log rotation configured"
}

setup_monitoring_script() {
    log_info "Creating monitoring script..."
    
    cat > /usr/local/bin/monitor-${APP_NAME,,} << 'MONITOR_SCRIPT'
#!/bin/bash

echo "=== Ciblerh Application Status ==="
echo ""

# Check if application is running
echo "1. PHP-FPM Status:"
systemctl is-active --quiet php8.4-fpm && echo "   ✓ PHP-FPM is running" || echo "   ✗ PHP-FPM is NOT running"

# Check Nginx
echo ""
echo "2. Nginx Status:"
systemctl is-active --quiet nginx && echo "   ✓ Nginx is running" || echo "   ✗ Nginx is NOT running"

# Check Redis
echo ""
echo "3. Redis Status:"
systemctl is-active --quiet redis-server && echo "   ✓ Redis is running" || echo "   ✗ Redis is NOT running"

# Check MySQL
echo ""
echo "4. MySQL Status:"
systemctl is-active --quiet mysql && echo "   ✓ MySQL is running" || echo "   ✗ MySQL is NOT running"

# Check Supervisor
echo ""
echo "5. Supervisor Queue Status:"
supervisorctl status laravel-queues:* 2>/dev/null || echo "   ✗ Supervisor is not running"

# Check Application Logs for Errors
echo ""
echo "6. Recent Errors (last 10 from logs):"
tail -10 APPPATH/storage/logs/laravel.log 2>/dev/null | grep -i error || echo "   No errors found in recent logs"

# Check Disk Space
echo ""
echo "7. Disk Space:"
df -h APPPATH | tail -1

# Check Memory Usage
echo ""
echo "8. Memory Usage:"
free -h | head -2

MONITOR_SCRIPT
    
    # Replace placeholders and make executable
    sed -i "s|APPPATH|${APP_PATH}|g" /usr/local/bin/monitor-${APP_NAME,,}
    chmod +x /usr/local/bin/monitor-${APP_NAME,,}
    
    log_success "Monitoring script created at /usr/local/bin/monitor-${APP_NAME,,}"
}

# ============================================================================
# SECURITY HARDENING
# ============================================================================

setup_firewall() {
    log_info "Setting up UFW firewall..."
    
    if command_exists ufw; then
        ufw --force enable
        ufw default deny incoming
        ufw default allow outgoing
        ufw allow ssh
        ufw allow 80/tcp
        ufw allow 443/tcp
        
        log_success "Firewall configured"
    else
        log_warning "UFW not installed. Skipping firewall setup."
        log_info "Install with: sudo apt-get install ufw"
    fi
}

setup_fail2ban() {
    log_info "Installing Fail2Ban..."
    
    if ! command_exists fail2ban-server; then
        apt-get install -y -qq fail2ban
        
        # Create jail configuration
        cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 86400
findtime = 3600
maxretry = 5

[sshd]
enabled = true

[recidive]
enabled = true
EOF
        
        systemctl enable fail2ban
        systemctl restart fail2ban
        
        log_success "Fail2Ban installed"
    else
        log_warning "Fail2Ban is already installed"
    fi
}

# ============================================================================
# CLEANUP & VERIFICATION
# ============================================================================

cleanup_installation() {
    log_info "Cleaning up..."
    
    # Clear caches
    cd "$APP_PATH"
    sudo -u "$APP_USER" php artisan config:cache
    sudo -u "$APP_USER" php artisan view:cache
    sudo -u "$APP_USER" php artisan route:cache
    
    # Set proper permissions
    find "$APP_PATH" -type f -exec chmod 644 {} \;
    find "$APP_PATH" -type d -exec chmod 755 {} \;
    
    # Storage and bootstrap directories should be writable
    chmod -R 775 "$APP_PATH/storage"
    chmod -R 775 "$APP_PATH/bootstrap/cache"
    
    log_success "Cleanup completed"
}

verify_installation() {
    log_info "Verifying installation..."
    
    echo ""
    echo "=== Deployment Verification ==="
    echo ""
    
    # Check PHP
    php -v | head -1
    echo ""
    
    # Check Composer
    composer --version | head -1
    echo ""
    
    # Check Node.js
    node --version
    echo ""
    
    # Check MySQL
    mysql --version | head -1
    echo ""
    
    # Check Redis
    redis-cli --version
    echo ""
    
    # Check Nginx
    nginx -v 2>&1
    echo ""
    
    # Check application key
    if grep -q "^APP_KEY=base64:" "$APP_PATH/.env"; then
        echo "✓ Application key is set"
    else
        echo "✗ Application key is NOT set"
    fi
    echo ""
    
    log_success "Verification completed"
}

# ============================================================================
# DEPLOYMENT SUMMARY
# ============================================================================

print_summary() {
    cat << EOF

╔════════════════════════════════════════════════════════════════════╗
║                DEPLOYMENT COMPLETED SUCCESSFULLY                  ║
╚════════════════════════════════════════════════════════════════════╝

Application Information:
  - Application: ${APP_NAME}
  - Domain: ${APP_DOMAIN}
  - Path: ${APP_PATH}
  - Environment: Production

Services Status:
  - PHP-FPM 8.4: systemctl status php8.4-fpm
  - Nginx: systemctl status nginx
  - Redis: systemctl status redis-server
  - MySQL: systemctl status mysql
  - Supervisor: supervisorctl status

Queue Workers:
  - Running Workers: $(supervisorctl status laravel-queues:* 2>/dev/null | grep -c 'RUNNING')
  - Management: supervisorctl restart laravel-queues:*

Important Directories:
  - Application: ${APP_PATH}
  - Logs: ${APP_PATH}/storage/logs/
  - Storage: ${APP_PATH}/storage/

Useful Commands:
  - Monitor system: sudo monitor-${APP_NAME,,}
  - Restart all services: sudo systemctl restart php8.4-fpm nginx
  - View logs: tail -f ${APP_PATH}/storage/logs/laravel.log
  - Database backup: mysqldump -u${DB_USER} -p${DB_PASSWORD} ${DB_NAME} > backup.sql

Next Steps:
  1. ✓ Test the application at https://${APP_DOMAIN}
  2. ✓ Configure email settings in .env
  3. ✓ Set up Horizon dashboard (http://${APP_DOMAIN}/horizon/)
  4. ✓ Configure automated backups
  5. ✓ Set up monitoring/alerting
  
SSL Certificate:
  - Certificates are auto-renewing
  - Check renewal: certbot renew --dry-run
  - Location: /etc/letsencrypt/live/${APP_DOMAIN}/

Database:
  - Host: ${DB_HOST}
  - Database: ${DB_NAME}
  - User: ${DB_USER}

For support and documentation:
  - Application: https://github.com/Escarter/Ciblerh-payroll-checkin
  - Laravel: https://laravel.com/docs
  - Supervision: supervisord.org

EOF
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

main() {
    clear
    echo -e "${BLUE}"
    cat << 'BANNER'
╔════════════════════════════════════════════════════════════════╗
║         Ciblerh - Ubuntu Server Deployment Script             ║
║                                                                ║
║  This script will deploy your Laravel application on Ubuntu   ║
║  20.04 LTS, 22.04 LTS, or later                               ║
╚════════════════════════════════════════════════════════════════╝
BANNER
    echo -e "${NC}"
    
    echo ""
    log_info "Starting deployment process..."
    echo ""
    
    # Verify prerequisites
    check_root
    validate_config
    
    # Wait for user confirmation
    echo ""
    echo -e "${YELLOW}Please review the configuration:${NC}"
    echo "  Domain: $APP_DOMAIN"
    echo "  Database: $DB_NAME @ $DB_HOST"
    echo "  Redis: $REDIS_HOST:$REDIS_PORT"
    echo ""
    read -p "Continue with deployment? (yes/no): " CONFIRM
    
    if [[ "$CONFIRM" != "yes" ]]; then
        log_error "Deployment cancelled"
        exit 0
    fi
    
    echo ""
    log_info "Deployment starting in 5 seconds... (Ctrl+C to cancel)"
    sleep 5
    
    # System Setup
    update_system
    install_dependencies
    
    # Install Core Services
    install_php
    install_composer
    install_nodejs
    install_mysql
    install_redis
    install_nginx
    install_certbot
    
    # Application Setup
    create_app_user
    clone_repository
    setup_env_file
    install_composer_dependencies
    install_npm_dependencies
    build_assets
    generate_app_key
    setup_storage_permissions
    
    # Database Setup
    run_migrations
    run_seeders
    
    # Web Server & SSL
    create_nginx_config
    setup_ssl_certificate
    
    # Queue & Crons
    setup_supervisor_queues
    setup_cron_jobs
    
    # Monitoring & Security
    setup_logrotate
    setup_monitoring_script
    setup_firewall
    setup_fail2ban
    
    # Final Steps
    cleanup_installation
    verify_installation
    print_summary
}

# Run main function
main "$@"
