#!/bin/bash

echo "================================================"
echo "Etch Cleanup Script"
echo "================================================"
echo ""
echo "This will delete all posts, pages, attachments,"
echo "styles, and clear the cache in Etch."
echo ""
echo "⚠️  WARNING: This action cannot be undone!"
echo ""
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "🧹 Starting cleanup..."
echo ""

# Delete all posts, pages, and attachments
echo "📄 Deleting posts, pages, and attachments..."
POST_COUNT=$(docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=post,page,attachment --format=ids --allow-root 2>/dev/null) --force --allow-root 2>&1 | grep -c "Success" 2>/dev/null)
echo "   ✅ Deleted $POST_COUNT items"

# Delete etch_styles option
echo "🎨 Deleting Etch styles..."
docker exec b2e-etch wp option delete etch_styles --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Styles deleted" || echo "   ℹ️  No styles to delete"

# Clear WordPress cache
echo "🗑️  Clearing WordPress cache..."
docker exec b2e-etch wp cache flush --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Cache cleared" || echo "   ℹ️  No cache to clear"

# Clear transients (optional but recommended)
echo "⏱️  Clearing transients..."
docker exec b2e-etch wp transient delete --all --allow-root 2>&1 | grep -q "Success" && echo "   ✅ Transients cleared" || echo "   ℹ️  No transients to clear"

echo ""
echo "================================================"
echo "✅ Cleanup Complete!"
echo "================================================"
echo ""
echo "Etch is now ready for a fresh migration."
echo ""
