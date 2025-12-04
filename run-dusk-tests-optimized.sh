#!/bin/bash

# Optimized Dusk test runner
# This script runs Dusk tests with proper environment setup and collects results

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting Dusk tests with optimized configuration...${NC}"

# Set environment variables
export APP_URL=http://127.0.0.1:8000
export DUSK_DRIVER_URL=http://localhost:9515
export DB_HOST=127.0.0.1
export DB_DATABASE=ciblerh_payroll_test
export DB_USERNAME=root
export DB_PASSWORD=root
export CACHE_DRIVER=array
export SESSION_DRIVER=array

# Check if ChromeDriver is running
if ! curl -s http://localhost:9515/status > /dev/null 2>&1; then
    echo -e "${RED}ChromeDriver is not running. Please start it first.${NC}"
    exit 1
fi

# Check if Laravel server is running
if ! curl -s http://127.0.0.1:8000 > /dev/null 2>&1; then
    echo -e "${YELLOW}Laravel server is not running. Starting it in background...${NC}"
    php artisan serve --host=127.0.0.1 --port=8000 > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 3
    echo -e "${GREEN}Server started (PID: $SERVER_PID)${NC}"
fi

# Run tests
echo -e "${YELLOW}Running Dusk tests...${NC}"
php artisan dusk "$@" 2>&1 | tee dusk-results.log

# Check exit code
EXIT_CODE=${PIPESTATUS[0]}

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
else
    echo -e "${RED}Some tests failed. Check dusk-results.log for details.${NC}"
fi

# Cleanup if we started the server
if [ ! -z "$SERVER_PID" ]; then
    kill $SERVER_PID 2>/dev/null || true
fi

exit $EXIT_CODE






