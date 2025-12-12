#!/bin/bash

# Docker Queue Management Script for Laravel
# Usage: ./manage-docker-queues.sh [start|stop|restart|status|logs|scale] [queue-name] [count]

set -e

COMPOSE_FILE="docker-compose.yml"
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
Docker Queue Management Script for Laravel

USAGE:
    $0 [COMMAND] [QUEUE_NAME] [COUNT]

COMMANDS:
    start       Start queue workers
    stop        Stop queue workers
    restart     Restart queue workers
    status      Show status of queue workers
    logs        Show logs for queue workers
    scale       Scale queue workers to specified count
    monitor     Monitor queue status (continuous)
    cleanup     Remove stopped containers and unused images

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
    $0 scale emails 8     # Scale email workers to 8 instances
    $0 status             # Show status of all workers
    $0 logs processing    # Show logs for processing workers
    $0 monitor            # Monitor all queues continuously

EOF
}

check_docker() {
    if ! command -v docker &> /dev/null; then
        error "Docker not found. Please install Docker."
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose not found. Please install Docker Compose."
        exit 1
    fi
}

get_services() {
    local queue="$1"
    if [ "$queue" = "all" ] || [ -z "$queue" ]; then
        echo "queue-high-priority queue-emails queue-processing queue-pdf-processing queue-default"
    else
        echo "queue-$queue"
    fi
}

start_queues() {
    local queue="${1:-all}"
    log "Starting queue workers: $queue"

    local services=$(get_services "$queue")

    if docker-compose -f "$COMPOSE_FILE" up -d $services; then
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

    local services=$(get_services "$queue")

    if docker-compose -f "$COMPOSE_FILE" down $services; then
        success "Queue workers stopped successfully"
    else
        error "Failed to stop queue workers"
        exit 1
    fi
}

restart_queues() {
    local queue="${1:-all}"
    log "Restarting queue workers: $queue"

    local services=$(get_services "$queue")

    if docker-compose -f "$COMPOSE_FILE" restart $services; then
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
        docker-compose -f "$COMPOSE_FILE" ps
    else
        docker-compose -f "$COMPOSE_FILE" ps "queue-$queue"
    fi

    echo ""
    log "Queue counts:"
    show_queue_counts
}

show_logs() {
    local queue="${1:-all}"
    local lines="${2:-50}"

    if [ "$queue" = "all" ] || [ -z "$queue" ]; then
        log "Showing recent logs for all queues (last $lines lines):"
        docker-compose -f "$COMPOSE_FILE" logs --tail="$lines" -f
    else
        log "Showing recent logs for $queue queue (last $lines lines):"
        docker-compose -f "$COMPOSE_FILE" logs --tail="$lines" "queue-$queue"
    fi
}

scale_queues() {
    local queue="$1"
    local count="$2"

    if [ -z "$queue" ] || [ -z "$count" ]; then
        error "Queue name and count are required for scaling"
        echo "Usage: $0 scale <queue> <count>"
        exit 1
    fi

    if ! [[ "$count" =~ ^[0-9]+$ ]] || [ "$count" -lt 1 ]; then
        error "Count must be a positive integer"
        exit 1
    fi

    log "Scaling $queue queue to $count instances"

    if docker-compose -f "$COMPOSE_FILE" up -d --scale "queue-$queue=$count" "queue-$queue"; then
        success "Queue $queue scaled to $count instances"
        show_status "$queue"
    else
        error "Failed to scale queue $queue"
        exit 1
    fi
}

show_queue_counts() {
    # This requires access to the Laravel app container
    # Note: ciblerh_app container must be running
    if docker ps --format "table {{.Names}}" | grep -q "^ciblerh_app$"; then
        for q in "${QUEUES[@]}"; do
            count=$(docker exec ciblerh_app php artisan queue:count "$q" 2>/dev/null || echo "N/A")
            printf "%-15s: %s jobs\n" "$q" "$count"
        done
    else
        echo "App container not running - cannot check queue counts"
    fi
}

monitor_queues() {
    log "Monitoring queue status (Ctrl+C to stop)"
    echo "Press Ctrl+C to stop monitoring"
    echo ""

    while true; do
        clear
        echo "=== Docker Queue Monitor ==="
        echo "Timestamp: $(date)"
        echo ""
        show_status "all"
        sleep 10
    done
}

cleanup_docker() {
    log "Cleaning up stopped containers and unused images..."

    # Remove stopped containers
    docker container prune -f

    # Remove unused images
    docker image prune -f

    # Remove unused volumes
    docker volume prune -f

    success "Cleanup completed"
}

# Main script logic
COMMAND="$1"
QUEUE="$2"
COUNT="$3"

case "$COMMAND" in
    start)
        check_docker
        start_queues "$QUEUE"
        ;;
    stop)
        check_docker
        stop_queues "$QUEUE"
        ;;
    restart)
        check_docker
        restart_queues "$QUEUE"
        ;;
    status)
        check_docker
        show_status "$QUEUE"
        ;;
    logs)
        check_docker
        show_logs "$QUEUE"
        ;;
    scale)
        check_docker
        scale_queues "$QUEUE" "$COUNT"
        ;;
    monitor)
        check_docker
        monitor_queues
        ;;
    cleanup)
        check_docker
        cleanup_docker
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