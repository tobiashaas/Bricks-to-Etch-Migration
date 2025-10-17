#!/bin/bash

# Setze die Testumgebung komplett zurück

echo "🧹 Setze Bricks to Etch Migration Testumgebung zurück..."

# Bestätigung
read -p "⚠️  Dies wird ALLE Daten löschen. Fortfahren? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Abgebrochen."
    exit 1
fi

# Container stoppen und entfernen
echo "🐳 Stoppe und entferne Container..."
docker-compose down -v

# Volumes entfernen
echo "🗑️  Entferne Docker Volumes..."
docker volume rm test-environment_mysql-bricks-data test-environment_mysql-etch-data 2>/dev/null || true

# WordPress-Ordner leeren
echo "📁 Lösche WordPress-Dateien..."
rm -rf wordpress-bricks/*
rm -rf wordpress-etch/*

echo "✅ Testumgebung zurückgesetzt!"
echo ""
echo "💡 Führe './setup.sh' aus, um eine neue Umgebung zu erstellen."
