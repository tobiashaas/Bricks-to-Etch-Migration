#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
PLUGIN_PATH="${PLUGIN_PATH:-/var/www/html/bricks/wp-content/plugins/etch-fusion-suite}"

run_compose() {
  ${DOCKER_COMPOSE_BIN} "$@"
}

ensure_composer() {
  if run_compose exec -T "${WPCLI_SERVICE}" composer --version >/dev/null 2>&1; then
    echo "[install-composer-deps] Composer already installed in ${WPCLI_SERVICE}."
    return
  fi

  echo "[install-composer-deps] Installing Composer in ${WPCLI_SERVICE}."
  
  # Try primary installation method with hash verification
  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    php -r \"unlink('composer-setup.php');\"" >/dev/null 2>&1; then
    echo "[install-composer-deps] Composer installed successfully"
    return
  fi
  
  # Fallback: Try alternative installation method
  echo "[install-composer-deps] Primary installation failed, trying fallback method..." >&2
  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "wget -q https://getcomposer.org/composer-stable.phar -O /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer" >/dev/null 2>&1; then
    echo "[install-composer-deps] Composer installed via fallback method"
    return
  fi
  
  if ! run_compose exec -T "${WPCLI_SERVICE}" composer --version >/dev/null 2>&1; then
    echo "[install-composer-deps] ERROR: All Composer installation methods failed" >&2
    echo "[install-composer-deps] Please check internet connectivity and try again" >&2
    exit 1
  fi
}

install_dependencies() {
  # Pre-installation checks
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -d '${PLUGIN_PATH}'" >/dev/null 2>&1; then
    echo "[install-composer-deps] ERROR: Plugin directory ${PLUGIN_PATH} does not exist" >&2
    exit 1
  fi
  
  if ! run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${PLUGIN_PATH}/composer.json'" >/dev/null 2>&1; then
    echo "[install-composer-deps] ERROR: composer.json not found in ${PLUGIN_PATH}" >&2
    exit 1
  fi
  
  echo "[install-composer-deps] Installing Composer dependencies in ${PLUGIN_PATH}."
  run_compose exec -T "${WPCLI_SERVICE}" sh -c "cd '${PLUGIN_PATH}' && composer install --no-dev --optimize-autoloader"
  run_compose exec -T "${WPCLI_SERVICE}" sh -c "cd '${PLUGIN_PATH}' && composer dump-autoload -o"
  
  # Verify autoloader was created
  if run_compose exec -T "${WPCLI_SERVICE}" sh -c "test -f '${PLUGIN_PATH}/vendor/autoload.php'" >/dev/null 2>&1; then
    echo "[install-composer-deps] Autoloader verified at ${PLUGIN_PATH}/vendor/autoload.php"
  else
    echo "[install-composer-deps] WARNING: Autoloader not found at ${PLUGIN_PATH}/vendor/autoload.php" >&2
  fi
}

main() {
  if ! run_compose ps >/dev/null 2>&1; then
    echo "[install-composer-deps] docker-compose command failed." >&2
    exit 1
  fi

  ensure_composer
  install_dependencies

  local package_count
  package_count=$(run_compose exec -T "${WPCLI_SERVICE}" sh -c "cd '${PLUGIN_PATH}' && composer show | wc -l" | tr -d '\r')
  echo "[install-composer-deps] Installed packages: ${package_count}"
}

main "$@"
