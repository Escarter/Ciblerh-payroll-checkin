#!/bin/bash

# Script to run tests with coverage analysis
# Usage: ./run-tests-with-coverage.sh [min-coverage-percentage]

MIN_COVERAGE=${1:-90}

echo "Running tests with coverage analysis..."
echo "Minimum coverage threshold: ${MIN_COVERAGE}%"
echo ""

# Run tests with coverage
php artisan test --coverage --min=$MIN_COVERAGE

# Check exit code
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ All tests passed with ${MIN_COVERAGE}%+ coverage!"
else
    echo ""
    echo "❌ Tests failed or coverage below ${MIN_COVERAGE}%"
    exit 1
fi









