# Docker Setup Guide

This document provides comprehensive instructions for setting up and running the Ciblerh Payroll and Check-in Management System using Docker.

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 4GB of available RAM
- At least 10GB of free disk space

## Quick Start

1. **Clone the repository** (if you haven't already):
   ```bash
   git clone <repository-url>
   cd Ciblerh-payroll-checkin
   ```

2. **Copy environment file**:
   ```bash
   cp .env.docker.example .env
   ```

3. **Update `.env` file** with your configuration:
   - Set `APP_KEY` (will be generated automatically if empty)
   - Configure database credentials
   - Set Redis password (optional but recommended)
   - Configure mail settings
   - Set timezone (default: `Africa/Douala`)

4. **Build and start containers**:
   ```bash
   docker-compose up -d --build
   ```

5. **Generate application key** (if not set):
   ```bash
   docker-compose exec app php artisan key:generate
   ```

6. **Run migrations**:
   ```bash
   docker-compose exec app php artisan migrate
   ```

7. **Seed database** (optional):
   ```bash
   docker-compose exec app php artisan db:seed
   ```

8. **Create storage symlink**:
   ```bash
   docker-compose exec app php artisan storage:link
   ```

9. **Access the application**:
   - Web: http://localhost
   - Horizon Dashboard: http://localhost/horizon (if enabled)

## Services Overview

The Docker setup includes the following services:

### Core Services

- **nginx**: Web server (port 80)
- **app**: PHP-FPM application container
- **mysql**: MySQL 8.0 database (port 3306)
- **redis**: Redis cache and queue (port 6379)

### Queue & Processing Services

- **horizon**: Laravel Horizon queue worker (monitors and processes queues)
- **scheduler**: Laravel Scheduler (runs scheduled tasks)
- **queue**: Alternative queue worker (use with `--profile queue-worker`)

## Scheduled Tasks

The application includes the following scheduled tasks (handled by the `scheduler` service):

- `wima:clean-processed` - Runs daily at 01:30
- `wima:leave-update-process` - Runs on the last day of the month at 23:50 (Africa/Douala timezone)
- `wima:wish-happy-birthday` - Runs daily at 08:00 (Africa/Douala timezone)

The scheduler service runs `php artisan schedule:work` which continuously checks for scheduled tasks.

## Queue Processing

### Using Laravel Horizon (Recommended)

Horizon is enabled by default and provides:
- Queue monitoring dashboard
- Job metrics and statistics
- Failed job management
- Auto-scaling workers

Access Horizon at: http://localhost/horizon

### Using Standard Queue Worker

If you prefer not to use Horizon, you can use the standard queue worker:

```bash
docker-compose --profile queue-worker up -d queue
```

## Environment Variables

Key environment variables for Docker setup:

### Application
- `APP_ENV`: Application environment (local, production)
- `APP_DEBUG`: Enable/disable debug mode
- `APP_URL`: Application URL
- `TZ`: Timezone (default: `Africa/Douala`)

### Database
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database user
- `DB_PASSWORD`: Database password

### Redis
- `REDIS_PASSWORD`: Redis password (optional)

### PDF Tools
- `PDFTOTEXT_PATH`: Path to pdftotext binary (default: `/usr/bin/pdftotext`)
- `PDFSEPARATE_PATH`: Path to pdfseparate binary (default: `/usr/bin/pdfseparate`)
- `PDFTK_PATH`: Path to pdftk binary (default: `/usr/bin/pdftk`)

## Common Commands

### View logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f horizon
docker-compose logs -f scheduler
```

### Execute Artisan commands
```bash
docker-compose exec app php artisan <command>
```

### Access container shell
```bash
docker-compose exec app bash
```

### Restart services
```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart horizon
docker-compose restart scheduler
```

### Stop services
```bash
docker-compose down
```

### Stop and remove volumes (⚠️ deletes data)
```bash
docker-compose down -v
```

## Storage Volumes

The following Docker volumes are created:

- `mysql_data`: MySQL database files
- `redis_data`: Redis persistence data

Application storage directories are mounted from the host:
- `./storage`: Application storage
- `./bootstrap/cache`: Bootstrap cache

## Production Considerations

### Security

1. **Change default passwords**:
   - Update `DB_PASSWORD` in `.env`
   - Set `REDIS_PASSWORD` in `.env`

2. **Disable debug mode**:
   ```env
   APP_DEBUG=false
   APP_ENV=production
   ```

3. **Use HTTPS**:
   - Configure SSL certificates
   - Update nginx configuration
   - Set `APP_URL` to HTTPS URL

4. **Enable Horizon authentication**:
   - Update `HorizonServiceProvider` gate
   - Restrict Horizon access

### Performance

1. **Optimize Laravel**:
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

2. **Adjust Horizon workers**:
   - Edit `config/horizon.php`
   - Adjust `maxProcesses` based on server resources

3. **Enable OPcache**:
   - Already enabled in Dockerfile
   - Configure in `php.ini` if needed

### Monitoring

1. **Monitor queue processing**:
   - Access Horizon dashboard
   - Check logs: `docker-compose logs -f horizon`

2. **Monitor scheduled tasks**:
   - Check logs: `docker-compose logs -f scheduler`
   - Verify tasks run at expected times

3. **Database monitoring**:
   - Check MySQL logs: `docker-compose logs -f mysql`
   - Monitor slow queries (configured in `docker/mysql/my.cnf`)

## Troubleshooting

### Services won't start

1. Check logs:
   ```bash
   docker-compose logs
   ```

2. Verify ports aren't in use:
   ```bash
   # Check port 80
   lsof -i :80
   
   # Check port 3306
   lsof -i :3306
   ```

3. Check disk space:
   ```bash
   df -h
   ```

### Database connection errors

1. Verify MySQL is healthy:
   ```bash
   docker-compose ps mysql
   ```

2. Check database credentials in `.env`

3. Wait for MySQL to be ready (healthcheck may take time)

### Queue jobs not processing

1. Check Horizon is running:
   ```bash
   docker-compose ps horizon
   ```

2. Check Horizon logs:
   ```bash
   docker-compose logs -f horizon
   ```

3. Verify Redis connection:
   ```bash
   docker-compose exec redis redis-cli ping
   ```

### Scheduled tasks not running

1. Check scheduler is running:
   ```bash
   docker-compose ps scheduler
   ```

2. Check scheduler logs:
   ```bash
   docker-compose logs -f scheduler
   ```

3. Verify timezone is set correctly:
   ```bash
   docker-compose exec scheduler date
   ```

### Permission errors

1. Fix storage permissions:
   ```bash
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
   ```

## Development

### Running tests

```bash
docker-compose exec app php artisan test
```

### Installing dependencies

```bash
# PHP dependencies
docker-compose exec app composer install

# Node dependencies
docker-compose exec app npm install
```

### Building assets

```bash
docker-compose exec app npm run build
# or for development
docker-compose exec app npm run dev
```

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

