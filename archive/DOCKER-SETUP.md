# Docker Setup - Bricks to Etch Migration

## 🐳 Übersicht

Das Projekt verwendet zwei separate WordPress-Installationen in Docker-Containern:

- **Bricks-Seite**: Quell-Installation mit Bricks Builder
- **Etch-Seite**: Ziel-Installation mit Etch PageBuilder

## 📦 Container-Konfiguration

### Container-Namen und URLs

| Container | Name | URL (Host) | URL (intern) |
|-----------|------|------------|--------------|
| Bricks | `b2e-bricks` | http://localhost:8080 | http://b2e-bricks |
| Etch | `b2e-etch` | http://localhost:8081 | http://b2e-etch |
| MySQL (Bricks) | `b2e-bricks-db` | - | - |
| MySQL (Etch) | `b2e-etch-db` | - | - |

### Netzwerk

Alle Container sind im gleichen Docker-Netzwerk (`b2e-network`) verbunden:
- Container können sich gegenseitig über ihre Namen erreichen
- Bricks kann Etch über `http://b2e-etch` erreichen (nicht `localhost:8081`)

## 📁 Datei-Struktur

### Lokales Dateisystem

```
/Users/tobiashaas/bricks-etch-migration/
├── bricks-etch-migration/          # Plugin-Quellcode (HIER BEARBEITEN!)
│   ├── bricks-etch-migration.php
│   ├── includes/
│   │   ├── admin_interface.php
│   │   ├── api_client.php
│   │   ├── api_endpoints.php
│   │   ├── content_parser.php
│   │   ├── css_converter.php
│   │   ├── gutenberg_generator.php
│   │   ├── media_migrator.php
│   │   └── migration_manager.php
│   └── assets/
│       ├── css/admin.css
│       └── js/admin.js
├── docker-compose.yml
├── cleanup-etch.sh
└── DOCKER-SETUP.md (diese Datei)
```

### Container-Dateisystem

```
/var/www/html/wp-content/plugins/bricks-etch-migration/
├── bricks-etch-migration.php
├── includes/
│   └── ... (alle PHP-Dateien)
└── assets/
    └── ... (CSS/JS)
```

## 🔄 Workflow: Dateien aktualisieren

### ✨ Live-Entwicklung (EMPFOHLEN)

Das Plugin-Verzeichnis ist **direkt gemountet**! Alle Änderungen sind **sofort** in beiden Containern sichtbar:

```bash
# Datei lokal bearbeiten
vim /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/DATEI.php

# ✅ Änderungen sind SOFORT im Container sichtbar!
# KEIN Kopieren nötig!
```

**Wichtig:** Nach Änderungen nur den Cache leeren (siehe unten).

### 1. Datei lokal bearbeiten

Bearbeite die Dateien **NUR** im lokalen Verzeichnis:
```
/Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/
```

**NIEMALS** direkt in den Containern bearbeiten!

### 2. ~~Dateien in Container kopieren~~ (NICHT MEHR NÖTIG!)

~~Nach jeder Änderung müssen die Dateien in **BEIDE** Container kopiert werden:~~

**UPDATE:** Das Plugin-Verzeichnis ist jetzt in der `docker-compose.yml` als Volume gemountet. Kopieren ist **nicht mehr nötig**!

```bash
# Einzelne Datei aktualisieren
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/DATEINAME.php | \
docker exec -i b2e-bricks tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/DATEINAME.php > /dev/null && \
docker exec -i b2e-etch tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/DATEINAME.php < /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/DATEINAME.php > /dev/null
```

### 3. Cache leeren

Nach dem Kopieren **IMMER** den Cache leeren:

```bash
# OPcache leeren (PHP)
docker exec b2e-bricks bash -c "php -r 'opcache_reset();'" 2>/dev/null
docker exec b2e-etch bash -c "php -r 'opcache_reset();'" 2>/dev/null

# WordPress Cache leeren
docker exec b2e-bricks wp cache flush --allow-root
docker exec b2e-etch wp cache flush --allow-root
```

### 4. Kompletter Update-Befehl

Für häufig geänderte Dateien:

```bash
# gutenberg_generator.php
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/gutenberg_generator.php | \
docker exec -i b2e-bricks tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/gutenberg_generator.php > /dev/null && \
docker exec -i b2e-etch tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/gutenberg_generator.php < /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/gutenberg_generator.php > /dev/null && \
docker exec b2e-bricks bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-etch bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-bricks wp cache flush --allow-root && \
docker exec b2e-etch wp cache flush --allow-root && \
echo "✅ gutenberg_generator.php updated!"

# content_parser.php
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/content_parser.php | \
docker exec -i b2e-bricks tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/content_parser.php > /dev/null && \
docker exec -i b2e-etch tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/content_parser.php < /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/content_parser.php > /dev/null && \
docker exec b2e-bricks bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-etch bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-bricks wp cache flush --allow-root && \
docker exec b2e-etch wp cache flush --allow-root && \
echo "✅ content_parser.php updated!"

# css_converter.php
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/css_converter.php | \
docker exec -i b2e-bricks tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/css_converter.php > /dev/null && \
docker exec -i b2e-etch tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/css_converter.php < /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/css_converter.php > /dev/null && \
docker exec b2e-bricks bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-etch bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-bricks wp cache flush --allow-root && \
docker exec b2e-etch wp cache flush --allow-root && \
echo "✅ css_converter.php updated!"
```

## 🛠️ Nützliche Docker-Befehle

### Container-Verwaltung

```bash
# Container starten
docker-compose up -d

# Container stoppen
docker-compose down

# Container neu starten
docker-compose restart

# Container-Status prüfen
docker-compose ps

# Logs anzeigen
docker-compose logs -f b2e-bricks
docker-compose logs -f b2e-etch
```

### WordPress CLI (WP-CLI)

```bash
# Plugin-Status prüfen
docker exec b2e-bricks wp plugin list --allow-root
docker exec b2e-etch wp plugin list --allow-root

# Plugin aktivieren/deaktivieren
docker exec b2e-bricks wp plugin activate bricks-etch-migration --allow-root
docker exec b2e-etch wp plugin activate bricks-etch-migration --allow-root

# Posts/Pages auflisten
docker exec b2e-bricks wp post list --post_type=page --allow-root
docker exec b2e-etch wp post list --post_type=page --allow-root

# Option lesen
docker exec b2e-etch wp option get etch_styles --allow-root
docker exec b2e-bricks wp option get bricks_global_classes --allow-root

# Option löschen
docker exec b2e-etch wp option delete etch_styles --allow-root
docker exec b2e-etch wp option delete b2e_style_map --allow-root
```

### Datenbank-Zugriff

```bash
# MySQL-Shell öffnen
docker exec -it b2e-bricks-db mysql -u wordpress -pwordpress wordpress
docker exec -it b2e-etch-db mysql -u wordpress -pwordpress wordpress

# SQL-Query ausführen
docker exec b2e-bricks-db mysql -u wordpress -pwordpress wordpress -e "SELECT * FROM wp_posts WHERE post_type='page' LIMIT 5;"
```

### Debugging

```bash
# Debug-Log anzeigen (Bricks)
docker exec b2e-bricks tail -f /var/www/html/wp-content/debug.log

# Debug-Log anzeigen (Etch)
docker exec b2e-etch tail -f /var/www/html/wp-content/debug.log

# Debug-Log filtern
docker exec b2e-bricks tail -200 /var/www/html/wp-content/debug.log | grep "B2E:"

# PHP-Fehler anzeigen
docker exec b2e-bricks tail -f /var/log/apache2/error.log
docker exec b2e-etch tail -f /var/log/apache2/error.log
```

## 🧹 Cleanup-Befehle

### Etch-Seite komplett zurücksetzen

```bash
# Alle migrierten Inhalte löschen
docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=page,post,attachment --format=ids --allow-root) --force --allow-root

# Etch Styles löschen
docker exec b2e-etch wp option delete etch_styles --allow-root

# Migration-Tracking löschen
docker exec b2e-etch wp option delete b2e_style_map --allow-root
docker exec b2e-etch wp option delete b2e_migrated_posts --allow-root

# Cache leeren
docker exec b2e-etch wp cache flush --allow-root
```

### Oder Cleanup-Script verwenden

```bash
./cleanup-etch.sh
```

## 🔍 Troubleshooting

### Problem: Änderungen werden nicht übernommen

**Lösung:**
1. Datei lokal gespeichert?
2. Update-Befehl ausgeführt?
3. Cache geleert?
4. Browser-Cache geleert (Hard Refresh: Cmd+Shift+R)?

### Problem: Plugin nicht gefunden

**Lösung:**
```bash
# Plugin-Verzeichnis prüfen
docker exec b2e-bricks ls -la /var/www/html/wp-content/plugins/bricks-etch-migration/

# Plugin neu aktivieren
docker exec b2e-bricks wp plugin deactivate bricks-etch-migration --allow-root
docker exec b2e-bricks wp plugin activate bricks-etch-migration --allow-root
```

### Problem: Container läuft nicht

**Lösung:**
```bash
# Container-Status prüfen
docker-compose ps

# Container neu starten
docker-compose restart

# Logs prüfen
docker-compose logs b2e-bricks
docker-compose logs b2e-etch
```

### Problem: Datenbank-Verbindung fehlgeschlagen

**Lösung:**
```bash
# Datenbank-Container prüfen
docker-compose ps b2e-bricks-db
docker-compose ps b2e-etch-db

# Datenbank neu starten
docker-compose restart b2e-bricks-db b2e-etch-db
```

## 📊 Performance-Tipps

### OPcache-Konfiguration

OPcache ist aktiviert für bessere Performance. Nach Datei-Änderungen **IMMER** leeren:

```bash
docker exec b2e-bricks bash -c "php -r 'opcache_reset();'"
docker exec b2e-etch bash -c "php -r 'opcache_reset();'"
```

### WordPress-Cache

WordPress Object Cache ist aktiviert. Nach Änderungen leeren:

```bash
docker exec b2e-bricks wp cache flush --allow-root
docker exec b2e-etch wp cache flush --allow-root
```

## 🔐 Zugangsdaten

### WordPress Admin

- **Bricks**: http://localhost:8080/wp-admin
  - User: `admin`
  - Pass: `admin`

- **Etch**: http://localhost:8081/wp-admin
  - User: `admin`
  - Pass: `admin`

### Datenbank

- **Host**: `b2e-bricks-db` / `b2e-etch-db`
- **User**: `wordpress`
- **Pass**: `wordpress`
- **DB**: `wordpress`

## 📝 Best Practices

1. **Immer lokal bearbeiten** - Niemals direkt in Containern
2. **Cache leeren** - Nach jeder Änderung
3. **Logs prüfen** - Bei Problemen zuerst die Logs checken
4. **Backup** - Vor großen Änderungen Container-Snapshot erstellen
5. **Testing** - Auf Etch-Seite testen, dann auf Bricks anwenden

## 🚀 Schnell-Referenz

```bash
# Datei aktualisieren + Cache leeren (Template)
cat /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/DATEI.php | \
docker exec -i b2e-bricks tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/DATEI.php > /dev/null && \
docker exec -i b2e-etch tee /var/www/html/wp-content/plugins/bricks-etch-migration/includes/DATEI.php < /Users/tobiashaas/bricks-etch-migration/bricks-etch-migration/includes/DATEI.php > /dev/null && \
docker exec b2e-bricks bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-etch bash -c "php -r 'opcache_reset();'" 2>/dev/null && \
docker exec b2e-bricks wp cache flush --allow-root && \
docker exec b2e-etch wp cache flush --allow-root && \
echo "✅ DATEI.php updated!"

# Etch komplett zurücksetzen
docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=page,post,attachment --format=ids --allow-root) --force --allow-root && \
docker exec b2e-etch wp option delete etch_styles --allow-root && \
docker exec b2e-etch wp option delete b2e_style_map --allow-root && \
docker exec b2e-etch wp cache flush --allow-root && \
echo "✅ Etch cleaned!"

# Debug-Log live anzeigen
docker exec b2e-bricks tail -f /var/www/html/wp-content/debug.log | grep "B2E:"
```

---

**Letzte Aktualisierung**: 21. Oktober 2025
**Version**: 1.0
