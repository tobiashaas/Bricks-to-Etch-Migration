#!/bin/bash

echo "🎨 CSS Migration Debug Test"
echo "================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Step 1: Checking Bricks classes...${NC}"
BRICKS_CLASSES=$(docker exec b2e-bricks wp option get bricks_global_classes --format=json --allow-root 2>/dev/null)
BRICKS_COUNT=$(echo "$BRICKS_CLASSES" | jq '. | length' 2>/dev/null || echo "0")
echo -e "  Found ${GREEN}${BRICKS_COUNT}${NC} Bricks classes"
echo ""

echo -e "${BLUE}Step 2: Checking current Etch styles...${NC}"
ETCH_STYLES_BEFORE=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null)
ETCH_COUNT_BEFORE=$(echo "$ETCH_STYLES_BEFORE" | jq '. | length' 2>/dev/null || echo "0")
echo -e "  Current Etch styles: ${YELLOW}${ETCH_COUNT_BEFORE}${NC}"
echo ""

echo -e "${BLUE}Step 3: Starting log monitoring...${NC}"
echo -e "  ${YELLOW}Open browser and start CSS migration now!${NC}"
echo -e "  Bricks: http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration"
echo -e "  Etch: http://localhost:8081/wp-admin/admin.php?page=bricks-etch-migration"
echo ""
echo -e "${BLUE}Monitoring logs (Ctrl+C to stop)...${NC}"
echo "================================"
echo ""

# Monitor both containers' logs in real-time
docker logs -f b2e-bricks 2>&1 | grep --line-buffered "B2E\|CSS" &
BRICKS_PID=$!

docker logs -f b2e-etch 2>&1 | grep --line-buffered "B2E\|CSS\|Etch" &
ETCH_PID=$!

# Wait for user to stop
trap "kill $BRICKS_PID $ETCH_PID 2>/dev/null; exit" INT TERM

wait
