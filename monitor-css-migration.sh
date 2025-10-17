#!/bin/bash

# CSS Migration Monitor
# Monitors CSS styles before and after migration

echo "========================================="
echo "🎨 CSS Migration Monitor"
echo "========================================="
echo ""

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}Before Migration:${NC}"
echo "=================="
echo ""

# Bricks classes
BRICKS_COUNT=$(docker exec b2e-bricks wp option get bricks_global_classes --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; print(len(json.load(sys.stdin)))" 2>/dev/null)
echo "Bricks Global Classes: $BRICKS_COUNT"

# Etch styles before
ETCH_BEFORE=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; data=json.load(sys.stdin); print(len(data)) if data else print(0)" 2>/dev/null)
echo "Etch Styles (Before): ${ETCH_BEFORE:-0}"

echo ""
echo -e "${YELLOW}Waiting for migration to complete...${NC}"
echo "Run the migration in the browser, then press Enter to check results."
read -p ""

echo ""
echo -e "${BLUE}After Migration:${NC}"
echo "================="
echo ""

# Etch styles after
ETCH_AFTER=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; data=json.load(sys.stdin); print(len(data)) if data else print(0)" 2>/dev/null)
echo "Etch Styles (After): ${ETCH_AFTER:-0}"

if [ "${ETCH_AFTER:-0}" -gt "${ETCH_BEFORE:-0}" ]; then
    ADDED=$((ETCH_AFTER - ETCH_BEFORE))
    echo -e "${GREEN}✅ Added $ADDED styles!${NC}"
else
    echo -e "${YELLOW}⚠️  No new styles added${NC}"
fi

echo ""
echo "Sample Etch Styles:"
echo "==================="
docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -m json.tool 2>/dev/null | head -80

echo ""
echo "========================================="
echo "CSS Migration Check Complete"
echo "========================================="
