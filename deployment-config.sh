#!/bin/bash

################################################################################
# CIBLERH DEPLOYMENT CONFIGURATION
# Domain: portail.ciblerh-emploi.com
# 
# Created: 2026-02-26
# Status: Ready for deployment
#
################################################################################

# ============================================================================
# ✅ REQUIRED - CUSTOMIZE THESE VALUES ONLY
# ============================================================================

# Domain Name (Already set for you!)
export APP_DOMAIN="portail.ciblerh-emploi.com"

# 🔐 IMPORTANT: Set a STRONG database password
# Generate one: openssl rand -base64 32
# Then paste it below (remove the quotes, keep the value):
export DB_PASSWORD="2Hc8aIgy2m+CUaw17hjH2RANzzYO7wHuOChtajNUNeA="

# Email Configuration (SMTP)
# Choose one of the examples below and uncomment it:

# --- OPTION 1: Gmail with App Password ---
export MAIL_HOST="smtp.gmail.com"
export MAIL_PORT="587"
export MAIL_USERNAME="your-email@gmail.com"
export MAIL_PASSWORD="your-16-char-app-password"

# --- OPTION 2: SendGrid ---
# export MAIL_HOST="smtp.sendgrid.net"
# export MAIL_PORT="587"
# export MAIL_USERNAME="apikey"
# export MAIL_PASSWORD="SG.xxxxxxxxxxxxx"

# --- OPTION 3: AWS SES ---
# export MAIL_HOST="email-smtp.us-east-1.amazonaws.com"  # Change region as needed
# export MAIL_PORT="587"
# export MAIL_USERNAME="your-ses-username"
# export MAIL_PASSWORD="your-ses-password"

# --- OPTION 4: Mailgun ---
# export MAIL_HOST="smtp.mailgun.org"
# export MAIL_PORT="587"
# export MAIL_USERNAME="postmaster@yourdomain.com"
# export MAIL_PASSWORD="your-mailgun-password"

# Let's Encrypt Email (for SSL certificate notifications)
export LE_EMAIL="admin@ciblerh-emploi.com"

# ============================================================================
# 📋 OPTIONAL - These have good defaults, customize if needed
# ============================================================================

# Application Settings
export APP_NAME="Ciblerh"
export APP_USER="laravel"
export APP_GROUP="laravel"

# Database Configuration
export DB_HOST="127.0.0.1"              # localhost (change if remote database)
export DB_PORT="3306"
export DB_NAME="ciblerh_db"
export DB_USER="ciblerh_db_user"
export SETUP_LOCAL_DB="true"            # Install MySQL on this server

# Redis Configuration
export REDIS_HOST="127.0.0.1"           # localhost
export REDIS_PORT="6379"
export SETUP_REDIS="true"               # Install Redis on this server

# Email From Address
export MAIL_FROM="noreply@ciblerh-emploi.com"

# SSL Configuration
export ENABLE_SSL="true"                # Use Let's Encrypt (HIGHLY RECOMMENDED)

# Git Configuration
export GIT_REPO="https://github.com/Escarter/Ciblerh-payroll-checkin.git"
export GIT_BRANCH="main"

# ============================================================================
# 🔑 OPTIONAL - AWS S3 (Only if using AWS for file storage)
# ============================================================================

# Leave empty if not using S3
export AWS_ACCESS_KEY=""
export AWS_SECRET_KEY=""
export AWS_BUCKET=""
export AWS_REGION="us-east-1"

# ============================================================================
# ⚙️ ADVANCED - Usually don't need to change these
# ============================================================================

# Queue Worker Configuration
export QUEUE_WORKERS="14"

# PHP-FPM Settings
export PHP_MAX_CHILDREN="50"
export PHP_MIN_SPARES="5"
export PHP_MAX_SPARES="20"

# ============================================================================
# 📝 INSTRUCTIONS FOR YOU
# ============================================================================

# Step 1: Fill in the REQUIRED values above:
#   ✓ DB_PASSWORD - Set a strong password
#   ✓ MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD - Choose your email provider
#   ✓ LE_EMAIL - Should be admin@ciblerh-emploi.com (already set)

# Step 2: SSH into your Ubuntu server:
#   ssh root@YOUR_SERVER_IP

# Step 3: Upload this file to the server (or type it):
#   scp deployment-config.sh root@YOUR_SERVER_IP:/root/

# Step 4: Still on your server, run these commands:
#   source /root/deployment-config.sh
#   cd /root
#   bash deploy-ubuntu.sh

# Step 5: Wait 15-25 minutes for deployment to complete

# Step 6: Verify everything works:
#   sudo bash post-deployment-check.sh

# Step 7: Access your application:
#   https://portail.ciblerh-emploi.com

# ============================================================================
# 📋 QUICK CHECKLIST BEFORE DEPLOYMENT
# ============================================================================

checklist() {
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║         PRE-DEPLOYMENT CHECKLIST - VERIFY BEFORE RUN       ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
    
    if [[ "$DB_PASSWORD" == "YOUR_SECURE_PASSWORD_HERE" ]]; then
        echo "❌ DB_PASSWORD not set - REQUIRED"
    else
        echo "✅ DB_PASSWORD is set"
    fi
    
    if [[ -z "$MAIL_USERNAME" ]]; then
        echo "❌ MAIL_USERNAME not set - REQUIRED"
    else
        echo "✅ MAIL_USERNAME is set"
    fi
    
    if [[ -z "$MAIL_PASSWORD" ]]; then
        echo "❌ MAIL_PASSWORD not set - REQUIRED"
    else
        echo "✅ MAIL_PASSWORD is set"
    fi
    
    echo ""
    echo "Domain: $APP_DOMAIN"
    echo "Database: $DB_NAME @ $DB_HOST"
    echo "Email: From $MAIL_FROM via $MAIL_HOST"
    echo "SSL: Enabled with Let's Encrypt"
    echo ""
    
    if [[ "$DB_PASSWORD" != "YOUR_SECURE_PASSWORD_HERE" ]] && [[ -n "$MAIL_USERNAME" ]] && [[ -n "$MAIL_PASSWORD" ]]; then
        echo "✅ All required values configured - Ready to deploy!"
    else
        echo "❌ Please complete the REQUIRED configuration above"
    fi
    echo ""
}

# Run checklist on source
echo "Loading configuration for portail.ciblerh-emploi.com..."
checklist

# ============================================================================
# 📞 NEED HELP?
# ============================================================================

# 1. Database Password Generator:
#    openssl rand -base64 32

# 2. Gmail App Password Guide:
#    https://support.google.com/accounts/answer/185833

# 3. SendGrid API Key:
#    https://app.sendgrid.com/settings/api_keys

# 4. AWS SES Setup:
#    https://docs.aws.amazon.com/ses/latest/dg/

# 5. Mailgun Setup:
#    https://www.mailgun.com/

# ============================================================================
# ✨ YOU'RE READY!
# ============================================================================

# Once configured:
#   1. Upload this file to your server: scp deployment-config.sh root@YOUR_IP:/root/
#   2. SSH into server: ssh root@YOUR_IP
#   3. Run: source /root/deployment-config.sh && bash deploy-ubuntu.sh
#   4. Wait 15-25 minutes
#   5. Access: https://portail.ciblerh-emploi.com

# Questions? See:
#   - UBUNTU_DEPLOYMENT_README.md
#   - UBUNTU_DEPLOYMENT_GUIDE.md
#   - QUICK_REFERENCE.md
