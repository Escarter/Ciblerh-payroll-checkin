#!/bin/bash

# Laravel Queue Management Script for Supervisor
# Usage: ./manage-queues.sh [start|stop|restart|status|reload] [queue-name]

set -e

SUPERVISORCTL="supervisorctl"
APP_PATH="/path/to/your/app"
QUEUES=("high-priority" "emails" "processing" "pdf-processing" "default")

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

show_help() {
    cat << EOF
Laravel Queue Management Script

USAGE:
    $0 [COMMAND] [QUEUE_NAME]

COMMANDS:
    start       Start queue workers
    stop        Stop queue workers
    restart     Restart queue workers
    status      Show status of queue workers
    reload      Reload supervisor configuration
    logs        Show logs for queue workers
    monitor     Monitor queue status (continuous)

QUEUE_NAMES:
    high-priority   High priority queue (email retries)
    emails          Email/SMS sending queue
    processing      Import/Export processing queue
    pdf-processing  PDF processing queue
    default         Default queue
    all             All queues (default if no queue specified)

EXAMPLES:
    $0 start all          # Start all queue workers
    $0 stop emails        # Stop email queue workers
    $0 restart processing # Restart processing queue workers
    $0 status             # Show status of all workers
    $0 monitor            # Monitor all queues continuously

EOF
}

get_supervisor_programs() {
    local queue="$1"
    if [ "$queue" = "all" ] || [ -z "$queue" ]; then
        echo "queue-high-priority:* queue-emails:* queue-processing:* queue-pdf-processing:* queue-default:*"
    else
        echo "queue-$queue:*"
    fi
}

check_supervisor_config() {
    if ! command -v supervisorctl &> /dev/null; then
        error "supervisorctl not found. Please install supervisor."
        exit 1
    fi

    # Check if config is loaded
    if ! $SUPERVISORCTL avail | grep -q "queue-"; then
        warning "Queue programs not found in supervisor."
        log "Run: sudo supervisorctl reread && sudo supervisorctl update"
        exit 1
    fi
}

start_queues() {
    local queue="${1:-all}"
    log "Starting queue workers: $queue"

    local programs=$(get_supervisor_programs "$queue")

    if $SUPERVISORCTL start $programs; then
        success "Queue workers started successfully"
        show_status "$queue"
    else
        error "Failed to start queue workers"
        exit 1
    fi
}

stop_queues() {
    local queue="${1:-all}"
    log "Stopping queue workers: $queue"

    local programs=$(get_supervisor_programs "$queue")

    if $SUPERVISORCTL stop $programs; then
        success "Queue workers stopped successfully"
    else
        error "Failed to stop queue workers"
        exit 1
    fi
}

restart_queues() {
    local queue="${1:-all}"
    log "Restarting queue workers: $queue"

    local programs=$(get_supervisor_programs "$queue")

    if $SUPERVISORCTL restart $programs; then
        success "Queue workers restarted successfully"
        show_status "$queue"
    else
        error "Failed to restart queue workers"
        exit 1
    fi
}

show_status() {
    local queue="${1:-all}"
    log "Queue worker status:"

    if [ "$queue" = "all" ] || [ -z "$queue" ]; then
        $SUPERVISORCTL status queue-high-priority:* queue-emails:* queue-processing:* queue-pdf-processing:* queue-default:*
    else
        $SUPERVISORCTL status queue-$queue:*
    fi
}

show_logs() {
    local queue="${1:-all}"
    log "Showing recent logs:"

    local log_files=()
    if [ "$queue" = "all" ] || [ -z "$queue" ]; then
        log_files=("/var/log/supervisor/queue-high-priority.log" "/var/log/supervisor/queue-emails.log" "/var/log/supervisor/queue-processing.log" "/var/log/supervisor/queue-pdf-processing.log" "/var/log/supervisor/queue-default.log")
    else
        log_files=("/var/log/supervisor/queue-$queue.log")
    fi

    for log_file in "${log_files[@]}"; do
        if [ -f "$log_file" ]; then
            echo "=== $log_file ==="
            tail -20 "$log_file"
            echo ""
        else
            warning "Log file not found: $log_file"
        fi
    done
}

monitor_queues() {
    log "Monitoring queue status (Ctrl+C to stop)"
    echo "Press Ctrl+C to stop monitoring"
    echo ""

    while true; do
        clear
        echo "=== Laravel Queue Monitor ==="
        echo "Timestamp: $(date)"
        echo ""
        show_status "all"
        echo ""
        echo "=== Queue Counts ==="

        # Show queue counts from Laravel (requires artisan tinker access)
        if cd "$APP_PATH" 2>/dev/null; then
            for q in "${QUEUES[@]}"; do
                count=$(php artisan queue:count "$q" 2>/dev/null || echo "N/A")
                printf "%-15s: %s jobs\n" "$q" "$count"
            done
        fi

        sleep 5
    done
}

reload_supervisor() {
    log "Reloading supervisor configuration..."

    if sudo supervisorctl reread && sudo supervisorctl update; then
        success "Supervisor configuration reloaded"
    else
        error "Failed to reload supervisor configuration"
        exit 1
    fi
}

# Main script logic
COMMAND="$1"
QUEUE="$2"

case "$COMMAND" in
    start)
        check_supervisor_config
        start_queues "$QUEUE"
        ;;
    stop)
        check_supervisor_config
        stop_queues "$QUEUE"
        ;;
    restart)
        check_supervisor_config
        restart_queues "$QUEUE"
        ;;
    status)
        check_supervisor_config
        show_status "$QUEUE"
        ;;
    logs)
        show_logs "$QUEUE"
        ;;
    monitor)
        check_supervisor_config
        monitor_queues
        ;;
    reload)
        reload_supervisor
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        error "Unknown command: $COMMAND"
        echo ""
        show_help
        exit 1
        ;;
esac