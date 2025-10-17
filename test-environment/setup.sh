#!/bin/bash

# Bricks to Etch Migration - Test Environment Setup
# Erstellt automatisch eine vollständige Testumgebung

set -e

echo "🚀 Bricks to Etch Migration - Test Environment Setup"
echo "=================================================="

# Prüfen ob Docker installiert ist
if ! command -v docker &> /dev/null; then
    echo "❌ Docker ist nicht installiert. Bitte installiere Docker zuerst."
    echo "   https://docs.docker.com/get-docker/"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose ist nicht installiert. Bitte installiere Docker Compose zuerst."
    echo "   https://docs.docker.com/compose/install/"
    exit 1
fi

echo "✅ Docker und Docker Compose gefunden"

# Plugin-Pfad prüfen
PLUGIN_PATH="../bricks-etch-migration"
if [ ! -d "$PLUGIN_PATH" ]; then
    echo "❌ Plugin-Ordner nicht gefunden: $PLUGIN_PATH"
    echo "   Bitte führe dieses Script aus dem test-environment/ Ordner aus"
    exit 1
fi

echo "✅ Plugin-Ordner gefunden: $PLUGIN_PATH"

# WordPress-Ordner erstellen
echo "📁 Erstelle WordPress-Ordner..."
mkdir -p wordpress-bricks/wp-content/plugins
mkdir -p wordpress-etch/wp-content/plugins

# Plugin-Symlinks erstellen
echo "🔗 Erstelle Plugin-Symlinks..."
rm -f wordpress-bricks/wp-content/plugins/bricks-etch-migration
rm -f wordpress-etch/wp-content/plugins/bricks-etch-migration

ln -sf "$(realpath $PLUGIN_PATH)" wordpress-bricks/wp-content/plugins/bricks-etch-migration
ln -sf "$(realpath $PLUGIN_PATH)" wordpress-etch/wp-content/plugins/bricks-etch-migration

echo "✅ Plugin-Symlinks erstellt"

# Docker-Container starten
echo "🐳 Starte Docker-Container..."
docker-compose up -d

# PHP Upload-Limits fixen
echo "🔧 Konfiguriere PHP Upload-Limits..."
sleep 10  # Warten bis Container bereit sind

# PHP-Konfiguration in beiden Containern
docker exec b2e-bricks bash -c "
echo 'upload_max_filesize = 256M' > /usr/local/etc/php/conf.d/99-custom.ini
echo 'post_max_size = 256M' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'max_file_uploads = 50' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'max_input_vars = 3000' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'display_errors = On' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'log_errors = On' >> /usr/local/etc/php/conf.d/99-custom.ini
" 2>/dev/null || echo "⚠️  PHP-Konfiguration für Bricks Site übersprungen"

docker exec b2e-etch bash -c "
echo 'upload_max_filesize = 256M' > /usr/local/etc/php/conf.d/99-custom.ini
echo 'post_max_size = 256M' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'max_file_uploads = 50' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'max_input_vars = 3000' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'display_errors = On' >> /usr/local/etc/php/conf.d/99-custom.ini
echo 'log_errors = On' >> /usr/local/etc/php/conf.d/99-custom.ini
" 2>/dev/null || echo "⚠️  PHP-Konfiguration für Etch Site übersprungen"

# Apache neu starten
docker exec b2e-bricks service apache2 reload 2>/dev/null || true
docker exec b2e-etch service apache2 reload 2>/dev/null || true

echo "✅ PHP Upload-Limits konfiguriert (256M)"

# Warten bis WordPress bereit ist
echo "⏳ Warte auf WordPress-Initialisierung..."
sleep 30

# WordPress-Installation durchführen
echo "🔧 Installiere WordPress..."

# Bricks Site
echo "📝 Installiere WordPress auf Bricks Site (Port 8080)..."
curl -X POST "http://localhost:8080/wp-admin/install.php?step=2" \
  -d "weblog_title=Bricks Test Site&user_name=admin&admin_password=admin&admin_password2=admin&admin_email=admin@test.local&Submit=Install+WordPress" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --silent --output /dev/null || echo "⚠️  WordPress-Installation möglicherweise bereits abgeschlossen"

# Etch Site
echo "📝 Installiere WordPress auf Etch Site (Port 8081)..."
curl -X POST "http://localhost:8081/wp-admin/install.php?step=2" \
  -d "weblog_title=Etch Test Site&user_name=admin&admin_password=admin&admin_password2=admin&admin_email=admin@test.local&Submit=Install+WordPress" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --silent --output /dev/null || echo "⚠️  WordPress-Installation möglicherweise bereits abgeschlossen"

# Plugin aktivieren
echo "🔌 Aktiviere B2E Migration Plugin..."

# Bricks Site
curl -X POST "http://localhost:8080/wp-admin/admin-ajax.php" \
  -d "action=activate_plugin&plugin=bricks-etch-migration/bricks-etch-migration.php" \
  --cookie-jar /tmp/bricks-cookies.txt \
  --silent --output /dev/null || echo "⚠️  Plugin-Aktivierung auf Bricks Site übersprungen"

# Etch Site
curl -X POST "http://localhost:8081/wp-admin/admin-ajax.php" \
  -d "action=activate_plugin&plugin=bricks-etch-migration/bricks-etch-migration.php" \
  --cookie-jar /tmp/etch-cookies.txt \
  --silent --output /dev/null || echo "⚠️  Plugin-Aktivierung auf Etch Site übersprungen"

echo ""
echo "🎉 Setup abgeschlossen!"
echo ""
echo "📋 Zugriff auf die Testumgebung:"
echo "   🔗 Bricks Site (Source): http://localhost:8080"
echo "      👤 Admin: admin / admin"
echo "      📁 Admin: http://localhost:8080/wp-admin"
echo ""
echo "   🔗 Etch Site (Target):   http://localhost:8081"
echo "      👤 Admin: admin / admin"
echo "      📁 Admin: http://localhost:8081/wp-admin"
echo ""
echo "   🔗 phpMyAdmin:          http://localhost:8082"
echo "      👤 Admin: root / rootpassword"
echo ""
echo "🔧 Nützliche Befehle:"
echo "   ./start.sh     - Umgebung starten"
echo "   ./stop.sh      - Umgebung stoppen"
echo "   ./reset.sh     - Komplett zurücksetzen"
echo "   ./sync-plugin.sh - Plugin synchronisieren"
echo ""
echo "💡 Tipp: Änderungen am Plugin werden automatisch synchronisiert!"
