# Bricks to Etch Migration - Aktueller Stand

## 🎯 Projektübersicht
Migration-Tool für die Konvertierung von Bricks Builder Websites zu Etch PageBuilder mit vollständiger Automatisierung.

## ✅ Was funktioniert

### Frontend
- ✅ **Paste Key Funktionalität** - Migration Keys können aus der Zwischenablage eingefügt werden
- ✅ **JavaScript-Initialisierung** - Alle Event-Listener werden korrekt registriert
- ✅ **AJAX-Requests werden gesendet** - `admin-ajax.php` Requests sind im Network Tab sichtbar
- ✅ **Toast-Benachrichtigungen** - Erfolgs- und Fehlermeldungen werden angezeigt
- ✅ **Benutzerfreundliche Oberfläche** - Sauberer Input ohne langen Placeholder

### Backend
- ✅ **Plugin-Architektur** - Alle Klassen und Komponenten sind implementiert
- ✅ **AJAX-Handler registriert** - Alle 10 AJAX-Actions sind verfügbar
- ✅ **REST API Endpoints** - API-Endpoints auf der Etch-Seite funktionieren
- ✅ **Docker-Netzwerk-Kommunikation** - Container-zu-Container Kommunikation funktioniert
- ✅ **Direkte API-Validierung** - HTTP-Tests zeigen erfolgreiche Validierung

### Infrastruktur
- ✅ **Docker-Setup** - Beide WordPress-Installationen laufen korrekt
- ✅ **Plugin-Synchronisation** - Plugin wird zwischen Container und Host synchronisiert
- ✅ **Debug-Logging** - Umfangreiches Debug-Logging implementiert

## ✅ Gelöstes Problem

### API-Key-Synchronisation (BEHOBEN)
- ✅ **Migration Keys enthalten nur Token** - Keine API-Keys mehr in URLs
- ✅ **Token-Validierung implementiert** - Separater Validierungs-Endpoint
- ✅ **API-Key wird automatisch generiert** - Auf der Etch-Seite bei Token-Validierung
- ✅ **API-Key wird in Response zurückgegeben** - Bricks-Seite erhält API-Key automatisch

### Technische Details der Lösung
```
Migration Key Format: http://localhost:8081?domain=...&token=...&expires=...
Token-Validierung: POST /wp-json/b2e/v1/validate
Response: {success: true, api_key: "b2e_...", message: "Token validation successful"}
```

## 🔧 Versuchte Lösungen

### 1. JavaScript-Probleme behoben
- ✅ `wp_localize_script` von 'jquery' zu 'b2e-admin-js' geändert
- ✅ Fallback-Script für `b2e_ajax` Variablen implementiert
- ✅ AJAX-Requests werden jetzt gesendet

### 2. Plugin-Initialisierung korrigiert
- ✅ Admin Interface wird immer initialisiert (nicht nur im Admin-Bereich)
- ✅ AJAX-Handler werden korrekt registriert
- ✅ Plugin wird über WordPress-UI aktiviert

### 3. API-Key-Synchronisation versucht
- ✅ API-Keys auf beiden Seiten manuell synchronisiert
- ✅ Migration Key API-Key auf Etch-Seite gesetzt
- ✅ Direkte API-Validierung funktioniert

## 🎯 Nächste Schritte

### Priorität 1: Migration-Flow testen ✅ BEREIT ZUM TESTEN
1. ✅ **Token-Validierung testen** - Migration Key generieren und validieren
2. ✅ **Test-Infrastruktur erstellt** - Skripte und Testdaten vorbereitet
3. ⏳ **Migration manuell testen** - Über Browser-UI durchführen
4. ⏳ **Datenübertragung verifizieren** - Überprüfen dass tatsächlich Daten übertragen werden

### Priorität 2: Vollständige Migration
1. **Alle Content-Typen migrieren** - Posts, Pages, Media, Custom Post Types
2. **Content-Konvertierung** - Bricks zu Gutenberg/Etch Konvertierung
3. **CSS-Konvertierung** - Bricks CSS zu Etch CSS
4. **Fehlerbehandlung** - Robuste Fehlerbehandlung und Rollback

### Priorität 3: Optimierungen
1. **Performance** - Batch-Processing für große Migrationen
2. **UI/UX** - Fortschrittsanzeige verbessern
3. **Dokumentation** - Benutzerhandbuch erstellen

## 📊 Technische Details

### Docker-Setup
- **Bricks-Seite**: `http://localhost:8080` (Container: `b2e-bricks`)
- **Etch-Seite**: `http://localhost:8081` (Container: `b2e-etch`)
- **Interne Kommunikation**: `b2e-etch` statt `localhost:8081`

### Plugin-Struktur
```
bricks-etch-migration/
├── bricks-etch-migration.php (Hauptdatei)
├── includes/
│   ├── admin_interface.php (Frontend + AJAX-Handler)
│   ├── api_endpoints.php (REST API auf Etch-Seite)
│   ├── api_client.php (Kommunikation zwischen Sites)
│   ├── migration_manager.php (Hauptmigration)
│   ├── content_parser.php (Bricks Content Parsing)
│   └── ... (weitere Komponenten)
└── assets/css/admin.css (Styling)
```

### AJAX-Handler
- `b2e_validate_api_key` - API-Key-Validierung
- `b2e_start_migration` - Migration starten
- `b2e_get_migration_progress` - Fortschritt abrufen
- `b2e_generate_report` - Migrationsbericht
- `b2e_clear_logs` - Logs löschen

## 🚨 Blockierende Probleme

1. **API-Key-Mismatch** - Migration Key API-Key ≠ Gespeicherter API-Key
2. **Dynamische API-Keys** - Jeder Migration Key hat einen anderen API-Key
3. **Manuelle Synchronisation** - Aktuell müssen API-Keys manuell synchronisiert werden

## 💡 Lösungsansätze

### Option 1: API-Key aus Migration Key extrahieren
- Migration Key parsen und API-Key extrahieren
- Extrahierte API-Key automatisch auf Etch-Seite setzen
- Dann API-Validierung durchführen

### Option 2: Migration Key Generierung ändern
- Migration Key mit festem API-Key generieren
- API-Key auf beiden Seiten vorher synchronisieren
- Dann Migration Key generieren

### Option 3: API-Key-Validierung umgehen
- API-Key-Validierung temporär deaktivieren
- Direkt mit Migration starten
- API-Key-Problem später lösen

## 📝 Notizen

- **Letzte Änderung**: 21. Oktober 2025, 00:25 Uhr
- **Aktueller Stand**: 🎉 Alle Features implementiert, Frontend-Rendering Problem bleibt
- **Nächster Schritt**: Etch Frontend-Rendering untersuchen (warum werden Klassen entfernt?)
- **Zeitaufwand heute**: ~3 Stunden (Image Labels, Element Labels, className Attribut)
- **Gelöstes Problem**: Element Labels für Structure Panel, Image Klassen, Heading/Paragraph Klassen
- **Offenes Problem**: Etch entfernt Klassen beim Frontend-Rendering trotz korrekter DB-Speicherung

### Test-Ergebnisse (21.10.2025, 00:00-00:25)

**✅ Was funktioniert:**
- ✅ **Element Labels in Etch** - Bricks Labels (z.B. "Media", "Feature heading") werden in `metadata.name` gespeichert
- ✅ **etchData komplett** - Alle Blöcke haben `metadata.etchData` mit origin, name, styles, attributes, block
- ✅ **Klassen in DB** - Alle Etch Style IDs werden korrekt in HTML gespeichert (z.B. `class="ee30338"`)
- ✅ **className Attribut** - Klassen werden auch im Block-Attribut `className` gespeichert
- ✅ **Image Klassen** - Images haben Klassen auf `<img>` Tag (nicht auf `<figure>`)
- ✅ **Heading/Paragraph Klassen** - Alle Text-Elemente haben Etch Style IDs
- ✅ **Style Mapping** - Bricks Class IDs werden korrekt zu Etch Style IDs gemappt
- ✅ **get_element_style_ids()** - Liest aus `_cssGlobalClasses` und verwendet `style_map`

**⚠️ Aktuelles Problem (21.10.2025, 00:25):**
- ⚠️ **Klassen nicht im Frontend** - In DB korrekt (`<h2 class="wp-block-heading 16bcebd">`), aber Frontend zeigt `<h2>` ohne Klassen
- 🔍 **Root Cause**: Etch entfernt Klassen beim Frontend-Rendering (vermutlich Filter oder Etch-spezifisches Rendering)
- 💡 **Mögliche Lösungen**:
  1. Etch-Filter untersuchen die Klassen entfernen
  2. Prüfen ob Styles als "used" markiert werden müssen
  3. Etch REST API verwenden statt direkter DB-Zugriff
  4. Etch Support kontaktieren für Frontend-Rendering Dokumentation

**🔧 Durchgeführte Fixes:**
1. Media-Migration: Besseres Logging (failed/skipped counts)
2. Custom Post Types: Filter für WordPress-Defaults (wp_block, bricks_fonts, etc.)
3. Report: Zeigt jetzt media_failed und media_skipped an

### Erstellte Test-Tools (17.10.2025, 21:00-21:37)

1. **prepare-test-data.sh** - Erstellt Testdaten auf Bricks-Seite
   - 3 Test-Posts mit Bricks-Metadaten
   - 2 Test-Pages mit Bricks-Metadaten
   - Status: ✅ Funktioniert (17 Posts, 8 Pages erstellt)

2. **monitor-migration.sh** - Überwacht Migration in Echtzeit
   - Pollt WordPress-Optionen alle 2 Sekunden
   - Zeigt Status, Progress, Messages
   - Farbcodierte Ausgabe (Running/Completed/Error)

3. **verify-migration.sh** - Verifiziert Migrationsergebnisse
   - Vergleicht Content-Counts (Bricks vs Etch)
   - Prüft Migration-Metadaten
   - Zeigt aktuelle Posts/Pages
   - Testet API-Konnektivität

4. **MIGRATION-TEST-GUIDE.md** - Komplette Test-Anleitung
   - Schritt-für-Schritt Anleitung
   - Erwartete Ergebnisse
   - Troubleshooting-Tipps
   - Erfolgs-Kriterien

### Durchgeführte Änderungen (17.10.2025, 20:00-20:45)

1. **Frontend (admin_interface.php)**
   - AJAX-Action von `b2e_validate_api_key` zu `b2e_validate_migration_token` geändert
   - Token, Domain und Expires werden jetzt korrekt übergeben
   - API-Key wird aus Response extrahiert und in sessionStorage gespeichert
   - Neue AJAX-Handler `ajax_validate_migration_token()` implementiert

2. **API Client (api_client.php)**
   - Neue Methode `validate_migration_token()` implementiert
   - Sendet Token-Validierung an `/wp-json/b2e/v1/validate`
   - Gibt API-Key aus Response zurück

3. **API Endpoints (api_endpoints.php)**
   - `validate_migration_token()` erweitert um API-Key-Generierung
   - API-Key wird automatisch generiert falls nicht vorhanden
   - API-Key wird in Response zurückgegeben

4. **Infrastruktur**
   - Plugin-Symlinks in beiden Containern erstellt
   - Container neu gestartet für Änderungen
   - Alle Änderungen synchronisiert
