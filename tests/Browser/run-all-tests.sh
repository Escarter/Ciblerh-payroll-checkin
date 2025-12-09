#!/bin/bash

# Run all browser tests with proper environment

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}Running all browser tests...${NC}"

# Set environment
export APP_URL=http://127.0.0.1:8000
export DUSK_DRIVER_URL=http://localhost:9515
export DB_HOST=127.0.0.1
export DB_DATABASE=ciblerh_payroll_test
export DB_USERNAME=root
export DB_PASSWORD=root
export CACHE_DRIVER=array
export SESSION_DRIVER=array

# Check ChromeDriver
if ! curl -s http://localhost:9515/status > /dev/null 2>&1; then
    echo -e "${RED}ChromeDriver not running. Start it first.${NC}"
    exit 1
fi

# Check Laravel server
if ! curl -s http://127.0.0.1:8000 > /dev/null 2>&1; then
    echo -e "${YELLOW}Starting Laravel server...${NC}"
    php artisan serve --host=127.0.0.1 --port=8000 > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 3
    echo -e "${GREEN}Server started (PID: $SERVER_PID)${NC}"
    trap "kill $SERVER_PID 2>/dev/null || true" EXIT
fi

# Run tests
echo -e "${YELLOW}Executing tests...${NC}"
php artisan dusk "$@" 2>&1 | tee dusk-full-results.log

EXIT_CODE=${PIPESTATUS[0]}

if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}All tests passed!${NC}"
else
    echo -e "${RED}Some tests failed. Check dusk-full-results.log${NC}"
    # Show summary
    echo -e "\n${YELLOW}Test Summary:${NC}"
    grep -E "(PASS|FAIL|Tests:)" dusk-full-results.log | tail -5
fi

exit $EXIT_CODE












