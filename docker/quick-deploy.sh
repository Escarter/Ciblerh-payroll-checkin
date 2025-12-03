#!/bin/bash

# Quick deployment script (non-interactive)
# Usage: ./docker/quick-deploy.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "🚀 Quick Docker Deployment"
echo ""

# Check Docker
if ! docker ps > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop."
    exit 1
fi

# Update .env (silently)
if [ -f .env ]; then
    sed -i.bak 's/DB_HOST=127.0.0.1/DB_HOST=mysql/' .env 2>/dev/null || true
    sed -i.bak 's/DB_HOST=localhost/DB_HOST=mysql/' .env 2>/dev/null || true
    sed -i.bak 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env 2>/dev/null || true
    sed -i.bak 's/REDIS_HOST=localhost/REDIS_HOST=redis/' .env 2>/dev/null || true
    rm -f .env.bak
fi

# Build and start
echo "📦 Building images..."
docker-compose build --quiet

echo "🚀 Starting services..."
docker-compose up -d

echo "⏳ Waiting for services..."
sleep 30

echo "🔧 Initializing application..."
docker-compose exec -T app php artisan key:generate --force 2>/dev/null || true
docker-compose exec -T app php artisan migrate --force 2>/dev/null || true
docker-compose exec -T app php artisan storage:link 2>/dev/null || true

echo ""
echo "✅ Deployment complete!"
echo "Access: http://localhost"
echo "Logs: docker-compose logs -f"

