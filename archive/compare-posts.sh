#!/bin/bash

# Vergleichs-Skript für native Etch-Posts vs. migrierte Posts

echo "================================================"
echo "Etch Post Comparison Tool"
echo "================================================"
echo ""

# Frage nach Post-IDs
read -p "Native Etch Post ID: " NATIVE_ID
read -p "Migrierter Post ID (z.B. 3388): " MIGRATED_ID

echo ""
echo "🔍 Vergleiche Posts..."
echo ""

# 1. Post Content vergleichen
echo "📄 Post Content Struktur:"
echo "------------------------"
echo ""
echo "NATIVE POST ($NATIVE_ID):"
docker exec b2e-etch wp post get $NATIVE_ID --field=post_content --allow-root 2>/dev/null | head -20
echo ""
echo "MIGRIERTER POST ($MIGRATED_ID):"
docker exec b2e-etch wp post get $MIGRATED_ID --field=post_content --allow-root 2>/dev/null | head -20
echo ""

# 2. Post Meta vergleichen
echo "================================================"
echo "🏷️  Post Meta:"
echo "------------------------"
echo ""
echo "NATIVE POST ($NATIVE_ID):"
docker exec b2e-etch wp post meta list $NATIVE_ID --allow-root 2>/dev/null
echo ""
echo "MIGRIERTER POST ($MIGRATED_ID):"
docker exec b2e-etch wp post meta list $MIGRATED_ID --allow-root 2>/dev/null
echo ""

# 3. Frontend HTML vergleichen
echo "================================================"
echo "🌐 Frontend HTML:"
echo "------------------------"
echo ""

NATIVE_SLUG=$(docker exec b2e-etch wp post get $NATIVE_ID --field=post_name --allow-root 2>/dev/null)
MIGRATED_SLUG=$(docker exec b2e-etch wp post get $MIGRATED_ID --field=post_name --allow-root 2>/dev/null)

echo "NATIVE POST (/$NATIVE_SLUG/):"
curl -s "http://localhost:8081/$NATIVE_SLUG/" | grep -o '<h[1-6][^>]*>.*</h[1-6]>' | head -3
echo ""
echo "MIGRIERTER POST (/$MIGRATED_SLUG/):"
curl -s "http://localhost:8081/$MIGRATED_SLUG/" | grep -o '<h[1-6][^>]*>.*</h[1-6]>' | head -3
echo ""

# 4. Block-Attribute vergleichen
echo "================================================"
echo "🧩 Block-Attribute (erste Heading):"
echo "------------------------"
echo ""
echo "NATIVE POST:"
docker exec b2e-etch wp post get $NATIVE_ID --field=post_content --allow-root 2>/dev/null | grep -o 'wp:heading {[^}]*}' | head -1
echo ""
echo "MIGRIERTER POST:"
docker exec b2e-etch wp post get $MIGRATED_ID --field=post_content --allow-root 2>/dev/null | grep -o 'wp:heading {[^}]*}' | head -1
echo ""

echo "================================================"
echo "✅ Vergleich abgeschlossen!"
echo "================================================"
