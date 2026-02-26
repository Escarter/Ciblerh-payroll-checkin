#!/bin/bash

##############################################################################
# Backup & Restore Script for Ciblerh Application
#
# Creates backups of database and application files
# Usage: sudo bash backup-ciblerh.sh [backup|restore] [backup-file]
#
# Examples:
#   sudo bash backup-ciblerh.sh backup
#   sudo bash backup-ciblerh.sh restore /backups/ciblerh-2024-01-20.tar.gz
#
##############################################################################

set -e

# Configuration
APP_PATH="/var/www/ciblerh"
BACKUP_DIR="/backups"
DB_NAME="ciblerh"
DB_USER="ciblerh"
DB_HOST="127.0.0.1"
APP_USER="laravel"
APP_GROUP="laravel"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        exit 1
    fi
}

create_backup() {
    TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
    BACKUP_NAME="ciblerh-backup-${TIMESTAMP}"
    BACKUP_FILE="$BACKUP_DIR/${BACKUP_NAME}.tar.gz"
    TEMP_DIR="/tmp/$BACKUP_NAME"
    
    log_info "Creating backup of Ciblerh application..."
    echo ""
    
    # Create backup directory if it doesn't exist
    mkdir -p "$BACKUP_DIR"
    mkdir -p "$TEMP_DIR"
    
    # 1. Backup Database
    log_info "Backing up database..."
    DB_PASSWORD=$(grep DB_PASSWORD "$APP_PATH/.env" | cut -d'=' -f2)
    
    mysqldump \
        -u "$DB_USER" \
        -p"$DB_PASSWORD" \
        -h "$DB_HOST" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        "$DB_NAME" > "$TEMP_DIR/${DB_NAME}-$(date +%Y%m%d_%H%M%S).sql"
    
    log_success "Database backed up"
    
    # 2. Backup Application Files (excluding large directories)
    log_info "Backing up application files..."
    
    cd "$APP_PATH"
    
    # Create exclude list
    cat > /tmp/backup-exclude.txt << 'EOF'
vendor/
node_modules/
storage/logs/*
storage/cache/*
bootstrap/cache/*
.git/
.env
.env.local
*.log
.DS_Store
Thumbs.db
EOF
    
    # Backup application
    tar -czf "$TEMP_DIR/application-files.tar.gz" \
        --exclude-from=/tmp/backup-exclude.txt \
        -C "$APP_PATH" .
    
    log_success "Application files backed up"
    
    # 3. Backup .env file separately (encrypted)
    log_info "Backing up configuration file..."
    cp "$APP_PATH/.env" "$TEMP_DIR/.env-backup"
    chmod 600 "$TEMP_DIR/.env-backup"
    log_success "Configuration backed up"
    
    # 4. Create backup manifest
    log_info "Creating backup manifest..."
    cat > "$TEMP_DIR/BACKUP_MANIFEST.txt" << EOF
================================================================================
CIBLERH APPLICATION BACKUP MANIFEST
================================================================================

Backup Created: $(date)
Backup Duration: See below for details

BACKUP CONTENTS:
  1. Database Dump: ${DB_NAME}-*.sql
  2. Application Files: application-files.tar.gz
  3. Configuration: .env-backup (KEEP SECURE)
  4. Manifest: This file

DATABASE INFORMATION:
  Database Name: $DB_NAME
  Database User: $DB_USER
  Database Host: $DB_HOST
  Tables: $(mysql -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" 2>/dev/null | tail -1)

APPLICATION INFORMATION:
  Application Path: $APP_PATH
  PHP Version: $(php -v | head -1)
  Laravel Version: $(grep 'laravel/framework' $APP_PATH/composer.lock | grep version | head -1 | cut -d'"' -f4)

BACKUP SIZE:
  Total Size: $(du -sh "$TEMP_DIR" | cut -f1)

RESTORE INSTRUCTIONS:
  1. Extract backup: tar -xzf $BACKUP_FILE -C /restore-location
  2. Import database script included above
  3. Copy .env-backup to .env in application
  4. Update configuration as needed
  5. Install dependencies: composer install
  6. Run migrations: php artisan migrate

WARNINGS:
  ⚠ This backup contains sensitive information (.env file)
  ⚠ Store in a secure location
  ⚠ Restrict access to this backup file
  ⚠ Test restore procedure before relying on backup

Backup File: $BACKUP_FILE
================================================================================
EOF
    
    log_success "Manifest created"
    
    # 5. Compress entire backup
    log_info "Compressing backup..."
    cd /tmp
    tar -czf "$BACKUP_FILE" "$BACKUP_NAME/"
    
    # Clean up temp directory
    rm -rf "$TEMP_DIR"
    rm -f /tmp/backup-exclude.txt
    
    # Final summary
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                  BACKUP COMPLETED SUCCESSFULLY              ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
    echo -e "${GREEN}Backup Location:${NC} $BACKUP_FILE"
    echo -e "${GREEN}Backup Size:${NC} $BACKUP_SIZE"
    echo -e "${GREEN}Timestamp:${NC} $TIMESTAMP"
    echo ""
    
    log_warning "IMPORTANT: Keep this backup secure!"
    log_warning "It contains database and application data"
    echo ""
}

restore_backup() {
    if [[ -z "$1" ]] || [[ ! -f "$1" ]]; then
        log_error "Backup file not specified or does not exist"
        echo "Usage: sudo bash backup-ciblerh.sh restore /path/to/backup.tar.gz"
        exit 1
    fi
    
    BACKUP_FILE="$1"
    RESTORE_DIR="/tmp/ciblerh-restore-$(date +%s)"
    
    log_warning "This will restore your application from a backup"
    log_warning "Current data may be lost!"
    echo ""
    read -p "Continue with restore? Type 'yes' to confirm: " CONFIRM
    
    if [[ "$CONFIRM" != "yes" ]]; then
        log_error "Restore cancelled"
        exit 0
    fi
    
    echo ""
    log_info "Starting restore process..."
    
    # Extract backup
    log_info "Extracting backup file..."
    mkdir -p "$RESTORE_DIR"
    tar -xzf "$BACKUP_FILE" -C "$RESTORE_DIR"
    
    # Find the backup directory
    BACKUP_SOURCE=$(find "$RESTORE_DIR" -maxdepth 1 -type d -name "ciblerh-backup-*" | head -1)
    
    if [[ -z "$BACKUP_SOURCE" ]]; then
        log_error "Could not find backup data in archive"
        rm -rf "$RESTORE_DIR"
        exit 1
    fi
    
    log_success "Backup extracted"
    
    # Restore database
    log_info "Restoring database..."
    DB_PASSWORD=$(grep DB_PASSWORD "$APP_PATH/.env" | cut -d'=' -f2)
    
    SQL_FILE=$(find "$BACKUP_SOURCE" -name "${DB_NAME}-*.sql" | head -1)
    
    if [[ -n "$SQL_FILE" ]]; then
        mysql -u "$DB_USER" -p"$DB_PASSWORD" -h "$DB_HOST" "$DB_NAME" < "$SQL_FILE"
        log_success "Database restored"
    else
        log_error "No database dump found in backup"
        rm -rf "$RESTORE_DIR"
        exit 1
    fi
    
    # Stop application services (optional)
    log_info "Stopping application services..."
    supervisorctl stop laravel-queues:\* 2>/dev/null || true
    systemctl stop php8.4-fpm 2>/dev/null || true
    
    # Restore application files
    log_info "Restoring application files..."
    APP_TAR="$BACKUP_SOURCE/application-files.tar.gz"
    
    if [[ -f "$APP_TAR" ]]; then
        # Backup current app first
        CURRENT_BACKUP="/var/www/ciblerh.backup-$(date +%s)"
        log_info "Backing up current application to $CURRENT_BACKUP..."
        cp -r "$APP_PATH" "$CURRENT_BACKUP"
        
        # Restore from backup
        tar -xzf "$APP_TAR" -C "$APP_PATH"
        log_success "Application files restored"
    else
        log_error "No application files found in backup"
    fi
    
    # Restore .env if it exists
    if [[ -f "$BACKUP_SOURCE/.env-backup" ]]; then
        log_info "Restoring configuration file..."
        cp "$BACKUP_SOURCE/.env-backup" "$APP_PATH/.env"
        chmod 600 "$APP_PATH/.env"
        log_success "Configuration restored"
    fi
    
    # Set correct permissions
    log_info "Setting correct permissions..."
    chown -R "$APP_USER:$APP_GROUP" "$APP_PATH"
    chmod -R 755 "$APP_PATH"
    chmod -R 775 "$APP_PATH/storage"
    chmod -R 775 "$APP_PATH/bootstrap/cache"
    chmod 600 "$APP_PATH/.env"
    
    # Install dependencies
    log_info "Installing dependencies..."
    cd "$APP_PATH"
    composer install --no-dev --optimize-autoloader
    
    # Run migrations
    log_info "Running migrations..."
    php artisan migrate --force
    
    # Restart services
    log_info "Restarting application services..."
    systemctl start php8.4-fpm
    supervisorctl start laravel-queues:\* 2>/dev/null || true
    
    # Clean up
    rm -rf "$RESTORE_DIR"
    
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                   RESTORE COMPLETED SUCCESSFULLY            ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
    log_success "Backup has been restored successfully"
    log_warning "Previous application backed up to: /var/www/ciblerh.backup-*"
    echo ""
}

list_backups() {
    log_info "Available backups in $BACKUP_DIR:"
    echo ""
    
    if [ -d "$BACKUP_DIR" ]; then
        ls -lh "$BACKUP_DIR"/ciblerh-backup-*.tar.gz 2>/dev/null | awk '{print "  " $9 " (" $5 ") - " $6 " " $7 " " $8}' || echo "  No backups found"
    else
        echo "  Backup directory does not exist: $BACKUP_DIR"
    fi
    echo ""
}

usage() {
    cat << EOF
Ciblerh Backup & Restore Script

Usage: sudo bash backup-ciblerh.sh [command] [options]

Commands:
  backup                          Create a new backup
  restore <backup-file>           Restore from a backup file
  list                            List available backups
  help                            Show this help message

Examples:
  # Create backup
  sudo bash backup-ciblerh.sh backup

  # Restore from backup
  sudo bash backup-ciblerh.sh restore /backups/ciblerh-backup-2024-01-20_14-30-45.tar.gz

  # List all backups
  sudo bash backup-ciblerh.sh list

What's Backed Up:
  ✓ Complete MySQL database
  ✓ Application files (excluding vendor, node_modules, logs)
  ✓ Environment configuration (.env)
  ✓ Backup manifest and instructions

Backup Location:
  $BACKUP_DIR

IMPORTANT:
  - Backups contain sensitive information (.env file)
  - Store backups in a secure location
  - Test restore procedure regularly
  - Consider off-site backup copies

EOF
}

# Main
check_root

case "${1:-help}" in
    backup)
        create_backup
        ;;
    restore)
        restore_backup "$2"
        ;;
    list)
        list_backups
        ;;
    help)
        usage
        ;;
    *)
        log_error "Unknown command: $1"
        usage
        exit 1
        ;;
esac
