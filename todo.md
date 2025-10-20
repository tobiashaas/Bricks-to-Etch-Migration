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

## ✅ Gelöste Probleme

### CSS Frontend Rendering (BEHOBEN - 20. Oktober 2025)
- ✅ **Style-IDs stimmen überein** - IDs im Content matchen mit etch_styles
- ✅ **Style-Map funktioniert** - Bricks-IDs werden korrekt zu Etch-IDs gemapped
- ✅ **CSS wird gerendert** - Styles erscheinen im Frontend
- ✅ **Klassen im HTML** - CSS-Klassen werden korrekt ausgegeben
- 📄 **Dokumentation:** `CSS-RENDERING-SUCCESS.md`

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

### Notizen

- **Letzte Änderung**: 20. Oktober 2025, 10:30 Uhr
- **Aktueller Stand**: 🟡 className-Problem gelöst, Selector-Bug wird debuggt
- **Nächster Schritt**: JSON-Encoding/Decoding testen, Selector-Bug fixen
- **Zeitaufwand heute**: ~5 Stunden (Content Detection, API Fixes, Style Debugging, Logging)
- **Durchgeführte Fixes**:
  - ✅ Debug-Logging in allen CSS-Migration-Komponenten hinzugefügt
  - ✅ Frontend JavaScript Logging (Console)
  - ✅ AJAX Handler Logging (admin_interface.php)
  - ✅ CSS Converter Logging (css_converter.php)
  - ✅ API Client Logging (api_client.php)
  - ✅ API Endpoint Logging (api_endpoints.php)
  - ✅ Test-Skripte erstellt: test-css-migration-debug.sh, verify-css-migration.sh
- **Nächste Schritte**:
  1. Migration über Browser starten
  2. Logs mit test-css-migration-debug.sh überwachen
  3. Ergebnisse mit verify-css-migration.sh prüfen
  4. Basierend auf Logs Problem identifizieren und fixen

### Test-Ergebnisse (18.10.2025, 00:00-01:15)

**✅ Was funktioniert:**
- ✅ **Element Labels** - Benutzerdefinierte Namen aus Structure Panel werden migriert
- ✅ **Hierarchische Verschachtelung** - Korrekte Parent-Child-Beziehungen
- ✅ **Block-Elemente** - brxe-block wird als Container erkannt
- ✅ **Klassennamen** - Alle Klassen im HTML (inkl. Headings & Paragraphen)
- ✅ **ACSS-Prefix entfernt** - acss_import_ wird automatisch entfernt
- ✅ **Leere Utility-Klassen** - Framework-Klassen werden migriert (auch ohne CSS)
- ✅ **Custom CSS als Raw Stylesheet** - Verschachtelung bleibt erhalten
- ✅ **Image-Blöcke** - Inline HTML, keine "invalid content" Fehler
- ✅ **Cache-Invalidierung** - etch_svg_version wird erhöht
- ✅ **~2211 CSS Styles** migriert (inkl. Framework-Klassen)

**⚠️ Aktuelles Problem (20.10.2025, 10:30):**
- ⚠️ **Selectors in etch_styles sind null** - CSS kann nicht gerendert werden
- 🔍 **Symptome**: 
  - Posts werden migriert (6 Posts in Etch)
  - Style-IDs sind im Content vorhanden (✅ in etchData.styles)
  - Styles sind in etch_styles gespeichert
  - ABER: selector Feld ist null statt ".klassenname"
  - Frontend rendert keine CSS-Styles
- 💡 **Root Cause 1 (✅ GELÖST)**: className statt etchData.styles
  - **Entwickler-Info**: "Die klassen müssten mit ihrer Unique ID in block.attr.metadata.etchData.styles = [\"unique-Id-hier\", \"unique-ID-von-class-2\"]"
  - ❌ FALSCH: `{"className": "hero-barcelona bg--ultra-dark"}`
  - ✅ RICHTIG: `{"metadata": {"etchData": {"styles": ["7b5a2e3", "8ff1c7f"]}}}`
  - Fix: Alle `className` und `attributes.class` entfernt
- 💡 **Root Cause 2 (🔍 IN ARBEIT)**: Selectors werden zu null
  - CSS-Converter generiert Selectors korrekt
  - Etch API überschreibt/löscht Selectors beim Import
  - Vermutlich JSON-Encoding/Decoding Problem
- 📝 **Details**: Siehe CSS-FRONTEND-RENDERING-STATUS.md

**🔧 Durchgeführte Fixes (19-20.10.2025):**
1. ✅ **Content Detection** - Separate Queries für Bricks/Gutenberg/Media
   - `get_bricks_posts()` - Nur Posts mit `_bricks_page_content_2` + `_bricks_editor_mode = 'bricks'`
   - `get_gutenberg_posts()` - Posts OHNE Bricks Meta
   - `get_media()` - Alle Attachments
2. ✅ **migrate_single_post() Rewrite** - Nutzt jetzt `send_post()` statt falsche API
   - Vorher: Versuchte `/b2e-migration/v1/post/{id}/blocks` (existiert nicht) → 404
   - Nachher: Nutzt `/b2e/v1/receive-post` (korrekt)
3. ✅ **Etch API für Styles** - Zurück zur Etch API statt direktem DB-Zugriff
   - Etch API dekodiert Unicode richtig (`\u002d` → `-`)
   - Triggert interne Etch Hooks
   - Invalidiert Cache automatisch
4. ✅ **ID-Generierung** - Nutzt jetzt uniqid() wie Etch (statt MD5)
   - Vorher: `substr(md5($class_name), 0, 7)` → falsche IDs
   - Nachher: `substr(uniqid(), -7)` → korrekte IDs wie Etch
5. ✅ **_cssClasses Handling** - String-Split implementiert
   - Vorher: Erwartete Array, bekam String → keine Klassen gefunden
   - Nachher: Splittet String bei Leerzeichen → alle Klassen gefunden
6. ✅ **Style-Map Verwendung** - Content-Migration nutzt jetzt Style-Map
   - Findet Bricks-ID für Klassenname
   - Lookt Etch-ID in Style-Map auf
   - Fügt Etch-ID in etchData.styles ein
7. ✅ **className vs etchData.styles** - Entwickler-Info umgesetzt
   - Vorher: Nutzte Gutenberg `className` → funktioniert nicht mit Etch
   - Nachher: Nutzt `metadata.etchData.styles` mit Style-IDs → korrekt!
   - Entfernt: `className` aus allen Block-Attributen
   - Entfernt: `attributes.class` aus `etchData.attributes`
8. ⚠️ **Problem**: Selectors werden zu null (Etch API Bug - wird debuggt)
   - Logging hinzugefügt: BEFORE/AFTER API call
   - JSON-Encoding/Decoding wird getestet
   - Element-Styles behalten Selectors, User-Styles verlieren sie

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
