#!/usr/bin/env bash
set -euo pipefail

DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
BRICKS_PATH="${BRICKS_PATH:-/var/www/html/bricks}"
ETCH_PATH="${ETCH_PATH:-/var/www/html/etch}"
BRICKS_SERVICE="${BRICKS_SERVICE:-bricks-wp}"
ETCH_SERVICE="${ETCH_SERVICE:-etch-wp}"

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
OUTPUT_FILE="debug-report-${TIMESTAMP}.txt"

run_compose() {
  ${DOCKER_COMPOSE_BIN} "$@"
}

wp_cli() {
  local path="$1"
  shift
  run_compose exec -T "${WPCLI_SERVICE}" wp --path="${path}" "$@" 2>&1 || echo "[ERROR: Command failed]"
}

section() {
  echo ""
  echo "=========================================="
  echo "  $1"
  echo "=========================================="
  echo ""
}

exec > >(tee "${OUTPUT_FILE}")
exec 2>&1

echo "Bricks2Etch Debug Information Report"
echo "Generated: $(date)"
echo "=========================================="

# 1. Docker version and container status
section "1. Docker Environment"
echo "Docker version:"
docker --version 2>/dev/null || echo "Docker not available"
echo ""
echo "Docker Compose version:"
${DOCKER_COMPOSE_BIN} --version 2>/dev/null || echo "Docker Compose not available"
echo ""
echo "Container status:"
run_compose ps 2>/dev/null || echo "Could not get container status"

# 2. WordPress versions
section "2. WordPress Versions"
echo "Bricks WordPress:"
wp_cli "${BRICKS_PATH}" core version --extra
echo ""
echo "Etch WordPress:"
wp_cli "${ETCH_PATH}" core version --extra

# 3. Active plugins
section "3. Active Plugins"
echo "Bricks site plugins:"
wp_cli "${BRICKS_PATH}" plugin list --status=active --format=table
echo ""
echo "Etch site plugins:"
wp_cli "${ETCH_PATH}" plugin list --status=active --format=table

# 4. PHP version and extensions
section "4. PHP Environment"
echo "PHP version (Bricks):"
run_compose exec -T "${BRICKS_SERVICE}" php -v 2>/dev/null || echo "Could not get PHP version"
echo ""
echo "Loaded PHP extensions (Bricks):"
run_compose exec -T "${BRICKS_SERVICE}" php -m 2>/dev/null | head -30 || echo "Could not get PHP extensions"

# 5. Composer packages
section "5. Composer Dependencies"
echo "Composer version:"
run_compose exec -T "${WPCLI_SERVICE}" composer --version 2>/dev/null || echo "Composer not installed"
echo ""
echo "Installed packages:"
run_compose exec -T "${WPCLI_SERVICE}" sh -c "cd '${BRICKS_PATH}/wp-content/plugins/etch-fusion-suite' && composer show 2>/dev/null" || echo "Could not list packages"

# 6. Plugin configuration
section "6. Plugin Configuration"
echo "B2E Settings (Bricks):"
wp_cli "${BRICKS_PATH}" option get b2e_settings --format=json 2>/dev/null || echo "No settings found"
echo ""
echo "Migration Progress (Bricks):"
wp_cli "${BRICKS_PATH}" option get b2e_migration_progress 2>/dev/null || echo "No migration progress found"
echo ""
echo "Migration Steps (Bricks):"
wp_cli "${BRICKS_PATH}" option get b2e_migration_steps --format=json 2>/dev/null || echo "No migration steps found"

# 7. WordPress debug logs
section "7. WordPress Debug Logs (Last 50 lines)"
echo "Bricks debug.log:"
run_compose exec -T "${BRICKS_SERVICE}" sh -c "if [ -f /var/www/html/wp-content/debug.log ]; then tail -50 /var/www/html/wp-content/debug.log; else echo 'No debug.log found'; fi" 2>/dev/null
echo ""
echo "Etch debug.log:"
run_compose exec -T "${ETCH_SERVICE}" sh -c "if [ -f /var/www/html/wp-content/debug.log ]; then tail -50 /var/www/html/wp-content/debug.log; else echo 'No debug.log found'; fi" 2>/dev/null

# 8. Container logs
section "8. Container Logs (Last 50 lines)"
echo "Bricks container logs:"
run_compose logs --tail=50 "${BRICKS_SERVICE}" 2>/dev/null || echo "Could not get container logs"
echo ""
echo "Etch container logs:"
run_compose logs --tail=50 "${ETCH_SERVICE}" 2>/dev/null || echo "Could not get container logs"

# 9. Network connectivity
section "9. Network Connectivity"
echo "Testing connectivity to Etch from Bricks container:"
run_compose exec -T "${BRICKS_SERVICE}" curl -s -I http://etch-wp/ 2>/dev/null | head -5 || echo "HTTP connectivity test failed"
echo ""
echo "Curl to Etch REST API from Bricks:"
run_compose exec -T "${BRICKS_SERVICE}" curl -s -I http://etch-wp/wp-json/b2e/v1/status 2>/dev/null || echo "Curl failed"

# 10. File permissions
section "10. Plugin Directory Permissions"
echo "Bricks plugin directory:"
run_compose exec -T "${BRICKS_SERVICE}" ls -la /var/www/html/wp-content/plugins/etch-fusion-suite 2>/dev/null | head -20 || echo "Could not list directory"
echo ""
echo "Vendor directory exists:"
run_compose exec -T "${BRICKS_SERVICE}" sh -c "if [ -d /var/www/html/wp-content/plugins/etch-fusion-suite/vendor ]; then echo 'Yes'; ls -la /var/www/html/wp-content/plugins/etch-fusion-suite/vendor | head -10; else echo 'No'; fi" 2>/dev/null

# 11. Disk space
section "11. Disk Space"
echo "Bricks container:"
run_compose exec -T "${BRICKS_SERVICE}" df -h / 2>/dev/null || echo "Could not get disk space"
echo ""
echo "Etch container:"
run_compose exec -T "${ETCH_SERVICE}" df -h / 2>/dev/null || echo "Could not get disk space"

# 12. Database connection
section "12. Database Connection"
echo "Bricks database:"
wp_cli "${BRICKS_PATH}" db check
echo ""
echo "Etch database:"
wp_cli "${ETCH_PATH}" db check

echo ""
echo "=========================================="
echo "Debug report saved to: ${OUTPUT_FILE}"
echo "=========================================="
echo ""
echo "Please review the report above for any errors or warnings."
echo "You can share this file when reporting issues."
