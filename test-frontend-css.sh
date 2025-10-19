#!/bin/bash

echo "🎨 Testing Frontend CSS Rendering"
echo "================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Step 1: Checking etch_global_stylesheets (Frontend)...${NC}"
GLOBAL_COUNT=$(docker exec b2e-etch wp option get etch_global_stylesheets --format=json --allow-root 2>/dev/null | jq '. | length' 2>/dev/null || echo "0")
echo -e "  Global stylesheets: ${YELLOW}${GLOBAL_COUNT}${NC}"
echo ""

echo -e "${BLUE}Step 2: Checking etch_styles (Editor)...${NC}"
STYLES_COUNT=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | jq '. | length' 2>/dev/null || echo "0")
echo -e "  Editor styles: ${YELLOW}${STYLES_COUNT}${NC}"
echo ""

echo -e "${BLUE}Step 3: Sample global stylesheet...${NC}"
docker exec b2e-etch wp option get etch_global_stylesheets --format=json --allow-root 2>/dev/null | jq '.[0:2]' 2>/dev/null || echo "  No stylesheets found"
echo ""

echo -e "${BLUE}Step 4: Checking if styles are rendered in frontend...${NC}"
echo -e "  Testing homepage HTML output..."
HOMEPAGE_HTML=$(docker exec b2e-etch wp eval 'ob_start(); wp_head(); $output = ob_get_clean(); echo $output;' --url=http://localhost:8081 --allow-root 2>/dev/null)

if echo "$HOMEPAGE_HTML" | grep -q "etch_global_stylesheets"; then
    echo -e "  ${GREEN}✅ Global stylesheets found in HTML${NC}"
else
    echo -e "  ${RED}❌ Global stylesheets NOT found in HTML${NC}"
fi

STYLE_COUNT=$(echo "$HOMEPAGE_HTML" | grep -c "<style" || echo "0")
echo -e "  Found ${YELLOW}${STYLE_COUNT}${NC} <style> tags in wp_head"
echo ""

echo "================================"
if [ "$GLOBAL_COUNT" -gt 1 ]; then
    echo -e "${GREEN}✅ Styles are saved to etch_global_stylesheets${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Open http://localhost:8081 in browser"
    echo "2. View Page Source (Cmd+U / Ctrl+U)"
    echo "3. Search for your class names (e.g. 'fr-feature-section-sierra')"
    echo "4. Check DevTools > Elements > <head> for <style> tags"
else
    echo -e "${RED}⚠️  No global stylesheets found - run CSS migration first${NC}"
fi
