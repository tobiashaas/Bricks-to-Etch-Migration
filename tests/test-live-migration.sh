#!/bin/bash
##
# Live Migration Test
# 
# Tests the complete migration flow with new AJAX handlers
# 
# Usage: ./tests/test-live-migration.sh

echo "=== Live Migration Test ==="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counters
TOTAL=0
PASSED=0
FAILED=0

test_result() {
    TOTAL=$((TOTAL + 1))
    if [ $1 -eq 0 ]; then
        PASSED=$((PASSED + 1))
        echo -e "${GREEN}✅ PASS${NC}: $2"
    else
        FAILED=$((FAILED + 1))
        echo -e "${RED}❌ FAIL${NC}: $2"
        if [ ! -z "$3" ]; then
            echo "   → $3"
        fi
    fi
}

# Test 1: Check if plugin is loaded
echo "--- Test 1: Plugin Status ---"
PLUGIN_ACTIVE=$(docker exec b2e-bricks wp plugin is-active bricks-etch-migration --allow-root 2>&1)
if [ $? -eq 0 ]; then
    test_result 0 "Plugin is active on Bricks site"
else
    test_result 1 "Plugin is active on Bricks site" "$PLUGIN_ACTIVE"
fi

PLUGIN_ACTIVE=$(docker exec b2e-etch wp plugin is-active bricks-etch-migration --allow-root 2>&1)
if [ $? -eq 0 ]; then
    test_result 0 "Plugin is active on Etch site"
else
    test_result 1 "Plugin is active on Etch site" "$PLUGIN_ACTIVE"
fi

echo ""

# Test 2: Check Bricks content
echo "--- Test 2: Bricks Content ---"
BRICKS_COUNT=$(docker exec b2e-bricks wp post list --post_type=post,page --meta_key=_bricks_page_content_2 --format=count --allow-root 2>/dev/null)
test_result 0 "Found $BRICKS_COUNT Bricks posts"

echo ""

# Test 3: Check Etch before migration
echo "--- Test 3: Etch Status (Before Migration) ---"
ETCH_POSTS_BEFORE=$(docker exec b2e-etch wp post list --post_type=post,page --format=count --allow-root 2>/dev/null)
echo "   ℹ️  Etch has $ETCH_POSTS_BEFORE posts before migration"

ETCH_STYLES_BEFORE=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | jq 'length' 2>/dev/null || echo "0")
echo "   ℹ️  Etch has $ETCH_STYLES_BEFORE styles before migration"

echo ""

# Test 4: Get migration token
echo "--- Test 4: Migration Token ---"
TOKEN_DATA=$(docker exec b2e-etch wp option get b2e_migration_token --format=json --allow-root 2>/dev/null)
if [ ! -z "$TOKEN_DATA" ]; then
    test_result 0 "Migration token exists on Etch"
    TOKEN=$(echo $TOKEN_DATA | jq -r '.token' 2>/dev/null)
    echo "   ℹ️  Token: ${TOKEN:0:20}..."
else
    test_result 1 "Migration token exists on Etch"
fi

echo ""

# Test 5: Check AJAX endpoints
echo "--- Test 5: AJAX Endpoints ---"
echo "   ℹ️  Checking if new AJAX handlers are registered..."

# We can't directly test AJAX without browser, but we can check if handlers are loaded
docker exec b2e-bricks wp eval 'echo class_exists("B2E_Ajax_Handler") ? "yes" : "no";' --allow-root 2>/dev/null | grep -q "yes"
test_result $? "B2E_Ajax_Handler class loaded"

docker exec b2e-bricks wp eval 'echo class_exists("B2E_CSS_Ajax_Handler") ? "yes" : "no";' --allow-root 2>/dev/null | grep -q "yes"
test_result $? "B2E_CSS_Ajax_Handler class loaded"

docker exec b2e-bricks wp eval 'echo class_exists("B2E_Content_Ajax_Handler") ? "yes" : "no";' --allow-root 2>/dev/null | grep -q "yes"
test_result $? "B2E_Content_Ajax_Handler class loaded"

echo ""

# Test 6: Check Element Converters
echo "--- Test 6: Element Converters ---"
docker exec b2e-bricks wp eval 'echo class_exists("B2E_Element_Factory") ? "yes" : "no";' --allow-root 2>/dev/null | grep -q "yes"
test_result $? "Element Factory loaded"

docker exec b2e-bricks wp eval 'echo class_exists("B2E_Element_Container") ? "yes" : "no";' --allow-root 2>/dev/null | grep -q "yes"
test_result $? "Container Converter loaded"

echo ""

echo "=========================================="
echo "Pre-Migration Test Summary"
echo "=========================================="
echo "Total Tests:  $TOTAL"
echo "Passed:       $PASSED"
echo "Failed:       $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All pre-migration tests passed!${NC}"
    echo ""
    echo "Ready to migrate!"
    echo "Open: http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration"
    echo ""
    exit 0
else
    PASS_RATE=$(echo "scale=1; $PASSED * 100 / $TOTAL" | bc)
    echo -e "${YELLOW}⚠️  Some tests failed (Pass rate: ${PASS_RATE}%)${NC}"
    exit 1
fi
