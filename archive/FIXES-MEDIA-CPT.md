# Fixes: Media-Migration & Custom Post Types

## Probleme identifiziert

### 1. Media zeigt 0 obwohl 19 verfügbar

**Problem:**
- Migration-Report zeigt: `Media Migrated: 0 / 19`
- Statistik zeigt: `media_migrated: 0`
- Aber: 19 Media-Dateien sind auf Bricks-Seite vorhanden

**Ursache:**
- `migrate_media_files()` läuft durch
- API-Calls zu `/receive-media` schlagen fehl
- Fehler werden geloggt, aber `$migrated_count` bleibt bei 0
- Keine Unterscheidung zwischen failed/skipped

**Fix implementiert:**
- Besseres Logging mit separaten Counters:
  - `$migrated_count` - Erfolgreich übertragen
  - `$failed_count` - API-Call fehlgeschlagen
  - `$skipped_count` - Datei nicht auf Disk gefunden
- Stats speichern jetzt alle 3 Werte
- Report zeigt: `%d media files (%d failed, %d skipped)`

**Nächster Schritt:**
- API-Endpoint `/receive-media` debuggen
- Warum schlagen die Calls fehl?
- Logs prüfen für genaue Fehlermeldung

---

### 2. Custom Post Types: 1 statt 0

**Problem:**
- Report zeigt: `Custom Post Types: 1`
- Aber: Du hast keine Custom Post Types erstellt
- WordPress hat viele interne CPTs (wp_block, bricks_fonts, etc.)

**Ursache:**
```php
$custom_post_types = get_post_types(array(
    'public' => true,
    '_builtin' => false
), 'objects');
```

Diese Abfrage zählt **alle** nicht-builtin Post Types, inkl.:
- `wp_block` - WordPress Patterns
- `wp_template` - WordPress Templates
- `bricks_fonts` - Bricks Custom Fonts
- `bricks_template` - Bricks Templates
- etc.

**Fix implementiert:**
```php
// Get all non-builtin CPTs
$all_custom_post_types = get_post_types(array(
    '_builtin' => false
), 'objects');

// Filter out WordPress core CPTs and Bricks internal types
$exclude_types = array(
    'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 
    'wp_global_styles', 'wp_font_family', 'wp_font_face', 
    'bricks_fonts', 'bricks_template', 'acf-field-group', 'acf-field'
);

$custom_post_types = array_filter($all_custom_post_types, function($cpt) use ($exclude_types) {
    return !in_array($cpt->name, $exclude_types);
});
```

**Ergebnis:**
- Nur **echte** Custom Post Types werden gezählt
- WordPress-Defaults und Bricks-interne Types werden ausgeschlossen
- Report sollte jetzt `0` zeigen (wenn keine echten CPTs vorhanden)

---

## Dateien geändert

1. **migration_manager.php**
   - `migrate_media_files()`: Besseres Logging, failed/skipped counts
   - Stats speichern: `media_migrated`, `media_failed`, `media_skipped`

2. **admin_interface.php**
   - `ajax_generate_report()`: Custom Post Types Filter
   - Report-Daten: `media_failed`, `media_skipped` hinzugefügt
   - Details-String: Zeigt failed/skipped counts

3. **update-plugin.sh** (NEU)
   - Skript zum schnellen Update des Plugins in beiden Containern
   - Kopiert Dateien und flusht Cache

---

## Testen

### Nach nächster Migration:

```bash
# Plugin aktualisieren
./update-plugin.sh

# Migration durchführen (über Browser)
# ...

# Report prüfen
```

**Erwartetes Ergebnis:**
- Custom Post Types: `0` (statt 1)
- Media Migrated: `0 / 19` (unverändert, aber...)
- Details zeigen: `0 media files (19 failed, 0 skipped)` oder ähnlich

### Media-Migration debuggen:

```bash
# Logs nach Migration prüfen
docker exec b2e-bricks wp option get b2e_error_log --format=json --allow-root | grep -i media

# Oder: Error-Handler Logs durchsuchen
docker logs b2e-bricks | grep -i "media"
```

---

## Nächste Schritte

### Priorität 1: Media-Migration fixen

1. **API-Endpoint prüfen:**
   - Ist `/wp-json/b2e/v1/receive-media` erreichbar?
   - Funktioniert die Authentifizierung?
   - Gibt es PHP-Fehler?

2. **Logs analysieren:**
   - Welche genaue Fehlermeldung kommt zurück?
   - `E105` Error-Logs prüfen

3. **API-Client testen:**
   - `send_media_data()` isoliert testen
   - Payload-Größe prüfen (base64-encoded images können groß sein)
   - Timeout-Probleme?

### Priorität 2: Content-Konvertierung

Aktuell werden Posts/Pages übertragen, aber:
- **Kein Bricks-Content wird konvertiert**
- Content bleibt als Bricks-JSON
- Muss zu Gutenberg/Etch-Blöcken konvertiert werden

### Priorität 3: CSS-Konvertierung

- Bricks CSS-Classes zu Etch CSS
- Global Styles übertragen
- Theme-Settings migrieren

---

## Zusammenfassung

**✅ Behoben:**
- Custom Post Types: Korrekte Zählung (nur echte CPTs)
- Media-Logging: Bessere Fehleranalyse möglich

**⏳ Noch zu beheben:**
- Media-Migration: API-Calls schlagen fehl
- Content-Konvertierung: Bricks → Gutenberg/Etch
- CSS-Konvertierung: Bricks CSS → Etch CSS

**🎉 Funktioniert:**
- Token-Validierung
- Migration-Flow
- Posts/Pages-Übertragung
- Progress-Monitoring
- Migration-Report
