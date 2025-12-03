# Docker Deployment Guide

## Prerequisites

1. **Start Docker Desktop** (if on macOS/Windows)
   - Open Docker Desktop application
   - Wait for it to fully start (whale icon in menu bar should be steady)

2. **Verify Docker is running**:
   ```bash
   docker ps
   ```

## Quick Deployment Steps

### 1. Update Environment Variables

Update your `.env` file with Docker-specific settings:

```bash
# Database (Docker service names)
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ciblerh
DB_USERNAME=ciblerh
DB_PASSWORD=password

# Redis (Docker service name)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Application URL
APP_URL=http://localhost

# PDF Tools (Docker paths)
PDFTOTEXT_PATH=/usr/bin/pdftotext
PDFSEPARATE_PATH=/usr/bin/pdfseparate
PDFTK_PATH=/usr/bin/pdftk

# Queue
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Timezone
TZ=Africa/Douala
```

### 2. Build Docker Images

```bash
# Build all services
docker-compose build

# Or build without cache (if you want fresh build)
docker-compose build --no-cache
```

### 3. Start All Services

```bash
# Start in detached mode
docker-compose up -d

# Or start with logs visible
docker-compose up
```

### 4. Initialize Application

```bash
# Generate application key (if not set)
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Create storage symlink
docker-compose exec app php artisan storage:link

# Seed database (optional)
docker-compose exec app php artisan db:seed
```

### 5. Verify Services

```bash
# Check all services are running
docker-compose ps

# Check logs
docker-compose logs -f

# Check specific service logs
docker-compose logs -f app
docker-compose logs -f horizon
docker-compose logs -f scheduler
```

### 6. Access Application

- **Web Application**: http://localhost
- **Horizon Dashboard**: http://localhost/horizon

## Troubleshooting

### Docker Daemon Not Running

**Error**: `Cannot connect to the Docker daemon`

**Solution**:
1. Start Docker Desktop
2. Wait for it to fully initialize
3. Verify: `docker ps`

### Port Already in Use

**Error**: `Bind for 0.0.0.0:80 failed: port is already allocated`

**Solution**:
1. Find what's using the port:
   ```bash
   lsof -i :80
   ```
2. Stop the service or change port in `.env`:
   ```env
   APP_PORT=8080
   ```

### Database Connection Errors

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solution**:
1. Check MySQL is healthy:
   ```bash
   docker-compose ps mysql
   docker-compose logs mysql
   ```
2. Wait for MySQL to be ready (may take 30-60 seconds)
3. Verify connection:
   ```bash
   docker-compose exec app php artisan db:monitor
   ```

### Permission Errors

**Error**: `Permission denied` in storage

**Solution**:
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### PDF Tools Not Found

**Error**: `pdftk: command not found` or similar

**Solution**:
1. Verify tools are installed:
   ```bash
   docker-compose exec app which pdftk
   docker-compose exec app which pdftotext
   docker-compose exec app which pdfseparate
   ```
2. If missing, rebuild the image:
   ```bash
   docker-compose build --no-cache app
   ```

### Queue Jobs Not Processing

**Error**: Jobs stuck in queue

**Solution**:
1. Check Horizon is running:
   ```bash
   docker-compose ps horizon
   docker-compose logs horizon
   ```
2. Restart Horizon:
   ```bash
   docker-compose restart horizon
   ```
3. Check Redis connection:
   ```bash
   docker-compose exec redis redis-cli ping
   ```

## Useful Commands

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f horizon
docker-compose logs -f scheduler
docker-compose logs -f mysql
docker-compose logs -f redis
```

### Execute Commands
```bash
# Artisan commands
docker-compose exec app php artisan <command>

# Composer
docker-compose exec app composer <command>

# NPM
docker-compose exec app npm <command>

# Shell access
docker-compose exec app bash
```

### Restart Services
```bash
# All services
docker-compose restart

# Specific service
docker-compose restart horizon
docker-compose restart scheduler
```

### Stop Services
```bash
# Stop (keeps containers)
docker-compose stop

# Stop and remove containers
docker-compose down

# Stop and remove volumes (⚠️ deletes data)
docker-compose down -v
```

### Rebuild After Changes
```bash
# Rebuild specific service
docker-compose build app

# Rebuild and restart
docker-compose up -d --build
```

## Health Checks

### Verify All Services
```bash
# Check service status
docker-compose ps

# Expected output: All services should show "Up" status
```

### Test Database
```bash
docker-compose exec app php artisan tinker
# Then in tinker:
DB::connection()->getPdo();
```

### Test Redis
```bash
docker-compose exec redis redis-cli ping
# Should return: PONG
```

### Test PDF Tools
```bash
docker-compose exec app pdftk --version
docker-compose exec app pdftotext -v
docker-compose exec app pdfseparate -v
```

### Test Queue
```bash
# Check Horizon dashboard
# Visit: http://localhost/horizon

# Or check queue status
docker-compose exec app php artisan queue:monitor
```

## Production Deployment

For production, ensure:

1. **Update `.env`**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   ```

2. **Set strong passwords**:
   ```env
   DB_PASSWORD=<strong-password>
   REDIS_PASSWORD=<strong-password>
   ```

3. **Optimize Laravel**:
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

4. **Configure SSL** in nginx configuration

5. **Set up monitoring** for services

6. **Backup strategy** for MySQL and Redis volumes

