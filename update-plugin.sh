#!/bin/bash

# ⚠️  DEPRECATED: This script is no longer needed!
# The plugin is now mounted as a volume in docker-compose.yml
# Changes are automatically reflected in the containers.
#
# If you need to flush cache, use:
# docker exec b2e-bricks wp cache flush --allow-root
# docker exec b2e-etch wp cache flush --allow-root

echo "========================================="
echo "⚠️  DEPRECATED SCRIPT"
echo "========================================="
echo ""
echo "This script is no longer needed!"
echo "The plugin is now mounted as a Docker volume."
echo ""
echo "Changes to the plugin are automatically"
echo "reflected in both containers."
echo ""
echo "To flush cache manually, run:"
echo "  docker exec b2e-bricks wp cache flush --allow-root"
echo "  docker exec b2e-etch wp cache flush --allow-root"
echo ""
echo "========================================="
echo ""

# Still flush cache for convenience
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Flushing WordPress cache...${NC}"
docker exec b2e-bricks wp cache flush --allow-root 2>/dev/null
docker exec b2e-etch wp cache flush --allow-root 2>/dev/null
echo -e "${GREEN}✅ Cache flushed${NC}"
