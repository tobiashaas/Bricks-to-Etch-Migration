#!/bin/bash

# Update Plugin in Docker Containers
# Syncs plugin changes to both containers

echo "========================================="
echo "Updating Plugin in Containers"
echo "========================================="
echo ""

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

PLUGIN_SOURCE="/Users/tobiashaas/bricks-etch-migration/bricks-etch-migration"
BRICKS_CONTAINER="b2e-bricks"
ETCH_CONTAINER="b2e-etch"

echo -e "${BLUE}Copying plugin files to containers...${NC}"

# Copy to Bricks container
docker cp "$PLUGIN_SOURCE" "$BRICKS_CONTAINER:/var/www/html/wp-content/plugins/"
echo -e "${GREEN}✅ Copied to Bricks container${NC}"

# Copy to Etch container
docker cp "$PLUGIN_SOURCE" "$ETCH_CONTAINER:/var/www/html/wp-content/plugins/"
echo -e "${GREEN}✅ Copied to Etch container${NC}"

echo ""
echo -e "${BLUE}Flushing WordPress cache...${NC}"

# Flush cache on both sites
docker exec "$BRICKS_CONTAINER" wp cache flush --allow-root 2>/dev/null
docker exec "$ETCH_CONTAINER" wp cache flush --allow-root 2>/dev/null

echo -e "${GREEN}✅ Cache flushed${NC}"

echo ""
echo "========================================="
echo "Plugin Update Complete"
echo "========================================="
echo ""
echo "Changes are now live in both containers."
echo "You can test the updated plugin immediately."
