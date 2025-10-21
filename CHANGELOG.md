# Changelog - Bricks to Etch Migration

## [0.5.0] - 2025-10-22 (00:22) - REFACTORING (IN PROGRESS)

### 🔧 Refactoring

#### Modulare Element-Converter Struktur
- **Neue Ordnerstruktur:**
  - `includes/converters/` - Conversion Logic
  - `includes/converters/elements/` - Individual Element Converters
  - `includes/core/` - Core Functionality
  - `includes/admin/` - Admin Interface
  - `includes/ajax/` - AJAX Handlers
  - `includes/api/` - API Communication
  - `includes/utils/` - Utilities

#### Element-Converter (NEU)
- ✅ `class-base-element.php` - Abstract base class for all converters
- ✅ `class-container.php` - Container element (supports ul, ol, etc.)
- ✅ `class-section.php` - Section element
- ✅ `class-heading.php` - Heading element (h1-h6)
- ✅ `class-paragraph.php` - Paragraph/Text element
- ✅ `class-image.php` - Image element (uses figure tag!)
- ✅ `class-div.php` - Div/Flex-Div element (supports li, span, etc.)
- ✅ `class-element-factory.php` - Factory for creating converters

### 📝 Vorteile
- **Ein Element = Eine Datei** - Einfacher zu warten
- **Änderungen nur an einer Stelle** - z.B. Container-Tag-Support
- **Wiederverwendbarer Code** - Base class mit gemeinsamer Logik
- **Bessere Testbarkeit** - Jedes Element einzeln testbar

### ⚠️ Status
- Phase 1: Element-Converter ✅ COMPLETE (00:38)
- Phase 2: AJAX-Handler - PENDING
- Phase 3: Admin-Interface - PENDING
- Phase 4: Utilities - PENDING
- Phase 5: Integration & Testing - PENDING

### 📄 Dokumentation
- ✅ `REFACTORING-STATUS.md` erstellt - Umfassender Refactoring-Bericht
- ✅ `includes/converters/README.md` erstellt - Converter-Dokumentation (00:44)
- ✅ `PROJECT-RULES.md` aktualisiert - Converter-Dokumentations-Regel hinzugefügt
- ✅ Alle Tests dokumentiert und bestanden
- ✅ Cleanup-Script gefixed - Löscht jetzt alle Styles

---

## [0.4.1] - 2025-10-21 (23:40)

### 🐛 Bug Fixes

#### Listen-Elemente (ul, ol, li) Support
- **Problem:** Container und Div mit custom tags (ul, ol, li) wurden als `<div>` gerendert
- **Lösung:** 
  - `process_container_element()` berücksichtigt jetzt `tag` Setting aus Bricks
  - `convert_etch_container()` verwendet custom tag in `etchData.block.tag`
  - Gutenberg `tagName` Attribut wird gesetzt für non-div tags
- **Geänderte Dateien:**
  - `includes/gutenberg_generator.php` - Zeilen 1512-1520, 236-269

### 🔧 Technische Details

**Container mit custom tags:**
```php
// Bricks
'settings' => ['tag' => 'ul']

// Etch
'etchData' => [
  'block' => ['tag' => 'ul']
]
'tagName' => 'ul'  // For Gutenberg
```

**Frontend Output:**
```html
<ul data-etch-element="container" class="my-class">
  <li>...</li>
</ul>
```

---

## [0.4.0] - 2025-10-21 (22:24)

### 🎉 Major Release: CSS-Klassen Frontend-Rendering

**Durchbruch:** CSS-Klassen werden jetzt korrekt im Frontend-HTML gerendert!

### ✨ Neue Features

#### CSS-Klassen in etchData.attributes.class
- **Kern-Erkenntnis:** Etch rendert CSS-Klassen aus `etchData.attributes.class`, nicht aus Style-IDs
- Alle Element-Typen unterstützt: Headings, Paragraphs, Images, Sections, Containers, Flex-Divs
- Neue Funktion: `get_css_classes_from_style_ids()` konvertiert Style-IDs → CSS-Klassen

#### Erweiterte Style-Map
- Style-Map enthält jetzt: `['bricks_id' => ['id' => 'etch_id', 'selector' => '.css-class']]`
- Ermöglicht CSS-Klassen-Generierung auf Bricks-Seite
- Backward-kompatibel mit altem Format

#### Custom CSS Migration Fix
- Custom CSS (`_cssCustom`) wird jetzt korrekt mit normalen Styles zusammengeführt
- `parse_custom_css_stylesheet()` verwendet existierende Style-IDs
- Unterstützt komplexe Selektoren (`.class > *`, Media Queries, etc.)

#### Image-Rendering Fix
- Images verwenden jetzt `block.tag = 'figure'` statt `'img'`
- CSS-Klassen auf `<figure>`, nicht auf `<img>`
- Verhindert doppelte `<img>`-Tags im Frontend

### 🐛 Bug Fixes

#### Kritischer Fix: unset($attributes['class'])
- Entfernt `unset()` das CSS-Klassen gelöscht hat
- Betraf alle Container/Section-Elemente
- Klassen werden jetzt korrekt in `etchData.attributes` behalten

#### Etch-interne Styles überspringen
- `etch-section-style`, `etch-container-style` werden bei Klassen-Suche übersprungen
- Verhindert leere Klassen-Strings

### 📚 Dokumentation

Neue Dokumentations-Dateien:
- `CSS-CLASSES-FINAL-SOLUTION.md` - Vollständige technische Dokumentation
- `CSS-CLASSES-QUICK-REFERENCE.md` - Schnell-Referenz
- `MIGRATION-SUCCESS-SUMMARY.md` - Projekt-Zusammenfassung
- `REFERENCE-POST.md` - Referenz-Post (3411) Dokumentation

### 🔧 Technische Änderungen

**Geänderte Dateien:**
- `includes/gutenberg_generator.php`
  - Neue Funktion: `get_css_classes_from_style_ids()`
  - Headings, Paragraphs, Images: CSS-Klassen in `etchData.attributes.class`
  - Sections, Containers: `process_*_element()` verwendet neue Funktion
  - Images: `block.tag = 'figure'`, Klasse auf `<figure>`
  - Entfernt: `unset($etch_data_attributes['class'])`
  
- `includes/css_converter.php`
  - Erweiterte Style-Map: ID + Selector
  - `parse_custom_css_stylesheet()` mit `$style_map` Parameter
  - Custom CSS verwendet existierende Style-IDs

### 🎯 Erfolgs-Kriterien

✅ Alle Element-Typen rendern CSS-Klassen im Frontend
✅ Custom CSS wird korrekt zusammengeführt
✅ Images ohne doppelte `<img>`-Tags
✅ Referenz-Post (3411) bleibt bei Cleanup erhalten

### 🚀 Migration-Workflow

1. Cleanup: `./cleanup-etch.sh` (behält Post 3411)
2. Migration: "Start Migration" Button
3. Verifizierung: CSS-Klassen im Frontend prüfen

---

## [0.3.9] - 2025-10-17 (20:50)

### 🐛 Critical Fix: API-Key nicht bei Migration verwendet

**Problem:** Obwohl die Token-Validierung funktionierte und den API-Key zurückgab, wurde dieser nicht bei der tatsächlichen Migration verwendet. Stattdessen wurde der Token fälschlicherweise als API-Key gesendet, was zu 401-Fehlern bei allen `/receive-post` und `/receive-media` Requests führte.

**Lösung:** 
- API-Key wird jetzt aus `sessionStorage` gelesen (wurde dort bei Token-Validierung gespeichert)
- `startMigrationProcess()` verwendet den echten API-Key statt des Tokens
- Validierung vor Migration-Start: Fehler wenn kein API-Key in sessionStorage

**Geänderte Dateien:**
- `includes/admin_interface.php` - Zeilen 542-577

---

## [0.3.8] - 2025-10-17 (20:45)

### 🎉 Major Fix: Token-Based Validation System

**Problem gelöst:** Migration Keys enthielten fälschlicherweise den Token als API-Key, was zu 401-Fehlern führte.

### ✨ Neue Features

#### Token-Validierung statt API-Key in URL
- Migration Keys enthalten jetzt nur noch `domain`, `token` und `expires`
- API-Key wird **nicht mehr** in der URL übertragen
- Sicherer und sauberer Ansatz

#### Automatische API-Key-Generierung
- API-Key wird automatisch auf der Etch-Seite generiert
- Bei Token-Validierung wird der API-Key in der Response zurückgegeben
- Bricks-Seite speichert den API-Key automatisch in sessionStorage

### 🔧 Technische Änderungen

#### Frontend (`includes/admin_interface.php`)
- **Neue AJAX-Action:** `b2e_validate_migration_token`
  - Ersetzt die fehlerhafte `b2e_validate_api_key` für Migration-Keys
  - Sendet `token`, `domain` und `expires` statt `api_key`
  - Extrahiert API-Key aus Response und speichert in sessionStorage

- **Verbesserte UI-Meldungen:**
  - "Migration token validated successfully!" statt "API key validated"
  - Zeigt Token-Ablaufzeit an
  - Klarere Fehlermeldungen

#### Backend (`includes/api_client.php`)
- **Neue Methode:** `validate_migration_token()`
  - Sendet POST-Request an `/wp-json/b2e/v1/validate`
  - Überträgt Token-Daten als JSON
  - Gibt vollständige Response mit API-Key zurück

#### API Endpoints (`includes/api_endpoints.php`)
- **Erweitert:** `validate_migration_token()`
  - Generiert automatisch API-Key falls nicht vorhanden
  - Verwendet `B2E_API_Client::create_api_key()`
  - Gibt API-Key in Response zurück
  - Logging für Debugging

### 📊 Validierungs-Flow

```
1. Etch-Seite: Migration Key generieren
   ↓
   URL: http://localhost:8081?domain=...&token=...&expires=...
   
2. Bricks-Seite: Migration Key validieren
   ↓
   AJAX: b2e_validate_migration_token
   ↓
   POST /wp-json/b2e/v1/validate
   {
     "token": "...",
     "source_domain": "...",
     "expires": 1234567890
   }
   
3. Etch-Seite: Token validieren + API-Key generieren
   ↓
   Response:
   {
     "success": true,
     "api_key": "b2e_...",
     "message": "Token validation successful",
     "target_domain": "...",
     "site_name": "...",
     "etch_active": true
   }
   
4. Bricks-Seite: API-Key speichern
   ↓
   sessionStorage.setItem('b2e_api_key', api_key)
   ↓
   ✅ Bereit für Migration
```

### 🧪 Testing

- **Automatisiertes Test-Script:** `test-token-validation.sh`
  - Generiert Token
  - Speichert in Datenbank
  - Testet Validierung
  - Verifiziert API-Key-Rückgabe

- **Manuelles Test-Script:** `test-migration-flow.sh`
  - Prüft WordPress-Sites
  - Testet API-Endpoints
  - Zeigt Test-Checkliste

### 🐛 Behobene Bugs

1. **401 Unauthorized bei Token-Validierung**
   - Ursache: Token wurde als API-Key behandelt
   - Lösung: Separater Validierungs-Endpoint mit Token-Parameter

2. **API-Key-Mismatch**
   - Ursache: Jeder Migration Key hatte anderen "API-Key" (war eigentlich Token)
   - Lösung: API-Key wird serverseitig generiert und übertragen

3. **Fehlende API-Key-Synchronisation**
   - Ursache: Keine automatische Übertragung des API-Keys
   - Lösung: API-Key in Validierungs-Response enthalten

### 📝 Migrations-Hinweise

**Für bestehende Installationen:**
1. Plugin auf Version 0.3.8 aktualisieren
2. Alte Migration Keys sind ungültig
3. Neue Migration Keys auf Etch-Seite generieren
4. Token-Validierung auf Bricks-Seite durchführen

**Wichtig:** Die alte `b2e_validate_api_key` AJAX-Action existiert noch für Kompatibilität, wird aber nicht mehr für Migration-Keys verwendet.

### 🔒 Sicherheit

- Token-Validierung mit Ablaufzeit (8 Stunden)
- API-Key wird nicht in URL übertragen
- Sichere Token-Generierung mit `wp_generate_password(64, false)`
- API-Key wird nur bei erfolgreicher Token-Validierung zurückgegeben

### 🚀 Performance

- Keine Änderungen an der Performance
- Zusätzlicher API-Call für Token-Validierung (einmalig)
- API-Key wird in sessionStorage gecacht

### 📚 Dokumentation

- `todo.md` aktualisiert mit gelöstem Problem
- Test-Scripts für automatisierte Validierung
- Detaillierte Changelog-Einträge

---

## [0.3.7] - 2025-10-16

### Vorherige Version
- Basis-Implementierung der Migration
- AJAX-Handler für verschiedene Aktionen
- REST API Endpoints
- Docker-Setup für Testing

---

**Hinweis:** Vollständige Versionshistorie in Git verfügbar.
