# Migration Testing Tools

Automatisierte Test-Tools für den Bricks to Etch Migration-Flow.

## Schnellstart

```bash
# 1. Testdaten erstellen
./prepare-test-data.sh

# 2. Migration über Browser starten (siehe MIGRATION-TEST-GUIDE.md)
#    - http://localhost:8081/wp-admin (Etch: Key generieren)
#    - http://localhost:8080/wp-admin (Bricks: Migration starten)

# 3. Migration überwachen (in separatem Terminal)
./monitor-migration.sh

# 4. Ergebnisse verifizieren
./verify-migration.sh
```

## Verfügbare Skripte

### `prepare-test-data.sh`
Erstellt Testdaten auf der Bricks-Seite für Migration-Tests.

**Was es tut:**
- Erstellt 3 Test-Posts mit Bricks-Metadaten
- Erstellt 2 Test-Pages mit Bricks-Metadaten
- Zeigt aktuelle Content-Counts

**Verwendung:**
```bash
./prepare-test-data.sh
```

---

### `monitor-migration.sh`
Überwacht eine laufende Migration in Echtzeit.

**Was es zeigt:**
- Migration Status (running/completed/error)
- Progress Percentage (0-100%)
- Aktuelle Schritte und Messages
- Migration Statistics bei Completion

**Verwendung:**
```bash
# In separatem Terminal starten
./monitor-migration.sh

# Dann Migration über Browser starten
```

**Ausgabe-Beispiel:**
```
[21:30:15] 🔄 Status: running | Progress: 20% | Step: analyzing
[21:30:15] Message: Analyzing Bricks content...
[21:30:17] 🔄 Status: running | Progress: 30% | Step: cpts
[21:30:17] Message: Migrating custom post types...
...
[21:30:45] ✅ Status: completed | Progress: 100% | Step: completed
[21:30:45] Message: Migration completed successfully!
```

---

### `verify-migration.sh`
Verifiziert Migrationsergebnisse nach Abschluss.

**Was es prüft:**
- Content-Counts auf Bricks-Seite (Source)
- Content-Counts auf Etch-Seite (Target)
- Vergleich der Counts
- Migration-Metadaten
- Aktuelle Posts/Pages auf Etch-Seite
- API-Konnektivität

**Verwendung:**
```bash
# Nach Abschluss der Migration
./verify-migration.sh
```

**Ausgabe-Beispiel:**
```
Bricks Site (Source):
  Posts: 17
  Pages: 8
  Media: 5

Etch Site (Target):
  Posts: 17
  Pages: 8
  Media: 5

✅ Posts: Migration successful (17 >= 17)
✅ Pages: Migration successful (8 >= 8)
✅ Media: Migration successful (5 >= 5)
```

---

## Detaillierte Anleitungen

- **MIGRATION-TEST-GUIDE.md** - Komplette Schritt-für-Schritt Anleitung
- **TEST-READY-SUMMARY.md** - Übersicht über Test-Setup und Status

## Voraussetzungen

- Docker-Container müssen laufen
- WordPress muss auf beiden Seiten installiert sein
- Plugin muss auf beiden Seiten aktiviert sein

**Container prüfen:**
```bash
docker ps | grep b2e
# Sollte zeigen: b2e-bricks, b2e-etch, b2e-mysql-bricks, b2e-mysql-etch, b2e-phpmyadmin
```

## Troubleshooting

### Skript-Fehler: "Permission denied"
```bash
chmod +x *.sh
```

### WP-CLI Fehler: "Run as root"
Alle Skripte verwenden bereits `--allow-root`. Falls Fehler auftreten:
```bash
docker exec b2e-bricks wp --info --allow-root
```

### Container nicht erreichbar
```bash
# Container neu starten
cd test-environment
docker-compose restart

# Container-Status prüfen
docker-compose ps
```

### Migration hängt
```bash
# Migration-Progress prüfen
docker exec b2e-bricks wp option get b2e_migration_progress --format=json --allow-root

# Error-Log prüfen
docker exec b2e-bricks wp option get b2e_error_log --format=json --allow-root

# Container-Logs prüfen
docker logs b2e-bricks --tail=50
```

## Manuelle Befehle

### Content-Counts prüfen
```bash
# Bricks-Seite
docker exec b2e-bricks wp post list --post_type=post --format=count --allow-root
docker exec b2e-bricks wp post list --post_type=page --format=count --allow-root

# Etch-Seite
docker exec b2e-etch wp post list --post_type=post --format=count --allow-root
docker exec b2e-etch wp post list --post_type=page --format=count --allow-root
```

### Migration-Status prüfen
```bash
# Progress
docker exec b2e-bricks wp option get b2e_migration_progress --format=json --allow-root | python3 -m json.tool

# Stats
docker exec b2e-bricks wp option get b2e_migration_stats --format=json --allow-root | python3 -m json.tool
```

### API testen
```bash
# Etch API Test
curl -X GET "http://localhost:8081/wp-json/b2e/v1/auth/test"

# Token-Validierung testen
curl -X POST "http://localhost:8081/wp-json/b2e/v1/validate" \
  -H "Content-Type: application/json" \
  -d '{"token":"test","domain":"http://localhost:8081","expires":"9999999999"}'
```

## Login-Daten

**Bricks-Seite:** http://localhost:8080/wp-admin
- Username: `admin`
- Password: `admin`

**Etch-Seite:** http://localhost:8081/wp-admin
- Username: `admin`
- Password: `admin`

## Support

Bei Problemen siehe:
1. **MIGRATION-TEST-GUIDE.md** - Detailliertes Troubleshooting
2. **todo.md** - Bekannte Probleme und Lösungen
3. Docker-Logs: `docker logs b2e-bricks` oder `docker logs b2e-etch`
