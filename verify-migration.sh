#!/bin/bash

# Verify Migration Results
# This script checks if data was successfully transferred from Bricks to Etch

echo "========================================="
echo "Migration Verification"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BRICKS_CONTAINER="b2e-bricks"
ETCH_CONTAINER="b2e-etch"

echo -e "${BLUE}Step 1: Checking content on Bricks site (Source)${NC}"
echo "---------------------------------------------------"

# Get counts from Bricks site
BRICKS_POSTS=$(docker exec $BRICKS_CONTAINER wp post list --post_type=post --post_status=publish --format=count --allow-root 2>/dev/null)
BRICKS_PAGES=$(docker exec $BRICKS_CONTAINER wp post list --post_type=page --post_status=publish --format=count --allow-root 2>/dev/null)
BRICKS_MEDIA=$(docker exec $BRICKS_CONTAINER wp post list --post_type=attachment --format=count --allow-root 2>/dev/null)

echo "Bricks Site (Source):"
echo "  Posts: $BRICKS_POSTS"
echo "  Pages: $BRICKS_PAGES"
echo "  Media: $BRICKS_MEDIA"
echo ""

echo -e "${BLUE}Step 2: Checking content on Etch site (Target)${NC}"
echo "------------------------------------------------"

# Get counts from Etch site
ETCH_POSTS=$(docker exec $ETCH_CONTAINER wp post list --post_type=post --post_status=publish --format=count --allow-root 2>/dev/null)
ETCH_PAGES=$(docker exec $ETCH_CONTAINER wp post list --post_type=page --post_status=publish --format=count --allow-root 2>/dev/null)
ETCH_MEDIA=$(docker exec $ETCH_CONTAINER wp post list --post_type=attachment --format=count --allow-root 2>/dev/null)

echo "Etch Site (Target):"
echo "  Posts: $ETCH_POSTS"
echo "  Pages: $ETCH_PAGES"
echo "  Media: $ETCH_MEDIA"
echo ""

echo -e "${BLUE}Step 3: Comparing counts${NC}"
echo "-------------------------"

# Compare posts
if [ "$ETCH_POSTS" -ge "$BRICKS_POSTS" ]; then
    echo -e "${GREEN}✅ Posts: Migration successful ($ETCH_POSTS >= $BRICKS_POSTS)${NC}"
else
    echo -e "${YELLOW}⚠️  Posts: Some posts may not have been migrated ($ETCH_POSTS < $BRICKS_POSTS)${NC}"
fi

# Compare pages
if [ "$ETCH_PAGES" -ge "$BRICKS_PAGES" ]; then
    echo -e "${GREEN}✅ Pages: Migration successful ($ETCH_PAGES >= $BRICKS_PAGES)${NC}"
else
    echo -e "${YELLOW}⚠️  Pages: Some pages may not have been migrated ($ETCH_PAGES < $BRICKS_PAGES)${NC}"
fi

# Compare media
if [ "$ETCH_MEDIA" -ge "$BRICKS_MEDIA" ]; then
    echo -e "${GREEN}✅ Media: Migration successful ($ETCH_MEDIA >= $BRICKS_MEDIA)${NC}"
else
    echo -e "${YELLOW}⚠️  Media: Some media may not have been migrated ($ETCH_MEDIA < $BRICKS_MEDIA)${NC}"
fi

echo ""
echo -e "${BLUE}Step 4: Checking migration metadata${NC}"
echo "------------------------------------"

# Check for migration-specific meta data on Etch site
MIGRATED_POSTS=$(docker exec $ETCH_CONTAINER wp post list --post_type=post --meta_key=_b2e_migrated --format=count --allow-root 2>/dev/null)

if [ -n "$MIGRATED_POSTS" ] && [ "$MIGRATED_POSTS" -gt 0 ]; then
    echo -e "${GREEN}✅ Found $MIGRATED_POSTS posts with migration metadata${NC}"
else
    echo -e "${YELLOW}⚠️  No posts with migration metadata found${NC}"
    echo "   (This might be expected if metadata is not being set)"
fi

echo ""
echo -e "${BLUE}Step 5: Checking recent posts on Etch site${NC}"
echo "-------------------------------------------"

# Show recent posts on Etch site
echo "Recent posts on Etch site:"
docker exec $ETCH_CONTAINER wp post list --post_type=post --posts_per_page=5 --format=table --allow-root 2>/dev/null

echo ""
echo "Recent pages on Etch site:"
docker exec $ETCH_CONTAINER wp post list --post_type=page --posts_per_page=5 --format=table --allow-root 2>/dev/null

echo ""
echo -e "${BLUE}Step 6: Checking migration status${NC}"
echo "----------------------------------"

# Get migration progress
MIGRATION_PROGRESS=$(docker exec $BRICKS_CONTAINER wp option get b2e_migration_progress --format=json --allow-root 2>/dev/null)

if [ -n "$MIGRATION_PROGRESS" ] && [ "$MIGRATION_PROGRESS" != "false" ]; then
    echo "Migration Progress:"
    echo "$MIGRATION_PROGRESS" | python3 -m json.tool 2>/dev/null || echo "$MIGRATION_PROGRESS"
else
    echo -e "${YELLOW}⚠️  No migration progress data found${NC}"
fi

echo ""

# Get migration stats
MIGRATION_STATS=$(docker exec $BRICKS_CONTAINER wp option get b2e_migration_stats --format=json --allow-root 2>/dev/null)

if [ -n "$MIGRATION_STATS" ] && [ "$MIGRATION_STATS" != "false" ]; then
    echo "Migration Statistics:"
    echo "$MIGRATION_STATS" | python3 -m json.tool 2>/dev/null || echo "$MIGRATION_STATS"
else
    echo -e "${YELLOW}⚠️  No migration statistics found${NC}"
fi

echo ""
echo -e "${BLUE}Step 7: Testing API connectivity${NC}"
echo "----------------------------------"

# Test Etch API endpoint
API_TEST=$(curl -s -X GET "http://localhost:8081/wp-json/b2e/v1/auth/test")

if echo "$API_TEST" | grep -q "success"; then
    echo -e "${GREEN}✅ Etch API is responding${NC}"
else
    echo -e "${RED}❌ Etch API is not responding correctly${NC}"
    echo "Response: $API_TEST"
fi

echo ""
echo "========================================="
echo "Verification Complete"
echo "========================================="
echo ""

# Summary
echo "Summary:"
echo "--------"
echo "Source (Bricks): $BRICKS_POSTS posts, $BRICKS_PAGES pages, $BRICKS_MEDIA media"
echo "Target (Etch):   $ETCH_POSTS posts, $ETCH_PAGES pages, $ETCH_MEDIA media"
echo ""

# Overall status
if [ "$ETCH_POSTS" -ge "$BRICKS_POSTS" ] && [ "$ETCH_PAGES" -ge "$BRICKS_PAGES" ]; then
    echo -e "${GREEN}✅ Migration appears successful!${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Migration may be incomplete or still in progress${NC}"
    exit 1
fi
