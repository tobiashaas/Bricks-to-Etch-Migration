# Migration Flow Test Guide

## Übersicht

Dieser Guide führt dich durch den kompletten Test des Migration-Flows von der Token-Generierung bis zur Datenübertragung.

## Voraussetzungen

✅ Docker-Container laufen (geprüft)
✅ Testdaten wurden erstellt:
- 17 Posts auf Bricks-Seite
- 8 Pages auf Bricks-Seite

## Test-Schritte

### 1. Migration Key generieren (Etch-Seite)

1. **Browser öffnen**: http://localhost:8081/wp-admin
2. **Login**: 
   - Username: `admin`
   - Password: `admin`
3. **Navigation**: Menü → `B2E Migration` → `Etch Site`
4. **Key generieren**: Klick auf `Generate Migration Key`
5. **Key kopieren**: Der generierte Key sollte etwa so aussehen:
   ```
   http://localhost:8081?domain=http://localhost:8081&token=...&expires=...
   ```

**Erwartetes Ergebnis**: ✅ Migration Key wird angezeigt und kann kopiert werden

---

### 2. Migration Key validieren (Bricks-Seite)

1. **Neuer Browser-Tab**: http://localhost:8080/wp-admin
2. **Login**: 
   - Username: `admin`
   - Password: `admin`
3. **Navigation**: Menü → `B2E Migration`
4. **Key einfügen**: 
   - Klick in das Eingabefeld "Migration Key"
   - Einfügen des kopierten Keys (Strg+V / Cmd+V)
5. **Validieren**: Klick auf `🔗 Validate Key`

**Erwartetes Ergebnis**: 
- ✅ Toast-Nachricht: "Migration token validated successfully! Ready to migrate."
- ✅ Info-Box erscheint mit:
  - ✅ Migration Token Valid
  - Target Site: http://localhost:8081
  - Status: Connected and ready
  - Token expires: [Datum/Zeit]

**Bei Fehler**:
- ❌ Prüfe ob beide Container laufen: `docker ps | grep b2e`
- ❌ Prüfe Browser-Konsole (F12) auf JavaScript-Fehler
- ❌ Prüfe ob der Key korrekt kopiert wurde (keine Leerzeichen)

---

### 3. Migration starten

1. **Monitor-Skript starten** (in neuem Terminal):
   ```bash
   cd /Users/tobiashaas/bricks-etch-migration
   ./monitor-migration.sh
   ```
   
2. **Migration starten**: Klick auf `🚀 Start Migration`

**Erwartetes Ergebnis**:
- ✅ Toast-Nachricht: "Migration started successfully!"
- ✅ Progress-Sektion wird sichtbar
- ✅ Im Monitor-Terminal erscheinen Fortschritts-Updates

**Monitor-Output sollte zeigen**:
```
[HH:MM:SS] 🔄 Status: running | Progress: 10% | Step: validation
[HH:MM:SS] Message: Validating migration requirements...
[HH:MM:SS] 🔄 Status: running | Progress: 20% | Step: analyzing
[HH:MM:SS] Message: Analyzing Bricks content...
...
[HH:MM:SS] ✅ Status: completed | Progress: 100% | Step: completed
[HH:MM:SS] Message: Migration completed successfully!
```

---

### 4. Datenübertragung verifizieren

Nach Abschluss der Migration:

#### 4.1 Über Browser (Etch-Seite)

1. **Etch-Admin öffnen**: http://localhost:8081/wp-admin
2. **Posts prüfen**: Menü → Posts → All Posts
3. **Pages prüfen**: Menü → Pages → All Pages

**Erwartetes Ergebnis**:
- ✅ Neue Posts von der Bricks-Seite sind sichtbar
- ✅ Neue Pages von der Bricks-Seite sind sichtbar
- ✅ Content wurde korrekt übertragen

#### 4.2 Über WP-CLI

```bash
# Posts auf Etch-Seite zählen
docker exec b2e-etch wp post list --post_type=post --format=count --allow-root

# Pages auf Etch-Seite zählen
docker exec b2e-etch wp post list --post_type=page --format=count --allow-root

# Letzte 5 Posts anzeigen
docker exec b2e-etch wp post list --post_type=post --posts_per_page=5 --allow-root
```

#### 4.3 Über REST API

```bash
# Posts auf Etch-Seite abrufen
curl -s "http://localhost:8081/wp-json/wp/v2/posts?per_page=5" | python3 -m json.tool

# Pages auf Etch-Seite abrufen
curl -s "http://localhost:8081/wp-json/wp/v2/pages?per_page=5" | python3 -m json.tool
```

---

### 5. Migration Report generieren

1. **Im Bricks-Admin**: Scroll nach unten zur Sektion "Migration Report"
2. **Report generieren**: Klick auf `📊 Generate Report`

**Erwartetes Ergebnis**:
- ✅ Report wird angezeigt mit:
  - Total Posts
  - Total Pages
  - Media Files
  - ACF Field Groups (falls vorhanden)
  - Custom Post Types (falls vorhanden)

---

## Troubleshooting

### Problem: Token-Validierung schlägt fehl

**Lösung**:
```bash
# Prüfe ob Plugin auf Etch-Seite aktiv ist
docker exec b2e-etch wp plugin list --allow-root

# Prüfe API-Endpoint
curl -X POST "http://localhost:8081/wp-json/b2e/v1/validate" \
  -H "Content-Type: application/json" \
  -d '{"token":"test","domain":"http://localhost:8081","expires":"9999999999"}'
```

### Problem: Migration startet nicht

**Lösung**:
```bash
# Prüfe PHP-Fehler im Container
docker logs b2e-bricks --tail=50

# Prüfe WordPress Debug-Log
docker exec b2e-bricks tail -f /var/www/html/wp-content/debug.log
```

### Problem: Keine Daten werden übertragen

**Lösung**:
```bash
# Prüfe ob Bricks-Posts vorhanden sind
docker exec b2e-bricks wp post list --post_type=post --allow-root

# Prüfe Migration-Progress
docker exec b2e-bricks wp option get b2e_migration_progress --format=json --allow-root

# Prüfe Error-Log
docker exec b2e-bricks wp option get b2e_error_log --format=json --allow-root
```

---

## Erfolgs-Kriterien

Die Migration ist erfolgreich, wenn:

- ✅ Migration Key kann generiert werden
- ✅ Token-Validierung funktioniert
- ✅ Migration startet ohne Fehler
- ✅ Progress-Updates erscheinen im Monitor
- ✅ Migration erreicht 100% Completion
- ✅ Daten sind auf Etch-Seite sichtbar
- ✅ Content ist korrekt formatiert
- ✅ Keine Fehler in den Logs

---

## Nächste Schritte nach erfolgreichem Test

Wenn der Test erfolgreich ist:

1. ✅ Todo-Datei aktualisieren
2. ✅ Dokumentieren welche Content-Typen funktionieren
3. ✅ Identifizieren was noch fehlt:
   - Content-Konvertierung (Bricks → Gutenberg/Etch)
   - CSS-Konvertierung
   - Media-Migration
   - ACF-Migration
   - Custom Post Types

4. ✅ Prioritäten für nächste Entwicklungsschritte setzen

---

## Logs und Debugging

### Browser-Konsole (F12)
- Zeigt JavaScript-Fehler
- Zeigt AJAX-Requests
- Zeigt Debug-Ausgaben (🔍 B2E Debug)

### Monitor-Skript
- Zeigt Migration-Progress in Echtzeit
- Zeigt Status-Änderungen
- Zeigt Fehlermeldungen

### Docker-Logs
```bash
# Bricks-Container
docker logs b2e-bricks --tail=100 -f

# Etch-Container
docker logs b2e-etch --tail=100 -f
```

### WordPress-Optionen
```bash
# Migration Progress
docker exec b2e-bricks wp option get b2e_migration_progress --format=json --allow-root

# Migration Stats
docker exec b2e-bricks wp option get b2e_migration_stats --format=json --allow-root

# Error Log
docker exec b2e-bricks wp option get b2e_error_log --format=json --allow-root
```
