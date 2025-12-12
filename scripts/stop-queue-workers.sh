#!/bin/bash

# Laravel Queue Workers Stop Script
# This script stops all running queue workers

set -e

# Configuration
PID_PATH="/var/run/laravel-queues"
LOG_PATH="/var/log/laravel-queues"

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

# Function to stop workers for a specific queue
stop_workers() {
    local queue="$1"
    local pid_file="$PID_PATH/$queue.pid"
    local signal="${2:-TERM}"  # Default to SIGTERM, can be SIGKILL

    if [ ! -f "$pid_file" ]; then
        warning "No PID file found for queue $queue"
        return 0
    fi

    local pids=$(cat "$pid_file")

    log "Stopping workers for queue: $queue (Signal: $signal)"

    # Handle multiple PIDs (comma-separated)
    IFS=',' read -ra PID_ARRAY <<< "$pids"

    local failed_pids=()
    local stopped_count=0

    for pid in "${PID_ARRAY[@]}"; do
        pid=$(echo "$pid" | tr -d ' ')  # Remove any whitespace

        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            if kill -"$signal" "$pid" 2>/dev/null; then
                log "Sent $signal signal to worker PID: $pid"
                stopped_count=$((stopped_count + 1))
            else
                error "Failed to send $signal signal to PID: $pid"
                failed_pids+=("$pid")
            fi
        else
            warning "Worker PID $pid not found or already stopped"
        fi
    done

    # Wait a bit for graceful shutdown
    if [ "$signal" = "TERM" ]; then
        log "Waiting 10 seconds for graceful shutdown..."
        sleep 10

        # Check if processes are still running and force kill if needed
        for pid in "${PID_ARRAY[@]}"; do
            pid=$(echo "$pid" | tr -d ' ')
            if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
                warning "Worker PID $pid still running, sending SIGKILL..."
                if kill -9 "$pid" 2>/dev/null; then
                    log "Force killed worker PID: $pid"
                else
                    error "Failed to force kill PID: $pid"
                    failed_pids+=("$pid")
                fi
            fi
        done
    fi

    # Clean up PID file if all workers stopped
    if [ ${#failed_pids[@]} -eq 0 ]; then
        rm -f "$pid_file"
        success "Successfully stopped $stopped_count worker(s) for queue $queue"
    else
        error "Failed to stop ${#failed_pids[@]} worker(s) for queue $queue: ${failed_pids[*]}"
        return 1
    fi
}

# Stop all queue workers
stop_all_workers() {
    log "Stopping all Laravel queue workers..."

    local queues=("high-priority" "emails" "processing" "pdf-processing" "default")
    local failed_queues=()

    for queue in "${queues[@]}"; do
        if ! stop_workers "$queue"; then
            failed_queues+=("$queue")
        fi
    done

    if [ ${#failed_queues[@]} -eq 0 ]; then
        success "All queue workers stopped successfully"
    else
        error "Failed to stop workers for queues: ${failed_queues[*]}"
        return 1
    fi
}

# Show status of queue workers
show_status() {
    log "Queue workers status:"

    local queues=("high-priority" "emails" "processing" "pdf-processing" "default")
    local running_count=0

    for queue in "${queues[@]}"; do
        local pid_file="$PID_PATH/$queue.pid"
        local status="STOPPED"
        local pids=""

        if [ -f "$pid_file" ]; then
            pids=$(cat "$pid_file")
            local pid_count=$(echo "$pids" | tr ',' '\n' | wc -l)

            # Check if any PIDs are still running
            local running_pids=()
            IFS=',' read -ra PID_ARRAY <<< "$pids"
            for pid in "${PID_ARRAY[@]}"; do
                pid=$(echo "$pid" | tr -d ' ')
                if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
                    running_pids+=("$pid")
                fi
            done

            if [ ${#running_pids[@]} -gt 0 ]; then
                status="RUNNING (${#running_pids[@]}/$pid_count workers)"
                running_count=$((running_count + 1))
            else
                status="STOPPED (stale PID file)"
            fi
        fi

        printf "%-15s: %s\n" "$queue" "$status"
        if [ -n "$pids" ] && [ "$status" != "STOPPED" ]; then
            printf "%-15s  PIDs: %s\n" "" "$pids"
        fi
    done

    echo ""
    log "Summary: $running_count queue(s) running"
}

# Force stop all workers (SIGKILL)
force_stop_all() {
    warning "Force stopping all queue workers (SIGKILL)..."
    local queues=("high-priority" "emails" "processing" "pdf-processing" "default")

    for queue in "${queues[@]}"; do
        stop_workers "$queue" "KILL"
    done
}

# Show usage information
show_usage() {
    cat << EOF
Laravel Queue Workers Stop Script

USAGE:
    $0 [OPTIONS]

OPTIONS:
    (no options)    Stop all workers gracefully
    --force         Force stop all workers (SIGKILL)
    --status        Show status of all workers
    --help, -h      Show this help

This script stops all configured queue workers:

QUEUES CONFIGURED:
  - high-priority
  - emails
  - processing
  - pdf-processing
  - default

PID FILES: $PID_PATH/

EOF
}

# Main script
main() {
    case "$1" in
        --force)
            force_stop_all
            ;;
        --status)
            show_status
            ;;
        --help|-h)
            show_usage
            exit 0
            ;;
        "")
            stop_all_workers
            ;;
        *)
            error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
}

main "$@"