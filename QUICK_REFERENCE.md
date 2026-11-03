# Quick Reference Card - Ciblerh Ubuntu Deployment

## 📋 Pre-Deployment (5 minutes)

```bash
# 1. Connect to server
ssh root@your_server_ip

# 2. Update system
sudo apt-get update && apt-get upgrade -y

# 3. Create config
cp deployment-config.example.sh deployment-config.sh

# 4. Edit config (set these REQUIRED values):
nano deployment-config.sh
# ► APP_DOMAIN="yourdomain.com"
# ► DB_PASSWORD="secure_password"
# ► MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD

# 5. Source config
source deployment-config.sh
```

## 🚀 Deployment (15-25 minutes)

```bash
# Run deployment
sudo bash deploy-ubuntu.sh

# Watch the automated installation happen...
# Script will:
# ✓ Install PHP 8.4, Nginx, MySQL, Redis
# ✓ Clone application
# ✓ Install dependencies
# ✓ Setup database
# ✓ Configure SSL
# ✓ Setup queue workers
```

## ✅ Post-Deployment (5 minutes)

```bash
# Verify everything
sudo bash post-deployment-check.sh

# Check all services
sudo monitor-ciblerh

# Access application
https://yourdomain.com
```

---

## 📁 Important Paths

```
Application:    /var/www/ciblerh
Configuration:  /var/www/ciblerh/.env
Logs:           /var/www/ciblerh/storage/logs/laravel.log
Database:       ciblerh (MySQL)
Redis:          127.0.0.1:6379
Nginx Config:   /etc/nginx/sites-available/yourdomain.com
Supervisor:     /etc/supervisor/conf.d/laravel-queues.conf
Backups:        /backups/
```

---

## 🔧 Essential Commands

### System Status
```bash
sudo monitor-ciblerh                          # Overall status
systemctl status php8.4-fpm                   # PHP-FPM
systemctl status nginx                        # Web server
systemctl status redis-server                 # Cache/queue
systemctl status mysql                        # Database
```

### View Logs
```bash
tail -f /var/www/ciblerh/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
supervisorctl tail queue-emails -f
```

### Queue Management
```bash
supervisorctl status laravel-queues:*         # View all workers
supervisorctl restart laravel-queues:*        # Restart all
supervisorctl restart queue-emails:*          # Restart specific
```

### Manage Services
```bash
systemctl restart php8.4-fpm                  # Restart PHP
systemctl restart nginx                       # Restart web server
systemctl restart redis-server                # Restart Redis
systemctl restart mysql                       # Restart database
```

### Database
```bash
mysql -u ciblerh -p ciblerh                   # Connect to database
php artisan migrate                           # Run migrations
php artisan db:seed                           # Seed database
php artisan tinker                            # Laravel REPL
```

### Clear Caches
```bash
cd /var/www/ciblerh
php artisan optimize:clear                    # Clear all
php artisan cache:clear                       # Cache only
php artisan view:clear                        # View cache
php artisan route:clear                       # Route cache
```

### Backups
```bash
sudo bash backup-ciblerh.sh backup             # Create backup
sudo bash backup-ciblerh.sh list               # List backups
sudo backup-ciblerh.sh restore /path/to/backup.tar.gz  # Restore
```

---

## ⚙️ Common Tasks

### Deploy Updates
```bash
cd /var/www/ciblerh
sudo -u laravel git pull origin main
sudo -u laravel composer install --no-dev
sudo -u laravel npm install && npm run build
sudo -u laravel php artisan migrate --force
supervisorctl restart laravel-queues:*
```

### Monitor Resources
```bash
htop                                          # System monitor
ps aux | grep php                             # List PHP processes
redis-cli INFO                                # Redis stats
mysql -e "SHOW PROCESSLIST;"                  # Database queries
df -h                                         # Disk space
free -h                                       # Memory usage
```

### Check Certificate
```bash
sudo certbot certificates                     # View certs
sudo certbot renew --dry-run                  # Test renewal
openssl x509 -in /etc/letsencrypt/live/yourdomain.com/fullchain.pem -text -noout
```

### View Error Rates
```bash
grep ERROR /var/www/ciblerh/storage/logs/laravel.log | wc -l
grep -i "fatal\|exception" /var/www/ciblerh/storage/logs/laravel.log | tail -20
tail -100 /var/log/nginx/error.log | grep -i error
```

---

## 🔐 Security Quick Checks

```bash
# Firewall status
sudo ufw status                               # Should show active + rules

# Fail2Ban status
sudo systemctl status fail2ban                # Should be running

# SSH key auth
ssh-keygen -t ed25519                         # Generate new key (if needed)

# File permissions
ls -la /var/www/ciblerh/.env                  # Should be 600
ls -la /var/www/ciblerh/                      # Check owner is laravel

# SSL status
sudo certbot certificates                     # Check expiration
curl -I https://yourdomain.com                # Check SSL response
```

---

## ⚠️ Troubleshooting Quick Links

| Problem | Check | Command |
|---------|-------|---------|
| App not loading | PHP-FPM | `systemctl status php8.4-fpm` |
| 502 Bad Gateway | Nginx/PHP | `tail -f /var/log/nginx/error.log` |
| Database error | MySQL | `systemctl status mysql` |
| Queue not working | Supervisor | `supervisorctl status laravel-queues:*` |
| Email not sending | Logs | `tail -f /var/www/ciblerh/storage/logs/laravel.log` |
| High memory | Processes | `htop` / reduce workers |
| Slow page loads | Cache | `php artisan optimize:clear` |

---

## 📚 Documentation Quick Links

- **Start Here**: `UBUNTU_DEPLOYMENT_README.md`
- **Full Guide**: `UBUNTU_DEPLOYMENT_GUIDE.md`
- **Go-Live**: `PRODUCTION_DEPLOYMENT_CHECKLIST.md`
- **What's Here**: `DEPLOYMENT_PACKAGE_SUMMARY.md`
- **This Card**: `QUICK_REFERENCE.md`

---

## 🎯 Deployment Success Indicators

✓ All services running (`sudo monitor-ciblerh`)  
✓ Application loads at `https://yourdomain.com`  
✓ SSL certificate valid (no browser warnings)  
✓ Queue workers showing RUNNING status  
✓ Database tables present (`mysql ... SHOW TABLES;`)  
✓ No errors in logs (`tail /var/www/ciblerh/storage/logs/laravel.log`)  
✓ Can login to application  

---

## 🆘 Emergency Contacts & Resources

**Quick Help**
```bash
# Real-time status check
sudo monitor-ciblerh

# Application logs
tail -f /var/www/ciblerh/storage/logs/laravel.log

# System logs
journalctl -e -n 50

# Help text
sudo bash deploy-ubuntu.sh --help
sudo bash backup-ciblerh.sh help
```

**Resources**
- Laravel: https://laravel.com/docs
- Nginx: https://nginx.org/docs/
- Supervisor: http://supervisord.org/
- MySQL: https://dev.mysql.com/doc/
- PHP: https://www.php.net/manual/

---

## 📅 Maintenance Calendar

**Daily**
- [ ] Check logs for errors
- [ ] Monitor system resources
- [ ] Verify queue workers running

**Weekly**
- [ ] Review performance metrics
- [ ] Test backup restore
- [ ] Check for updates

**Monthly**
- [ ] Database optimization
- [ ] Security audit
- [ ] Capacity planning
- [ ] Update dependencies

**Quarterly**
- [ ] Disaster recovery test
- [ ] Performance analysis
- [ ] Team review

---

## 🎯 Deployment Summary

```
Before:  Blank Ubuntu server
  ↓
After:  Production-ready Ciblerh application with:
  ✓ PHP 8.4 + Laravel 12
  ✓ MySQL 8 database
  ✓ Redis caching & queues
  ✓ Nginx web server
  ✓ SSL/TLS HTTPS
  ✓ Queue workers (16 processes)
  ✓ Automated backups
  ✓ Security hardening
  ✓ Monitoring scripts
```

**Time to Live**: ~1 hour ⏱️

---

## ✏️ Deployment Notes

**Server IP**: _____________________  
**Domain**: _____________________  
**Database Password**: _____________________ (store securely!)  
**Start Time**: _____________________  
**Completion Time**: _____________________  
**Issues**: ___________________________________________________  
**Notes**: ___________________________________________________  

---

**Printed**: ________________  
**By**: ________________  
**Date**: ________________  

---

*Keep this card handy for daily administration!*
