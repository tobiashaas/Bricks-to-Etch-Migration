#!/usr/bin/env bash
set -euo pipefail

DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
BRICKS_PATH="${BRICKS_PATH:-/var/www/html/bricks}"
ETCH_PATH="${ETCH_PATH:-/var/www/html/etch}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

FAILED_CHECKS=0

run_compose() {
  ${DOCKER_COMPOSE_BIN} "$@"
}

wp_cli() {
  local path="$1"
  shift
  run_compose exec -T "${WPCLI_SERVICE}" wp --path="${path}" "$@"
}

check_pass() {
  echo -e "${GREEN}✓${NC} $1"
}

check_fail() {
  echo -e "${RED}✗${NC} $1"
  FAILED_CHECKS=$((FAILED_CHECKS + 1))
}

check_warn() {
  echo -e "${YELLOW}⚠${NC} $1"
}

echo "=========================================="
echo "  Bricks2Etch Setup Validation"
echo "=========================================="
echo ""

# Check 1: Docker containers running
echo "[1/9] Checking Docker containers..."
if run_compose ps 2>/dev/null | grep -q "Up"; then
  CONTAINER_COUNT=$(run_compose ps 2>/dev/null | grep -c "Up" || echo "0")
  if [[ ${CONTAINER_COUNT} -ge 4 ]]; then
    check_pass "All Docker containers are running (${CONTAINER_COUNT} containers)"
  else
    check_warn "Only ${CONTAINER_COUNT} containers running (expected 4+)"
  fi
else
  check_fail "Docker containers are not running. Run 'make start' first."
fi

# Check 2: MySQL databases reachable
echo "[2/9] Checking MySQL databases..."
if wp_cli "${BRICKS_PATH}" db check >/dev/null 2>&1; then
  check_pass "Bricks MySQL database is reachable"
else
  check_fail "Bricks MySQL database is not reachable"
fi

if wp_cli "${ETCH_PATH}" db check >/dev/null 2>&1; then
  check_pass "Etch MySQL database is reachable"
else
  check_fail "Etch MySQL database is not reachable"
fi

# Check 3: WordPress installed
echo "[3/9] Checking WordPress installation..."
if wp_cli "${BRICKS_PATH}" core is-installed >/dev/null 2>&1; then
  WP_VERSION=$(wp_cli "${BRICKS_PATH}" core version 2>/dev/null || echo "unknown")
  check_pass "WordPress installed on Bricks site (version ${WP_VERSION})"
else
  check_fail "WordPress not installed on Bricks site"
fi

if wp_cli "${ETCH_PATH}" core is-installed >/dev/null 2>&1; then
  WP_VERSION=$(wp_cli "${ETCH_PATH}" core version 2>/dev/null || echo "unknown")
  check_pass "WordPress installed on Etch site (version ${WP_VERSION})"
else
  check_fail "WordPress not installed on Etch site"
fi

# Check 4: Plugin directory mounted
echo "[4/9] Checking plugin directory..."
if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -d '${BRICKS_PATH}/wp-content/plugins/etch-fusion-suite'" >/dev/null 2>&1; then
  check_pass "Plugin directory mounted on Bricks site"
else
  check_fail "Plugin directory not found on Bricks site"
fi

if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -d '${ETCH_PATH}/wp-content/plugins/etch-fusion-suite'" >/dev/null 2>&1; then
  check_pass "Plugin directory mounted on Etch site"
else
  check_fail "Plugin directory not found on Etch site"
fi

# Check 5: Composer autoloader exists
echo "[5/9] Checking Composer autoloader..."
if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${BRICKS_PATH}/wp-content/plugins/etch-fusion-suite/vendor/autoload.php'" >/dev/null 2>&1; then
  check_pass "Composer autoloader exists"
else
  check_fail "Composer autoloader not found. Run 'make composer-install'"
fi

# Check 6: Plugin activated
echo "[6/9] Checking plugin activation..."
if wp_cli "${BRICKS_PATH}" plugin is-active etch-fusion-suite >/dev/null 2>&1; then
  PLUGIN_VERSION=$(wp_cli "${BRICKS_PATH}" plugin get etch-fusion-suite --field=version 2>/dev/null || echo "unknown")
  check_pass "Plugin activated on Bricks site (version ${PLUGIN_VERSION})"
else
  check_fail "Plugin not activated on Bricks site"
fi

if wp_cli "${ETCH_PATH}" plugin is-active etch-fusion-suite >/dev/null 2>&1; then
  PLUGIN_VERSION=$(wp_cli "${ETCH_PATH}" plugin get etch-fusion-suite --field=version 2>/dev/null || echo "unknown")
  check_pass "Plugin activated on Etch site (version ${PLUGIN_VERSION})"
else
  check_fail "Plugin not activated on Etch site"
fi

# Check 7: Service container initialized
echo "[7/9] Checking service container..."
if wp_cli "${BRICKS_PATH}" eval 'echo function_exists("b2e_container") ? "yes" : "no";' 2>/dev/null | grep -q "yes"; then
  check_pass "Service container function available on Bricks site"
else
  check_warn "Service container function not available (plugin may not be fully loaded)"
fi

# Check 8: REST API reachable
echo "[8/9] Checking REST API..."
BRICKS_API_URL="http://localhost:8080/wp-json/b2e/v1/status"
ETCH_API_URL="http://localhost:8081/wp-json/b2e/v1/status"

if command -v curl >/dev/null 2>&1; then
  HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${ETCH_API_URL}" 2>/dev/null || echo "000")
  if [[ "${HTTP_CODE}" == "200" ]]; then
    check_pass "REST API endpoint reachable on Etch site (HTTP ${HTTP_CODE})"
  else
    check_warn "REST API endpoint returned HTTP ${HTTP_CODE} (endpoint may not be implemented yet)"
  fi
else
  check_warn "curl not available, skipping REST API check"
fi

# Check 9: Application Passwords available
echo "[9/9] Checking Application Passwords..."
if wp_cli "${ETCH_PATH}" user application-password list admin --format=count 2>/dev/null | grep -q "[0-9]"; then
  APP_PASS_COUNT=$(wp_cli "${ETCH_PATH}" user application-password list admin --format=count 2>/dev/null || echo "0")
  if [[ ${APP_PASS_COUNT} -gt 0 ]]; then
    check_pass "Application Passwords configured (${APP_PASS_COUNT} password(s))"
  else
    check_warn "No Application Passwords created yet"
  fi
else
  check_warn "Could not check Application Passwords"
fi

echo ""
echo "=========================================="
if [[ ${FAILED_CHECKS} -eq 0 ]]; then
  echo -e "${GREEN}✓ All validation checks passed!${NC}"
  echo "=========================================="
  exit 0
else
  echo -e "${RED}✗ ${FAILED_CHECKS} validation check(s) failed${NC}"
  echo "=========================================="
  echo ""
  echo "Troubleshooting tips:"
  echo "  - Run 'make composer-install' to install dependencies"
  echo "  - Run 'make setup' to reinitialize the environment"
  echo "  - Check logs with 'make logs-bricks' or 'make logs-etch'"
  echo "  - Run 'make debug' for detailed debug information"
  exit 1
fi
