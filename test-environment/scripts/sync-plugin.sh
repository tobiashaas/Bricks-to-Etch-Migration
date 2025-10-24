#!/usr/bin/env bash
set -euo pipefail

DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
BRICKS_CONTAINER="${BRICKS_CONTAINER:-b2e-bricks-wp}"
ETCH_CONTAINER="${ETCH_CONTAINER:-b2e-etch-wp}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
PLUGIN_SOURCE="${PLUGIN_SOURCE:-../bricks-etch-migration}"
BRICKS_PLUGIN_PATH="${BRICKS_PLUGIN_PATH:-/var/www/html/wp-content/plugins/bricks-etch-migration}"
ETCH_PLUGIN_PATH="${ETCH_PLUGIN_PATH:-/var/www/html/wp-content/plugins/bricks-etch-migration}"
WATCH_MODE=0

usage() {
  cat <<EOF
Usage: sync-plugin.sh [--watch]

Options:
  --watch   Continuously watch for changes and sync.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --watch)
      WATCH_MODE=1
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

require_tools() {
  if [[ ${WATCH_MODE} -eq 1 ]]; then
    if command -v inotifywait >/dev/null 2>&1; then
      WATCH_TOOL="inotifywait"
    elif command -v fswatch >/dev/null 2>&1; then
      WATCH_TOOL="fswatch"
    else
      echo "[sync-plugin] --watch requested but neither inotifywait nor fswatch is installed." >&2
      exit 1
    fi
  fi
}

sync_once() {
  echo "[sync-plugin] Syncing plugin sources from ${PLUGIN_SOURCE}"

  docker cp "${PLUGIN_SOURCE}/." "${BRICKS_CONTAINER}:${BRICKS_PLUGIN_PATH}" >/dev/null
  docker cp "${PLUGIN_SOURCE}/." "${ETCH_CONTAINER}:${ETCH_PLUGIN_PATH}" >/dev/null

  ${DOCKER_COMPOSE_BIN} exec -T bricks-wp chown -R www-data:www-data "${BRICKS_PLUGIN_PATH}"
  ${DOCKER_COMPOSE_BIN} exec -T etch-wp chown -R www-data:www-data "${ETCH_PLUGIN_PATH}"

  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" sh -c "cd /var/www/html/bricks/wp-content/plugins/bricks-etch-migration && composer install --no-dev --optimize-autoloader" || true
  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" sh -c "cd /var/www/html/etch/wp-content/plugins/bricks-etch-migration && composer install --no-dev --optimize-autoloader" || true

  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp plugin deactivate bricks-etch-migration --path="/var/www/html/bricks" >/dev/null 2>&1 || true
  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp plugin activate bricks-etch-migration --path="/var/www/html/bricks"

  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp plugin deactivate bricks-etch-migration --path="/var/www/html/etch" >/dev/null 2>&1 || true
  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp plugin activate bricks-etch-migration --path="/var/www/html/etch"

  echo "[sync-plugin] Sync completed."
}

watch_loop() {
  echo "[sync-plugin] Watching ${PLUGIN_SOURCE} for changes using ${WATCH_TOOL}."
  if [[ "${WATCH_TOOL}" == "inotifywait" ]]; then
    while inotifywait -r -e modify,create,delete,move "${PLUGIN_SOURCE}" >/dev/null; do
      sync_once || true
    done
  else
    ${WATCH_TOOL} -o "${PLUGIN_SOURCE}" | while read -r _; do
      sync_once || true
    done
  fi
}

main() {
  if [[ ! -d "${PLUGIN_SOURCE}" ]]; then
    echo "[sync-plugin] Plugin source directory ${PLUGIN_SOURCE} not found." >&2
    exit 1
  fi

  ${DOCKER_COMPOSE_BIN} ps >/dev/null
  require_tools

  sync_once

  if [[ ${WATCH_MODE} -eq 1 ]]; then
    watch_loop
  fi
}

main "$@"
