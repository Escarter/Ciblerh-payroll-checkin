#!/bin/bash

# Laravel Queue Monitoring Script
# Monitors queue status, worker health, and performance metrics

set -e

# Configuration
APP_PATH="/path/to/your/laravel/app"
LOG_PATH="/var/log/laravel-queues"
PID_PATH="/var/run/laravel-queues"
MONITOR_INTERVAL=60  # seconds
ALERT_EMAIL="admin@yourcompany.com"

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

alert() {
    local message="$1"
    local subject="Laravel Queue Alert: $message"

    log "ALERT: $message"

    # Send email alert if configured
    if command -v mail &> /dev/null && [ -n "$ALERT_EMAIL" ]; then
        echo "$message

Timestamp: $(date)
Server: $(hostname)
App Path: $APP_PATH

Queue Status:
$(get_queue_status)

Worker Status:
$(get_worker_status)" | mail -s "$subject" "$ALERT_EMAIL"
    fi
}

# Get queue counts from Laravel
get_queue_counts() {
    cd "$APP_PATH" 2>/dev/null || return 1

    local queues=("high-priority" "emails" "processing" "pdf-processing" "default")
    local result=""

    for queue in "${queues[@]}"; do
        local count
        count=$(php artisan queue:count "$queue" 2>/dev/null || echo "ERROR")
        result+="$queue: $count jobs\n"
    done

    echo -e "$result"
}

# Get worker status
get_worker_status() {
    local queues=("high-priority" "emails" "processing" "pdf-processing" "default")
    local result=""

    for queue in "${queues[@]}"; do
        local pid_file="$PID_PATH/$queue.pid"
        local status="STOPPED"
        local worker_count=0

        if [ -f "$pid_file" ]; then
            local pids=$(cat "$pid_file" 2>/dev/null || echo "")
            local running_count=0

            if [ -n "$pids" ]; then
                IFS=',' read -ra PID_ARRAY <<< "$pids"
                worker_count=${#PID_ARRAY[@]}

                for pid in "${PID_ARRAY[@]}"; do
                    pid=$(echo "$pid" | tr -d ' ')
                    if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
                        running_count=$((running_count + 1))
                    fi
                done

                if [ "$running_count" -gt 0 ]; then
                    status="RUNNING ($running_count/$worker_count)"
                else
                    status="STOPPED (stale PIDs)"
                fi
            fi
        fi

        result+="$queue: $status\n"
    done

    echo -e "$result"
}

# Check worker health
check_worker_health() {
    local queues=("high-priority" "emails" "processing" "pdf-processing" "default")
    local unhealthy_queues=()

    for queue in "${queues[@]}"; do
        local pid_file="$PID_PATH/$queue.pid"

        if [ -f "$pid_file" ]; then
            local pids=$(cat "$pid_file" 2>/dev/null || echo "")
            local running_count=0
            local total_count=0

            if [ -n "$pids" ]; then
                IFS=',' read -ra PID_ARRAY <<< "$pids"
                total_count=${#PID_ARRAY[@]}

                for pid in "${PID_ARRAY[@]}"; do
                    pid=$(echo "$pid" | tr -d ' ')
                    if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
                        running_count=$((running_count + 1))
                    fi
                done

                # Alert if less than 50% of workers are running
                if [ "$total_count" -gt 0 ] && [ "$running_count" -lt $((total_count / 2)) ]; then
                    unhealthy_queues+=("$queue: only $running_count/$total_count workers running")
                fi
            fi
        else
            unhealthy_queues+=("$queue: no PID file found")
        fi
    done

    if [ ${#unhealthy_queues[@]} -gt 0 ]; then
        alert "Unhealthy queue workers detected: ${unhealthy_queues[*]}"
    fi
}

# Check queue backlog
check_queue_backlog() {
    cd "$APP_PATH" 2>/dev/null || return 1

    local high_priority_count
    high_priority_count=$(php artisan queue:count "high-priority" 2>/dev/null || echo "0")

    # Alert if high priority queue has more than 10 jobs
    if [[ "$high_priority_count" =~ ^[0-9]+$ ]] && [ "$high_priority_count" -gt 10 ]; then
        alert "High priority queue backlog: $high_priority_count jobs waiting"
    fi

    local email_count
    email_count=$(php artisan queue:count "emails" 2>/dev/null || echo "0")

    # Alert if email queue has more than 100 jobs
    if [[ "$email_count" =~ ^[0-9]+$ ]] && [ "$email_count" -gt 100 ]; then
        alert "Email queue backlog: $email_count jobs waiting"
    fi
}

# Get system resource usage
get_system_stats() {
    local cpu_usage
    local mem_usage
    local disk_usage

    cpu_usage=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
    mem_usage=$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')
    disk_usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')

    echo "CPU: ${cpu_usage}%, Memory: ${mem_usage}%, Disk: ${disk_usage}%"
}

# Show dashboard
show_dashboard() {
    clear
    echo "========================================"
    echo "  Laravel Queue Monitor Dashboard"
    echo "========================================"
    echo "Timestamp: $(date)"
    echo "Server: $(hostname)"
    echo ""
    echo "System Resources:"
    echo "$(get_system_stats)"
    echo ""
    echo "Queue Status:"
    echo "$(get_queue_counts)"
    echo ""
    echo "Worker Status:"
    echo "$(get_worker_status)"
    echo ""
    echo "Recent Log Activity:"
    if [ -d "$LOG_PATH" ]; then
        find "$LOG_PATH" -name "*.log" -type f -mmin -5 -exec basename {} \; 2>/dev/null | head -5
    fi
    echo ""
    echo "Press Ctrl+C to stop monitoring"
}

# Run health checks
run_health_checks() {
    check_worker_health
    check_queue_backlog
}

# Continuous monitoring mode
monitor_continuous() {
    log "Starting continuous queue monitoring (interval: ${MONITOR_INTERVAL}s)"
    log "Press Ctrl+C to stop"

    while true; do
        show_dashboard
        run_health_checks
        sleep "$MONITOR_INTERVAL"
    done
}

# One-time status check
show_status() {
    echo "========================================"
    echo "  Laravel Queue Status Report"
    echo "========================================"
    echo "Timestamp: $(date)"
    echo "Server: $(hostname)"
    echo ""
    echo "System Resources:"
    echo "$(get_system_stats)"
    echo ""
    echo "Queue Status:"
    echo "$(get_queue_counts)"
    echo ""
    echo "Worker Status:"
    echo "$(get_worker_status)"
}

# Show usage information
show_usage() {
    cat << EOF
Laravel Queue Monitoring Script

USAGE:
    $0 [COMMAND]

COMMANDS:
    monitor     Start continuous monitoring (default)
    status      Show current status once
    check       Run health checks once
    help        Show this help

CONFIGURATION:
    APP_PATH: $APP_PATH
    LOG_PATH: $LOG_PATH
    PID_PATH: $PID_PATH
    INTERVAL: ${MONITOR_INTERVAL}s
    ALERT_EMAIL: $ALERT_EMAIL

FEATURES:
    - Real-time queue monitoring
    - Worker health checks
    - Backlog alerts
    - System resource monitoring
    - Email alerts for issues

EOF
}

# Main script
main() {
    case "${1:-monitor}" in
        monitor)
            monitor_continuous
            ;;
        status)
            show_status
            ;;
        check)
            run_health_checks
            success "Health checks completed"
            ;;
        help|--help|-h)
            show_usage
            ;;
        *)
            error "Unknown command: $1"
            show_usage
            exit 1
            ;;
    esac
}

main "$@"