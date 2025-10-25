#!/usr/bin/env bash
set -euo pipefail

DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
BRICKS_SERVICE="${BRICKS_SERVICE:-bricks-wp}"
ETCH_SERVICE="${ETCH_SERVICE:-etch-wp}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
BRICKS_SITE_PATH="${BRICKS_SITE_PATH:-/var/www/html/bricks}"
ETCH_SITE_PATH="${ETCH_SITE_PATH:-/var/www/html/etch}"
TARGET_URL="${TARGET_URL:-http://etch-wp}"
BRICKS_URL="${BRICKS_URL:-http://localhost:8080}"
ETCH_URL="${ETCH_URL:-http://localhost:8081}"
CLEANUP=0

usage() {
  cat <<EOF
Usage: test-migration.sh [--cleanup]

Options:
  --cleanup   Reset Etch instance before running migration.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --cleanup)
      CLEANUP=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 1
      ;;
  esac
done

run_compose() {
  ${DOCKER_COMPOSE_BIN} "$@"
}

wp_cli() {
  local path="$1"
  shift
  run_compose exec -T "${WPCLI_SERVICE}" wp --path="${path}" "$@"
}

reset_etch() {
  echo "[test-migration] Resetting Etch database"
  wp_cli "${ETCH_SITE_PATH}" db reset --yes || true
  wp_cli "${ETCH_SITE_PATH}" core install \
    --url="${ETCH_URL}" \
    --title="Etch Target" \
    --admin_user="admin" \
    --admin_password="admin" \
    --admin_email="admin@local.dev"
  wp_cli "${ETCH_SITE_PATH}" plugin activate etch-fusion-suite || true
}

fetch_application_password() {
  local json_output
  json_output=$(run_compose exec -T "${WPCLI_SERVICE}" wp user application-password list admin --format=json --path="${ETCH_SITE_PATH}")
  
  if command -v jq >/dev/null 2>&1; then
    echo "${json_output}" | jq -r '.[0].password'
  else
    # Fallback: extract password using grep and sed
    echo "${json_output}" | grep -o '"password":"[^"]*"' | head -1 | cut -d'"' -f4
  fi
}

configure_bricks_plugin() {
  local api_key="$1"
  local settings_json
  settings_json=$(cat <<JSON
{"target_url":"${TARGET_URL}","api_key":"${api_key}","api_username":"admin"}
JSON
)
  run_compose exec -T "${WPCLI_SERVICE}" wp option update b2e_settings "${settings_json}" --path="${BRICKS_SITE_PATH}"
}

check_prerequisites() {
  echo "[test-migration] Running pre-migration checks..."
  
  # Check if both WordPress instances are reachable
  if ! wp_cli "${BRICKS_SITE_PATH}" core is-installed >/dev/null 2>&1; then
    echo "[test-migration] ERROR: Bricks WordPress not installed" >&2
    return 1
  fi
  
  if ! wp_cli "${ETCH_SITE_PATH}" core is-installed >/dev/null 2>&1; then
    echo "[test-migration] ERROR: Etch WordPress not installed" >&2
    return 1
  fi
  
  # Check if plugin is activated
  if ! wp_cli "${BRICKS_SITE_PATH}" plugin is-active etch-fusion-suite >/dev/null 2>&1; then
    echo "[test-migration] ERROR: Plugin not activated on Bricks site" >&2
    return 1
  fi
  
  if ! wp_cli "${ETCH_SITE_PATH}" plugin is-active etch-fusion-suite >/dev/null 2>&1; then
    echo "[test-migration] ERROR: Plugin not activated on Etch site" >&2
    return 1
  fi
  
  # Check Composer dependencies
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${BRICKS_SITE_PATH}/wp-content/plugins/etch-fusion-suite/vendor/autoload.php'" >/dev/null 2>&1; then
    echo "[test-migration] ERROR: Composer dependencies not installed" >&2
    return 1
  fi
  
  echo "[test-migration] ✓ All prerequisites met"
  return 0
}

start_migration() {
  local api_key="$1"
  echo "[test-migration] Attempting to trigger migration via REST API..."
  
  # Step 1: Generate migration token on Etch site using WP-CLI
  local token_response
  token_response=$(run_compose exec -T "${WPCLI_SERVICE}" wp eval \
    'echo json_encode(apply_filters("rest_pre_dispatch", null, null, new WP_REST_Request("POST", "/b2e/v1/generate-key")));' \
    --path="${ETCH_SITE_PATH}" 2>/dev/null || echo "")
  
  if [[ -z "${token_response}" ]] || [[ "${token_response}" == "null" ]]; then
    echo "[test-migration] Warning: Could not generate migration token."
    echo "[test-migration] Please visit ${BRICKS_URL}/wp-admin to start the migration manually."
    echo "[test-migration] Continuing to monitor for migration progress..."
    return 0
  fi
  
  # Extract token from response using jq if available, otherwise grep
  local migration_token
  if command -v jq >/dev/null 2>&1; then
    migration_token=$(echo "${token_response}" | jq -r '.token // empty' 2>/dev/null || echo "")
  else
    migration_token=$(echo "${token_response}" | grep -o '"token":"[^"]*"' | cut -d'"' -f4 || echo "")
  fi
  
  if [[ -z "${migration_token}" ]]; then
    echo "[test-migration] Warning: Could not extract migration token from response."
    echo "[test-migration] Response: ${token_response:0:100}"
    echo "[test-migration] Please visit ${BRICKS_URL}/wp-admin to start the migration manually."
    echo "[test-migration] Continuing to monitor for migration progress..."
    return 0
  fi
  
  echo "[test-migration] Migration token generated successfully"
  
  # Step 2: Trigger migration on Bricks site using the token
  local migration_result
  migration_result=$(run_compose exec -T "${WPCLI_SERVICE}" wp eval \
    "\$_POST['migration_token'] = '${migration_token}'; \$_POST['batch_size'] = 50; \$_REQUEST['action'] = 'efs_start_migration'; \$_REQUEST['_wpnonce'] = wp_create_nonce('b2e_ajax_nonce'); do_action('wp_ajax_efs_start_migration');" \
    --path="${BRICKS_SITE_PATH}" \
    --user=admin 2>&1 || echo "")
  
  if [[ -z "${migration_result}" ]] || [[ "${migration_result}" == *"error"* ]]; then
    echo "[test-migration] Warning: Could not trigger migration automatically."
    echo "[test-migration] Please visit ${BRICKS_URL}/wp-admin to start the migration manually."
    echo "[test-migration] Continuing to monitor for migration progress..."
    return 0
  fi
  
  echo "[test-migration] Migration triggered successfully!"
  return 0
}

poll_progress() {
  echo "[test-migration] Monitoring migration progress"
  local timeout=300  # 5 minutes timeout
  local elapsed=0
  local interval=5
  
  while true; do
    local status steps
    status=$(run_compose exec -T "${WPCLI_SERVICE}" wp option get b2e_migration_progress --path="${BRICKS_SITE_PATH}" 2>/dev/null || echo "unknown")
    steps=$(run_compose exec -T "${WPCLI_SERVICE}" wp option get b2e_migration_steps --path="${BRICKS_SITE_PATH}" --format=json 2>/dev/null || echo "{}")
    
    echo "[test-migration] Status: ${status} (${elapsed}s elapsed)"
    
    # Show detailed steps if available
    if [[ "${steps}" != "{}" ]] && command -v jq >/dev/null 2>&1; then
      echo "${steps}" | jq -r 'to_entries[] | "  \(.key): \(.value)"' 2>/dev/null || true
    fi
    
    if [[ "${status}" == "completed" ]]; then
      echo "[test-migration] ✓ Migration completed successfully"
      break
    fi
    
    if [[ "${status}" == "error" ]] || [[ "${status}" == "failed" ]]; then
      echo "[test-migration] ✗ Migration failed with status: ${status}" >&2
      check_errors
      return 1
    fi
    
    # Check timeout
    elapsed=$((elapsed + interval))
    if [[ ${elapsed} -ge ${timeout} ]]; then
      echo "[test-migration] ✗ Migration timeout after ${timeout}s" >&2
      check_errors
      return 1
    fi
    
    sleep ${interval}
  done
  
  return 0
}

check_errors() {
  echo "[test-migration] Checking for errors..."
  local error_log
  error_log=$(run_compose exec -T "${WPCLI_SERVICE}" wp option get b2e_error_log --path="${BRICKS_SITE_PATH}" 2>/dev/null || echo "")
  
  if [[ -n "${error_log}" ]] && [[ "${error_log}" != "false" ]]; then
    echo "[test-migration] Error log:" >&2
    echo "${error_log}" | head -20 >&2
  else
    echo "[test-migration] No errors found in log"
  fi
}

compare_counts() {
  local bricks_posts bricks_pages etch_posts etch_pages
  bricks_posts=$(run_compose exec -T "${WPCLI_SERVICE}" wp post list --post_type=post --format=count --path="${BRICKS_SITE_PATH}" | tr -d '\r')
  bricks_pages=$(run_compose exec -T "${WPCLI_SERVICE}" wp post list --post_type=page --format=count --path="${BRICKS_SITE_PATH}" | tr -d '\r')
  etch_posts=$(run_compose exec -T "${WPCLI_SERVICE}" wp post list --post_type=post --format=count --path="${ETCH_SITE_PATH}" | tr -d '\r')
  etch_pages=$(run_compose exec -T "${WPCLI_SERVICE}" wp post list --post_type=page --format=count --path="${ETCH_SITE_PATH}" | tr -d '\r')

  cat <<EOF
[test-migration] Record counts after migration:
  Bricks Posts : ${bricks_posts}
  Etch Posts   : ${etch_posts}
  Bricks Pages : ${bricks_pages}
  Etch Pages   : ${etch_pages}
EOF
}

main() {
  run_compose ps >/dev/null

  if ! command -v jq >/dev/null 2>&1; then
    echo "[test-migration] Warning: jq not found, using grep fallback for JSON parsing." >&2
  fi

  if [[ ${CLEANUP} -eq 1 ]]; then
    reset_etch
  fi

  local api_key
  api_key=$(fetch_application_password)
  if [[ -z "${api_key}" || "${api_key}" == "null" ]]; then
    echo "[test-migration] Failed to obtain Etch application password." >&2
    exit 1
  fi

  # Run prerequisite checks
  if ! check_prerequisites; then
    echo "[test-migration] ✗ Prerequisites check failed. Please fix the issues and try again." >&2
    exit 1
  fi
  
  configure_bricks_plugin "${api_key}"

  local start_ts end_ts
  start_ts=$(date +%s)
  start_migration "${api_key}"
  
  if ! poll_progress; then
    echo "[test-migration] ✗ Migration failed or timed out" >&2
    exit 1
  fi
  
  end_ts=$(date +%s)

  compare_counts

  local duration=$((end_ts - start_ts))
  echo "[test-migration] Migration completed in ${duration}s"
}

main "$@"
