#!/bin/bash

echo "================================================"
echo "Etch Cleanup Script - COMPLETE"
echo "================================================"
echo ""
echo "This will delete ALL migrated content:"
echo "  - Posts, Pages, Attachments (except reference post 3411)"
echo "  - ALL Etch Styles (etch_styles) - will be regenerated"
echo "  - Migration tracking (efs_style_map, efs_migrated_posts)"
echo "  - Legacy migration tracking (b2e_style_map, b2e_migrated_posts)"
echo "  - Cache & Transients"
echo ""
echo "⚠️  WARNING: This action cannot be undone!"
echo ""
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Cancelled."
    exit 0
fi

echo ""
lookup_container() {
    for candidate in "$@"; do
        if docker ps -a --format '{{.Names}}' | grep -Fxq "$candidate"; then
            echo "$candidate"
            return 0
        fi
    done
    echo "$1"
}

lookup_volume() {
    for candidate in "$@"; do
        if docker volume ls --format '{{.Name}}' | grep -Fxq "$candidate"; then
            echo "$candidate"
        fi
    done
}

lookup_network() {
    for candidate in "$@"; do
        if docker network ls --format '{{.Name}}' | grep -Fxq "$candidate"; then
            echo "$candidate"
            return 0
        fi
    done
    echo "$1"
}

delete_wp_option() {
    local container="$1"
    local option_name="$2"
    local label="$3"

    if docker exec "$container" wp option get "$option_name" --allow-root >/dev/null 2>&1; then
        docker exec "$container" wp option delete "$option_name" --allow-root >/dev/null 2>&1
        echo "   ✅ Deleted ${label:-option} ($option_name)"
    else
        echo "   ℹ️  No ${label:-option} to delete ($option_name)"
    fi
}

echo "🧹 Starting complete cleanup..."
echo ""

BRICKS_WP=$(lookup_container efs-bricks-wp b2e-bricks-wp)
ETCH_WP=$(lookup_container efs-etch-wp b2e-etch-wp)

# Delete all posts, pages, and attachments (EXCEPT reference post 3411)
echo "📄 Deleting posts, pages, and attachments..."
echo "   ℹ️  Keeping reference post ID 3411 (Claude Test)"
POST_IDS=$(docker exec "$ETCH_WP" wp post list --post_type=post,page,attachment --format=ids --allow-root 2>/dev/null)
if [ -n "$POST_IDS" ]; then
    # Filter out post 3411 from deletion
    FILTERED_IDS=$(echo $POST_IDS | tr ' ' '\n' | grep -v "^3411$" | tr '\n' ' ')
    if [ -n "$FILTERED_IDS" ]; then
        POST_COUNT=$(docker exec "$ETCH_WP" wp post delete $FILTERED_IDS --force --allow-root 2>&1 | grep -c "Success" 2>/dev/null)
        echo "   ✅ Deleted $POST_COUNT items (kept post 3411)"
    else
        echo "   ℹ️  No posts to delete (only reference post 3411 exists)"
    fi
else
    echo "   ℹ️  No posts to delete"
fi

# Delete ALL Etch styles (will be regenerated during migration)
echo "🎨 Deleting Etch styles..."
STYLES_DELETED=$(docker exec "$ETCH_WP" wp option get etch_styles --allow-root 2>/dev/null | wc -l)
docker exec "$ETCH_WP" wp option delete etch_styles --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Etch styles deleted (${STYLES_DELETED} entries)" || echo "   ℹ️  No styles to delete"

# Delete style maps (new + legacy)
echo "🗺️  Deleting style maps..."
delete_wp_option "$ETCH_WP" efs_style_map "current style map"
delete_wp_option "$ETCH_WP" b2e_style_map "legacy style map"

# Delete migration tracking (new + legacy)
echo "📋 Deleting migration tracking..."
delete_wp_option "$ETCH_WP" efs_migrated_posts "migration tracking"
delete_wp_option "$ETCH_WP" b2e_migrated_posts "legacy migration tracking"

# Increment etch_svg_version (force CSS reload)
echo "🔄 Incrementing Etch version..."
CURRENT_VERSION=$(docker exec "$ETCH_WP" wp option get etch_svg_version --allow-root 2>/dev/null || echo "1")
NEW_VERSION=$((CURRENT_VERSION + 1))
docker exec "$ETCH_WP" wp option update etch_svg_version $NEW_VERSION --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Version updated to $NEW_VERSION" || echo "   ℹ️  Version not updated"

# Clear WordPress cache
echo "🗑️  Clearing WordPress cache..."
docker exec "$ETCH_WP" wp cache flush --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Cache cleared" || echo "   ℹ️  No cache to clear"

# Clear transients
echo "⏱️  Clearing transients..."
docker exec "$ETCH_WP" wp transient delete --all --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Transients cleared" || echo "   ℹ️  No transients to clear"

# Verify cleanup
echo ""
echo "🔍 Verifying cleanup..."
POST_COUNT=$(docker exec "$ETCH_WP" wp post list --post_type=post,page,attachment --format=count --allow-root 2>/dev/null)
echo "   📊 Remaining posts: $POST_COUNT"

echo ""
echo "================================================"
echo "✅ Complete Cleanup Finished!"
echo "================================================"
echo ""
echo "Etch is now completely clean and ready for a fresh migration."
echo "You can start the migration at:"
echo "http://localhost:8080/wp-admin/admin.php?page=etch-fusion-suite"
echo ""
