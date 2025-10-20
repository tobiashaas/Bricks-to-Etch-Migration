# Content & CSS Konvertierung: Bricks → Etch

## Zusammenfassung aus dem Masterplan

Basierend auf dem detaillierten Masterplan (`Masterplan/bricks-etch-migration-complete-plan.md`)

---

## 🎯 Kernproblem

**Bricks und Etch speichern Content KOMPLETT VERSCHIEDEN!**

| Aspect | Bricks (Source) | Etch (Target) |
|--------|-----------------|---------------|
| **post_content** | ❌ **LEER!** | ✅ **GEFÜLLT!** |
| **Content Storage** | `_bricks_page_content_2` (meta) | `post_content` (Gutenberg) |
| **Format** | Serialized PHP Array | Gutenberg Blocks |
| **Styles** | `bricks_global_classes` (options) | `etch_styles` (options) |

---

## 📄 Content-Konvertierung

### Bricks Format (Source)

**wp_postmeta:**
```php
'_bricks_page_content_2' => array(
    0 => array(
        'id' => '953e49',
        'name' => 'section',
        'parent' => 0,
        'children' => array('def456'),
        'settings' => array(
            'background' => array('color' => '#ffffff'),
            '_cssClasses' => 'my-section-class',
            'tag' => 'section'
        ),
        'content' => array(
            'text' => 'Welcome {post_title}' // Dynamic data!
        )
    )
);
```

### Etch Format (Target)

**post_content (Gutenberg):**
```html
<!-- wp:group {"metadata":{"name":"Section","etchData":{"origin":"etch","name":"Section","styles":["my-section-class"],"attributes":{"data-etch-element":"section","class":"my-section-class"},"block":{"type":"html","tag":"section"}}}} -->
<div class="wp-block-group">
    <p>Welcome {this.title}</p>
</div>
<!-- /wp:group -->
```

**Frontend Rendering:**
```html
<section data-etch-element="section" class="my-section-class">
    <p>Welcome {this.title}</p>
</section>
```

### Element-Mapping

```
Bricks              →  Etch
─────────────────────────────────────
brxe-section        →  data-etch-element="section"
brxe-container      →  data-etch-element="container"
brxe-block          →  data-etch-element="flex-div"
brxe-div            →  (empty) oder flex-div
iframe              →  data-etch-element="iframe"
```

### etchData Struktur

```json
{
  "origin": "etch",
  "name": "Section",
  "styles": ["style-id-1", "style-id-2"],
  "attributes": {
    "data-etch-element": "section",
    "class": "my-custom-class"
  },
  "block": {
    "type": "html",
    "tag": "section"
  }
}
```

---

## 🎨 CSS-Konvertierung

### Bricks CSS Format (Source)

**wp_options: `bricks_global_classes`**
```php
array(
    array(
        'id' => 'my-button-class',
        'name' => 'My Button Class',
        'settings' => array(
            'background' => array('color' => '#007cba'),
            'border' => array('radius' => '4px'),
            'typography' => array('font-size' => '16px'),
            '_cssCustom' => '.my-button:hover { background: #005a8b; }',
            '_cssGlobalClasses' => array('global-class-1')
        )
    )
);
```

### Etch CSS Format (Target)

**wp_options: `etch_styles`**
```php
array(
    // 1. ELEMENT STYLES (readonly)
    'etch-section-style' => array(
        'type' => 'element',
        'selector' => ':where([data-etch-element="section"])',
        'collection' => 'default',
        'css' => 'inline-size: 100%; display: flex; flex-direction: column;',
        'readonly' => true
    ),
    
    // 2. CSS VARIABLES (in :root)
    'etch-global-variable-style' => array(
        'type' => 'custom',
        'selector' => ':root',
        'collection' => 'default',
        'css' => '--test: #fff; --example: #000;',
        'readonly' => false
    ),
    
    // 3. USER CLASSES (mit Hash-IDs)
    '054usim' => array(
        'type' => 'class',
        'selector' => '.test',
        'collection' => 'default',
        'css' => 'padding: 2em; background: lightgoldenrodyellow;',
        'readonly' => false
    )
);
```

### CSS-Konvertierungs-Regeln

1. **Flat CSS → Nested CSS**
   ```css
   /* Bricks (Flat) */
   .button { background: blue; }
   .button:hover { background: darkblue; }
   
   /* Etch (Nested) */
   .button {
     background: blue;
     &:hover {
       background: darkblue;
     }
   }
   ```

2. **CSS Variables → :root**
   ```css
   /* Extract all CSS variables */
   --primary-color: #007cba;
   --spacing: 2rem;
   
   /* Move to etch-global-variable-style */
   ```

3. **Media Queries → Nested**
   ```css
   /* Bricks (Flat) */
   .container { width: 100%; }
   @media (min-width: 768px) {
     .container { width: 750px; }
   }
   
   /* Etch (Nested) */
   .container {
     width: 100%;
     @media (min-width: 768px) {
       width: 750px;
     }
   }
   ```

4. **Style IDs**
   - Element styles: `etch-{element}-style`
   - CSS variables: `etch-global-variable-style`
   - User classes: 7-character hash (z.B. `054usim`)

---

## 🔄 Dynamic Data Konvertierung

### Bricks → Etch Mapping

```php
// Post Data
'{post_title}'           → '{this.title}'
'{post_content}'         → '{this.content}'
'{post_excerpt}'         → '{this.excerpt}'
'{featured_image}'       → '{this.image.url}'

// Author Data
'{author_name}'          → '{this.author.name}'
'{author_id}'            → '{this.author.id}'

// User Data
'{user_id}'              → '{user.id}'
'{user_email}'           → '{user.email}'

// Site Data
'{site_title}'           → '{site.name}'
'{home_url}'             → '{site.home_url}'
```

### Custom Fields

**ACF:**
```php
// Bricks
{acf_headline}
{acf_image:url}

// Etch
{this.acf.headline}
{this.acf.image.url}
```

**MetaBox:**
```php
// Bricks
{mb_headline}

// Etch
{this.metabox.headline}
```

**JetEngine:**
```php
// Etch
{this.jetengine.headline}
```

### Image Fields

```php
// ACF Image Field (Full Object)
{this.acf.image_field.url}
{this.acf.image_field.alt}
{this.acf.image_field.sizes.medium.url}
{this.acf.image_field.sizes.medium.width}
```

### Repeater/Gallery Loops

```php
// ACF Repeater
{#loop this.acf.faq as faq}
  <div class="faq">
    <h3>{faq.question}</h3>
    <p>{faq.answer}</p>
  </div>
{/loop}

// ACF Gallery
{#loop this.acf.gallery_field as image}
  <img src="{image.url}" alt="{image.alt}" />
{/loop}
```

---

## 🛠️ Implementierte Klassen

### Content-Konvertierung

1. **`content_parser.php`**
   - Liest `_bricks_page_content_2`
   - Parst Bricks Element-Struktur
   - Extrahiert Settings, Children, Content

2. **`gutenberg_generator.php`**
   - Generiert Gutenberg Blocks
   - Erstellt `etchData` Struktur
   - Baut HTML-Kommentare

3. **`dynamic_data_converter.php`**
   - Konvertiert `{post_title}` → `{this.title}`
   - Mapped 50+ Bricks Tags
   - Unterstützt Modifiers

### CSS-Konvertierung

1. **`css_converter.php`**
   - Liest `bricks_global_classes`
   - Konvertiert zu `etch_styles` Format
   - Generiert Hash-IDs für User Classes

2. **CSS Nesting Parser** (falls vorhanden)
   - Flat CSS → Nested CSS
   - Media Queries verschachteln
   - CSS Variables extrahieren

---

## 📋 Migration-Prozess

### Schritt-für-Schritt

1. **Validation**
   - Plugins prüfen (ACF, MetaBox, etc.)
   - Warnings bei Mismatch

2. **Custom Post Types**
   - Exportieren & Registrieren

3. **ACF Field Groups**
   - Via `acf_get_field_groups()` exportieren
   - Via `acf_import_field_group()` importieren

4. **CSS Classes**
   - Bricks global classes exportieren
   - Zu Nested CSS konvertieren
   - In `etch_styles` importieren

5. **Posts & Content**
   - `_bricks_page_content_2` lesen
   - Zu Gutenberg konvertieren
   - In `post_content` schreiben
   - Alle `wp_postmeta` migrieren

6. **Finalization**
   - Rewrite rules flushen
   - Transients clearen

---

## 🔍 Wichtige Erkenntnisse

### Bricks speichert NICHTS in post_content!
```sql
-- Bricks
post_content: (LEER!)
meta_key: '_bricks_page_content_2' (Serialized Array)

-- Etch
post_content: '<!-- wp:group {...} -->' (Gutenberg Blocks)
meta_key: (Nur Standard WordPress)
```

### Etch verwendet Standard Gutenberg!
- **KEINE** Custom Block Types
- **Standard** `wp:group` Blocks
- **Metadata** mit `etchData` Objekt
- **Frontend** rendert mit `data-etch-element`

### CSS wird komplett umstrukturiert!
- Flat → Nested
- Arrays → Strings
- Bricks IDs → Etch Hash-IDs
- Neue Struktur mit `type`, `selector`, `collection`

---

## 📚 Weitere Ressourcen

### Im Projekt vorhanden:

1. **Masterplan** (6140 Zeilen!)
   - `/Masterplan/bricks-etch-migration-complete-plan.md`
   - Komplette Spezifikation
   - Code-Beispiele
   - DB-Strukturen

2. **Implementierte Klassen:**
   - `content_parser.php` - Bricks Content parsen
   - `gutenberg_generator.php` - Gutenberg Blocks generieren
   - `css_converter.php` - CSS konvertieren
   - `dynamic_data_converter.php` - Dynamic Data mappen

3. **Etch Dokumentation:**
   - https://docs.etchwp.com/dynamic-data/dynamic-data-keys
   - https://docs.etchwp.com/integrations/custom-fields

---

## ✅ Nächste Schritte

1. **Content Parser testen**
   - Bricks Content lesen
   - Element-Struktur verstehen

2. **Gutenberg Generator testen**
   - etchData Struktur generieren
   - Gutenberg Blocks erstellen

3. **CSS Converter testen**
   - Bricks Classes lesen
   - Zu Etch Format konvertieren

4. **Integration testen**
   - Kompletter Flow: Bricks → Etch
   - Mit echten Daten testen

---

**Der Masterplan enthält ALLE Details! 🎯**

Soll ich spezifische Teile genauer erklären oder direkt mit der Implementierung anfangen?
