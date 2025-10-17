#!/bin/bash

# Überwache Plugin-Änderungen und synchronisiere automatisch

echo "👀 Überwache Bricks to Etch Migration Plugin-Änderungen..."
echo "   Drücke Ctrl+C zum Beenden"
echo ""

# Prüfen ob fswatch installiert ist
if ! command -v fswatch &> /dev/null; then
    echo "⚠️  fswatch ist nicht installiert. Installiere es mit:"
    echo "   macOS: brew install fswatch"
    echo "   Ubuntu: sudo apt install fswatch"
    echo ""
    echo "🔄 Führe manuelle Synchronisation durch..."
    ./sync-plugin.sh
    exit 0
fi

# Plugin-Pfad
PLUGIN_PATH="../bricks-etch-migration"

echo "📁 Überwache: $PLUGIN_PATH"
echo ""

# fswatch starten
fswatch -o "$PLUGIN_PATH" | while read f; do
    echo "🔄 Änderung erkannt, synchronisiere..."
    ./sync-plugin.sh
    echo "✅ Synchronisation abgeschlossen!"
    echo ""
done
