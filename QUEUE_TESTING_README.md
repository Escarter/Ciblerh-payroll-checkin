# Queue Testing System

This document describes the queue testing system implemented for your Laravel application.

## Overview

Your application uses **5 different queues**:

1. **`default`** - General operations (default queue)
2. **`high-priority`** - Critical operations requiring immediate attention
3. **`emails`** - Email and SMS operations
4. **`processing`** - Heavy processing tasks (imports, exports, reports)
5. **`pdf-processing`** - PDF manipulation and processing operations

## Queue Configuration

The queues are configured in `config/queue.php` with Redis as the driver:

- All queues use Redis connection
- Each queue has a 90-second retry timeout
- Failed jobs are stored in the `failed_jobs` table

## Testing Commands

### 1. Test All Queues

Dispatch test jobs to all queues:

```bash
php artisan queue:test
```

**Options:**
- `--wait` - Wait for jobs to complete
- `--timeout=30` - Set wait timeout in seconds
- `--queue=default` - Test only specific queue

### 2. Monitor Queue Status

Check the current status of your queues:

```bash
php artisan queue:monitor
```

**Options:**
- `--queue=emails` - Monitor specific queue
- `--failed` - Show only failed jobs
- `--pending` - Show only pending jobs
- `--recent=10` - Show last N jobs
- `--session=uuid` - Monitor specific test session

## Quick Test Workflow

### Step 1: Start Queue Workers

Make sure your queue workers are running for each queue:

```bash
# Terminal 1 - Default queue
php artisan queue:work --queue=default

# Terminal 2 - High priority queue
php artisan queue:work --queue=high-priority

# Terminal 3 - Emails queue
php artisan queue:work --queue=emails

# Terminal 4 - Processing queue
php artisan queue:work --queue=processing

# Terminal 5 - PDF processing queue
php artisan queue:work --queue=pdf-processing
```

### Step 2: Run Queue Tests

```bash
# Test all queues
php artisan queue:test

# Test specific queue
php artisan queue:test --queue=emails

# Test and wait for completion
php artisan queue:test --wait --timeout=60
```

### Step 3: Monitor Results

```bash
# Check queue status
php artisan queue:monitor

# Monitor specific queue
php artisan queue:monitor --queue=emails

# Check for failed jobs
php artisan queue:monitor --failed
```

## Test Jobs

The system includes test job classes for each queue:

- `TestDefaultQueueJob` - Tests the default queue
- `TestHighPriorityQueueJob` - Tests high-priority operations
- `TestEmailsQueueJob` - Tests email/SMS operations
- `TestProcessingQueueJob` - Tests heavy processing operations
- `TestPdfProcessingQueueJob` - Tests PDF processing operations

Each test job:
- Logs execution start/completion
- Simulates realistic work duration
- Handles failures gracefully

## Monitoring & Troubleshooting

### Check Redis Connection

```bash
php artisan queue:monitor
```

### View Failed Jobs

```bash
php artisan queue:failed
```

### Clear Failed Jobs

```bash
php artisan queue:flush
```

### Restart Queue Workers

```bash
php artisan queue:restart
```

## Production Considerations

### Queue Worker Management

Use process managers like Supervisor to manage queue workers:

```ini
[program:laravel-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=default --sleep=3 --tries=3
directory=/path/to/project
autostart=true
autorestart=true
numprocs=2
```

### Monitoring

- Set up monitoring for queue sizes
- Alert on failed job accumulation
- Monitor worker process health
- Track job execution times

### Performance Tuning

- Adjust worker count based on load
- Use different retry policies per queue
- Implement job batching for bulk operations
- Monitor memory usage of workers

## Files Created

- `app/Jobs/TestJobs/` - Test job classes
- `app/Console/Commands/TestQueuesCommand.php` - Main testing command
- `app/Console/Commands/MonitorQueuesCommand.php` - Monitoring command

## Usage Examples

### Complete System Test

```bash
# 1. Check current status
php artisan queue:monitor

# 2. Run comprehensive test
php artisan queue:test --wait --timeout=120

# 3. Check results
php artisan queue:monitor --recent=20

# 4. Check for any failures
php artisan queue:monitor --failed
```

### Quick Health Check

```bash
# Test one queue quickly
php artisan queue:test --queue=default --wait --timeout=10
php artisan queue:monitor --queue=default
```
