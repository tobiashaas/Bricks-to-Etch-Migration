#!/bin/bash
##
# Post-Migration Test
# 
# Tests the results after migration
# 
# Usage: ./tests/test-post-migration.sh

echo "=== Post-Migration Test ==="
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

# Test 1: Check Etch posts after migration
echo "--- Test 1: Etch Content (After Migration) ---"
ETCH_POSTS_AFTER=$(docker exec b2e-etch wp post list --post_type=post,page --format=count --allow-root 2>/dev/null)
echo "   ℹ️  Etch has $ETCH_POSTS_AFTER posts after migration"

if [ $ETCH_POSTS_AFTER -gt 21 ]; then
    test_result 0 "New posts were created ($ETCH_POSTS_AFTER > 21)"
else
    test_result 1 "New posts were created" "Expected more than 21, got $ETCH_POSTS_AFTER"
fi

echo ""

# Test 2: Check Etch styles
echo "--- Test 2: Etch Styles ---"
ETCH_STYLES_AFTER=$(docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | jq 'length' 2>/dev/null || echo "0")
echo "   ℹ️  Etch has $ETCH_STYLES_AFTER styles after migration"

if [ $ETCH_STYLES_AFTER -gt 1140 ]; then
    test_result 0 "New styles were created ($ETCH_STYLES_AFTER > 1140)"
else
    test_result 1 "New styles were created" "Expected more than 1140, got $ETCH_STYLES_AFTER"
fi

echo ""

# Test 3: Check specific migrated post
echo "--- Test 3: Feature Section Sierra ---"
POST_ID=$(docker exec b2e-etch wp post list --name=feature-section-sierra --format=ids --allow-root 2>/dev/null)

if [ ! -z "$POST_ID" ]; then
    test_result 0 "Feature Section Sierra exists (ID: $POST_ID)"
    
    # Check post content
    CONTENT=$(docker exec b2e-etch wp post get $POST_ID --field=post_content --allow-root 2>/dev/null)
    
    # Check for ul tag
    echo "$CONTENT" | grep -q '"tagName":"ul"'
    test_result $? "Post contains tagName:ul (lists)"
    
    # Check for li tag
    echo "$CONTENT" | grep -q '"tagName":"li"'
    test_result $? "Post contains tagName:li (list items)"
    
    # Check for section tag
    echo "$CONTENT" | grep -q '"tagName":"section"'
    test_result $? "Post contains tagName:section"
    
    # Check for v0.5.0 marker
    echo "$CONTENT" | grep -q "v0.5.0"
    test_result $? "Post has v0.5.0 marker (new converters used)"
    
else
    test_result 1 "Feature Section Sierra exists"
fi

echo ""

# Test 4: Check style map
echo "--- Test 4: Style Map ---"
STYLE_MAP=$(docker exec b2e-etch wp option get b2e_style_map --format=json --allow-root 2>/dev/null)

if [ ! -z "$STYLE_MAP" ] && [ "$STYLE_MAP" != "false" ]; then
    STYLE_MAP_COUNT=$(echo "$STYLE_MAP" | jq 'length' 2>/dev/null || echo "0")
    test_result 0 "Style map exists with $STYLE_MAP_COUNT entries"
else
    test_result 1 "Style map exists"
fi

echo ""

# Test 5: Check frontend rendering
echo "--- Test 5: Frontend Rendering ---"
echo "   ℹ️  Checking http://b2e-etch/feature-section-sierra/"

# Get the HTML
HTML=$(docker exec b2e-etch curl -s http://b2e-etch/feature-section-sierra/ 2>/dev/null)

if [ ! -z "$HTML" ]; then
    # Check for <ul> tag in HTML
    echo "$HTML" | grep -q '<ul'
    test_result $? "Frontend contains <ul> tag"
    
    # Check for <li> tag in HTML
    echo "$HTML" | grep -q '<li'
    test_result $? "Frontend contains <li> tag"
    
    # Check for <section> tag in HTML
    echo "$HTML" | grep -q '<section'
    test_result $? "Frontend contains <section> tag"
    
    # Check for CSS classes
    echo "$HTML" | grep -q 'class="'
    test_result $? "Frontend has CSS classes"
else
    test_result 1 "Frontend is accessible"
fi

echo ""

echo "=========================================="
echo "Post-Migration Test Summary"
echo "=========================================="
echo "Total Tests:  $TOTAL"
echo "Passed:       $PASSED"
echo "Failed:       $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All post-migration tests passed!${NC}"
    echo ""
    echo "Migration successful! 🎉"
    echo "Check: http://localhost:8081/feature-section-sierra/"
    echo ""
    exit 0
else
    PASS_RATE=$(echo "scale=1; $PASSED * 100 / $TOTAL" | bc)
    echo -e "${YELLOW}⚠️  Some tests failed (Pass rate: ${PASS_RATE}%)${NC}"
    exit 1
fi
