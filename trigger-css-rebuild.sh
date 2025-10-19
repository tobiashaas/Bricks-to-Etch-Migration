#!/bin/bash

echo "🔄 Triggering Etch CSS Rebuild"
echo "================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Step 1: Checking current etch_svg_version...${NC}"
CURRENT_VERSION=$(docker exec b2e-etch wp option get etch_svg_version --allow-root 2>/dev/null | tail -1)
echo -e "  Current version: ${YELLOW}${CURRENT_VERSION}${NC}"
echo ""

echo -e "${BLUE}Step 2: Incrementing etch_svg_version...${NC}"
NEW_VERSION=$((CURRENT_VERSION + 1))
docker exec b2e-etch wp option update etch_svg_version $NEW_VERSION --allow-root 2>/dev/null
echo -e "  New version: ${GREEN}${NEW_VERSION}${NC}"
echo ""

echo -e "${BLUE}Step 3: Clearing WordPress cache...${NC}"
docker exec b2e-etch wp cache flush --allow-root 2>/dev/null
echo -e "  ${GREEN}✅ Cache cleared${NC}"
echo ""

echo -e "${BLUE}Step 4: Triggering Etch actions...${NC}"
docker exec b2e-etch wp eval "do_action('etch_styles_updated'); do_action('etch_rebuild_css'); echo 'Actions triggered';" --allow-root 2>/dev/null
echo ""

echo -e "${BLUE}Step 5: Checking if CSS file exists...${NC}"
# Check for common Etch CSS file locations
docker exec b2e-etch find /var/www/html/wp-content -name "*etch*.css" -type f 2>/dev/null | head -5
echo ""

echo "================================"
echo -e "${GREEN}✅ CSS rebuild triggered${NC}"
echo ""
echo "Next steps:"
echo "1. Open a page on Etch frontend: http://localhost:8081"
echo "2. Check browser DevTools > Network tab for CSS files"
echo "3. Check browser DevTools > Elements > <head> for inline styles"
echo "4. Hard refresh (Cmd+Shift+R / Ctrl+Shift+R)"
