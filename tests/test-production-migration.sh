#!/bin/bash

# Production-Ready Migration Test
# Automated end-to-end test of the complete migration flow

echo "========================================="
echo "🚀 Production Migration Test"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

ETCH_URL="http://localhost:8081"
BRICKS_URL="http://localhost:8080"

lookup_container() {
    for candidate in "$@"; do
        if docker ps -a --format '{{.Names}}' | grep -Fxq "$candidate"; then
            echo "$candidate"
            return 0
        fi
    done
    echo "$1"
}

BRICKS_WP=$(lookup_container efs-bricks-wp b2e-bricks-wp)
ETCH_WP=$(lookup_container efs-etch-wp b2e-etch-wp)
BRICKS_DB=$(lookup_container efs-bricks-db b2e-bricks-db)
ETCH_DB=$(lookup_container efs-etch-db b2e-etch-db)

echo -e "${CYAN}Phase 1: Pre-Migration Status${NC}"
echo "========================================"
echo ""

# Get source counts
BRICKS_POSTS=$(docker exec "$BRICKS_WP" wp post list --post_type=post --post_status=publish --format=count --allow-root 2>/dev/null)
BRICKS_PAGES=$(docker exec "$BRICKS_WP" wp post list --post_type=page --post_status=publish --format=count --allow-root 2>/dev/null)
BRICKS_MEDIA=$(docker exec "$BRICKS_WP" wp post list --post_type=attachment --format=count --allow-root 2>/dev/null)

echo -e "${BLUE}Bricks Site (Source):${NC}"
echo "  Posts: $BRICKS_POSTS"
echo "  Pages: $BRICKS_PAGES"
echo "  Media: $BRICKS_MEDIA"
echo ""

# Get target counts (should be 0)
ETCH_POSTS_BEFORE=$(docker exec "$ETCH_WP" wp post list --post_type=post --post_status=publish --format=count --allow-root 2>/dev/null)
ETCH_PAGES_BEFORE=$(docker exec "$ETCH_WP" wp post list --post_type=page --post_status=publish --format=count --allow-root 2>/dev/null)
ETCH_MEDIA_BEFORE=$(docker exec "$ETCH_WP" wp post list --post_type=attachment --format=count --allow-root 2>/dev/null)

echo -e "${BLUE}Etch Site (Target - Before):${NC}"
echo "  Posts: $ETCH_POSTS_BEFORE"
echo "  Pages: $ETCH_PAGES_BEFORE"
echo "  Media: $ETCH_MEDIA_BEFORE"
echo ""

if [ "$ETCH_POSTS_BEFORE" != "0" ] || [ "$ETCH_PAGES_BEFORE" != "0" ]; then
    echo -e "${YELLOW}⚠️  Warning: Etch site is not clean!${NC}"
    echo "Run this first: ./test-production-migration.sh --clean"
    exit 1
fi

echo -e "${CYAN}Phase 2: Generate Migration Key${NC}"
echo "========================================"
echo ""

# Generate migration key
KEY_RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/efs/v1/generate-key" \
    -H "Content-Type: application/json" \
    -d '{}')

if ! echo "$KEY_RESPONSE" | grep -q "migration_key"; then
    echo -e "${RED}❌ Failed to generate migration key${NC}"
    echo "Response: $KEY_RESPONSE"
    exit 1
fi

MIGRATION_KEY=$(echo "$KEY_RESPONSE" | grep -o '"migration_key":"[^"]*"' | sed 's/"migration_key":"//;s/"//')
TOKEN=$(echo "$KEY_RESPONSE" | grep -o '"token":"[^"]*"' | sed 's/"token":"//;s/"//')
DOMAIN=$(echo "$KEY_RESPONSE" | grep -o '"domain":"[^"]*"' | sed 's/"domain":"//;s/"//')
EXPIRES=$(echo "$KEY_RESPONSE" | grep -o '"expires":[0-9]*' | sed 's/"expires"://')

echo -e "${GREEN}✅ Migration Key Generated${NC}"
echo "  Domain: $DOMAIN"
echo "  Token: ${TOKEN:0:20}..."
echo "  Expires: $(date -r $EXPIRES '+%Y-%m-%d %H:%M:%S')"
echo ""

echo -e "${CYAN}Phase 3: Validate Token${NC}"
echo "========================================"
echo ""

# Validate token
VALIDATE_RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/efs/v1/validate" \
    -H "Content-Type: application/json" \
    -d "{\"token\":\"$TOKEN\",\"domain\":\"$DOMAIN\",\"expires\":$EXPIRES}")

if ! echo "$VALIDATE_RESPONSE" | grep -q "success"; then
    echo -e "${RED}❌ Token validation failed${NC}"
    echo "Response: $VALIDATE_RESPONSE"
    exit 1
fi

API_KEY=$(echo "$VALIDATE_RESPONSE" | grep -o '"api_key":"[^"]*"' | sed 's/"api_key":"//;s/"//')

echo -e "${GREEN}✅ Token Validated${NC}"
echo "  API Key: ${API_KEY:0:20}..."
echo ""

echo -e "${CYAN}Phase 4: Start Migration${NC}"
echo "========================================"
echo ""

# Convert domain for Docker internal communication
API_DOMAIN="$DOMAIN"
if echo "$DOMAIN" | grep -q "localhost:8081"; then
    API_DOMAIN=$(echo "$DOMAIN" | sed 's/localhost:8081/efs-etch/')
fi

# Start migration
START_RESPONSE=$(curl -s -X POST "$BRICKS_URL/wp-admin/admin-ajax.php" \
    -F "action=efs_start_migration" \
    -F "nonce=$(docker exec "$BRICKS_WP" wp nonce create efs_nonce --allow-root 2>/dev/null)" \
    -F "target_url=$API_DOMAIN" \
    -F "api_key=$API_KEY")

if echo "$START_RESPONSE" | grep -q "success"; then
    echo -e "${GREEN}✅ Migration Started${NC}"
else
    echo -e "${YELLOW}⚠️  Migration start response:${NC}"
    echo "$START_RESPONSE"
fi
echo ""

echo -e "${CYAN}Phase 5: Monitor Progress${NC}"
echo "========================================"
echo ""

# Monitor progress
MAX_POLLS=60
POLL_COUNT=0
MIGRATION_COMPLETE=false

while [ $POLL_COUNT -lt $MAX_POLLS ]; do
    POLL_COUNT=$((POLL_COUNT + 1))
    
    PROGRESS_JSON=$(docker exec "$BRICKS_WP" wp option get efs_migration_progress --format=json --allow-root 2>/dev/null)
    
    if [ -z "$PROGRESS_JSON" ] || [ "$PROGRESS_JSON" = "false" ]; then
        echo -e "${YELLOW}⏳ Waiting for migration to start... ($POLL_COUNT/$MAX_POLLS)${NC}"
        sleep 2
        continue
    fi
    
    STATUS=$(echo "$PROGRESS_JSON" | grep -o '"status":"[^"]*"' | sed 's/"status":"//;s/"//' | head -1)
    PERCENTAGE=$(echo "$PROGRESS_JSON" | grep -o '"percentage":[0-9]*' | sed 's/"percentage"://' | head -1)
    MESSAGE=$(echo "$PROGRESS_JSON" | grep -o '"message":"[^"]*"' | sed 's/"message":"//;s/"//' | head -1)
    CURRENT_STEP=$(echo "$PROGRESS_JSON" | grep -o '"current_step":"[^"]*"' | sed 's/"current_step":"//;s/"//' | head -1)
    
    TIMESTAMP=$(date '+%H:%M:%S')
    
    if [ "$STATUS" = "completed" ]; then
        echo -e "${GREEN}[$TIMESTAMP] ✅ Migration Completed! (100%)${NC}"
        MIGRATION_COMPLETE=true
        break
    elif [ "$STATUS" = "error" ]; then
        echo -e "${RED}[$TIMESTAMP] ❌ Migration Failed${NC}"
        echo -e "${RED}Error: $MESSAGE${NC}"
        exit 1
    elif [ "$STATUS" = "running" ]; then
        echo -e "${BLUE}[$TIMESTAMP] 🔄 $PERCENTAGE% - $CURRENT_STEP${NC}"
    fi
    
    sleep 2
done

if [ "$MIGRATION_COMPLETE" = false ]; then
    echo -e "${YELLOW}⚠️  Migration timeout or still in progress${NC}"
fi

echo ""
echo -e "${CYAN}Phase 6: Verify Results${NC}"
echo "========================================"
echo ""

# Get post-migration counts
ETCH_POSTS_AFTER=$(docker exec "$ETCH_WP" wp post list --post_type=post --post_status=publish --format=count --allow-root 2>/dev/null)
ETCH_PAGES_AFTER=$(docker exec "$ETCH_WP" wp post list --post_type=page --post_status=publish --format=count --allow-root 2>/dev/null)
ETCH_MEDIA_AFTER=$(docker exec "$ETCH_WP" wp post list --post_type=attachment --format=count --allow-root 2>/dev/null)

echo -e "${BLUE}Etch Site (Target - After):${NC}"
echo "  Posts: $ETCH_POSTS_AFTER"
echo "  Pages: $ETCH_PAGES_AFTER"
echo "  Media: $ETCH_MEDIA_AFTER"
echo ""

# Get migration stats
STATS_JSON=$(docker exec "$BRICKS_WP" wp option get efs_migration_stats --format=json --allow-root 2>/dev/null)

if [ -n "$STATS_JSON" ] && [ "$STATS_JSON" != "false" ]; then
    echo -e "${BLUE}Migration Statistics:${NC}"
    echo "$STATS_JSON" | python3 -m json.tool 2>/dev/null || echo "$STATS_JSON"
    echo ""
fi

# Verify counts
echo -e "${CYAN}Verification:${NC}"

if [ "$ETCH_POSTS_AFTER" -ge "$BRICKS_POSTS" ]; then
    echo -e "${GREEN}✅ Posts: $ETCH_POSTS_AFTER/$BRICKS_POSTS migrated${NC}"
else
    echo -e "${RED}❌ Posts: Only $ETCH_POSTS_AFTER/$BRICKS_POSTS migrated${NC}"
fi

if [ "$ETCH_PAGES_AFTER" -ge "$BRICKS_PAGES" ]; then
    echo -e "${GREEN}✅ Pages: $ETCH_PAGES_AFTER/$BRICKS_PAGES migrated${NC}"
else
    echo -e "${RED}❌ Pages: Only $ETCH_PAGES_AFTER/$BRICKS_PAGES migrated${NC}"
fi

if [ "$ETCH_MEDIA_AFTER" -gt 0 ]; then
    echo -e "${GREEN}✅ Media: $ETCH_MEDIA_AFTER files transferred${NC}"
else
    echo -e "${YELLOW}⚠️  Media: $ETCH_MEDIA_AFTER files (expected: $BRICKS_MEDIA)${NC}"
fi

echo ""
echo "========================================="
echo -e "${CYAN}📊 Test Summary${NC}"
echo "========================================="
echo ""

if [ "$ETCH_POSTS_AFTER" -ge "$BRICKS_POSTS" ] && [ "$ETCH_PAGES_AFTER" -ge "$BRICKS_PAGES" ]; then
    echo -e "${GREEN}✅ MIGRATION SUCCESSFUL!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Check content on Etch site: $ETCH_URL/wp-admin"
    echo "2. Verify post content is readable"
    echo "3. Check for any errors in logs"
    exit 0
else
    echo -e "${YELLOW}⚠️  MIGRATION INCOMPLETE${NC}"
    echo ""
    echo "Some content may not have been migrated."
    echo "Check logs for details."
    exit 1
fi
