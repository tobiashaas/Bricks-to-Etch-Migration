#!/bin/bash

# Prepare Test Data for Migration
# This script creates sample posts and pages on the Bricks site for testing

echo "========================================="
echo "Preparing Test Data for Migration"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

BRICKS_CONTAINER="b2e-bricks"

echo -e "${BLUE}Creating test posts on Bricks site...${NC}"

# Create 3 test posts
for i in {1..3}; do
    POST_ID=$(docker exec $BRICKS_CONTAINER wp post create \
        --post_title="Test Post $i" \
        --post_content="This is test post number $i. It contains some sample content for migration testing." \
        --post_status=publish \
        --post_type=post \
        --porcelain \
        --allow-root 2>/dev/null)
    
    if [ -n "$POST_ID" ]; then
        echo -e "${GREEN}✅ Created post: Test Post $i (ID: $POST_ID)${NC}"
        
        # Add some meta data to simulate Bricks data
        docker exec $BRICKS_CONTAINER wp post meta add $POST_ID _bricks_page_content_2 '[]' --allow-root 2>/dev/null
        docker exec $BRICKS_CONTAINER wp post meta add $POST_ID _bricks_editor_mode 'bricks' --allow-root 2>/dev/null
    else
        echo "❌ Failed to create post $i"
    fi
done

echo ""
echo -e "${BLUE}Creating test pages on Bricks site...${NC}"

# Create 2 test pages
for i in {1..2}; do
    PAGE_ID=$(docker exec $BRICKS_CONTAINER wp post create \
        --post_title="Test Page $i" \
        --post_content="This is test page number $i. It will be migrated to the Etch site." \
        --post_status=publish \
        --post_type=page \
        --porcelain \
        --allow-root 2>/dev/null)
    
    if [ -n "$PAGE_ID" ]; then
        echo -e "${GREEN}✅ Created page: Test Page $i (ID: $PAGE_ID)${NC}"
        
        # Add Bricks meta data
        docker exec $BRICKS_CONTAINER wp post meta add $PAGE_ID _bricks_page_content_2 '[]' --allow-root 2>/dev/null
        docker exec $BRICKS_CONTAINER wp post meta $PAGE_ID _bricks_editor_mode 'bricks' --allow-root 2>/dev/null
    else
        echo "❌ Failed to create page $i"
    fi
done

echo ""
echo -e "${BLUE}Checking content on Bricks site...${NC}"

# Count posts and pages
POST_COUNT=$(docker exec $BRICKS_CONTAINER wp post list --post_type=post --post_status=publish --format=count --allow-root 2>/dev/null)
PAGE_COUNT=$(docker exec $BRICKS_CONTAINER wp post list --post_type=page --post_status=publish --format=count --allow-root 2>/dev/null)

echo "Total published posts: $POST_COUNT"
echo "Total published pages: $PAGE_COUNT"

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Test data preparation complete!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Open http://localhost:8081/wp-admin in your browser (Etch site)"
echo "2. Navigate to B2E Migration > Etch Site"
echo "3. Click 'Generate Migration Key' and copy the key"
echo "4. Open http://localhost:8080/wp-admin in your browser (Bricks site)"
echo "5. Navigate to B2E Migration"
echo "6. Paste the migration key and click 'Validate Key'"
echo "7. Click 'Start Migration'"
echo "8. Run ./monitor-migration.sh in another terminal to watch progress"
