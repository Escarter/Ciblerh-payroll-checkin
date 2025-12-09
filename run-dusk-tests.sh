#!/bin/bash

# Dusk Test Runner Script
# This script sets up and runs Laravel Dusk tests

set -e

echo "🚀 Starting Dusk Test Setup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if ChromeDriver is installed
check_chromedriver() {
    if command -v chromedriver &> /dev/null; then
        echo -e "${GREEN}✓ ChromeDriver found: $(chromedriver --version)${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠ ChromeDriver not found. Attempting to install...${NC}"
        return 1
    fi
}

# Install ChromeDriver
install_chromedriver() {
    echo "📦 Installing ChromeDriver..."
    
    # Try Laravel Dusk's installer first
    if php artisan dusk:chrome-driver 2>/dev/null; then
        echo -e "${GREEN}✓ ChromeDriver installed via Laravel Dusk${NC}"
        return 0
    fi
    
    # Try Homebrew on macOS
    if [[ "$OSTYPE" == "darwin"* ]]; then
        if command -v brew &> /dev/null; then
            echo "📦 Installing ChromeDriver via Homebrew..."
            brew install chromedriver || brew upgrade chromedriver
            echo -e "${GREEN}✓ ChromeDriver installed via Homebrew${NC}"
            return 0
        fi
    fi
    
    echo -e "${RED}✗ Failed to install ChromeDriver automatically${NC}"
    echo "Please install ChromeDriver manually:"
    echo "  macOS: brew install chromedriver"
    echo "  Linux: Download from https://chromedriver.chromium.org/"
    return 1
}

# Setup environment
setup_environment() {
    echo "⚙️  Setting up environment..."
    
    if [ ! -f .env ]; then
        echo "📝 Creating .env file..."
        cp .env.example .env
        php artisan key:generate
    fi
    
    # Ensure testing database exists
    php artisan migrate --env=testing --force || true
    
    echo -e "${GREEN}✓ Environment setup complete${NC}"
}

# Start ChromeDriver
start_chromedriver() {
    echo "🔧 Starting ChromeDriver..."
    
    # Kill any existing ChromeDriver processes
    pkill -f chromedriver || true
    
    # Start ChromeDriver in background
    chromedriver --port=9515 > /dev/null 2>&1 &
    CHROMEDRIVER_PID=$!
    
    # Wait for ChromeDriver to start
    sleep 2
    
    if ps -p $CHROMEDRIVER_PID > /dev/null; then
        echo -e "${GREEN}✓ ChromeDriver started (PID: $CHROMEDRIVER_PID)${NC}"
        echo $CHROMEDRIVER_PID > .chromedriver.pid
        return 0
    else
        echo -e "${RED}✗ Failed to start ChromeDriver${NC}"
        return 1
    fi
}

# Start Laravel server
start_server() {
    echo "🌐 Starting Laravel server..."
    
    # Kill any existing server on port 8000
    lsof -ti:8000 | xargs kill -9 2>/dev/null || true
    
    # Start server in background
    php artisan serve --host=127.0.0.1 --port=8000 > /dev/null 2>&1 &
    SERVER_PID=$!
    
    # Wait for server to start
    echo "⏳ Waiting for server to start..."
    for i in {1..30}; do
        if curl -s http://127.0.0.1:8000 > /dev/null 2>&1; then
            echo -e "${GREEN}✓ Server started (PID: $SERVER_PID)${NC}"
            echo $SERVER_PID > .server.pid
            return 0
        fi
        sleep 1
    done
    
    echo -e "${RED}✗ Server failed to start${NC}"
    return 1
}

# Cleanup function
cleanup() {
    echo ""
    echo "🧹 Cleaning up..."
    
    # Kill ChromeDriver
    if [ -f .chromedriver.pid ]; then
        CHROMEDRIVER_PID=$(cat .chromedriver.pid)
        kill $CHROMEDRIVER_PID 2>/dev/null || true
        rm .chromedriver.pid
        echo "✓ ChromeDriver stopped"
    fi
    
    # Kill server
    if [ -f .server.pid ]; then
        SERVER_PID=$(cat .server.pid)
        kill $SERVER_PID 2>/dev/null || true
        rm .server.pid
        echo "✓ Server stopped"
    fi
    
    # Kill any remaining processes
    pkill -f chromedriver || true
    lsof -ti:8000 | xargs kill -9 2>/dev/null || true
}

# Trap cleanup on exit
trap cleanup EXIT INT TERM

# Main execution
main() {
    # Check ChromeDriver
    if ! check_chromedriver; then
        if ! install_chromedriver; then
            exit 1
        fi
    fi
    
    # Setup environment
    setup_environment
    
    # Start ChromeDriver
    if ! start_chromedriver; then
        exit 1
    fi
    
    # Start server
    if ! start_server; then
        exit 1
    fi
    
    # Run tests
    echo ""
    echo "🧪 Running Dusk tests..."
    echo ""
    
    # Check for specific test file argument
    if [ -n "$1" ]; then
        php artisan dusk "$1"
    else
        php artisan dusk
    fi
    
    TEST_EXIT_CODE=$?
    
    if [ $TEST_EXIT_CODE -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ All tests passed!${NC}"
    else
        echo ""
        echo -e "${RED}✗ Some tests failed${NC}"
        echo "Check screenshots in tests/Browser/screenshots/"
    fi
    
    exit $TEST_EXIT_CODE
}

# Run main function
main "$@"












