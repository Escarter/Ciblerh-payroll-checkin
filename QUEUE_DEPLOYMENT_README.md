# Laravel Queue Deployment Scenarios

This guide covers three different approaches to deploying and managing Laravel queue workers in production, optimized for the new multi-queue architecture.

## Queue Architecture Overview

The application now uses 5 specialized queues:
- **`high-priority`**: Critical operations (email retries) - 2 workers
- **`emails`**: Email/SMS sending - 5 workers
- **`processing`**: Heavy processing (imports/exports) - 3 workers
- **`pdf-processing`**: PDF operations - 2 workers
- **`default`**: Fallback queue - 2 workers

## Scenario 1: Supervisor (Process Manager)

Best for: Traditional server deployments, easy management, process monitoring.

### Setup

1. **Install Supervisor**:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install supervisor

   # CentOS/RHEL
   sudo yum install supervisor
   ```

2. **Copy configuration**:
   ```bash
   sudo cp supervisor-queue-workers.conf /etc/supervisor/conf.d/
   ```

3. **Update paths** in the config file:
   ```ini
   command=php /path/to/your/app/artisan queue:work...
   directory=/path/to/your/app
   ```

4. **Load and start**:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-queues:*
   ```

### Management

```bash
# Use the management script
./scripts/manage-queues.sh start all
./scripts/manage-queues.sh status
./scripts/manage-queues.sh stop emails
./scripts/manage-queues.sh restart processing
./scripts/manage-queues.sh monitor
```

### Monitoring

- **Supervisor Web UI**: Access at `http://your-server:9001`
- **Logs**: Check `/var/log/supervisor/queue-*.log`
- **Management Script**: `./scripts/manage-queues.sh monitor`

## Scenario 2: Docker Containers

Best for: Containerized deployments, microservices architecture, easy scaling.

### Setup

Your existing `docker-compose.yml` now includes all queue workers. Simply start your full stack:

```bash
# Start all services including queue workers
docker-compose up -d

# Or start only queue workers with default scaling
docker-compose up -d queue-high-priority queue-emails queue-processing queue-pdf-processing queue-default

# Or use the helper script with scaling
./scripts/start-queue-containers.sh 2  # Double the default worker count
```

### Management

```bash
# Use the management script
./scripts/manage-docker-queues.sh start all
./scripts/manage-docker-queues.sh status
./scripts/manage-docker-queues.sh logs emails
./scripts/manage-docker-queues.sh monitor
./scripts/manage-docker-queues.sh cleanup
```

### Scaling

```bash
# Scale email workers to 8 instances
docker-compose up -d --scale queue-emails=8

# Scale processing workers to 5 instances
docker-compose up -d --scale queue-processing=5

# Or use the management script
./scripts/manage-docker-queues.sh scale emails 8
./scripts/manage-docker-queues.sh scale processing 5
```

### Management

```bash
# Use the management script
./scripts/manage-docker-queues.sh start all
./scripts/manage-docker-queues.sh status
./scripts/manage-docker-queues.sh logs emails
./scripts/manage-docker-queues.sh monitor
./scripts/manage-docker-queues.sh cleanup
```

### Resource Limits

Each queue has specific resource allocations:
- **high-priority**: 0.5 CPU, 256MB RAM
- **emails**: 1.0 CPU, 512MB RAM
- **processing**: 2.0 CPU, 1GB RAM
- **pdf-processing**: 1.0 CPU, 512MB RAM
- **default**: 0.5 CPU, 256MB RAM

## Scenario 3: Manual Process Management

Best for: Development, testing, or when you prefer direct control.

### Setup

1. **Start all workers**:
   ```bash
   ./scripts/start-queue-workers.sh
   ```

2. **Stop all workers**:
   ```bash
   ./scripts/stop-queue-workers.sh
   ```

### Management

```bash
# Start workers
./scripts/start-queue-workers.sh

# Stop workers gracefully
./scripts/stop-queue-workers.sh

# Force stop workers
./scripts/stop-queue-workers.sh --force

# Check status
./scripts/stop-queue-workers.sh --status
```

### Process Monitoring

```bash
# Monitor queues continuously
./scripts/monitor-queues.sh monitor

# One-time status check
./scripts/monitor-queues.sh status

# Run health checks
./scripts/monitor-queues.sh check
```

## Health Checks & Monitoring

### Web-based Health Checks

Add to `routes/web.php`:
```php
require __DIR__.'/health.php';
```

Access endpoints:
- `GET /health` - Basic health check
- `GET /health/detailed` - Comprehensive health check with queue status

### Monitoring Features

The monitoring system checks:
- ✅ Worker process health
- ✅ Queue backlog levels
- ✅ System resource usage
- ✅ Database connectivity
- ✅ Redis connectivity
- ✅ Storage permissions

### Alert Configuration

Edit `scripts/monitor-queues.sh` to configure:
```bash
ALERT_EMAIL="admin@yourcompany.com"
MONITOR_INTERVAL=60  # seconds
```

## Performance Tuning

### Worker Allocation Guidelines

| Queue | Purpose | Workers | CPU | Memory | Rationale |
|-------|---------|---------|-----|--------|-----------|
| high-priority | Email retries | 2 | 0.5 | 256MB | Fast response needed |
| emails | Email/SMS | 5 | 1.0 | 512MB | High throughput required |
| processing | Imports/Exports | 3 | 2.0 | 1GB | Memory-intensive operations |
| pdf-processing | PDF ops | 2 | 1.0 | 512MB | CPU-intensive operations |
| default | Fallback | 2 | 0.5 | 256MB | General purpose |

### Scaling Triggers

- **Scale up** when queue depth > 50 jobs for 5+ minutes
- **Scale down** when average queue depth < 5 jobs for 30+ minutes
- **Alert** when any queue > 100 jobs or worker failure detected

### Log Management

```bash
# Rotate logs weekly
find /var/log/laravel-queues -name "*.log" -mtime +7 -delete

# Monitor log sizes
du -sh /var/log/laravel-queues/*
```

## Troubleshooting

### Common Issues

1. **Workers not starting**:
   ```bash
   # Check Laravel logs
   tail -f storage/logs/laravel.log

   # Check worker logs
   ./scripts/manage-queues.sh logs
   ```

2. **High memory usage**:
   ```bash
   # Check processing queue workers
   ps aux | grep "queue:work --queue=processing"
   ```

3. **Queue backlog**:
   ```bash
   # Check queue counts
   php artisan queue:count
   ```

### Emergency Commands

```bash
# Clear all queues (CAUTION: destroys pending jobs)
php artisan queue:clear

# Clear specific queue
php artisan queue:clear emails

# Restart all workers
./scripts/manage-queues.sh restart all

# Force stop all workers
./scripts/stop-queue-workers.sh --force
```

## Deployment Checklist

- [ ] Choose deployment scenario (Supervisor/Docker/Manual)
- [ ] Update paths in configuration files
- [ ] Configure monitoring alerts
- [ ] Test worker startup/shutdown
- [ ] Verify queue processing
- [ ] Set up log rotation
- [ ] Configure health check endpoints
- [ ] Test scaling (if using Docker)
- [ ] Set up automated monitoring

## Production Considerations

1. **Resource Monitoring**: Monitor CPU, memory, and disk usage
2. **Queue Monitoring**: Set up alerts for queue backlogs
3. **Log Management**: Implement log rotation and archiving
4. **Backup Strategy**: Ensure queue data persistence
5. **Security**: Run workers as non-privileged users
6. **Updates**: Plan for zero-downtime deployments

---

Choose the scenario that best fits your infrastructure and operational preferences. All scenarios provide the same queue optimization benefits with different management approaches.