# Changelog - Bricks to Etch Migration

## [0.5.3] - 2025-10-22 (23:24) - Media Queries, Missing Properties & Element Converters

### 🎯 Media Query Fixes

#### Breakpoint-spezifisches CSS
- ✅ **Breakpoint CSS wird jetzt korrekt migriert**
  - Bricks Breakpoints (`_cssCustom:mobile_portrait`, etc.) werden zu Media Queries konvertiert
  - CSS Properties werden direkt in Media Query eingefügt (ohne zusätzliche Wrapper)
  - Breakpoint CSS wird nach Custom CSS Merge hinzugefügt

#### Media Query Extraktion
- ✅ **Verschachtelte Media Queries funktionieren jetzt**
  - Neue Funktion: `extract_media_queries()` mit manuellem Klammern-Zählen
  - Regex konnte verschachtelte Regeln nicht handhaben
  - Alle Regeln innerhalb von Media Queries werden jetzt korrekt extrahiert

#### Etch's moderne Media Query Syntax
- ✅ **Bricks Breakpoints → Etch Range Syntax**
  - `mobile_portrait`: `@media (width <= to-rem(478px))`
  - `mobile_landscape`: `@media (width >= to-rem(479px))`
  - `tablet_portrait`: `@media (width >= to-rem(768px))`
  - `tablet_landscape`: `@media (width >= to-rem(992px))`
  - `desktop`: `@media (width >= to-rem(1200px))`
  - Desktop-First mit Kaskadierung nach unten
  - `to-rem()` Funktion wird von Etch automatisch verarbeitet

#### Logical Properties in Media Queries
- ✅ **Media Queries werden NICHT zu Logical Properties konvertiert**
  - `@media (min-width: 768px)` bleibt `min-width` (nicht `min-inline-size`)
  - Logical Properties nur für CSS Properties, nicht für Media Queries
  - Media Queries werden vor Konvertierung extrahiert und geschützt

### 🔧 Fehlende CSS Properties

#### Neue Properties hinzugefügt
- ✅ `_direction` → `flex-direction` (Alias für `_flexDirection`)
- ✅ `_cursor` → `cursor`
- ✅ `_mixBlendMode` → `mix-blend-mode`
- ✅ `_pointerEvents` → `pointer-events`
- ✅ `_scrollSnapType` → `scroll-snap-type`
- ✅ `_scrollSnapAlign` → `scroll-snap-align`
- ✅ `_scrollSnapStop` → `scroll-snap-stop`

### 🆕 Element Converters

#### Button Element Converter
- ✅ **Bricks Button → Etch Link (Paragraph mit nested Link)**
  - Text aus `settings.text` extrahiert
  - Link aus `settings.link` extrahiert (Array und String Format)
  - Style Mapping: `btn--primary`, `btn--secondary`, `btn--outline`
  - Converter gibt STRING zurück (nicht Array)
  - CSS Klassen werden korrekt kombiniert

#### Image Element Converter
- ✅ **Bricks Image → Gutenberg Image mit Etch metadata**
  - Styles und Klassen auf `nestedData.img` (nicht auf `figure`)
  - `figure` ist nur Wrapper
  - Keine `wp-image-XX` Klasse auf `<img>` Tag
  - `size-full` und `linkDestination: none` hinzugefügt
  - Space vor `/>` für Gutenberg Validierung

#### Icon Element Converter
- ✅ **Placeholder erstellt** (zeigt `[Icon: library:name]`)
- ⏸️ **TODO:** Richtige Icon Konvertierung implementieren

#### Skip-Liste für nicht unterstützte Elemente
- ✅ **Elemente werden still übersprungen** (keine Logs)
  - `fr-notes` - Bricks Builder Notizen (nicht frontend)
  - `code` - Code Blocks (TODO)
  - `form` - Forms (TODO - Etch hat keine)
  - `map` - Maps (TODO - Etch hat keine)

### 📝 Technical Changes
- **Neue Dateien:**
  - `includes/converters/elements/class-button.php` - Button Converter
  - `includes/converters/elements/class-icon.php` - Icon Converter (Placeholder)
- **CSS Converter:**
  - `convert_to_logical_properties()` - Media Queries werden geschützt
  - `get_media_query_for_breakpoint()` - Etch Range Syntax mit `to-rem()`
  - `extract_media_queries()` - Klammern-Zählung für verschachtelte Regeln
  - `convert_flexbox()` - `_direction` Alias Support
  - `convert_effects()` - Cursor, Mix-Blend-Mode, Pointer-Events, Scroll-Snap
- **Element Factory:**
  - Skip-Liste für nicht unterstützte Elemente
  - Icon Converter registriert
- **Image Converter:**
  - Komplett umgebaut: nestedData.img Struktur
  - Keine wp-image-XX Klasse mehr

---

## [0.5.2] - 2025-10-22 (21:08) - Custom CSS & Nested CSS

### 🎨 Custom CSS Migration - FIXED

#### Problem gelöst
- **Custom CSS wurde nicht migriert** - Nur normale CSS Properties kamen in Etch an
- **Ursache 1:** Custom CSS wurde für ALLE Klassen gesammelt (auch Blacklist), aber Blacklist-Klassen wurden beim Konvertieren übersprungen → keine Zuordnung im `$style_map`
- **Ursache 2:** `parse_custom_css_stylesheet()` verarbeitete nur die ERSTE Klasse im Stylesheet, alle anderen wurden ignoriert

#### Lösung
1. ✅ **Custom CSS nur für erlaubte Klassen sammeln**
   - Blacklist-Check VOR dem Sammeln von Custom CSS
   - Nur Klassen die konvertiert werden, bekommen Custom CSS
   
2. ✅ **Alle Klassen im Stylesheet verarbeiten**
   - Neue Funktion: `extract_css_for_class()` - Extrahiert CSS für jede Klasse separat
   - `parse_custom_css_stylesheet()` findet ALLE Klassen und verarbeitet jede einzeln

### 🎯 Nested CSS mit & (Ampersand)

#### Feature: Automatisches CSS Nesting
- **Konvertiert mehrere Regeln** für die gleiche Klasse zu Nested CSS
- **Intelligente & Syntax:**
  - `& > *` - Leerzeichen bei Combinators (>, +, ~)
  - `& .child` - Leerzeichen bei Descendant Selectors
  - `&:hover` - Kein Leerzeichen bei Pseudo-Classes
  - `&::before` - Kein Leerzeichen bei Pseudo-Elements

#### Beispiel
**Input (Bricks):**
```css
.my-class {
    padding: 1rem;
}
.my-class > * {
    color: red;
}
```

**Output (Etch):**
```css
padding: 1rem;

& > * {
  color: red;
}
```

### 🚫 CSS Class Blacklist

#### Ausgeschlossene Klassen
- **Bricks:** `brxe-*`, `bricks-*`, `brx-*`
- **WordPress/Gutenberg:** `wp-*`, `wp-block-*`, `has-*`, `is-*`
- **WooCommerce:** `woocommerce-*`, `wc-*`, `product-*`, `cart-*`, `checkout-*`

#### Logging
- Zeigt Anzahl konvertierter Klassen
- Zeigt Anzahl ausgeschlossener Klassen

### 📊 Statistik
- ✅ **1134 Klassen** erfolgreich migriert
- ✅ **1 Klasse** ausgeschlossen (Blacklist)
- ✅ **Custom CSS** mit Nested Syntax funktioniert
- ✅ **Alle Tests** bestanden

### 🧪 Tests
- ✅ `tests/test-nested-css-conversion.php` - 5/5 Tests bestanden
- ✅ Live Migration Test erfolgreich
- ✅ Custom CSS im Frontend verifiziert

---

## [0.5.1] - 2025-10-22 (19:20) - Phase 2: AJAX Handlers

### 🔧 Refactoring

#### Modulare AJAX-Handler Struktur
- **Neue Ordnerstruktur:**
  - `includes/ajax/` - AJAX Handler
  - `includes/ajax/handlers/` - Individual AJAX Handlers
  
#### AJAX-Handler (NEU)
- ✅ `class-base-ajax-handler.php` - Abstract base class
- ✅ `class-ajax-handler.php` - Main AJAX handler (initialisiert alle)
- ✅ `handlers/class-css-ajax.php` - CSS migration handler
- ✅ `handlers/class-content-ajax.php` - Content migration handler
- ✅ `handlers/class-media-ajax.php` - Media migration handler
- ✅ `handlers/class-validation-ajax.php` - API key & token validation

### 📝 Features
- **Base Handler:** Gemeinsame Logik für alle AJAX-Handler
  - Nonce verification
  - Capability checks
  - URL sanitization
  - Logging
- **Modulare Struktur:** Jeder Handler in eigener Datei
- **Docker URL Conversion:** Automatische localhost → b2e-etch Konvertierung

### 🔄 Integration
- Plugin-Hauptdatei lädt AJAX-Handler automatisch
- Alle Handler werden bei Plugin-Initialisierung registriert
- Alte AJAX-Handler in admin_interface.php bleiben vorerst (Kompatibilität)

### ⚠️ Status
- Phase 2: AJAX-Handler ✅ COMPLETE (19:20)
- Phase 3: Admin-Interface - PENDING
- Phase 4: Utilities - PENDING
- Phase 5: Integration & Testing - PENDING

---

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
