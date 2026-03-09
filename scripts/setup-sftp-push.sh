#!/bin/bash

# SFTP Push Setup Script
# This script sets up the SFTP push infrastructure for CibleRh Payroll
# Automatically creates directories, sets permissions, and generates credentials

set -e

echo "🚀 SFTP Push Setup Script"
echo "========================"
echo ""

# Check if Laravel project exists
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

# Determine push path (default or custom)
PUSH_PATH="${1:-storage/app/sftp-push}"
FULL_PATH="$(pwd)/$PUSH_PATH"

echo "📁 Push Path: $FULL_PATH"
echo ""

# Step 1: Create directories
echo "Step 1️⃣ : Creating directory structure..."
mkdir -p "$FULL_PATH/processed"
mkdir -p "$FULL_PATH/failed"
echo "✅ Directories created"
echo ""

# Step 2: Set permissions
echo "Step 2️⃣ : Setting permissions..."
chmod -R 755 "$FULL_PATH"
chmod -R u+w "$FULL_PATH"
echo "✅ Permissions set"
echo ""

# Step 3: Run the artisan command
echo "Step 3️⃣ : Generating credentials..."
php artisan setup:sftp-push ${2:+--force}
echo ""

echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "  1. Copy the credentials shown above"
echo "  2. Configure your external systems to POST files to the API endpoint"
echo "  3. Use HTTP Basic Auth with the generated username and password"
echo "  4. Files will be automatically processed by the scheduled job"
echo ""
