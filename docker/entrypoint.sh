#!/bin/bash
set -e

echo "Starting application entrypoint..."

# Set timezone if provided
if [ -n "$TZ" ]; then
    echo "Setting timezone to $TZ"
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
fi

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until nc -z mysql 3306 2>/dev/null; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done
echo "MySQL is up!"

# Wait for Redis to be ready
echo "Waiting for Redis..."
until nc -z redis 6379 2>/dev/null; do
    echo "Redis is unavailable - sleeping"
    sleep 2
done
echo "Redis is up!"

# Set proper permissions (run as root)
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# Create storage directories if they don't exist
echo "Creating storage directories..."
mkdir -p /var/www/html/storage/app/public/attachments
mkdir -p /var/www/html/storage/app/processor/splitted
mkdir -p /var/www/html/storage/app/processor/modified
mkdir -p /var/www/html/storage/app/processor/raw
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Fix ownership of created directories
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true

# Create storage link if it doesn't exist (as www-data)
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    su -s /bin/bash -c "php artisan storage:link" www-data || true
fi

# Run migrations if needed (only in app container, not in horizon/queue/scheduler)
if [ "$1" = "php-fpm" ]; then
    # Check if database seeding is needed based on environment
    if [ "${FORCE_SEED:-false}" = "true" ] || [ "${APP_ENV}" = "local" ] && [ "${SKIP_SEED:-false}" != "true" ]; then
        echo "Running fresh migrations with seeding..."
        su -s /bin/bash -c "php artisan migrate:fresh --seed" www-data || true
    else
        echo "Running migrations..."
        su -s /bin/bash -c "php artisan migrate --force" www-data || true
        
        # Only seed in development or when explicitly requested
        if [ "${APP_ENV}" = "local" ] && [ "${SKIP_SEED:-false}" != "true" ]; then
            echo "Seeding database for development environment..."
            su -s /bin/bash -c "php artisan db:seed --force" www-data || true
        fi
    fi
    
    # Build frontend assets if not already built
    if [ ! -d "/var/www/html/public/build" ] || [ -z "$(ls -A /var/www/html/public/build 2>/dev/null)" ]; then
        echo "Building frontend assets..."
        su -s /bin/bash -c "npm run build" www-data || true
    fi
    
    # Clear caches only if not in production or explicitly requested
    if [ "${APP_ENV}" != "production" ] || [ "${FORCE_CACHE_CLEAR:-false}" = "true" ]; then
        echo "Clearing caches..."
        su -s /bin/bash -c "php artisan config:clear" www-data || true
        su -s /bin/bash -c "php artisan cache:clear" www-data || true
        su -s /bin/bash -c "php artisan route:clear" www-data || true
        su -s /bin/bash -c "php artisan view:clear" www-data || true
    fi
fi

# Optimize Laravel (production only)
if [ "${APP_ENV}" = "production" ]; then
    echo "Optimizing Laravel for production..."
    su -s /bin/bash -c "php artisan config:cache" www-data || true
    su -s /bin/bash -c "php artisan route:cache" www-data || true
    su -s /bin/bash -c "php artisan view:cache" www-data || true
fi

# Switch to www-data user for the main process
echo "Publishing Livewire assets..."
php artisan livewire:publish --assets --ansi

if [ "$1" = "php-fpm" ]; then
    echo "Starting PHP-FPM as root (will switch to www-data internally)"
    exec "$@"
elif [ "$1" = "artisan" ]; then
    echo "Switching to www-data user and starting $@"
    exec su -s /bin/bash -c "exec $@" www-data
elif [ "$1" = "schedule:work" ]; then
    echo "Starting Laravel scheduler as www-data user"
    exec su -s /bin/bash -c "php artisan schedule:work" www-data
else
    echo "Starting $@ as current user"
    exec "$@"
fi

