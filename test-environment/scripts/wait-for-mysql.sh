#!/usr/bin/env bash
set -euo pipefail

HOST="${1:-localhost}"
PORT="${2:-3306}"
USER="${3:-root}"
PASSWORD="${4:-}"
MAX_ATTEMPTS="${5:-30}"
DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"

if [[ -z "${HOST}" ]]; then
  echo "[wait-for-mysql] Host must be provided." >&2
  exit 1
fi

echo "[wait-for-mysql] Waiting for MySQL service ${HOST} to be ready..."

ATTEMPT=1
while [[ "${ATTEMPT}" -le "${MAX_ATTEMPTS}" ]]; do
  # Use docker-compose exec to check MySQL from inside the container
  if ${DOCKER_COMPOSE_BIN} exec -T "${HOST}" mysqladmin ping -h127.0.0.1 -P"${PORT}" -u"${USER}" -p"${PASSWORD}" --silent >/dev/null 2>&1; then
    echo "[wait-for-mysql] MySQL service ${HOST} is ready and accepting connections."
    exit 0
  fi

  echo "[wait-for-mysql] Waiting for MySQL at ${HOST}:${PORT}... attempt ${ATTEMPT}/${MAX_ATTEMPTS}" >&2
  sleep 2
  ATTEMPT=$((ATTEMPT + 1))
done

echo "[wait-for-mysql] MySQL at ${HOST}:${PORT} did not become available after ${MAX_ATTEMPTS} attempts." >&2
exit 1
