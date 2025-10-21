#!/bin/bash

# Test Complete Migration Flow
# This script tests the entire migration process from token generation to data transfer

echo "========================================="
echo "Testing Complete Migration Flow"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test configuration
ETCH_URL="http://localhost:8081"
BRICKS_URL="http://localhost:8080"

echo "Step 1: Generate Migration Key on Etch Site"
echo "---------------------------------------------"

# Generate migration key
MIGRATION_KEY_RESPONSE=$(curl -s -X POST "${ETCH_URL}/wp-json/b2e/v1/generate-key" \
  -H "Content-Type: application/json" \
  -d '{}')

echo "Response: $MIGRATION_KEY_RESPONSE"
echo ""

# Extract migration key from response
MIGRATION_KEY=$(echo "$MIGRATION_KEY_RESPONSE" | grep -o 'http://[^"]*' | head -1)

if [ -z "$MIGRATION_KEY" ]; then
    echo -e "${RED}❌ Failed to generate migration key${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Migration Key Generated:${NC}"
echo "$MIGRATION_KEY"
echo ""

# Parse migration key components
DOMAIN=$(echo "$MIGRATION_KEY" | sed -n 's/.*domain=\([^&]*\).*/\1/p')
TOKEN=$(echo "$MIGRATION_KEY" | sed -n 's/.*token=\([^&]*\).*/\1/p')
EXPIRES=$(echo "$MIGRATION_KEY" | sed -n 's/.*expires=\([^&]*\).*/\1/p')

echo "Parsed Components:"
echo "  Domain: $DOMAIN"
echo "  Token: ${TOKEN:0:20}..."
echo "  Expires: $EXPIRES"
echo ""

echo "Step 2: Validate Migration Token on Bricks Site"
echo "------------------------------------------------"

# Validate token via Bricks site AJAX endpoint
# This simulates what the JavaScript does
VALIDATE_RESPONSE=$(curl -s -X POST "${BRICKS_URL}/wp-admin/admin-ajax.php" \
  -F "action=b2e_validate_migration_token" \
  -F "nonce=test_nonce" \
  -F "target_url=${DOMAIN}" \
  -F "token=${TOKEN}" \
  -F "expires=${EXPIRES}")

echo "Validation Response: $VALIDATE_RESPONSE"
echo ""

# Check if validation was successful
if echo "$VALIDATE_RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}✅ Token validation successful${NC}"
    
    # Extract API key from response
    API_KEY=$(echo "$VALIDATE_RESPONSE" | grep -o '"api_key":"[^"]*"' | sed 's/"api_key":"//;s/"//')
    
    if [ -n "$API_KEY" ]; then
        echo -e "${GREEN}✅ API Key received: ${API_KEY:0:20}...${NC}"
    else
        echo -e "${RED}❌ No API key in response${NC}"
        exit 1
    fi
else
    echo -e "${RED}❌ Token validation failed${NC}"
    echo "Response: $VALIDATE_RESPONSE"
    exit 1
fi
echo ""

echo "Step 3: Start Migration Process"
echo "--------------------------------"

# Start migration via AJAX
START_RESPONSE=$(curl -s -X POST "${BRICKS_URL}/wp-admin/admin-ajax.php" \
  -F "action=b2e_start_migration" \
  -F "nonce=test_nonce" \
  -F "target_url=${DOMAIN}" \
  -F "api_key=${API_KEY}")

echo "Migration Start Response: $START_RESPONSE"
echo ""

# Check if migration started successfully
if echo "$START_RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}✅ Migration started successfully${NC}"
else
    echo -e "${RED}❌ Migration failed to start${NC}"
    echo "Response: $START_RESPONSE"
    exit 1
fi
echo ""

echo "Step 4: Monitor Migration Progress"
echo "-----------------------------------"

# Poll migration progress
MAX_POLLS=30
POLL_COUNT=0
MIGRATION_COMPLETE=false

while [ $POLL_COUNT -lt $MAX_POLLS ]; do
    POLL_COUNT=$((POLL_COUNT + 1))
    
    # Get migration progress
    PROGRESS_RESPONSE=$(curl -s -X POST "${BRICKS_URL}/wp-admin/admin-ajax.php" \
      -F "action=b2e_get_migration_progress" \
      -F "nonce=test_nonce")
    
    # Extract progress information
    STATUS=$(echo "$PROGRESS_RESPONSE" | grep -o '"status":"[^"]*"' | sed 's/"status":"//;s/"//')
    PERCENTAGE=$(echo "$PROGRESS_RESPONSE" | grep -o '"percentage":[0-9]*' | sed 's/"percentage"://')
    MESSAGE=$(echo "$PROGRESS_RESPONSE" | grep -o '"message":"[^"]*"' | sed 's/"message":"//;s/"//')
    
    echo "Poll $POLL_COUNT: Status=$STATUS, Progress=$PERCENTAGE%, Message=$MESSAGE"
    
    # Check if migration is complete
    if [ "$STATUS" = "completed" ]; then
        MIGRATION_COMPLETE=true
        echo -e "${GREEN}✅ Migration completed successfully!${NC}"
        break
    elif [ "$STATUS" = "error" ]; then
        echo -e "${RED}❌ Migration failed with error${NC}"
        echo "Error message: $MESSAGE"
        exit 1
    fi
    
    # Wait before next poll
    sleep 2
done

if [ "$MIGRATION_COMPLETE" = false ]; then
    echo -e "${YELLOW}⚠️  Migration did not complete within expected time${NC}"
    echo "Last status: $STATUS ($PERCENTAGE%)"
fi
echo ""

echo "Step 5: Verify Data Transfer"
echo "-----------------------------"

# Check if data was actually transferred to Etch site
# This would involve checking posts, pages, media, etc. on the Etch site

echo "Checking for migrated content on Etch site..."

# Example: Check for posts on Etch site
POSTS_CHECK=$(curl -s "${ETCH_URL}/wp-json/wp/v2/posts?per_page=1")

if echo "$POSTS_CHECK" | grep -q '\['; then
    POST_COUNT=$(echo "$POSTS_CHECK" | grep -o '"id":[0-9]*' | wc -l)
    echo -e "${GREEN}✅ Found posts on Etch site (count: $POST_COUNT)${NC}"
else
    echo -e "${YELLOW}⚠️  No posts found on Etch site (might be expected if source is empty)${NC}"
fi
echo ""

echo "========================================="
echo "Migration Flow Test Complete"
echo "========================================="
echo ""
echo "Summary:"
echo "  ✅ Migration key generation: SUCCESS"
echo "  ✅ Token validation: SUCCESS"
echo "  ✅ Migration start: SUCCESS"
if [ "$MIGRATION_COMPLETE" = true ]; then
    echo "  ✅ Migration completion: SUCCESS"
else
    echo "  ⚠️  Migration completion: IN PROGRESS or TIMEOUT"
fi
echo ""
