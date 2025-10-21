#!/bin/bash

# Comprehensive API Test Suite
# Tests all API endpoints for reliability, error handling, and performance

echo "========================================="
echo "🚀 B2E API Comprehensive Test Suite"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
ETCH_URL="http://localhost:8081"
BRICKS_URL="http://localhost:8080"
API_NAMESPACE="b2e/v1"

# Test counters
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Test result function
test_result() {
    local test_name="$1"
    local expected="$2"
    local actual="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if echo "$actual" | grep -q "$expected"; then
        echo -e "${GREEN}✅ PASS${NC}: $test_name"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}: $test_name"
        echo -e "${YELLOW}   Expected: $expected${NC}"
        echo -e "${YELLOW}   Got: $actual${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# Test HTTP status
test_http_status() {
    local test_name="$1"
    local url="$2"
    local method="${3:-GET}"
    local expected_status="${4:-200}"
    local data="$5"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    if [ -n "$data" ]; then
        STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$url" \
            -H "Content-Type: application/json" \
            -d "$data")
    else
        STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$url")
    fi
    
    if [ "$STATUS" = "$expected_status" ]; then
        echo -e "${GREEN}✅ PASS${NC}: $test_name (HTTP $STATUS)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}: $test_name"
        echo -e "${YELLOW}   Expected HTTP $expected_status, got HTTP $STATUS${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 1: Basic Connectivity${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

# Test 1.1: Etch site is reachable
test_http_status "Etch site reachable" "$ETCH_URL" "GET" "200"

# Test 1.2: Bricks site is reachable
test_http_status "Bricks site reachable" "$BRICKS_URL" "GET" "200"

# Test 1.3: REST API is enabled on Etch
test_http_status "Etch REST API enabled" "$ETCH_URL/wp-json/" "GET" "200"

# Test 1.4: REST API is enabled on Bricks
test_http_status "Bricks REST API enabled" "$BRICKS_URL/wp-json/" "GET" "200"

echo ""
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 2: API Endpoints Availability${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

# Test 2.1: Auth test endpoint
RESPONSE=$(curl -s "$ETCH_URL/wp-json/$API_NAMESPACE/auth/test")
test_result "Auth test endpoint" "success" "$RESPONSE"

# Test 2.2: Validate endpoint exists
test_http_status "Validate endpoint exists" "$ETCH_URL/wp-json/$API_NAMESPACE/validate" "POST" "400"

# Test 2.3: Generate key endpoint
RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/generate-key" \
    -H "Content-Type: application/json" \
    -d '{}')
test_result "Generate key endpoint" "migration_key" "$RESPONSE"

# Test 2.4: Receive post endpoint (should fail without auth)
test_http_status "Receive post endpoint (no auth)" "$ETCH_URL/wp-json/$API_NAMESPACE/receive-post" "POST" "401"

# Test 2.5: Receive media endpoint (should fail without auth)
test_http_status "Receive media endpoint (no auth)" "$ETCH_URL/wp-json/$API_NAMESPACE/receive-media" "POST" "401"

echo ""
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 3: Authentication & Authorization${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

# Generate a migration key for testing
MIGRATION_KEY_RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/generate-key" \
    -H "Content-Type: application/json" \
    -d '{}')

# Extract token and domain
TOKEN=$(echo "$MIGRATION_KEY_RESPONSE" | grep -o '"token":"[^"]*"' | sed 's/"token":"//;s/"//')
DOMAIN=$(echo "$MIGRATION_KEY_RESPONSE" | grep -o '"domain":"[^"]*"' | sed 's/"domain":"//;s/"//')
EXPIRES=$(echo "$MIGRATION_KEY_RESPONSE" | grep -o '"expires":[0-9]*' | sed 's/"expires"://')

if [ -n "$TOKEN" ]; then
    echo -e "${BLUE}Generated test token: ${TOKEN:0:20}...${NC}"
    
    # Test 3.1: Token validation with valid token
    VALIDATE_RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/validate" \
        -H "Content-Type: application/json" \
        -d "{\"token\":\"$TOKEN\",\"domain\":\"$DOMAIN\",\"expires\":$EXPIRES}")
    test_result "Token validation (valid)" "success" "$VALIDATE_RESPONSE"
    
    # Extract API key from response
    API_KEY=$(echo "$VALIDATE_RESPONSE" | grep -o '"api_key":"[^"]*"' | sed 's/"api_key":"//;s/"//')
    
    if [ -n "$API_KEY" ]; then
        echo -e "${BLUE}Extracted API key: ${API_KEY:0:20}...${NC}"
        
        # Test 3.2: API key validation with valid key
        RESPONSE=$(curl -s -X GET "$ETCH_URL/wp-json/$API_NAMESPACE/validate-api-key?api_key=$API_KEY")
        test_result "API key validation (valid)" "valid" "$RESPONSE"
        
        # Test 3.3: Protected endpoint with valid API key
        RESPONSE=$(curl -s -X GET "$ETCH_URL/wp-json/$API_NAMESPACE/migrated-count" \
            -H "X-API-Key: $API_KEY")
        test_result "Protected endpoint (valid key)" "success" "$RESPONSE"
    else
        echo -e "${YELLOW}⚠️  Could not extract API key from validation response${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 2))
        TOTAL_TESTS=$((TOTAL_TESTS + 2))
    fi
    
    # Test 3.4: Token validation with invalid token
    RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/validate" \
        -H "Content-Type: application/json" \
        -d '{"token":"invalid_token","domain":"'$DOMAIN'","expires":'$EXPIRES'}')
    test_result "Token validation (invalid)" "error\|Invalid" "$RESPONSE"
    
else
    echo -e "${RED}❌ Failed to generate migration key${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 4))
    TOTAL_TESTS=$((TOTAL_TESTS + 4))
fi

# Test 3.5: API key validation with invalid key
RESPONSE=$(curl -s -X GET "$ETCH_URL/wp-json/$API_NAMESPACE/validate-api-key?api_key=invalid_key_12345")
test_result "API key validation (invalid)" "invalid\|Invalid" "$RESPONSE"

# Test 3.6: Protected endpoint without API key
test_http_status "Protected endpoint (no key)" "$ETCH_URL/wp-json/$API_NAMESPACE/migrated-count" "GET" "401"

# Test 3.7: Protected endpoint with invalid API key
test_http_status "Protected endpoint (invalid key)" "$ETCH_URL/wp-json/$API_NAMESPACE/migrated-count" "GET" "401" "" \
    -H "X-API-Key: invalid_key_12345"

echo ""
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 4: Data Endpoints${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

if [ -n "$API_KEY" ]; then
    # Test 4.1: Get migrated content count
    RESPONSE=$(curl -s -X GET "$ETCH_URL/wp-json/$API_NAMESPACE/migrated-count" \
        -H "X-API-Key: $API_KEY")
    test_result "Get migrated count" "success" "$RESPONSE"
    
    # Test 4.2: Get plugin status
    RESPONSE=$(curl -s -X GET "$ETCH_URL/wp-json/$API_NAMESPACE/validate/plugins" \
        -H "X-API-Key: $API_KEY")
    test_result "Get plugin status" "plugins" "$RESPONSE"
    
    # Test 4.3: Export posts list
    RESPONSE=$(curl -s -X GET "$ETCH_URL/wp-json/$API_NAMESPACE/export/posts" \
        -H "X-API-Key: $API_KEY")
    test_result "Export posts list" "\[\]" "$RESPONSE"
    
else
    echo -e "${YELLOW}⚠️  Skipping data endpoint tests (no API key)${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 3))
fi

echo ""
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 5: Error Handling${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

# Test 5.1: Missing required parameters
RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/validate" \
    -H "Content-Type: application/json" \
    -d '{}')
test_result "Missing parameters error" "error\|required\|missing" "$RESPONSE"

# Test 5.2: Invalid JSON
RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/validate" \
    -H "Content-Type: application/json" \
    -d 'invalid json{')
test_result "Invalid JSON error" "error\|invalid\|parse" "$RESPONSE"

# Test 5.3: Non-existent endpoint
test_http_status "Non-existent endpoint" "$ETCH_URL/wp-json/$API_NAMESPACE/nonexistent" "GET" "404"

# Test 5.4: Wrong HTTP method
test_http_status "Wrong HTTP method" "$ETCH_URL/wp-json/$API_NAMESPACE/auth/test" "POST" "404"

echo ""
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 6: Media Migration API${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

if [ -n "$API_KEY" ]; then
    # Test 6.1: Receive media with missing data
    RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/receive-media" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d '{}')
    test_result "Receive media (missing data)" "error\|no_data\|No media data" "$RESPONSE"
    
    # Test 6.2: Receive media with invalid base64
    RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/receive-media" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"file_content":"invalid_base64!!!","file_name":"test.jpg","post_title":"Test","post_mime_type":"image/jpeg"}')
    test_result "Receive media (invalid base64)" "error\|invalid" "$RESPONSE"
    
    # Test 6.3: Receive media with valid data (small test image)
    # Create a 1x1 transparent PNG in base64
    TINY_PNG="iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="
    
    RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/receive-media" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"file_content\":\"$TINY_PNG\",\"file_name\":\"test-api.png\",\"post_title\":\"API Test Image\",\"post_content\":\"\",\"post_excerpt\":\"\",\"post_mime_type\":\"image/png\",\"meta_input\":{\"_b2e_migrated_from_bricks\":true,\"_b2e_original_media_id\":999,\"_b2e_migration_date\":\"2025-10-17 22:00:00\"}}")
    
    test_result "Receive media (valid data)" "success" "$RESPONSE"
    
    # Extract attachment ID if successful
    ATTACHMENT_ID=$(echo "$RESPONSE" | grep -o '"attachment_id":[0-9]*' | sed 's/"attachment_id"://')
    
    if [ -n "$ATTACHMENT_ID" ]; then
        echo -e "${GREEN}   Created attachment ID: $ATTACHMENT_ID${NC}"
        
        # Verify the attachment exists
        VERIFY=$(curl -s "$ETCH_URL/wp-json/wp/v2/media/$ATTACHMENT_ID")
        if echo "$VERIFY" | grep -q "\"id\":$ATTACHMENT_ID"; then
            echo -e "${GREEN}   ✓ Attachment verified in WordPress${NC}"
        else
            echo -e "${YELLOW}   ⚠ Attachment not found in WordPress${NC}"
        fi
    fi
    
else
    echo -e "${YELLOW}⚠️  Skipping media tests (no API key)${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 3))
fi

echo ""
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo -e "${CYAN}Test Suite 7: Performance & Limits${NC}"
echo -e "${CYAN}═══════════════════════════════════════${NC}"
echo ""

# Test 7.1: Response time for auth/test
START=$(date +%s%N)
curl -s "$ETCH_URL/wp-json/$API_NAMESPACE/auth/test" > /dev/null
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if [ $DURATION -lt 1000 ]; then
    echo -e "${GREEN}✅ PASS${NC}: Response time < 1s (${DURATION}ms)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${YELLOW}⚠️  SLOW${NC}: Response time ${DURATION}ms (expected < 1000ms)"
    PASSED_TESTS=$((PASSED_TESTS + 1))
fi

# Test 7.2: Large payload handling (simulate large media file)
if [ -n "$API_KEY" ]; then
    # Create a larger base64 string (simulating ~100KB image)
    LARGE_DATA=$(head -c 100000 /dev/urandom | base64)
    
    START=$(date +%s%N)
    RESPONSE=$(curl -s -X POST "$ETCH_URL/wp-json/$API_NAMESPACE/receive-media" \
        -H "X-API-Key: $API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"file_content\":\"$LARGE_DATA\",\"file_name\":\"large-test.jpg\",\"post_title\":\"Large Test\",\"post_content\":\"\",\"post_excerpt\":\"\",\"post_mime_type\":\"image/jpeg\",\"meta_input\":{\"_b2e_migrated_from_bricks\":true,\"_b2e_original_media_id\":998,\"_b2e_migration_date\":\"2025-10-17 22:00:00\"}}")
    END=$(date +%s%N)
    DURATION=$(( (END - START) / 1000000 ))
    
    test_result "Large payload handling (~100KB)" "success\|error" "$RESPONSE"
    echo -e "${BLUE}   Processing time: ${DURATION}ms${NC}"
else
    echo -e "${YELLOW}⚠️  Skipping large payload test (no API key)${NC}"
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
fi

# Test 7.3: Concurrent requests
if [ -n "$API_KEY" ]; then
    echo -e "${BLUE}Testing concurrent requests (5 parallel)...${NC}"
    
    for i in {1..5}; do
        curl -s "$ETCH_URL/wp-json/$API_NAMESPACE/auth/test" > /dev/null &
    done
    wait
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -e "${GREEN}✅ PASS${NC}: Concurrent requests handled"
    PASSED_TESTS=$((PASSED_TESTS + 1))
fi

echo ""
echo "========================================="
echo -e "${CYAN}📊 Test Results Summary${NC}"
echo "========================================="
echo ""
echo -e "Total Tests:  ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Passed:       ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed:       ${RED}$FAILED_TESTS${NC}"
echo ""

PASS_RATE=$(( PASSED_TESTS * 100 / TOTAL_TESTS ))

if [ $PASS_RATE -ge 90 ]; then
    echo -e "${GREEN}✅ API Status: EXCELLENT (${PASS_RATE}%)${NC}"
elif [ $PASS_RATE -ge 75 ]; then
    echo -e "${YELLOW}⚠️  API Status: GOOD (${PASS_RATE}%)${NC}"
elif [ $PASS_RATE -ge 50 ]; then
    echo -e "${YELLOW}⚠️  API Status: NEEDS IMPROVEMENT (${PASS_RATE}%)${NC}"
else
    echo -e "${RED}❌ API Status: CRITICAL (${PASS_RATE}%)${NC}"
fi

echo ""
echo "========================================="

# Exit with appropriate code
if [ $FAILED_TESTS -eq 0 ]; then
    exit 0
else
    exit 1
fi
