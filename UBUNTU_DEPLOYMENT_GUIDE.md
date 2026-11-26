# Ubuntu Deployment Guide - Ciblerh Payroll & Check-in System

## Overview

This guide provides step-by-step instructions to deploy the Ciblerh application on Ubuntu servers (20.04 LTS, 22.04 LTS, or later) without using Docker.

## Prerequisites

Before starting, ensure you have:

1. **Ubuntu Server** (18.04 LTS or later)
   - Cloud VPS or dedicated server
   - Minimum: 2 CPU cores, 4GB RAM, 20GB storage
   - Recommended: 4 CPU cores, 8GB RAM, 50GB SSD
   
2. **Domain Name**
   - Already owned domain or subdomain
   - Access to DNS records
   
3. **SSH Access**
   - SSH key or password for root user
   - Terminal/SSH client (PuTTY, Terminal, etc.)

4. **Optional but Recommended**
   - SMTP server for emails (Gmail, SendGrid, AWS SES, etc.)
   - AWS S3 bucket (if using file storage)

## Deployment Steps

### Step 1: Server Initial Setup

Connect to your server via SSH:

```bash
ssh root@your_server_ip
```

Update the system:

```bash
apt-get update && apt-get upgrade -y
```

### Step 2: Configure Deployment Variables

Create a deployment configuration file:

```bash
nano /root/deployment-config.sh
```

Copy and customize the following (adjust values in **BOLD**):

```bash
#!/bin/bash

# Domain Configuration
export APP_DOMAIN="**yourdomain.com**"
export APP_NAME="Ciblerh"

# Database Configuration
export DB_HOST="127.0.0.1"           # or remote IP if using external DB
export DB_PORT="3306"
export DB_NAME="ciblerh"
export DB_USER="ciblerh"
export DB_PASSWORD="**SECURE_PASSWORD_HERE**"  # Generate: openssl rand -base64 32
export SETUP_LOCAL_DB="true"         # Set to false if using external database

# Redis Configuration
export REDIS_HOST="127.0.0.1"
export REDIS_PORT="6379"
export SETUP_REDIS="true"            # Set to false if Redis already installed

# Email Configuration (SMTP)
export MAIL_HOST="smtp.gmail.com"
export MAIL_PORT="587"
export MAIL_USERNAME="**your-email@gmail.com**"
export MAIL_PASSWORD="**your-app-password**"   # Gmail app password or SMTP token
export MAIL_FROM="noreply@yourdomain.com"

# AWS S3 (Optional - leave empty if not using)
export AWS_ACCESS_KEY=""
export AWS_SECRET_KEY=""
export AWS_BUCKET=""
export AWS_REGION="us-east-1"

# SSL Configuration
export ENABLE_SSL="true"
export LE_EMAIL="admin@yourdomain.com"

# Git Configuration
export GIT_REPO="https://github.com/Escarter/Ciblerh-payroll-checkin.git"
export GIT_BRANCH="main"
```

Source the configuration:

```bash
source /root/deployment-config.sh
```

### Step 3: Download and Run Deployment Script

Download the deployment script:

```bash
cd /tmp
wget https://raw.githubusercontent.com/Escarter/Ciblerh-payroll-checkin/main/deploy-ubuntu.sh
chmod +x deploy-ubuntu.sh
```

Or if you have local access:

```bash
scp deploy-ubuntu.sh root@your_server_ip:/tmp/
```

### Step 4: Execute Deployment

Run the deployment script with your configuration:

```bash
sudo bash /tmp/deploy-ubuntu.sh
```

The script will:
- ✓ Update system packages
- ✓ Install PHP 8.4 and extensions
- ✓ Install Node.js and npm
- ✓ Install and configure MySQL
- ✓ Install and configure Redis
- ✓ Install and configure Nginx
- ✓ Clone the repository
- ✓ Install dependencies (Composer & npm)
- ✓ Build frontend assets
- ✓ Run database migrations
- ✓ Configure SSL with Let's Encrypt
- ✓ Setup Supervisor for queue workers
- ✓ Configure cron jobs
- ✓ Setup monitoring scripts
- ✓ Configure firewall

### Step 5: Post-Deployment Verification

After deployment completes:

1. **Check System Status**:
   ```bash
   sudo monitor-ciblerh
   ```

2. **Verify Services**:
   ```bash
   systemctl status php8.2-fpm nginx redis-server mysql
   ```

3. **Test Application**:
   - Open browser and navigate to: `https://yourdomain.com`
   - Login with default credentials (if seeded)

4. **Check Queue Workers**:
   ```bash
   supervisorctl status laravel-queues:*
   ```

5. **View Logs**:
   ```bash
   tail -f /var/www/ciblerh/storage/logs/laravel.log
   ```

## Common Tasks

### Viewing Application Logs

```bash
# Real-time logs
tail -f /var/www/ciblerh/storage/logs/laravel.log

# Last 100 lines
tail -100 /var/www/ciblerh/storage/logs/laravel.log

# Search for errors
grep -i error /var/www/ciblerh/storage/logs/laravel.log
```

### Managing Queue Workers

```bash
# View status
supervisorctl status laravel-queues:*

# Restart all workers
supervisorctl restart laravel-queues:*

# Restart specific queue
supervisorctl restart queue-emails:*

# Stop all workers
supervisorctl stop laravel-queues:*

# Start all workers
supervisorctl start laravel-queues:*
```

### Database Backups

```bash
# Create backup
mysqldump -u ciblerh -p ciblerh > /backup/ciblerh-$(date +%Y-%m-%d).sql

# Restore from backup
mysql -u ciblerh -p ciblerh < /backup/ciblerh-2024-01-20.sql

# Set up automatic daily backups
```

### Clearing Caches

```bash
cd /var/www/ciblerh

# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear

# All at once
php artisan optimize:clear
```

### Database Migrations

```bash
cd /var/www/ciblerh

# Run pending migrations
sudo -u laravel php artisan migrate

# Rollback last migration
sudo -u laravel php artisan migrate:rollback

# Rollback all migrations
sudo -u laravel php artisan migrate:reset

# Refresh database
sudo -u laravel php artisan migrate:refresh
```

### Deploying Updates

```bash
cd /var/www/ciblerh

# Pull latest code
sudo -u laravel git pull origin main

# Reinstall dependencies if any changed
sudo -u laravel composer install --no-dev --optimize-autoloader
sudo -u laravel npm install
sudo -u laravel npm run build

# Run migrations if any
sudo -u laravel php artisan migrate --force

# Clear caches
sudo -u laravel php artisan optimize:clear

# Restart queue workers
supervisorctl restart laravel-queues:*
```

## Scaling & Performance

### Adjusting Queue Workers

Edit `/etc/supervisor/conf.d/laravel-queues.conf`:

```bash
nano /etc/supervisor/conf.d/laravel-queues.conf
```

Default worker counts:
- `queue-high-priority`: 4 workers (critical operations)
- `queue-emails`: 5 workers (email/SMS sending)
- `queue-processing`: 3 workers (imports/exports)
- `queue-pdf-processing`: 2 workers (PDF generation)
- `queue-default`: 2 workers (fallback queue)

To increase email workers to 8:
```ini
numprocs=8  # Change this value
```

Reload supervisor:
```bash
supervisorctl reread
supervisorctl update
```

### PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/pool.d/ciblerh.conf`:

```bash
nano /etc/php/8.2/fpm/pool.d/ciblerh.conf
```

Key parameters:
- `pm.max_children`: Maximum PHP processes
- `pm.min_spare_servers`: Minimum idle processes
- `pm.max_spare_servers`: Maximum idle processes

Restart PHP-FPM:
```bash
systemctl restart php8.2-fpm
```

### Redis Optimization

Edit `/etc/redis/redis.conf`:

```bash
nano /etc/redis/redis.conf
```

For busy applications:
```
maxmemory 1gb            # Increase if needed
maxmemory-policy allkeys-lru  # Keep frequently used items
```

Restart Redis:
```bash
systemctl restart redis-server
```

## Security Best Practices

### 1. Regular Updates

```bash
# Weekly: Check for updates
sudo apt-get update
sudo apt-get upgrade -y

# Enable automatic security updates
apt-get install -y unattended-upgrades
systemctl enable unattended-upgrades
```

### 2. Firewall Management

```bash
# Check firewall status
sudo ufw status

# Allow specific ports
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS

# View rules
sudo ufw show added
```

### 3. SSL Certificate Renewal

Let's Encrypt certificates auto-renew. Test renewal:

```bash
sudo certbot renew --dry-run
```

View certificate info:
```bash
sudo certbot certificates
```

### 4. File Permissions

Never run as root. Application user should own files:

```bash
sudo chown -R laravel:laravel /var/www/ciblerh
```

Storage should be writable:
```bash
sudo chmod -R 775 /var/www/ciblerh/storage
sudo chmod -R 775 /var/www/ciblerh/bootstrap/cache
```

### 5. Environment File Protection

```bash
# .env should only be readable by application user
sudo chmod 600 /var/www/ciblerh/.env
sudo chown laravel:laravel /var/www/ciblerh/.env
```

## Monitoring & Alerts

### System Monitoring

```bash
# Real-time system stats
htop

# Memory usage
free -h

# Disk usage
df -h

# CPU usage
top
```

### Application Monitoring

Use the built-in monitoring script:

```bash
sudo monitor-ciblerh
```

### Queue Monitoring

Access Laravel Horizon dashboard:

```
https://yourdomain.com/horizon
```

### Log Analysis

```bash
# Count errors by type
grep -i error /var/www/ciblerh/storage/logs/laravel.log | cut -d':' -f5 | sort | uniq -c

# Find failed jobs
grep -A 5 "Exception" /var/www/ciblerh/storage/logs/laravel.log

# Monitor logs in real-time
tail -f /var/www/ciblerh/storage/logs/laravel.log | grep -i error
```

## Troubleshooting

### Application Not Loading

```bash
# Check Nginx config
sudo nginx -t

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check recent errors
tail -50 /var/www/ciblerh/storage/logs/laravel.log
```

### Queue Workers Not Running

```bash
# Check Supervisor status
sudo supervisorctl status laravel-queues:*

# Check Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check queue configuration
sudo redis-cli INFO
```

### Database Connection Issues

```bash
# Test MySQL connection
mysql -u ciblerh -p -h 127.0.0.1 ciblerh

# Check MySQL status
sudo systemctl status mysql

# View MySQL logs
sudo tail -f /var/log/mysql/error.log
```

### High Memory Usage

```bash
# Check PHP memory usage
ps aux | grep php

# Reduce PHP-FPM workers
# Edit /etc/php/8.2/fpm/pool.d/ciblerh.conf
# Reduce pm.max_children value

systemctl restart php8.2-fpm
```

### SSL Certificate Issues

```bash
# Check certificate expiration
sudo certbot certificates

# Renew certificate manually
sudo certbot renew --force-renewal

# Check certificate validity
openssl x509 -in /etc/letsencrypt/live/yourdomain.com/fullchain.pem -text -noout
```

## Uninstallation

To completely remove the application:

```bash
# Stop services
sudo systemctl stop php8.2-fpm nginx redis-server mysql
sudo supervisorctl stop laravel-queues:*

# Remove application files
sudo rm -rf /var/www/ciblerh

# Remove supervisor config
sudo rm /etc/supervisor/conf.d/laravel-queues.conf
sudo supervisorctl reread

# Remove Nginx config
sudo rm /etc/nginx/sites-available/yourdomain.com
sudo rm /etc/nginx/sites-enabled/yourdomain.com
sudo systemctl restart nginx

# Remove database
mysql -u root -p -e "DROP DATABASE ciblerh;"

# Remove application user
sudo userdel -r laravel
```

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Nginx Documentation**: https://nginx.org/en/docs/
- **Supervisor Documentation**: http://supervisord.org/
- **Let's Encrypt**: https://letsencrypt.org/
- **PHP FPM**: https://www.php.net/manual/en/install.fpm.php
- **Redis**: https://redis.io/documentation
- **Laravel Horizon**: https://laravel.com/docs/horizon

## Support

For issues or questions:

1. Check application logs: `/var/www/ciblerh/storage/logs/laravel.log`
2. Check system logs: `journalctl -xe`
3. Check Nginx errors: `/var/log/nginx/error.log`
4. Check supervisor logs: `/var/log/supervisor/supervisord.log`
5. Review this guide: Common Tasks and Troubleshooting sections

## Deployment Checklist

- [ ] Server provisioned with SSH access
- [ ] Domain DNS configured to point to server IP
- [ ] Configuration variables set in deployment-config.sh
- [ ] Deployment script downloaded and executed
- [ ] Application loads successfully at domain
- [ ] SSL certificate is valid
- [ ] Queue workers are running
- [ ] Email is sending correctly
- [ ] Database is accessible and containing data
- [ ] Backups are scheduled
- [ ] Monitoring is active
- [ ] Firewall rules configured
- [ ] Admin user created and password changed
- [ ] Application tested end-to-end

---

**Last Updated**: 2026-02-26
**Deployment Script Version**: 1.0.0
