#!/bin/bash

##############################################################################
# Quick Deployment Configuration for Ubuntu
# 
# Copy this file and customize the values, then source it before running
# the deployment script:
#
#   cp deployment-config.example.sh deployment-config.sh
#   nano deployment-config.sh
#   source deployment-config.sh
#   sudo bash deploy-ubuntu.sh
#
##############################################################################

# ============================================================================
# CRITICAL - MUST BE CONFIGURED
# ============================================================================

# Your domain name (REQUIRED)
export APP_DOMAIN="yourdomain.com"

# Database password - Generate a secure one:
# openssl rand -base64 32
export DB_PASSWORD="CHANGE_ME_SECURE_PASSWORD"

# SMTP Email Configuration - Get these from your email provider
export MAIL_HOST="smtp.gmail.com"
export MAIL_PORT="587"
export MAIL_USERNAME="your-email@gmail.com"
export MAIL_PASSWORD="your-app-password-or-smtp-token"

# Let's Encrypt email (for SSL certificate notifications)
export LE_EMAIL="admin@yourdomain.com"

# ============================================================================
# RECOMMENDED - REVIEW AND UPDATE
# ============================================================================

# Application name (used for system user and services)
export APP_NAME="Ciblerh"

# Database Configuration
export DB_HOST="127.0.0.1"              # localhost or remote IP
export DB_PORT="3306"
export DB_NAME="ciblerh"
export DB_USER="ciblerh"
export SETUP_LOCAL_DB="true"            # Set to false if using external database

# Redis Configuration (for queues and caching)
export REDIS_HOST="127.0.0.1"           # localhost or remote IP
export REDIS_PORT="6379"
export SETUP_REDIS="true"               # Set to false if Redis already installed

# Email From Address
export MAIL_FROM="noreply@yourdomain.com"

# SSL Configuration
export ENABLE_SSL="true"                # Enable Let's Encrypt SSL
export APP_USER="laravel"               # System user for application

# Git Configuration
export GIT_REPO="https://github.com/Escarter/Ciblerh-payroll-checkin.git"
export GIT_BRANCH="main"

# ============================================================================
# OPTIONAL - AWS S3 FILE STORAGE
# ============================================================================

# Leave empty if not using AWS S3
export AWS_ACCESS_KEY=""
export AWS_SECRET_KEY=""
export AWS_BUCKET=""
export AWS_REGION="us-east-1"

# ============================================================================
# OPTIONAL - ADVANCED SETTINGS
# ============================================================================

# Queue Worker Configuration
# Adjust based on server capacity:
# - Small server (2CPU, 4GB): 6 workers
# - Medium server (4CPU, 8GB): 14 workers (default)
# - Large server (8CPU, 16GB): 28 workers
export QUEUE_WORKERS="14"

# System Performance Tuning
# Auto-detect server specs to tune these
export PHP_MAX_CHILDREN="50"
export PHP_MIN_SPARES="5"
export PHP_MAX_SPARES="20"

# ============================================================================
# EXAMPLES FOR COMMON EMAIL PROVIDERS
# ============================================================================

# GMAIL (Google Workspace):
# export MAIL_HOST="smtp.gmail.com"
# export MAIL_PORT="587"
# export MAIL_USERNAME="your-email@gmail.com"
# export MAIL_PASSWORD="16-character-app-password"  # Generate: https://myaccount.google.com/apppasswords

# SENDGRID:
# export MAIL_HOST="smtp.sendgrid.net"
# export MAIL_PORT="587"
# export MAIL_USERNAME="apikey"
# export MAIL_PASSWORD="SG.xxxxxxxxxxxxx"

# AWS SES:
# export MAIL_HOST="email-smtp.region.amazonaws.com"
# export MAIL_PORT="587"
# export MAIL_USERNAME="AWS SES SMTP username"
# export MAIL_PASSWORD="AWS SES SMTP password"

# MAILGUN:
# export MAIL_HOST="smtp.mailgun.org"
# export MAIL_PORT="587"
# export MAIL_USERNAME="postmaster@your-domain.com"
# export MAIL_PASSWORD="Mailgun SMTP password"

# ============================================================================
# EXAMPLE AWS S3 CONFIGURATION
# ============================================================================

# export AWS_ACCESS_KEY="AKIAIOSFODNN7EXAMPLE"
# export AWS_SECRET_KEY="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
# export AWS_BUCKET="your-bucket-name"
# export AWS_REGION="us-east-1"

# ============================================================================
# VALIDATION
# ============================================================================

# This script provides sensible defaults. Ensure these critical values are set:
if [[ -z "$APP_DOMAIN" ]]; then
    echo "ERROR: APP_DOMAIN must be set"
    return 1
fi

if [[ -z "$DB_PASSWORD" ]]; then
    echo "ERROR: DB_PASSWORD must be set"
    return 1
fi

if [[ -z "$MAIL_HOST" ]] || [[ -z "$MAIL_USERNAME" ]]; then
    echo "WARNING: MAIL configuration may be incomplete"
fi

echo "✓ Configuration loaded successfully"
echo "  Domain: $APP_DOMAIN"
echo "  Database: $DB_NAME @ $DB_HOST"
echo "  Email: Configured for $MAIL_HOST"
