#!/bin/bash

echo "================================================"
echo "Etch Cleanup Script - COMPLETE"
echo "================================================"
echo ""
echo "This will delete ALL migrated content:"
echo "  - Posts, Pages, Attachments (except reference post 3411)"
echo "  - ALL Etch Styles (etch_styles) - will be regenerated"
echo "  - Migration tracking (b2e_style_map, b2e_migrated_posts)"
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
echo "🧹 Starting complete cleanup..."
echo ""

# Delete all posts, pages, and attachments (EXCEPT reference post 3411)
echo "📄 Deleting posts, pages, and attachments..."
echo "   ℹ️  Keeping reference post ID 3411 (Claude Test)"
POST_IDS=$(docker exec b2e-etch wp post list --post_type=post,page,attachment --format=ids --allow-root 2>/dev/null)
if [ -n "$POST_IDS" ]; then
    # Filter out post 3411 from deletion
    FILTERED_IDS=$(echo $POST_IDS | tr ' ' '\n' | grep -v "^3411$" | tr '\n' ' ')
    if [ -n "$FILTERED_IDS" ]; then
        POST_COUNT=$(docker exec b2e-etch wp post delete $FILTERED_IDS --force --allow-root 2>&1 | grep -c "Success" 2>/dev/null)
        echo "   ✅ Deleted $POST_COUNT items (kept post 3411)"
    else
        echo "   ℹ️  No posts to delete (only reference post 3411 exists)"
    fi
else
    echo "   ℹ️  No posts to delete"
fi

# Delete ALL Etch styles (will be regenerated during migration)
echo "🎨 Deleting Etch styles..."
STYLES_DELETED=$(docker exec b2e-etch wp option get etch_styles --allow-root 2>/dev/null | wc -l)
docker exec b2e-etch wp option delete etch_styles --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Etch styles deleted (${STYLES_DELETED} entries)" || echo "   ℹ️  No styles to delete"

# Delete b2e_style_map (Bricks ID → Etch ID mapping)
echo "🗺️  Deleting style map..."
docker exec b2e-etch wp option delete b2e_style_map --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Style map deleted" || echo "   ℹ️  No style map to delete"

# Delete b2e_migrated_posts (migration tracking)
echo "📋 Deleting migration tracking..."
docker exec b2e-etch wp option delete b2e_migrated_posts --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Migration tracking deleted" || echo "   ℹ️  No tracking data to delete"

# Increment etch_svg_version (force CSS reload)
echo "🔄 Incrementing Etch version..."
CURRENT_VERSION=$(docker exec b2e-etch wp option get etch_svg_version --allow-root 2>/dev/null || echo "1")
NEW_VERSION=$((CURRENT_VERSION + 1))
docker exec b2e-etch wp option update etch_svg_version $NEW_VERSION --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Version updated to $NEW_VERSION" || echo "   ℹ️  Version not updated"

# Clear WordPress cache
echo "🗑️  Clearing WordPress cache..."
docker exec b2e-etch wp cache flush --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Cache cleared" || echo "   ℹ️  No cache to clear"

# Clear transients
echo "⏱️  Clearing transients..."
docker exec b2e-etch wp transient delete --all --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Transients cleared" || echo "   ℹ️  No transients to clear"

# Verify cleanup
echo ""
echo "🔍 Verifying cleanup..."
POST_COUNT=$(docker exec b2e-etch wp post list --post_type=post,page,attachment --format=count --allow-root 2>/dev/null)
echo "   📊 Remaining posts: $POST_COUNT"

echo ""
echo "================================================"
echo "✅ Complete Cleanup Finished!"
echo "================================================"
echo ""
echo "Etch is now completely clean and ready for a fresh migration."
echo "You can start the migration at:"
echo "http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration"
echo ""
