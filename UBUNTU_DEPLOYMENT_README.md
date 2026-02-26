# Ubuntu Deployment Package - Ciblerh Payroll & Check-in System

This package contains everything needed to deploy the Ciblerh application on a Ubuntu server without Docker.

## 📋 Contents

### Main Scripts

1. **`deploy-ubuntu.sh`** - Main deployment script (⭐ START HERE)
   - Automates complete Ubuntu server setup
   - Installs all dependencies and services
   - No manual steps required (except configuration)

2. **`post-deployment-check.sh`** - Post-deployment verification
   - Verifies all services are running correctly
   - Checks database, cache, and queue workers
   - Displays quick reference guide

3. **`deployment-config.example.sh`** - Configuration template
   - Pre-deployment configuration values
   - Email provider examples
   - AWS S3 setup examples

### Documentation

1. **`UBUNTU_DEPLOYMENT_GUIDE.md`** 📖 (COMPREHENSIVE GUIDE)
   - Step-by-step deployment instructions
   - Post-deployment configuration
   - Troubleshooting guide
   - Performance tuning tips
   - Security best practices

## 🚀 Quick Start (5 Steps)

### Step 1: Get Your Server Ready
```bash
# Connect to your Ubuntu server via SSH
ssh root@your_server_ip

# Update system
sudo apt-get update && sudo apt-get upgrade -y
```

### Step 2: Prepare Configuration
```bash
# Copy configuration template
cp deployment-config.example.sh deployment-config.sh

# Edit with your settings
nano deployment-config.sh
```

Update these CRITICAL values:
- `APP_DOMAIN` - Your domain name
- `DB_PASSWORD` - Secure database password
- `MAIL_*` - Your SMTP email credentials
- `LE_EMAIL` - Email for SSL certificates

### Step 3: Download Deployment Script
```bash
# Download from repository (or copy from local)
wget https://raw.githubusercontent.com/Escarter/Ciblerh-payroll-checkin/main/deploy-ubuntu.sh
chmod +x deploy-ubuntu.sh
```

### Step 4: Run Deployment
```bash
# Load your configuration
source deployment-config.sh

# Run the deployment script
sudo bash deploy-ubuntu.sh
```

The script will:
- Install all system dependencies
- Configure PHP 8.2, Nginx, MySQL, Redis
- Clone and setup the application
- Configure SSL with Let's Encrypt
- Setup queue workers with Supervisor
- Configure backups and monitoring

### Step 5: Verify Deployment
```bash
# Run verification script
sudo bash post-deployment-check.sh

# Access your application
# Open browser to: https://yourdomain.com
```

## 📦 System Requirements

- **OS**: Ubuntu 20.04 LTS, 22.04 LTS, or later
- **RAM**: Minimum 4GB (8GB+ recommended)
- **CPU**: Minimum 2 cores (4 cores recommended)
- **Storage**: Minimum 20GB (50GB SSD recommended)
- **Domain**: Already registered and pointing to server
- **SSH**: Root or sudo access to server

## 🔧 What Gets Installed

The deployment script automatically installs:

### System Services
- ✅ PHP 8.2 with required extensions
- ✅ Nginx (reverse proxy & web server)
- ✅ MySQL 8.0 (or connects to existing database)
- ✅ Redis (caching & queue system)
- ✅ Node.js 20.x (frontend asset building)

### Application
- ✅ Composer (PHP dependency manager)
- ✅ npm (Node.js dependency manager)
- ✅ Laravel application from Git
- ✅ All PHP and JavaScript dependencies

### Production Features
- ✅ SSL/TLS with Let's Encrypt
- ✅ Supervisor (queue worker management)
- ✅ Cron jobs (Laravel scheduler)
- ✅ Log rotation
- ✅ Firewall (UFW)
- ✅ Fail2Ban (intrusion protection)
- ✅ Monitoring scripts

## 📊 Architecture

```
Ubuntu Server
├── Nginx (Port 80 → 443)
│   └── SSL/TLS Certificate
├── PHP 8.2-FPM
│   └── Laravel Application
├── MySQL 8.0
│   └── Ciblerh Database
├── Redis 6.x
│   ├── Queue Management
│   ├── Session Storage
│   └── Cache System
└── Supervisor
    ├── High Priority Queue Workers (4)
    ├── Email Queue Workers (5)
    ├── Processing Queue Workers (3)
    ├── PDF Queue Workers (2)
    └── Default Queue Workers (2)
```

## 🔐 Security Features Included

- ✅ UFW Firewall (blocks all except SSH, HTTP, HTTPS)
- ✅ Fail2Ban (blocks brute force attacks)
- ✅ Let's Encrypt SSL certificates (auto-renewal)
- ✅ Secure PHP-FPM configuration
- ✅ Proper file permissions (application user isolation)
- ✅ Environment file protection
- ✅ HTTPS redirect
- ✅ Security headers in Nginx

## 📝 Common Tasks

### Check Application Status
```bash
sudo monitor-ciblerh
```

### View Logs
```bash
# Application logs
tail -f /var/www/ciblerh/storage/logs/laravel.log

# Queue worker logs
tail -f /var/log/supervisor/queue-emails.log

# Web server logs
tail -f /var/log/nginx/error.log
```

### Manage Queue Workers
```bash
# Check status
supervisorctl status laravel-queues:*

# Restart workers
supervisorctl restart laravel-queues:*

# Restart specific queue
supervisorctl restart queue-emails:*
```

### Database Backup
```bash
mysqldump -u ciblerh -p ciblerh > backup-$(date +%Y%m%d).sql
```

### Deploy Updates
```bash
cd /var/www/ciblerh
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
supervisorctl restart laravel-queues:*
```

## 🆘 Troubleshooting Quick Links

| Issue | Solution |
|-------|----------|
| Application not loading | Check PHP-FPM: `systemctl status php8.2-fpm` |
| Database not connecting | Verify credentials in `/var/www/ciblerh/.env` |
| Queue workers not running | Check Supervisor: `supervisorctl status laravel-queues:*` |
| SSL certificate issues | Check: `certbot certificates` |
| High memory usage | Reduce PHP workers or queue workers |
| Slow performance | Increase queue workers or check database indexes |

See **UBUNTU_DEPLOYMENT_GUIDE.md** for detailed troubleshooting.

## 📚 Documentation Structure

```
UBUNTU_DEPLOYMENT_GUIDE.md
├── Overview
├── Prerequisites
├── Deployment Steps (5 main steps)
├── Post-Deployment Verification
├── Common Tasks (with commands)
├── Scaling & Performance
├── Security Best Practices
├── Monitoring & Alerts
├── Troubleshooting Guide
└── Uninstallation
```

## 🔄 Deployment Checklist

Before starting:
- [ ] Have domain name ready
- [ ] Server provisioned and accessible via SSH
- [ ] Domain DNS pointing to server IP
- [ ] SMTP credentials obtained
- [ ] Read UBUNTU_DEPLOYMENT_GUIDE.md
- [ ] Configuration variables prepared

During deployment:
- [ ] Running as root user
- [ ] Configuration file sourced
- [ ] Script execution confirmed
- [ ] Monitoring deployment progress
- [ ] Noting any warnings or errors

After deployment:
- [ ] Run post-deployment check script
- [ ] Test application in browser
- [ ] Verify queue workers running
- [ ] Test email sending
- [ ] Check logs for errors
- [ ] Setup backups
- [ ] Configure monitoring

## 🎯 Next Steps After Deployment

1. **Access Application**
   - Navigate to: `https://yourdomain.com`
   - Login with default credentials (check README or deployment logs)

2. **Change Admin Password**
   - Update default admin account password immediately

3. **Configure Email**
   - Test email sending from application
   - Verify SMTP credentials in `.env`

4. **Setup Backups**
   - Schedule automated database backups
   - Export application files backup

5. **Enable Monitoring**
   - Set up server monitoring/alerts
   - Check logs regularly: `tail -f /var/www/ciblerh/storage/logs/laravel.log`

6. **Performance Tuning**
   - Monitor queue worker performance
   - Adjust worker counts as needed
   - Check database query performance

## 📞 Getting Help

1. **Deployment Issues**
   - Review: UBUNTU_DEPLOYMENT_GUIDE.md
   - Check: `/var/www/ciblerh/storage/logs/laravel.log`
   - Run: `sudo monitor-ciblerh`

2. **Application Issues**
   - Check application logs
   - Run database migrations if needed
   - Clear application cache: `php artisan optimize:clear`

3. **Queue Issues**
   - Check supervisor status: `supervisorctl status laravel-queues:*`
   - Check queue logs: `tail -f /var/log/supervisor/queue-*.log`
   - Review Redis: `redis-cli INFO`

4. **Performance Issues**
   - Monitor resources: `htop`
   - Check PHP processes: `ps aux | grep php`
   - Review slow query logs in MySQL

## 📖 Resource Links

- **Laravel Docs**: https://laravel.com/docs
- **Nginx Docs**: https://nginx.org/
- **PHP FPM Docs**: https://www.php.net/manual/en/install.fpm.php
- **Supervisor Docs**: http://supervisord.org/
- **Let's Encrypt**: https://letsencrypt.org/
- **Redis**: https://redis.io/documentation

## ⚠️ Important Notes

⚠️ **Before Running Deployment:**
- Read the UBUNTU_DEPLOYMENT_GUIDE.md completely
- Ensure all configuration variables are set correctly
- Test SSH access to server
- Backup any existing data

⚠️ **During Deployment:**
- Do not interrupt the script
- Monitor progress for errors
- Note any warnings displayed
- Script may take 10-20 minutes to complete

⚠️ **After Deployment:**
- Change default passwords immediately
- Configure backups before going live
- Test all critical features
- Monitor application logs
- Keep system updated regularly

## 📋 Version Information

- **Script Version**: 1.0.0
- **Created**: 2026-02-26
- **Target**: Ubuntu 20.04 LTS, 22.04 LTS, 24.04 LTS
- **PHP Version**: 8.2
- **Laravel Version**: 12.41.0
- **Node.js Version**: 20.x

## 📄 License

This deployment package is part of the Ciblerh project and follows the same MIT license.

## 🤝 Support

For issues or questions:
1. Check the UBUNTU_DEPLOYMENT_GUIDE.md
2. Review troubleshooting section
3. Check application logs
4. Run post-deployment verification script

---

**Ready to deploy?** Start with:

```bash
# 1. Configure your settings
nano deployment-config.sh

# 2. Load configuration
source deployment-config.sh

# 3. Run deployment
sudo bash deploy-ubuntu.sh

# 4. Verify everything
sudo bash post-deployment-check.sh
```

Happy deploying! 🎉
