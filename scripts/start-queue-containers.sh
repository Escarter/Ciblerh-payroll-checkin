#!/bin/bash

# Start only queue worker containers
# Usage: ./start-queue-containers.sh [scale-factor]

set -e

SCALE_FACTOR="${1:-1}"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Starting Laravel Queue Workers...${NC}"
echo "Scale factor: ${SCALE_FACTOR}x"

# Start workers with scaling
docker-compose up -d \
    --scale queue-high-priority=$((2 * SCALE_FACTOR)) \
    --scale queue-emails=$((5 * SCALE_FACTOR)) \
    --scale queue-processing=$((3 * SCALE_FACTOR)) \
    --scale queue-pdf-processing=$((2 * SCALE_FACTOR)) \
    --scale queue-default=$((2 * SCALE_FACTOR)) \
    queue-high-priority queue-emails queue-processing queue-pdf-processing queue-default

echo -e "${GREEN}Queue workers started successfully!${NC}"
echo ""
echo "Monitor with: docker-compose logs -f queue-emails"
echo "Or use: ./scripts/manage-docker-queues.sh status"