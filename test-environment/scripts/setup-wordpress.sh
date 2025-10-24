#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"

BRICKS_SERVICE="${BRICKS_SERVICE:-bricks-wp}"
ETCH_SERVICE="${ETCH_SERVICE:-etch-wp}"
BRICKS_DB_SERVICE="${BRICKS_DB_SERVICE:-bricks-db}"
ETCH_DB_SERVICE="${ETCH_DB_SERVICE:-etch-db}"

BRICKS_PATH="${BRICKS_PATH:-/var/www/html/bricks}"
ETCH_PATH="${ETCH_PATH:-/var/www/html/etch}"

BRICKS_URL="${BRICKS_WP_URL:-http://localhost:8080}"
ETCH_URL="${ETCH_WP_URL:-http://localhost:8081}"
ADMIN_USER="${WP_ADMIN_USER:-admin}"
ADMIN_PASS="${WP_ADMIN_PASSWORD:-admin}"
ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@local.dev}"

BRICKS_PLUGIN_ZIP="${BRICKS_PLUGIN_ZIP:-/scripts/plugins/bricks.zip}"
ETCH_PLUGIN_ZIP="${ETCH_PLUGIN_ZIP:-/scripts/plugins/etch.zip}"
TEST_DATA_XML="${TEST_DATA_XML:-/scripts/test-data.xml}"

WP_DEBUG="${WP_DEBUG:-1}"
WP_DEBUG_LOG="${WP_DEBUG_LOG:-1}"
WP_DEBUG_DISPLAY="${WP_DEBUG_DISPLAY:-0}"

source "${SCRIPT_DIR}/wait-for-mysql.sh" >/dev/null 2>&1 || true

run_compose() {
  ${DOCKER_COMPOSE_BIN} "$@"
}

wp_cli() {
  local path="$1"
  shift
  run_compose exec -T "${WPCLI_SERVICE}" wp --path="${path}" "$@"
}

maybe_install_plugin() {
  local path="$1"
  local zip_path="$2"
  local plugin_slug="$3"

  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${zip_path}'" >/dev/null 2>&1; then
    echo "[setup-wordpress] Installing ${plugin_slug} from ${zip_path}"
    wp_cli "${path}" plugin install "${zip_path}" --activate
  else
    echo "[setup-wordpress] Plugin package ${zip_path} not found. Skipping installation for ${plugin_slug}."
  fi
}

maybe_import_demo_content() {
  local path="$1"
  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${TEST_DATA_XML}'" >/dev/null 2>&1; then
    echo "[setup-wordpress] Importing demo content from ${TEST_DATA_XML}"
    wp_cli "${path}" import "${TEST_DATA_XML}" --authors=create || echo "[setup-wordpress] Demo import failed" >&2
  else
    echo "[setup-wordpress] No demo content file at ${TEST_DATA_XML}."
  fi
}

wait_for_db() {
  local service_name="$1"
  local site_path="$2"
  local db_user="$3"
  local db_pass="$4"

  if [[ -x "${SCRIPT_DIR}/wait-for-mysql.sh" ]]; then
    "${SCRIPT_DIR}/wait-for-mysql.sh" "${service_name}" "3306" "${db_user}" "${db_pass}" "60" || true
  fi

  echo "[setup-wordpress] Waiting for database ${service_name} to accept connections"
  local max_attempts=30
  local attempt=1
  until run_compose exec -T "${service_name}" mysqladmin ping -h127.0.0.1 -u"${db_user}" -p"${db_pass}" --silent >/dev/null 2>&1; do
    if [[ ${attempt} -ge ${max_attempts} ]]; then
      echo "[setup-wordpress] ERROR: Database ${service_name} did not become available after ${max_attempts} attempts" >&2
      exit 1
    fi
    echo "[setup-wordpress] Waiting for MySQL at ${service_name}... attempt ${attempt}/${max_attempts}" >&2
    sleep 2
    attempt=$((attempt + 1))
  done
  echo "[setup-wordpress] Database ${service_name} is ready"
}

setup_bricks_site() {
  echo "[setup-wordpress] Configuring Bricks source site"
  wait_for_db "${BRICKS_DB_SERVICE}" "${BRICKS_PATH}" "bricks_user" "bricks_pass"
  
  # Verify WordPress directory exists and is writable
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -d '${BRICKS_PATH}'" >/dev/null 2>&1; then
    echo "[setup-wordpress] ERROR: WordPress directory ${BRICKS_PATH} does not exist" >&2
    exit 1
  fi

  if ! wp_cli "${BRICKS_PATH}" core is-installed >/dev/null 2>&1; then
    wp_cli "${BRICKS_PATH}" core install \
      --url="${BRICKS_URL}" \
      --title="Bricks Source" \
      --admin_user="${ADMIN_USER}" \
      --admin_password="${ADMIN_PASS}" \
      --admin_email="${ADMIN_EMAIL}"
  fi

  wp_cli "${BRICKS_PATH}" config set WP_DEBUG "${WP_DEBUG}" --raw
  wp_cli "${BRICKS_PATH}" config set WP_DEBUG_LOG "${WP_DEBUG_LOG}" --raw
  wp_cli "${BRICKS_PATH}" config set WP_DEBUG_DISPLAY "${WP_DEBUG_DISPLAY}" --raw

  # Install Bricks and related plugins
  maybe_install_plugin "${BRICKS_PATH}" "${BRICKS_PLUGIN_ZIP}" "Bricks Builder"
  maybe_install_plugin "${BRICKS_PATH}" "/scripts/plugins/frames.zip" "Frames"
  maybe_install_plugin "${BRICKS_PATH}" "/scripts/plugins/automatic-css-bricks.zip" "Automatic.css for Bricks"
  
  # Install Bricks Child theme
  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '/scripts/plugins/bricks-child.zip'" >/dev/null 2>&1; then
    echo "[setup-wordpress] Installing Bricks Child theme"
    wp_cli "${BRICKS_PATH}" theme install /scripts/plugins/bricks-child.zip --activate
  fi
  
  # Check Composer dependencies before activating plugin
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${BRICKS_PATH}/wp-content/plugins/bricks-etch-migration/vendor/autoload.php'" >/dev/null 2>&1; then
    echo "[setup-wordpress] WARNING: Composer autoloader not found at ${BRICKS_PATH}/wp-content/plugins/bricks-etch-migration/vendor/autoload.php" >&2
    echo "[setup-wordpress] Plugin may fail to activate. Run 'make composer-install' first." >&2
  fi
  
  # Activate B2E plugin with proper error handling
  if ! wp_cli "${BRICKS_PATH}" plugin activate bricks-etch-migration; then
    echo "[setup-wordpress] ERROR: Failed to activate bricks-etch-migration plugin on Bricks site" >&2
    echo "[setup-wordpress] Ensure the plugin is mounted correctly and Composer dependencies are installed" >&2
    exit 1
  fi
  echo "[setup-wordpress] Successfully activated bricks-etch-migration on Bricks site"
  
  # Debug output: Show plugin status
  echo "[setup-wordpress] Active plugins on Bricks site:"
  wp_cli "${BRICKS_PATH}" plugin list --status=active --format=table || true
  echo "[setup-wordpress] Site URL: $(wp_cli "${BRICKS_PATH}" option get siteurl 2>/dev/null || echo 'unknown')" 

  maybe_import_demo_content "${BRICKS_PATH}"

  local app_pass
  app_pass=$(wp_cli "${BRICKS_PATH}" user application-password create "${ADMIN_USER}" b2e-migration --porcelain || true)
  echo "[setup-wordpress] Bricks admin credentials: ${ADMIN_USER}/${ADMIN_PASS}. App Password: ${app_pass:-not generated}"
}

setup_etch_site() {
  echo "[setup-wordpress] Configuring Etch target site"
  wait_for_db "${ETCH_DB_SERVICE}" "${ETCH_PATH}" "etch_user" "etch_pass"
  
  # Verify WordPress directory exists and is writable
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -d '${ETCH_PATH}'" >/dev/null 2>&1; then
    echo "[setup-wordpress] ERROR: WordPress directory ${ETCH_PATH} does not exist" >&2
    exit 1
  fi

  if ! wp_cli "${ETCH_PATH}" core is-installed >/dev/null 2>&1; then
    wp_cli "${ETCH_PATH}" core install \
      --url="${ETCH_URL}" \
      --title="Etch Target" \
      --admin_user="${ADMIN_USER}" \
      --admin_password="${ADMIN_PASS}" \
      --admin_email="${ADMIN_EMAIL}"
  fi

  wp_cli "${ETCH_PATH}" config set WP_DEBUG "${WP_DEBUG}" --raw
  wp_cli "${ETCH_PATH}" config set WP_DEBUG_LOG "${WP_DEBUG_LOG}" --raw
  wp_cli "${ETCH_PATH}" config set WP_DEBUG_DISPLAY "${WP_DEBUG_DISPLAY}" --raw

  # Install Etch and related plugins
  maybe_install_plugin "${ETCH_PATH}" "${ETCH_PLUGIN_ZIP}" "Etch Plugin"
  maybe_install_plugin "${ETCH_PATH}" "/scripts/plugins/automatic-css-etch.zip" "Automatic.css for Etch"
  
  # Install Etch theme
  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '/scripts/plugins/etch-theme.zip'" >/dev/null 2>&1; then
    echo "[setup-wordpress] Installing Etch theme"
    wp_cli "${ETCH_PATH}" theme install /scripts/plugins/etch-theme.zip --activate
  fi
  
  # Check Composer dependencies before activating plugin
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${ETCH_PATH}/wp-content/plugins/bricks-etch-migration/vendor/autoload.php'" >/dev/null 2>&1; then
    echo "[setup-wordpress] WARNING: Composer autoloader not found at ${ETCH_PATH}/wp-content/plugins/bricks-etch-migration/vendor/autoload.php" >&2
    echo "[setup-wordpress] Plugin may fail to activate. Run 'make composer-install' first." >&2
  fi
  
  # Activate B2E plugin with proper error handling
  if ! wp_cli "${ETCH_PATH}" plugin activate bricks-etch-migration; then
    echo "[setup-wordpress] ERROR: Failed to activate bricks-etch-migration plugin on Etch site" >&2
    echo "[setup-wordpress] Ensure the plugin is mounted correctly and Composer dependencies are installed" >&2
    exit 1
  fi
  echo "[setup-wordpress] Successfully activated bricks-etch-migration on Etch site"
  
  # Debug output: Show plugin status
  echo "[setup-wordpress] Active plugins on Etch site:"
  wp_cli "${ETCH_PATH}" plugin list --status=active --format=table || true
  echo "[setup-wordpress] Site URL: $(wp_cli "${ETCH_PATH}" option get siteurl 2>/dev/null || echo 'unknown')"

  maybe_import_demo_content "${ETCH_PATH}"

  local app_pass
  app_pass=$(wp_cli "${ETCH_PATH}" user application-password create "${ADMIN_USER}" b2e-migration --porcelain || true)
  echo "[setup-wordpress] Etch admin credentials: ${ADMIN_USER}/${ADMIN_PASS}. App Password: ${app_pass:-not generated}"
}

main() {
  echo "[setup-wordpress] Starting automated setup"
  run_compose ps >/dev/null

  setup_bricks_site
  setup_etch_site

  echo "[setup-wordpress] Setup complete."
  cat <<EOF
Bricks Source: ${BRICKS_URL}
Etch Target : ${ETCH_URL}
Admin User  : ${ADMIN_USER}
Admin Pass  : ${ADMIN_PASS}
EOF
}

main "$@"
