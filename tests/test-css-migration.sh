#!/bin/bash

# CSS Migration Test
# Tests if Bricks CSS classes are correctly converted and migrated to Etch

echo "========================================="
echo "🎨 CSS Migration Test"
echo "========================================="
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Step 1: Check Bricks CSS Classes${NC}"
echo "========================================"
echo ""

# Count Bricks classes
BRICKS_CLASSES=$(docker exec b2e-bricks wp option get bricks_global_classes --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; print(len(json.load(sys.stdin)))" 2>/dev/null)

echo "Bricks Global Classes: $BRICKS_CLASSES"
echo ""

if [ "$BRICKS_CLASSES" = "0" ] || [ -z "$BRICKS_CLASSES" ]; then
    echo -e "${YELLOW}⚠️  No Bricks CSS classes found${NC}"
    echo "This is normal if Bricks doesn't have global classes defined."
    exit 0
fi

# Show sample classes
echo "Sample Bricks Classes:"
docker exec b2e-bricks wp option get bricks_global_classes --format=json --allow-root 2>/dev/null | python3 -m json.tool 2>/dev/null | head -30

echo ""
echo -e "${BLUE}Step 2: Check Etch Styles (Before Migration)${NC}"
echo "=============================================="
echo ""

# Count Etch styles
ETCH_STYLES_BEFORE=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; print(len(json.load(sys.stdin)))" 2>/dev/null)

echo "Etch Styles Count (Before): $ETCH_STYLES_BEFORE"
echo ""

echo -e "${YELLOW}Now run a migration to test CSS conversion...${NC}"
echo ""
echo "After migration, run this script again to see the results."
echo ""
echo "Or check manually:"
echo "  docker exec b2e-etch wp option get etch_styles --format=json --allow-root | python3 -m json.tool"
