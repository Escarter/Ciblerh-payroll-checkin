# Production Deployment Checklist - Ciblerh Payroll & Check-in System

This checklist ensures your Ciblerh application is properly configured and secure before going live in production.

**Completion Date**: ________________  
**Deployed By**: ________________  
**Server IP/Domain**: ________________

## Pre-Deployment (Before Running Scripts)

- [ ] **Domain Name**
  - [ ] Domain registered and owned
  - [ ] DNS records point to server IP
  - [ ] DNS propagation completed (test with `nslookup yourdomain.com`)

- [ ] **Server**
  - [ ] Ubuntu 20.04 LTS or later installed
  - [ ] SSH access confirmed (can connect as root)
  - [ ] Sufficient resources (2GB+ RAM, 20GB+ storage)
  - [ ] Server IP address noted

- [ ] **Credentials Prepared**
  - [ ] Strong database password generated
  - [ ] SMTP credentials obtained from email provider
  - [ ] AWS S3 credentials (if using S3)
  - [ ] SSL email address ready (for Let's Encrypt)

- [ ] **Documentation**
  - [ ] UBUNTU_DEPLOYMENT_GUIDE.md read and understood
  - [ ] Configuration variables documented
  - [ ] Backup strategy planned
  - [ ] Post-deployment testing plan ready

## During Deployment

- [ ] **Pre-Script Checks**
  - [ ] Running as root user
  - [ ] Configuration file created and verified
  - [ ] All environment variables set correctly
  - [ ] Script permissions set (`chmod +x deploy-ubuntu.sh`)

- [ ] **Deployment Execution**
  - [ ] Internet connection stable
  - [ ] Monitoring deployment progress
  - [ ] Recording any warnings or errors
  - [ ] Keeping terminal open (not interrupting)

- [ ] **Deployment Progress**
  - [ ] System updates completed
  - [ ] PHP 8.2 installed successfully
  - [ ] Composer dependencies installed
  - [ ] npm dependencies installed
  - [ ] Assets built successfully
  - [ ] Database migrations completed
  - [ ] SSL certificate obtained (Let's Encrypt)
  - [ ] Supervisor configured for queues
  - [ ] All services started without errors

## Post-Deployment (Immediately After)

- [ ] **Script Verifications**
  - [ ] Run `sudo bash post-deployment-check.sh`
  - [ ] All checks marked with ✓ (green)
  - [ ] No critical errors displayed
  - [ ] Review any warnings

- [ ] **Service Status**
  - [ ] PHP-FPM running: `systemctl status php8.2-fpm`
  - [ ] Nginx running: `systemctl status nginx`
  - [ ] MySQL running: `systemctl status mysql`
  - [ ] Redis running: `systemctl status redis-server`
  - [ ] Supervisor running: `supervisorctl status laravel-queues:*`

- [ ] **Application Access**
  - [ ] Domain accessible via HTTPS
  - [ ] SSL certificate is valid (no browser warnings)
  - [ ] Application loads without errors
  - [ ] No 500 errors in logs
  - [ ] Home page displays correctly
  - [ ] Static assets load (CSS, JS, images)

- [ ] **Application Functionality**
  - [ ] Login page accessible
  - [ ] Can login with test credentials
  - [ ] Dashboard loads correctly
  - [ ] Navigation works
  - [ ] Database queries successful
  - [ ] Sample data displays if seeded

## Security Configuration

- [ ] **Environment File**
  - [ ] `.env` file exists and is readable only by app user
  - [ ] Permissions are 600: `ls -la /var/www/ciblerh/.env`
  - [ ] Contains valid `APP_KEY`
  - [ ] `APP_DEBUG=false` in production
  - [ ] All sensitive values protected

- [ ] **Database**
  - [ ] Default credentials changed
  - [ ] Database user has limited privileges
  - [ ] Database only listening on localhost
  - [ ] Regular backups scheduled

- [ ] **Web Server**
  - [ ] Nginx not exposing server version (headers)
  - [ ] `.env` file not accessible via web
  - [ ] Sensitive directories protected
  - [ ] HTTPS redirect working (HTTP → HTTPS)

- [ ] **SSH Access**
  - [ ] Root login disabled (if applicable)
  - [ ] SSH keys configured (not password-only)
  - [ ] SSH port changed from 22 (optional)
  - [ ] Firewall blocking unauthorized SSH attempts

- [ ] **Firewall**
  - [ ] UFW enabled: `sudo ufw status`
  - [ ] Only necessary ports open (22, 80, 443)
  - [ ] Fail2Ban running: `systemctl status fail2ban`

## Email Configuration

- [ ] **SMTP Setup**
  - [ ] `MAIL_HOST` configured correctly
  - [ ] `MAIL_USERNAME` and `MAIL_PASSWORD` set
  - [ ] `MAIL_FROM_ADDRESS` configured
  - [ ] TLS/SSL encryption enabled if required

- [ ] **Email Testing**
  - [ ] Test email sends from application
  - [ ] Email arrives in inbox (check spam)
  - [ ] Email contains proper branding
  - [ ] Links in emails work correctly
  - [ ] No email delivery errors in logs

## Database Configuration

- [ ] **Database Health**
  - [ ] All tables present: 
    ```bash
    mysql -u ciblerh -p ciblerh -e "SHOW TABLES;"
    ```
  - [ ] Data integrity verified
  - [ ] Foreign key constraints intact
  - [ ] Indexes present on key columns

- [ ] **Migrations**
  - [ ] All migrations completed successfully:
    ```bash
    php artisan migrate:status
    ```
  - [ ] Latest migrations applied
  - [ ] No pending migrations

- [ ] **Seeds (if applicable)**
  - [ ] Database seeded with initial data
  - [ ] Default admin account exists
  - [ ] Test users created (if needed)
  - [ ] Permissions set correctly

- [ ] **Backups**
  - [ ] First backup created:
    ```bash
    sudo bash backup-ciblerh.sh backup
    ```
  - [ ] Backup verified as complete
  - [ ] Backup stored securely
  - [ ] Backup off-site copy (future)

## Queue Workers Configuration

- [ ] **Supervisor Setup**
  - [ ] Supervisor installed and running
  - [ ] Queue worker configuration loaded
  - [ ] All queue types configured:
    - [ ] high-priority (4 workers)
    - [ ] emails (5 workers)
    - [ ] processing (3 workers)
    - [ ] pdf-processing (2 workers)
    - [ ] default (2 workers)

- [ ] **Worker Status**
  - [ ] All workers showing RUNNING status
  - [ ] No workers in FATAL or ERROR state
  - [ ] Worker restart on failure enabled

- [ ] **Queue Testing**
  - [ ] Dispatch test job to queue
  - [ ] Job processes from queue
  - [ ] Job completes successfully
  - [ ] Logs show successful processing

## Cron Jobs Configuration

- [ ] **Laravel Scheduler**
  - [ ] Cron entry added for Laravel scheduler:
    ```bash
    sudo crontab -u laravel -l | grep "schedule:run"
    ```
  - [ ] Cron daemon running
  - [ ] Scheduled tasks executing as expected
  - [ ] No errors in cron logs

## Monitoring & Logging

- [ ] **Log Files**
  - [ ] Application logs generating: `/var/www/ciblerh/storage/logs/`
  - [ ] New log files created daily
  - [ ] No excessive error entries
  - [ ] Log rotation configured

- [ ] **Monitoring**
  - [ ] Monitoring script created: `/usr/local/bin/monitor-ciblerh`
  - [ ] Can run monitoring: `sudo monitor-ciblerh`
  - [ ] System resources monitored

- [ ] **Alerts**
  - [ ] Disk space alerts configured
  - [ ] Memory usage monitored
  - [ ] Log monitoring set up
  - [ ] Queue worker health monitored

## SSL/TLS Certificate

- [ ] **Certificate Status**
  - [ ] Certificate obtained from Let's Encrypt
  - [ ] Certificate valid (not self-signed)
  - [ ] Check expiration: `sudo certbot certificates`
  - [ ] Certificate auto-renewal enabled:
    ```bash
    systemctl is-enabled certbot.timer
    ```

- [ ] **HTTPS Configuration**
  - [ ] All traffic redirects to HTTPS
  - [ ] HSTS header enabled
  - [ ] Security headers present
  - [ ] No mixed content warnings

- [ ] **Certificate Renewal**
  - [ ] Auto-renewal tested: `sudo certbot renew --dry-run`
  - [ ] Renewal logs available
  - [ ] Renewal cron job active

## Performance Optimization

- [ ] **Caching**
  - [ ] Redis running and responding
  - [ ] Application cache working:
    ```bash
    php artisan cache:clear
    php artisan config:cache
    ```
  - [ ] View cache built:
    ```bash
    php artisan view:cache
    ```
  - [ ] Route cache built:
    ```bash
    php artisan route:cache
    ```

- [ ] **Database**
  - [ ] Slow query log checked
  - [ ] Indexes verified on key columns
  - [ ] Query optimization completed
  - [ ] Database size monitored

- [ ] **Frontend Assets**
  - [ ] Assets minified and compiled
  - [ ] CSS/JS serving correctly
  - [ ] Assets caching working
  - [ ] Build production: `npm run build`

## Permissions & File Structure

- [ ] **File Ownership**
  - [ ] All files owned by laravel user:
    ```bash
    ls -l /var/www/ciblerh | head -20
    ```
  - [ ] Correct permissions set (755 for dirs, 644 for files)

- [ ] **Writable Directories**
  - [ ] `/var/www/ciblerh/storage` writable (775)
  - [ ] `/var/www/ciblerh/bootstrap/cache` writable (775)
  - [ ] Proper user/group ownership

- [ ] **Protected Files**
  - [ ] `.env` file only readable by app user (600)
  - [ ] `vendor/` directory not web-accessible
  - [ ] `config/` directory not web-accessible

## Data & Content

- [ ] **User Accounts**
  - [ ] Admin account created
  - [ ] Admin password changed from default
  - [ ] Test user accounts created
  - [ ] User permissions verified

- [ ] **Sample Data**
  - [ ] Employees imported or seeded
  - [ ] Departments configured
  - [ ] Company/organization set up
  - [ ] Sample payroll data created (if needed)

- [ ] **File Uploads**
  - [ ] File upload directories writable
  - [ ] Upload limits configured
  - [ ] File storage location secure
  - [ ] Test file upload works

## Testing Checklist

### Core Functionality
- [ ] Login/Logout working
- [ ] User access controls enforced
- [ ] Role-based permissions working
- [ ] Dashboard loads without errors
- [ ] All main features accessible

### Payroll Features
- [ ] Payroll calculation working
- [ ] Payslip generation working
- [ ] PDF download functional
- [ ] Email payslips sending

### Employee Features
- [ ] Check-in/Check-out working
- [ ] Attendance tracking working
- [ ] Leave requests functional
- [ ] Overtime tracking working

### Admin Features
- [ ] Employee management functional
- [ ] Department management working
- [ ] Reporting functional
- [ ] Settings accessible and saving

### Queue Jobs
- [ ] Email queue processing
- [ ] PDF generation in queue
- [ ] Data import processing
- [ ] Failed jobs visible

### External Integrations (if applicable)
- [ ] AWS S3 connectivity (if configured)
- [ ] Email notifications sending
- [ ] Third-party API calls working
- [ ] WebhookS processing (if any)

## Documentation & Knowledge Transfer

- [ ] **Server Documentation**
  - [ ] Server IP/Domain documented
  - [ ] Admin credentials secured (not in script)
  - [ ] Database credentials secured
  - [ ] SMTP credentials documented

- [ ] **Runbooks Created**
  - [ ] Daily shutdown/startup procedures
  - [ ] Emergency restart procedures
  - [ ] Queue worker restart procedures
  - [ ] Database backup/restore procedures

- [ ] **Team Training**
  - [ ] Admin user trained on system
  - [ ] Support team aware of escalation paths
  - [ ] Monitoring contacts established
  - [ ] Communication channels set up

## Launch Authorization

- [ ] **Sign-Off**
  - [ ] Technical team sign-off: _________________ Date: _____
  - [ ] Project manager sign-off: _________________ Date: _____
  - [ ] System owner sign-off: _________________ Date: _____

- [ ] **Go-Live Readiness**
  - [ ] All systems tested and verified
  - [ ] Backups confirmed working
  - [ ] Team notified and ready
  - [ ] Monitoring active
  - [ ] Incident response plan ready

## Post-Launch Monitoring (24-48 Hours)

- [ ] **System Health**
  - [ ] Server resources stable
  - [ ] No unusual CPU/memory spikes
  - [ ] Disk space adequate
  - [ ] All services running

- [ ] **Application Performance**
  - [ ] Page load times acceptable
  - [ ] Queue processing normal
  - [ ] Database performance good
  - [ ] No error spikes in logs

- [ ] **User Activity**
  - [ ] Users can login successfully
  - [ ] Core features working
  - [ ] No reported access issues
  - [ ] Performance acceptable for load

- [ ] **Monitoring Active**
  - [ ] Alert system functioning
  - [ ] On-call team available
  - [ ] Issues logged and tracked
  - [ ] Daily review of logs and metrics

## Ongoing Maintenance Schedule

- [ ] **Daily**
  - [ ] [ ] Check error logs
  - [ ] [ ] Verify service health
  - [ ] [ ] Monitor disk space

- [ ] **Weekly**
  - [ ] [ ] Review system performance
  - [ ] [ ] Test backup restore
  - [ ] [ ] Check security updates

- [ ] **Monthly**
  - [ ] [ ] Full security audit
  - [ ] [ ] Database optimization
  - [ ] [ ] Performance analysis
  - [ ] [ ] Team meeting review

- [ ] **Quarterly**
  - [ ] [ ] Capacity planning
  - [ ] [ ] Disaster recovery test
  - [ ] [ ] Security update patches
  - [ ] [ ] Client satisfaction review

## Contacts & Resources

**Technical Support**
- Name: _________________________ 
- Phone: _________________________ 
- Email: _________________________

**System Administrator**
- Name: _________________________ 
- Phone: _________________________ 
- Email: _________________________

**Emergency Contact**
- Name: _________________________ 
- Phone: _________________________ 
- Available: _________________________

**Useful Resources**
- Server IP: _________________________
- Domain: _________________________
- Database Host: _________________________
- Backup Location: _________________________
- Documentation: _________________________

---

## Approval Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| DevOps Engineer | _____________ | _____________ | ________ |
| Project Manager | _____________ | _____________ | ________ |
| System Owner | _____________ | _____________ | ________ |
| Operations Lead | _____________ | _____________ | ________ |

---

**Last Updated**: 2026-02-26  
**Checklist Version**: 1.0.0  
**Application**: Ciblerh Payroll & Check-in System
