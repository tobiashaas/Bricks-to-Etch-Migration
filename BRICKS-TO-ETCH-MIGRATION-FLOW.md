# Bricks to Etch Migration - Technische Dokumentation

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Datenstrukturen](#datenstrukturen)
3. [CSS-Migration Flow](#css-migration-flow)
4. [Content-Migration Flow](#content-migration-flow)
5. [ID-Mapping System](#id-mapping-system)
6. [Frontend-Rendering](#frontend-rendering)
7. [Troubleshooting](#troubleshooting)

---

## Übersicht

### Architektur

```
┌─────────────────┐                    ┌─────────────────┐
│  Bricks Site    │                    │   Etch Site     │
│  (Source)       │                    │   (Target)      │
├─────────────────┤                    ├─────────────────┤
│ bricks_global_  │  ──CSS Migration──>│ etch_styles     │
│ classes         │                    │                 │
│                 │                    │                 │
│ _bricks_page_   │  ─Content Migr.──>│ post_content    │
│ content_2       │                    │ (Gutenberg)     │
│                 │                    │                 │
│ b2e_style_map   │  ──ID Mapping────>│ (verwendet in   │
│ (Bricks→Etch)   │                    │  etchData)      │
└─────────────────┘                    └─────────────────┘
```

### Hauptkomponenten

| Komponente | Datei | Zweck |
|------------|-------|-------|
| CSS Converter | `css_converter.php` | Konvertiert Bricks Classes → Etch Styles |
| Content Parser | `content_parser.php` | Extrahiert Bricks Content |
| Gutenberg Generator | `gutenberg_generator.php` | Generiert Gutenberg Blocks mit Etch Metadata |
| API Client | `api_client.php` | Kommunikation zwischen Sites |
| API Endpoints | `api_endpoints.php` | REST API auf Etch-Seite |

---

## Datenstrukturen

### Bricks Global Class

```php
// Gespeichert in: bricks_global_classes (Bricks)
[
    'id' => 'bTyScxgmzei',              // Bricks-interne ID
    'name' => 'fr-intro-alpha',         // CSS-Klassenname
    'settings' => [
        '_cssCustom' => '...',          // Custom CSS
        'background' => '...',          // Bricks Settings
        'padding' => '...',
        // ... weitere Settings
    ]
]
```

### Etch Style

```php
// Gespeichert in: etch_styles (Etch)
[
    'type' => 'class',                  // 'class' oder 'element'
    'selector' => '.fr-intro-alpha',    // CSS-Selektor
    'collection' => 'default',          // Collection-Name
    'css' => 'padding: 20px; ...',      // Konvertiertes CSS
    'readonly' => false                 // Editierbar?
]
```

### Style Map

```php
// Gespeichert in: b2e_style_map (Bricks)
[
    'bTyScxgmzei' => '8c6eb1b',        // Bricks-ID => Etch-ID
    'bTyScblgfqd' => '5ca287b',
    // ...
]
```

### Bricks Element

```php
// Gespeichert in: _bricks_page_content_2 (Post Meta)
[
    'id' => 'section1',
    'name' => 'section',
    'parent' => 0,
    'children' => ['container1'],
    'settings' => [
        '_cssClasses' => 'my-section',           // Normale Klassen (String)
        '_cssGlobalClasses' => ['bTyScxgmzei'],  // Global Classes (Array von IDs)
        'tag' => 'section',
        // ... weitere Settings
    ]
]
```

### Gutenberg Block mit Etch Metadata

```json
{
  "metadata": {
    "name": "Intro Alpha",
    "etchData": {
      "origin": "etch",
      "name": "Intro Alpha",
      "styles": ["etch-container-style", "8c6eb1b"],  // Element + Custom IDs
      "attributes": {
        "data-etch-element": "container",
        "class": "fr-intro-alpha"
      },
      "block": {
        "type": "html",
        "tag": "div"
      }
    }
  },
  "className": "fr-intro-alpha"
}
```

---

## CSS-Migration Flow

### Phase 1: Konvertierung (Bricks-Seite)

```php
// css_converter.php :: convert_bricks_classes_to_etch()

1. Bricks Classes laden
   $bricks_classes = get_option('bricks_global_classes', []);

2. Für jede Bricks Class:
   a) Klassennamen extrahieren
      $class_name = $class['name'] ?: $class['id'];
      
   b) Etch-ID generieren (MD5-Hash vom Namen)
      $etch_id = substr(md5($class_name), 0, 7);
      
   c) Settings zu CSS konvertieren
      $css = convert_bricks_settings_to_css($class['settings']);
      
   d) Etch-Style erstellen
      $etch_styles[$etch_id] = [
          'type' => 'class',
          'selector' => '.' . $class_name,
          'css' => $css,
          'readonly' => false
      ];
      
   e) Mapping speichern
      $style_map[$class['id']] = $etch_id;

3. Style Map speichern
   update_option('b2e_style_map', $style_map);

4. Etch Styles zurückgeben
   return $etch_styles;
```

### Phase 2: Übertragung (API)

```php
// api_client.php :: send_css_styles()

1. AJAX Request von Bricks-Seite
   POST /wp-json/b2e/v1/import/css-classes
   Headers: X-API-Key: {api_key}
   Body: {etch_styles}

2. Etch-Seite empfängt
   // api_endpoints.php :: import_css_classes()
   $styles = $request->get_json_params();
```

### Phase 3: Import (Etch-Seite)

```php
// css_converter.php :: import_etch_styles()

1. Bestehende Styles laden
   $existing = get_option('etch_styles', []);

2. Mit neuen Styles mergen
   $merged = array_merge($existing, $etch_styles);

3. Via Etch API speichern (wichtig für Cache-Invalidierung!)
   $routes = new \Etch\RestApi\Routes\StylesRoutes();
   $request = new \WP_REST_Request('POST', '/etch-api/styles');
   $request->set_body(json_encode($merged));
   $routes->update_styles($request);

4. Cache invalidieren
   update_option('etch_svg_version', $version + 1);
   wp_cache_flush();
```

---

## Content-Migration Flow

### Phase 1: Content Parsing (Bricks-Seite)

```php
// content_parser.php :: get_bricks_posts()

1. Bricks Posts finden
   $posts = get_posts([
       'meta_query' => [
           ['key' => '_bricks_page_content_2', 'compare' => 'EXISTS'],
           ['key' => '_bricks_editor_mode', 'value' => 'bricks']
       ]
   ]);

2. Für jeden Post:
   $bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);
```

### Phase 2: Gutenberg Generierung (Bricks-Seite)

```php
// gutenberg_generator.php :: generate_gutenberg_blocks()

1. Element-Hierarchie aufbauen
   $element_map = []; // id => element
   $top_level = [];   // Elemente ohne Parent

2. Für jedes Top-Level Element:
   $block_html = generate_block_html($element, $element_map);

3. generate_block_html() - REKURSIV:
   
   a) Element-Typ bestimmen
      switch ($element['name']) {
          case 'section': return convert_etch_section($element);
          case 'container': return convert_etch_container($element);
          case 'heading': return convert_etch_heading($element);
          // ...
      }
   
   b) Klassen extrahieren
      $classes = get_element_classes($element);
      // Aus: _cssClasses (String) + _cssGlobalClasses (Array)
   
   c) Style-IDs finden (WICHTIG!)
      $style_ids = get_element_style_ids($element);
      
      // Method 1: Global Classes
      if (_cssGlobalClasses exists) {
          $style_map = get_option('b2e_style_map');
          foreach (_cssGlobalClasses as $bricks_id) {
              $style_ids[] = $style_map[$bricks_id];
          }
      }
      
      // Method 2: Normale Klassen (NEU!)
      $etch_styles = get_option('etch_styles');
      foreach ($classes as $class_name) {
          foreach ($etch_styles as $id => $style) {
              if ($style['selector'] === '.' . $class_name) {
                  $style_ids[] = $id;
                  break;
              }
          }
      }
   
   d) Etch Metadata erstellen
      $etchData = [
          'origin' => 'etch',
          'name' => $element['label'] ?: 'Element',
          'styles' => $style_ids,  // ← HIER KOMMEN DIE IDs REIN!
          'attributes' => [
              'data-etch-element' => $element_type,
              'class' => implode(' ', $classes)
          ],
          'block' => [
              'type' => 'html',
              'tag' => $tag
          ]
      ];
   
   e) Gutenberg Block generieren
      <!-- wp:group {
          "metadata": {
              "name": "...",
              "etchData": {...}
          },
          "className": "..."
      } -->
      <div class="wp-block-group">
          {children}
      </div>
      <!-- /wp:group -->
   
   f) Kinder rekursiv verarbeiten
      foreach ($element['children'] as $child_id) {
          $child_html = generate_block_html($child, $element_map);
      }
```

### Phase 3: Post-Übertragung (API)

```php
// api_client.php :: send_post()

1. Post-Daten vorbereiten
   $post_data = [
       'post' => [
           'ID' => $post->ID,
           'post_title' => $post->post_title,
           'post_type' => $post->post_type,
           'post_status' => $post->post_status,
       ],
       'etch_content' => $gutenberg_blocks  // Generierter Content!
   ];

2. API Request
   POST /wp-json/b2e/v1/receive-post
   Body: $post_data
```

### Phase 4: Post-Import (Etch-Seite)

```php
// api_endpoints.php :: receive_migrated_post()

1. Post erstellen/aktualisieren
   $post_id = wp_insert_post([
       'post_title' => $post_data['post_title'],
       'post_content' => $post_data['etch_content'],  // Gutenberg Blocks!
       'post_type' => $post_data['post_type'],
       'post_status' => 'publish'
   ]);

2. Meta-Daten speichern
   update_post_meta($post_id, '_migrated_from_bricks', true);
```

---

## ID-Mapping System

### Warum brauchen wir ID-Mapping?

**Problem:** Bricks und Etch verwenden unterschiedliche ID-Systeme:
- **Bricks:** Zufällige IDs wie `bTyScxgmzei`
- **Etch:** MD5-Hash-basierte IDs wie `8c6eb1b`

**Lösung:** Mapping-Tabelle auf Bricks-Seite

### ID-Generierung

```php
// css_converter.php :: generate_style_hash()

function generate_style_hash($class_name) {
    return substr(md5($class_name), 0, 7);
}

// Beispiel:
md5('fr-intro-alpha') = '8c6eb1b...'
substr(..., 0, 7) = '8c6eb1b'
```

**Wichtig:** Die ID wird aus dem **Klassennamen** generiert, nicht aus der Bricks-ID!

### Mapping-Verwendung

```php
// Während CSS-Migration
$style_map[$bricks_id] = $etch_id;
// 'bTyScxgmzei' => '8c6eb1b'

// Während Content-Migration
$etch_id = $style_map[$bricks_id];
// Lookup: 'bTyScxgmzei' → '8c6eb1b'

// Im generierten Content
"styles": ["8c6eb1b"]  // Etch-ID wird verwendet!
```

---

## Frontend-Rendering

### Wie Etch Styles rendert

```php
// Etch Plugin: classes/Preprocessor/Registry/StylesRegister.php

1. Block-Verarbeitung (während Rendering)
   
   Block-Parser liest etchData:
   {
       "styles": ["etch-container-style", "8c6eb1b"]
   }
   
   StylesRegister::register_styles(['etch-container-style', '8c6eb1b']);
   // Markiert diese Styles als "auf dieser Seite verwendet"

2. wp_head Hook (Priority 99)
   
   render_frontend_styles() {
       // Nur registrierte Styles laden
       $styles_to_render = self::$page_styles;
       
       // Aus etch_styles DB laden
       $all_styles = get_option('etch_styles');
       
       // CSS generieren
       foreach ($styles_to_render as $style_id) {
           $style = $all_styles[$style_id];
           $css = preprocess_css($style['css'], $style['selector']);
           echo "<style>{$style['selector']} { {$css} }</style>";
       }
   }
```

### Warum Styles nicht gerendert wurden (vor dem Fix)

```
❌ VORHER:
Block: "styles": ["etch-container-style"]  // Nur Element-Style
                                            // Keine Custom-Class-ID!
↓
StylesRegister registriert nur: ["etch-container-style"]
↓
Frontend rendert nur: Element-Styles
↓
Custom-Klassen haben kein CSS!

✅ NACHHER:
Block: "styles": ["etch-container-style", "8c6eb1b"]  // Mit Custom-ID!
↓
StylesRegister registriert: ["etch-container-style", "8c6eb1b"]
↓
Frontend rendert: Element-Styles + Custom-Class-Styles
↓
Alles funktioniert! 🎉
```

---

## Troubleshooting

### Problem: Styles werden nicht im Frontend gerendert

**Symptome:**
- Styles sind in `etch_styles` vorhanden
- Posts sind migriert
- Aber Frontend zeigt keine Styles

**Diagnose:**
```bash
# 1. Post-Content prüfen
docker exec b2e-etch wp post get POST_ID --field=post_content --allow-root | grep '"styles"'

# Sollte zeigen:
"styles":["etch-container-style","8c6eb1b"]

# Wenn nur:
"styles":["etch-container-style"]
# → Style-IDs fehlen!
```

**Lösung:**
- Neue Migration durchführen (mit aktuellem Code)
- `get_element_style_ids()` muss beide Methoden verwenden

### Problem: Style-IDs fehlen in migrierten Posts

**Ursache:**
- `get_element_style_ids()` findet keine Matches
- Style-Map ist leer
- `etch_styles` ist nicht verfügbar

**Diagnose:**
```bash
# Style-Map prüfen (Bricks-Seite)
docker exec b2e-bricks wp option get b2e_style_map --format=json --allow-root

# Etch-Styles prüfen (Bricks-Seite während Migration!)
docker exec b2e-bricks wp option get etch_styles --format=json --allow-root
```

**Lösung:**
- CSS-Migration VOR Content-Migration durchführen
- `etch_styles` muss auf Bricks-Seite verfügbar sein (wird von Etch-API zurückgegeben)

### Problem: Klassennamen stimmen nicht überein

**Symptome:**
- Style-ID wird nicht gefunden
- Klassenname im HTML ≠ Selector in etch_styles

**Diagnose:**
```bash
# Klassen im Post
docker exec b2e-etch wp post get POST_ID --field=post_content --allow-root | grep 'className'

# Styles in DB
docker exec b2e-etch wp option get etch_styles --format=json --allow-root | jq '.[] | select(.selector | contains("fr-intro"))'
```

**Lösung:**
- Klassennamen-Normalisierung prüfen
- ACSS-Prefix-Entfernung prüfen (`acss_import_` → ``)

---

## Best Practices

### 1. Migrations-Reihenfolge

```
1. CSS-Migration durchführen
   ↓
2. Warten bis abgeschlossen
   ↓
3. Content-Migration durchführen
```

**Warum?** Content-Migration braucht `etch_styles` für ID-Lookup!

### 2. Testing

```bash
# Nach CSS-Migration
./verify-css-migration.sh

# Nach Content-Migration
docker exec b2e-etch wp post get POST_ID --field=post_content --allow-root | grep '"styles"'
```

### 3. Debugging

```bash
# Logs in Echtzeit verfolgen
docker logs -f b2e-bricks 2>&1 | grep "B2E"
docker logs -f b2e-etch 2>&1 | grep "B2E"
```

### 4. Cache Management

```bash
# Nach jeder Migration
docker exec b2e-etch wp cache flush --allow-root
docker exec b2e-etch wp option update etch_svg_version $(($(docker exec b2e-etch wp option get etch_svg_version --allow-root) + 1)) --allow-root
```

---

## Wichtige Code-Stellen

### CSS-Konvertierung
- `css_converter.php::convert_bricks_classes_to_etch()` - Hauptkonvertierung
- `css_converter.php::generate_style_hash()` - ID-Generierung
- `css_converter.php::convert_bricks_settings_to_css()` - Settings → CSS

### Content-Konvertierung
- `gutenberg_generator.php::generate_gutenberg_blocks()` - Haupteinstieg
- `gutenberg_generator.php::get_element_style_ids()` - **KRITISCH!** ID-Lookup
- `gutenberg_generator.php::get_element_classes()` - Klassen-Extraktion

### API
- `api_client.php::send_css_styles()` - CSS senden
- `api_client.php::send_post()` - Post senden
- `api_endpoints.php::import_css_classes()` - CSS empfangen
- `api_endpoints.php::receive_migrated_post()` - Post empfangen

---

**Dokumentation erstellt:** 19. Oktober 2025
**Version:** 0.3.7
**Autor:** Tobias Haas
