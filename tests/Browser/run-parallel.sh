#!/bin/bash

# Parallel Dusk test runner
# Splits tests across multiple ChromeDriver instances

set -e

PROCESSES=${1:-4}  # Default to 4 processes
APP_URL=${APP_URL:-http://127.0.0.1:8000}

echo "Running Dusk tests in parallel with $PROCESSES processes..."

# Get all test files
TEST_FILES=($(find tests/Browser -name "*UITest.php" -type f))

# Split files into chunks
CHUNK_SIZE=$((${#TEST_FILES[@]} / $PROCESSES + 1))

# Function to run tests for a chunk
run_chunk() {
    local chunk_num=$1
    local port=$((9515 + $chunk_num))
    local start_idx=$(($chunk_num * $CHUNK_SIZE))
    local end_idx=$((($chunk_num + 1) * $CHUNK_SIZE))
    
    # Start ChromeDriver for this chunk
    chromedriver --port=$port > /dev/null 2>&1 &
    local chromedriver_pid=$!
    sleep 2
    
    # Run tests for this chunk
    for ((i=$start_idx; i<$end_idx && i<${#TEST_FILES[@]}; i++)); do
        file="${TEST_FILES[$i]}"
        echo "Process $chunk_num: Running $file"
        
        DUSK_DRIVER_URL=http://localhost:$port \
        DB_HOST=127.0.0.1 \
        DB_DATABASE=ciblerh_payroll_test_$chunk_num \
        DB_USERNAME=root \
        DB_PASSWORD=root \
        CACHE_DRIVER=array \
        SESSION_DRIVER=array \
        php artisan dusk "$file" 2>&1 | tee "dusk-process-$chunk_num.log" || true
    done
    
    # Kill ChromeDriver
    kill $chromedriver_pid 2>/dev/null || true
}

# Run chunks in parallel
for ((i=0; i<$PROCESSES; i++)); do
    run_chunk $i &
done

# Wait for all processes
wait

echo "All test processes completed!"
echo "Check dusk-process-*.log files for results"












