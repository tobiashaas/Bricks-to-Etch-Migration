#!/bin/bash

# Starte die Testumgebung

echo "🚀 Starte Bricks to Etch Migration Testumgebung..."

# Prüfen ob Docker läuft
if ! docker info >/dev/null 2>&1; then
    echo "❌ Docker ist nicht gestartet. Bitte starte Docker zuerst."
    exit 1
fi

# Container starten
docker-compose up -d

echo "✅ Testumgebung gestartet!"
echo ""
echo "📋 Zugriff:"
echo "   🔗 Bricks Site: http://localhost:8080"
echo "   🔗 Etch Site:   http://localhost:8081"
echo "   🔗 phpMyAdmin:  http://localhost:8082"
