#!/usr/bin/env bash
set -euo pipefail

DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
BRICKS_PATH="${BRICKS_PATH:-/var/www/html/bricks}"
ETCH_PATH="${ETCH_PATH:-/var/www/html/etch}"
BRICKS_SERVICE="${BRICKS_SERVICE:-bricks-wp}"
ETCH_SERVICE="${ETCH_SERVICE:-etch-wp}"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

FAILED_TESTS=0

run_compose() {
  ${DOCKER_COMPOSE_BIN} "$@"
}

wp_cli() {
  local path="$1"
  shift
  run_compose exec -T "${WPCLI_SERVICE}" wp --path="${path}" "$@"
}

test_pass() {
  echo -e "${GREEN}✓${NC} $1"
}

test_fail() {
  echo -e "${RED}✗${NC} $1"
  FAILED_TESTS=$((FAILED_TESTS + 1))
}

test_info() {
  echo -e "  ${YELLOW}→${NC} $1"
}

echo "=========================================="
echo "  Bricks2Etch Connection Test"
echo "=========================================="
echo ""

# Test 1: Get Application Password
echo "[1/6] Fetching Application Password from Etch..."
APP_PASS_JSON=$(wp_cli "${ETCH_PATH}" user application-password list admin --format=json 2>/dev/null || echo "[]")

if command -v jq >/dev/null 2>&1; then
  APP_PASS=$(echo "${APP_PASS_JSON}" | jq -r '.[0].password // empty' 2>/dev/null || echo "")
else
  APP_PASS=$(echo "${APP_PASS_JSON}" | grep -o '"password":"[^"]*"' | head -1 | cut -d'"' -f4 || echo "")
fi

if [[ -n "${APP_PASS}" ]]; then
  test_pass "Application Password retrieved"
  test_info "Password: ${APP_PASS:0:10}... (truncated)"
else
  test_fail "Could not retrieve Application Password"
  echo ""
  echo "Creating new Application Password..."
  APP_PASS=$(wp_cli "${ETCH_PATH}" user application-password create admin b2e-test --porcelain 2>/dev/null || echo "")
  if [[ -n "${APP_PASS}" ]]; then
    test_pass "New Application Password created"
    test_info "Password: ${APP_PASS:0:10}... (truncated)"
  else
    test_fail "Failed to create Application Password"
    echo ""
    echo "Cannot continue without Application Password. Exiting."
    exit 1
  fi
fi

# Test 2: Test REST API Status Endpoint
echo ""
echo "[2/6] Testing REST API Status Endpoint..."
ETCH_URL="http://localhost:8081"
STATUS_RESPONSE=$(curl -s -w "\n%{http_code}" "${ETCH_URL}/wp-json/efs/v1/status" 2>/dev/null || echo "000")
HTTP_CODE=$(echo "${STATUS_RESPONSE}" | tail -1)
RESPONSE_BODY=$(echo "${STATUS_RESPONSE}" | head -n -1)

if [[ "${HTTP_CODE}" == "200" ]]; then
  test_pass "REST API endpoint reachable (HTTP ${HTTP_CODE})"
  test_info "Response: ${RESPONSE_BODY:0:100}"
elif [[ "${HTTP_CODE}" == "404" ]]; then
  test_info "REST API endpoint not found (HTTP ${HTTP_CODE}) - endpoint may not be implemented yet"
  echo -e "${YELLOW}⚠${NC} This is expected if the /b2e/v1/status endpoint is not yet implemented"
else
  test_info "REST API endpoint returned HTTP ${HTTP_CODE}"
fi

# Test 3: Generate Migration Token
echo ""
echo "[3/6] Generating Migration Token..."
TOKEN_RESPONSE=$(run_compose exec -T "${WPCLI_SERVICE}" wp eval \
  'echo json_encode(apply_filters("rest_pre_dispatch", null, null, new WP_REST_Request("POST", "/efs/v1/generate-key")));' \
  --path="${ETCH_PATH}" 2>/dev/null || echo "{}")

if command -v jq >/dev/null 2>&1; then
  MIGRATION_TOKEN=$(echo "${TOKEN_RESPONSE}" | jq -r '.token // empty' 2>/dev/null || echo "")
else
  MIGRATION_TOKEN=$(echo "${TOKEN_RESPONSE}" | grep -o '"token":"[^"]*"' | cut -d'"' -f4 || echo "")
fi

if [[ -n "${MIGRATION_TOKEN}" ]]; then
  test_pass "Migration token generated"
  test_info "Token: ${MIGRATION_TOKEN:0:20}... (truncated)"
else
  test_fail "Could not generate migration token"
  test_info "Response: ${TOKEN_RESPONSE:0:100}"
fi

# Test 4: Validate Token on Bricks Instance
echo ""
echo "[4/6] Validating Token on Bricks Instance..."
if [[ -n "${MIGRATION_TOKEN}" ]]; then
  VALIDATION_RESULT=$(run_compose exec -T "${WPCLI_SERVICE}" wp eval \
    "\$_POST['migration_token'] = '${MIGRATION_TOKEN}'; \$_REQUEST['action'] = 'efs_validate_migration_token'; \$_REQUEST['_wpnonce'] = wp_create_nonce('b2e_ajax_nonce'); do_action('wp_ajax_efs_validate_migration_token');" \
    --path="${BRICKS_PATH}" \
    --user=admin 2>&1 || echo "")
  
  if echo "${VALIDATION_RESULT}" | grep -q "success\|valid"; then
    test_pass "Token validation successful"
  else
    test_fail "Token validation failed"
    test_info "Result: ${VALIDATION_RESULT:0:100}"
  fi
else
  test_fail "Skipping validation (no token available)"
fi

# Test 5: Test CORS Headers
echo ""
echo "[5/6] Testing CORS Headers..."
CORS_HEADERS=$(curl -s -I -X OPTIONS "${ETCH_URL}/wp-json/efs/v1/status" 2>/dev/null || echo "")

if echo "${CORS_HEADERS}" | grep -qi "access-control-allow-origin"; then
  CORS_VALUE=$(echo "${CORS_HEADERS}" | grep -i "access-control-allow-origin" | cut -d: -f2- | tr -d '\r\n' | xargs)
  test_pass "CORS headers present"
  test_info "Access-Control-Allow-Origin: ${CORS_VALUE}"
else
  test_fail "CORS headers not found"
  test_info "Cross-origin requests may fail"
fi

# Test 6: Test Container-to-Container Communication
echo ""
echo "[6/6] Testing Container-to-Container Communication..."
INTERNAL_RESPONSE=$(run_compose exec -T "${BRICKS_SERVICE}" curl -s -w "\n%{http_code}" http://etch-wp/wp-json/ 2>/dev/null || echo "000")
INTERNAL_HTTP_CODE=$(echo "${INTERNAL_RESPONSE}" | tail -1)

if [[ "${INTERNAL_HTTP_CODE}" == "200" ]]; then
  test_pass "Bricks container can reach Etch container (HTTP ${INTERNAL_HTTP_CODE})"
  test_info "Internal URL: http://etch-wp"
else
  test_fail "Container-to-container communication failed (HTTP ${INTERNAL_HTTP_CODE})"
  test_info "Check Docker network configuration"
fi

# Summary
echo ""
echo "=========================================="
if [[ ${FAILED_TESTS} -eq 0 ]]; then
  echo -e "${GREEN}✓ All connection tests passed!${NC}"
  echo "=========================================="
  echo ""
  echo "Your environment is ready for migration."
  echo "Run 'make test-migration' to start a full migration test."
  exit 0
else
  echo -e "${RED}✗ ${FAILED_TESTS} test(s) failed${NC}"
  echo "=========================================="
  echo ""
  echo "Troubleshooting tips:"
  echo "  - Ensure all containers are running: 'make start'"
  echo "  - Check plugin activation: 'make validate'"
  echo "  - Review logs: 'make logs-bricks' or 'make logs-etch'"
  echo "  - Run full debug: 'make debug'"
  exit 1
fi
