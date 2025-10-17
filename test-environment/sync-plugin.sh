#!/bin/bash

# Synchronisiere das Plugin zwischen den WordPress-Instanzen

echo "🔄 Synchronisiere Bricks to Etch Migration Plugin..."

# Plugin-Pfad prüfen
PLUGIN_PATH="../bricks-etch-migration"
if [ ! -d "$PLUGIN_PATH" ]; then
    echo "❌ Plugin-Ordner nicht gefunden: $PLUGIN_PATH"
    echo "   Bitte führe dieses Script aus dem test-environment/ Ordner aus"
    exit 1
fi

# Symlinks aktualisieren
echo "🔗 Aktualisiere Plugin-Symlinks..."

# Alte Symlinks entfernen
rm -f wordpress-bricks/wp-content/plugins/bricks-etch-migration
rm -f wordpress-etch/wp-content/plugins/bricks-etch-migration

# Neue Symlinks erstellen
ln -sf "$(realpath $PLUGIN_PATH)" wordpress-bricks/wp-content/plugins/bricks-etch-migration
ln -sf "$(realpath $PLUGIN_PATH)" wordpress-etch/wp-content/plugins/bricks-etch-migration

echo "✅ Plugin-Symlinks aktualisiert!"

# Optional: WordPress-Cache leeren (falls Container laufen)
if docker ps | grep -q b2e-bricks; then
    echo "🧹 Leere WordPress-Cache..."
    docker exec b2e-bricks rm -rf /var/www/html/wp-content/cache/* 2>/dev/null || true
    docker exec b2e-etch rm -rf /var/www/html/wp-content/cache/* 2>/dev/null || true
    echo "✅ Cache geleert!"
fi

echo ""
echo "🎉 Plugin-Synchronisation abgeschlossen!"
echo "💡 Änderungen sind sofort in beiden WordPress-Instanzen verfügbar."
