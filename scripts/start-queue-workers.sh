#!/bin/bash

# Laravel Queue Workers Startup Script
# This script starts multiple queue workers for different queues

set -e

# Configuration
APP_PATH="/path/to/your/laravel/app"
LOG_PATH="/var/log/laravel-queues"
PID_PATH="/var/run/laravel-queues"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Create directories if they don't exist
setup_directories() {
    mkdir -p "$LOG_PATH" "$PID_PATH"
    chown www-data:www-data "$LOG_PATH" "$PID_PATH" 2>/dev/null || true
}

# Function to start a queue worker
start_worker() {
    local queue="$1"
    local instances="$2"
    local command="$3"
    local log_file="$LOG_PATH/$queue.log"
    local pid_file="$PID_PATH/$queue.pid"

    log "Starting $instances worker(s) for queue: $queue"

    # Check if workers are already running
    if [ -f "$pid_file" ]; then
        if kill -0 $(cat "$pid_file") 2>/dev/null; then
            warning "Workers for queue $queue are already running (PID: $(cat $pid_file))"
            return 0
        else
            warning "Removing stale PID file for queue $queue"
            rm -f "$pid_file"
        fi
    fi

    # Start workers in background
    cd "$APP_PATH"

    if [ "$instances" -eq 1 ]; then
        # Single worker
        nohup php artisan $command >> "$log_file" 2>&1 &
        echo $! > "$pid_file"
    else
        # Multiple workers - start in parallel
        pids=()
        for i in $(seq 1 "$instances"); do
            nohup php artisan $command >> "${log_file%.log}-$i.log" 2>&1 &
            pids+=($!)
        done

        # Save PIDs (comma-separated for multiple workers)
        printf '%s\n' "${pids[@]}" | paste -sd ',' > "$pid_file"
    fi

    sleep 2

    # Verify workers started
    if [ -f "$pid_file" ] && kill -0 $(cat "$pid_file" | tr ',' ' ') 2>/dev/null; then
        success "Started $instances worker(s) for queue $queue"
    else
        error "Failed to start workers for queue $queue"
        return 1
    fi
}

# Start all queue workers
start_all_workers() {
    log "Starting all Laravel queue workers..."

    # High Priority Queue (Email retries) - 2 workers
    start_worker "high-priority" 2 "queue:work --queue=high-priority --sleep=3 --tries=3 --max-jobs=1000 --timeout=90"

    # Email Queue - 5 workers
    start_worker "emails" 5 "queue:work --queue=emails --sleep=3 --tries=3 --max-jobs=1000 --timeout=120"

    # Processing Queue (Imports/Exports) - 3 workers
    start_worker "processing" 3 "queue:work --queue=processing --sleep=3 --tries=3 --max-jobs=500 --timeout=1800 --memory=512"

    # PDF Processing Queue - 2 workers
    start_worker "pdf-processing" 2 "queue:work --queue=pdf-processing --sleep=3 --tries=3 --max-jobs=500 --timeout=600 --memory=256"

    # Default Queue - 2 workers
    start_worker "default" 2 "queue:work --queue=default --sleep=3 --tries=3 --max-jobs=1000 --timeout=90"

    success "All queue workers started successfully"
}

# Show usage information
show_usage() {
    cat << EOF
Laravel Queue Workers Startup Script

USAGE:
    $0

This script starts all configured queue workers with appropriate configurations:

QUEUES CONFIGURED:
  - high-priority: 2 workers (email retries, critical ops)
  - emails: 5 workers (email/SMS sending)
  - processing: 3 workers (imports/exports, memory-intensive)
  - pdf-processing: 2 workers (PDF operations)
  - default: 2 workers (fallback queue)

LOG FILES: $LOG_PATH/
PID FILES: $PID_PATH/

Use 'stop-queue-workers.sh' to stop the workers.

EOF
}

# Main script
main() {
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi

    # Check if we're running as appropriate user
    if [ "$EUID" -eq 0 ]; then
        warning "Running as root. Consider using a non-privileged user."
    fi

    # Check if Laravel app exists
    if [ ! -f "$APP_PATH/artisan" ]; then
        error "Laravel application not found at $APP_PATH"
        error "Please update APP_PATH in this script"
        exit 1
    fi

    setup_directories
    start_all_workers

    log "Queue workers are now running in the background"
    log "Use 'stop-queue-workers.sh' to stop them"
    log "Monitor with: tail -f $LOG_PATH/*.log"
}

main "$@"