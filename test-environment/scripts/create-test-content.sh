#!/usr/bin/env bash
set -euo pipefail

DOCKER_COMPOSE_BIN="${DOCKER_COMPOSE:-docker-compose}"
WPCLI_SERVICE="${WPCLI_SERVICE:-wpcli}"
BRICKS_SITE_PATH="${BRICKS_SITE_PATH:-/var/www/html/bricks}"

create_posts() {
  local count="$1"
  echo "[create-test-content] Creating ${count} test posts..."
  for ((i = 1; i <= count; i++)); do
    ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp post create \
      --path="${BRICKS_SITE_PATH}" \
      --post_type=post \
      --post_title="Test Post ${i}" \
      --post_content="This is test post ${i} created for Bricks to Etch migration testing." \
      --post_status=publish \
      --meta_input='{"_bricks_editor_mode":"bricks","_bricks_page_content_2":"[{\"id\":\"container-${i}\",\"name\":\"container\",\"children\":[\"text-${i}\"],\"settings\":{}},{\"id\":\"text-${i}\",\"name\":\"text-basic\",\"parent\":\"container-${i}\",\"settings\":{\"text\":\"<p>Sample content for Test Post ${i}.</p>\"}}]"}'
  done
  echo "[create-test-content] ✓ Created ${count} posts"
}

create_pages() {
  local count="$1"
  echo "[create-test-content] Creating ${count} test pages..."
  for ((i = 1; i <= count; i++)); do
    ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp post create \
      --path="${BRICKS_SITE_PATH}" \
      --post_type=page \
      --post_title="Test Page ${i}" \
      --post_status=publish \
      --meta_input='{"_bricks_editor_mode":"bricks","_bricks_page_content_2":"[{\"id\":\"section-${i}\",\"name\":\"section\",\"children\":[\"container-${i}\"],\"settings\":{\"background\":{\"color\":\"#f5f5f5\"}}},{\"id\":\"container-${i}\",\"name\":\"container\",\"parent\":\"section-${i}\",\"children\":[\"heading-${i}\",\"text-${i}\"],\"settings\":{}},{\"id\":\"heading-${i}\",\"name\":\"heading\",\"parent\":\"container-${i}\",\"settings\":{\"text\":\"Landing Page ${i}\",\"tag\":\"h1\"}},{\"id\":\"text-${i}\",\"name\":\"text-basic\",\"parent\":\"container-${i}\",\"settings\":{\"text\":\"<p>This is a test page with Bricks layout.</p>\"}}]"}'
  done
  echo "[create-test-content] ✓ Created ${count} pages"
}

create_global_classes() {
  echo "[create-test-content] Creating Bricks Global Classes..."
  local classes_json='[{"id":"test-class-1","name":"primary-button","settings":{"styles":{"typography":{"font_size":{"value":18,"unit":"px"}},"background":{"color":"#0066ff"},"border":{"radius":{"value":4,"unit":"px"}},"spacing":{"padding":{"top":{"value":12,"unit":"px"},"right":{"value":24,"unit":"px"},"bottom":{"value":12,"unit":"px"},"left":{"value":24,"unit":"px"}}}}},{"id":"test-class-2","name":"card","settings":{"styles":{"background":{"color":"#ffffff"},"border":{"radius":{"value":8,"unit":"px"},"width":{"value":1,"unit":"px"},"color":"#e5e5e5"},"box_shadow":{"css":"0 12px 24px rgba(0,0,0,0.08)"},"spacing":{"padding":{"top":{"value":24,"unit":"px"},"right":{"value":24,"unit":"px"},"bottom":{"value":24,"unit":"px"},"left":{"value":24,"unit":"px"}}}}}}]'
  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp option update bricks_global_classes "${classes_json}" --path="${BRICKS_SITE_PATH}" 2>/dev/null || echo "[create-test-content] WARNING: Could not create global classes"
  echo "[create-test-content] ✓ Created global classes"
}

create_media() {
  local media_dir="${MEDIA_DIR:-/scripts/test-images}"
  if ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" sh -c "test -d '${media_dir}'" >/dev/null 2>&1; then
    ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp media import "${media_dir}"/*.jpg --path="${BRICKS_SITE_PATH}" --featured_image || true
  else
    echo "[create-test-content] Media directory ${media_dir} not found. Skipping media import."
  fi
}

create_cpts() {
  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp scaffold post-type product --labels="name=Products,singular_name=Product" --path="${BRICKS_SITE_PATH}" --force || true
  ${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp post create --path="${BRICKS_SITE_PATH}" --post_type=product --post_title="Sample Product" --post_status=publish || true
}

main() {
  if ! ${DOCKER_COMPOSE_BIN} ps >/dev/null 2>&1; then
    echo "[create-test-content] ERROR: Docker containers not running" >&2
    exit 1
  fi
  
  echo "[create-test-content] Creating demo data on Bricks instance"
  echo ""

  create_posts 10
  create_pages 5
  create_global_classes
  create_media
  create_cpts

  local posts pages media
  posts=$(${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp post list --post_type=post --format=count --path="${BRICKS_SITE_PATH}" | tr -d '\r')
  pages=$(${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp post list --post_type=page --format=count --path="${BRICKS_SITE_PATH}" | tr -d '\r')
  media=$(${DOCKER_COMPOSE_BIN} exec -T "${WPCLI_SERVICE}" wp media list --format=count --path="${BRICKS_SITE_PATH}" | tr -d '\r')

  cat <<EOF
[create-test-content] Summary:
  Posts : ${posts}
  Pages : ${pages}
  Media : ${media}
EOF
}

main "$@"
