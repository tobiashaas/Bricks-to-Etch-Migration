#!/bin/bash

echo "🔍 CSS Migration Verification"
echo "================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}1. Bricks Classes (Source)${NC}"
echo "----------------------------"
BRICKS_CLASSES=$(docker exec b2e-bricks wp option get bricks_global_classes --format=json --allow-root 2>/dev/null)
BRICKS_COUNT=$(echo "$BRICKS_CLASSES" | jq '. | length' 2>/dev/null || echo "0")
echo -e "  Total Bricks classes: ${GREEN}${BRICKS_COUNT}${NC}"

if [ "$BRICKS_COUNT" -gt 0 ]; then
    echo ""
    echo "  Sample classes:"
    echo "$BRICKS_CLASSES" | jq -r '.[0:3] | .[] | "    - " + (.name // .id)' 2>/dev/null
fi
echo ""

echo -e "${BLUE}2. Etch Styles (Target)${NC}"
echo "----------------------------"
ETCH_STYLES=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null)
ETCH_COUNT=$(echo "$ETCH_STYLES" | jq '. | length' 2>/dev/null || echo "0")
echo -e "  Total Etch styles: ${GREEN}${ETCH_COUNT}${NC}"

if [ "$ETCH_COUNT" -gt 0 ]; then
    # Count by type
    CLASS_COUNT=$(echo "$ETCH_STYLES" | jq '[.[] | select(.type == "class")] | length' 2>/dev/null || echo "0")
    ELEMENT_COUNT=$(echo "$ETCH_STYLES" | jq '[.[] | select(.type == "element")] | length' 2>/dev/null || echo "0")
    
    echo -e "  - Classes: ${YELLOW}${CLASS_COUNT}${NC}"
    echo -e "  - Elements: ${YELLOW}${ELEMENT_COUNT}${NC}"
    
    echo ""
    echo "  Sample styles:"
    echo "$ETCH_STYLES" | jq -r 'to_entries | .[0:3] | .[] | "    - " + .value.selector + " (" + .value.type + ")"' 2>/dev/null
fi
echo ""

echo -e "${BLUE}3. Migration Status${NC}"
echo "----------------------------"
if [ "$BRICKS_COUNT" -eq 0 ]; then
    echo -e "  ${RED}⚠️  No Bricks classes found${NC}"
elif [ "$ETCH_COUNT" -eq 0 ]; then
    echo -e "  ${RED}❌ Migration failed - No Etch styles${NC}"
elif [ "$ETCH_COUNT" -lt "$BRICKS_COUNT" ]; then
    echo -e "  ${YELLOW}⚠️  Partial migration - Some styles missing${NC}"
    echo -e "     Expected: ${BRICKS_COUNT}, Got: ${ETCH_COUNT}"
else
    echo -e "  ${GREEN}✅ Migration successful!${NC}"
    echo -e "     Migrated ${ETCH_COUNT} styles"
fi
echo ""

echo -e "${BLUE}4. Recent Logs (Last 20 lines)${NC}"
echo "----------------------------"
echo ""
echo -e "${YELLOW}Bricks Container:${NC}"
docker logs b2e-bricks --tail=20 2>&1 | grep -E "B2E|CSS|Migration" || echo "  No relevant logs"
echo ""
echo -e "${YELLOW}Etch Container:${NC}"
docker logs b2e-etch --tail=20 2>&1 | grep -E "B2E|CSS|Etch|Migration" || echo "  No relevant logs"
echo ""

echo "================================"
echo "✅ Verification complete"
