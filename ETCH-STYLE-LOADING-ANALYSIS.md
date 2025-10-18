# Etch Style Loading Analysis

## Problem
Migrierte Styles sind in der DB (`etch_styles` Option), werden aber nicht im Frontend angewendet.

## Root Cause: CACHE!

### Wie Etch Styles lädt:

1. **REST API Endpoint:** `POST /wp-json/etch-api/styles?_method=PUT`
   - Überschreibt komplette `etch_styles` Option
   - Kein zusätzlicher Trigger

2. **Beim Seitenaufruf:**
   ```php
   // In WpApi.php
   public function register_etch_global_styles() {
       $all_styles = get_option('etch_styles', array());
       $this->etch_global->add_to_etch_global(
           array('styles' => $global_styles)
       );
   }
   ```

3. **Caching-Mechanismus:**
   ```php
   // In EtchGlobal.php
   $etch_global = wp_cache_get('etch_global_data', 'etch');
   wp_cache_set('etch_global_data', $combined_data, 'etch');
   ```

## Lösung

### Was wir machen müssen:

```php
// 1. Styles speichern
update_option('etch_styles', $merged_styles);

// 2. SVG-Version erhöhen (für Asset-Cache)
$current_version = get_option('etch_svg_version', 1);
update_option('etch_svg_version', $current_version + 1);

// 3. Etch-Cache löschen (WICHTIG!)
wp_cache_delete('etch_global_data', 'etch');

// 4. WordPress-Cache leeren
wp_cache_flush();
```

## Implementiert in:
- `css_converter.php` → `import_etch_styles()` Funktion

## Warum es vorher nicht funktionierte:
- ✅ Styles waren in DB
- ✅ `etch_svg_version` wurde erhöht
- ❌ **Cache wurde NICHT geleert!**
- → Etch hat alte Daten aus dem Cache geladen

## Warum es jetzt funktionieren sollte:
- ✅ Styles in DB
- ✅ `etch_svg_version` erhöht
- ✅ **Cache gelöscht!**
- → Etch lädt frische Daten beim nächsten Request

## Zusätzliche Erkenntnisse:

### Custom Styles Hook (für Plugins/Themes):
```php
// In CustomStyles.php
do_action_ref_array('etch/register_custom_styles', array(&$current_styles));
```

Könnte für zukünftige Erweiterungen nützlich sein!

### REST API Routes:
- `GET /wp-json/etch-api/styles` - Alle Styles abrufen
- `PUT /wp-json/etch-api/styles` - Alle Styles überschreiben
- `GET /wp-json/etch-api/stylesheets` - Globale Stylesheets
- `POST /wp-json/etch-api/stylesheets` - Neues Stylesheet erstellen

## Test-Szenario:

1. Migration durchführen
2. Etch öffnen
3. **Erwartung:** Alle migrierten Styles sind sichtbar und funktionieren
4. **Falls nicht:** Browser-Cache leeren (Ctrl+Shift+R)
