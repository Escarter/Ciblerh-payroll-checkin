#!/bin/bash

# Queue Testing Script for Ciblerh Payroll Application
# This script provides an easy way to test all queues in the application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="."
ARTISAN="${APP_PATH}/artisan"
PHP="php"

# Queues to test
QUEUES=("default" "high-priority" "emails" "processing" "pdf-processing")

echo -e "${BLUE}🚀 Ciblerh Queue Testing Script${NC}"
echo -e "${BLUE}=================================${NC}"

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
echo -e "\n${YELLOW}📋 Checking prerequisites...${NC}"

if ! command_exists php; then
    echo -e "${RED}❌ PHP not found. Please ensure PHP is installed and in your PATH.${NC}"
    exit 1
fi

if [ ! -f "$ARTISAN" ]; then
    echo -e "${RED}❌ Laravel artisan file not found at ${ARTISAN}${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Prerequisites OK${NC}"

# Function to test single queue
test_queue() {
    local queue=$1
    echo -e "\n${BLUE}🧪 Testing queue: ${queue}${NC}"

    # Test the queue
    if $PHP $ARTISAN queue:test --queue="$queue" --wait --timeout=30; then
        echo -e "${GREEN}✅ Queue '${queue}' test completed successfully${NC}"
        return 0
    else
        echo -e "${RED}❌ Queue '${queue}' test failed${NC}"
        return 1
    fi
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --all          Test all queues (default)"
    echo "  --queue=NAME   Test specific queue"
    echo "  --monitor      Show queue monitoring dashboard"
    echo "  --failed       Show failed jobs"
    echo "  --help         Show this help message"
    echo ""
    echo "Available queues:"
    for queue in "${QUEUES[@]}"; do
        echo "  - $queue"
    done
    echo ""
    echo "Examples:"
    echo "  $0                     # Test all queues"
    echo "  $0 --queue=emails      # Test only emails queue"
    echo "  $0 --monitor           # Show monitoring dashboard"
    echo "  $0 --failed            # Show failed jobs"
}

# Parse command line arguments
TEST_ALL=true
MONITOR_ONLY=false
FAILED_ONLY=false
SPECIFIC_QUEUE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --all)
            TEST_ALL=true
            shift
            ;;
        --queue=*)
            SPECIFIC_QUEUE="${1#*=}"
            TEST_ALL=false
            shift
            ;;
        --monitor)
            MONITOR_ONLY=true
            shift
            ;;
        --failed)
            FAILED_ONLY=true
            shift
            ;;
        --help)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

# Validate specific queue if provided
if [ -n "$SPECIFIC_QUEUE" ]; then
    if [[ ! " ${QUEUES[*]} " =~ " ${SPECIFIC_QUEUE} " ]]; then
        echo -e "${RED}❌ Invalid queue: ${SPECIFIC_QUEUE}${NC}"
        echo -e "${YELLOW}Available queues: ${QUEUES[*]}${NC}"
        exit 1
    fi
fi

# Main execution
if [ "$MONITOR_ONLY" = true ]; then
    echo -e "\n${BLUE}📊 Queue Monitoring Dashboard${NC}"
    $PHP $ARTISAN queue:monitor
    exit $?
fi

if [ "$FAILED_ONLY" = true ]; then
    echo -e "\n${BLUE}💥 Failed Jobs Report${NC}"
    $PHP $ARTISAN queue:monitor --failed
    exit $?
fi

if [ "$TEST_ALL" = true ]; then
    echo -e "\n${YELLOW}🧪 Starting comprehensive queue testing...${NC}"

    # First show current status
    echo -e "\n${BLUE}📊 Current Queue Status:${NC}"
    $PHP $ARTISAN queue:monitor --recent=5

    # Test all queues
    FAILED_QUEUES=()
    SUCCESS_COUNT=0

    for queue in "${QUEUES[@]}"; do
        if test_queue "$queue"; then
            ((SUCCESS_COUNT++))
        else
            FAILED_QUEUES+=("$queue")
        fi
    done

    # Show final results
    echo -e "\n${BLUE}📊 Testing Results:${NC}"
    echo -e "${GREEN}✅ Successful: ${SUCCESS_COUNT}/${#QUEUES[@]}${NC}"

    if [ ${#FAILED_QUEUES[@]} -gt 0 ]; then
        echo -e "${RED}❌ Failed: ${FAILED_QUEUES[*]}${NC}"
        echo -e "\n${YELLOW}💡 Troubleshooting tips:${NC}"
        echo "   - Check if queue workers are running: ps aux | grep queue:work"
        echo "   - Check Redis connection: redis-cli ping"
        echo "   - Check application logs for errors"
        echo "   - Verify queue configuration in config/queue.php"
        exit 1
    else
        echo -e "${GREEN}🎉 All queues tested successfully!${NC}"

        # Show final status
        echo -e "\n${BLUE}📊 Final Queue Status:${NC}"
        $PHP $ARTISAN queue:monitor --recent=10
    fi

elif [ -n "$SPECIFIC_QUEUE" ]; then
    if test_queue "$SPECIFIC_QUEUE"; then
        echo -e "\n${GREEN}🎉 Queue '${SPECIFIC_QUEUE}' test completed successfully!${NC}"
        $PHP $ARTISAN queue:monitor --queue="$SPECIFIC_QUEUE"
    else
        echo -e "\n${RED}❌ Queue '${SPECIFIC_QUEUE}' test failed${NC}"
        exit 1
    fi
fi

echo -e "\n${BLUE}📖 For more information, see QUEUE_TESTING_README.md${NC}"

