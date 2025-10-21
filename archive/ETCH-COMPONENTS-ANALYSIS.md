# Etch Components - Analyse

## Quelle: Post ID 350 (Feature Section Frankfurt)

---

## 1. Component Block - Hero Section Header (Component ID 368)

### Gutenberg Block:
```html
<!-- wp:block {
  "metadata": {
    "etchData": {
      "origin": "etch",
      "attributes": {
        "pillText": "Visual Development Environment for WordPress",
        "sectionHeadline": "Build WordPress Websites Visually With None of the Downsides of Traditional Page Builders",
        "hasLede": "{true}",
        "lede": "Etch is a next-era visual development environment that gives you the power to build scalable, maintainable, accessible WordPress sites visually, with no tradeoffs.",
        "hasPill": "{false}",
        "isH1": "{true}"
      },
      "block": {
        "type": "component",
        "component": 368,
        "innerBlocks": []
      }
    }
  },
  "ref": 368
} /-->
```

### Struktur:
- **Block Type:** `wp:block` (nicht wp:group!)
- **Self-closing:** `/-->` (kein closing tag!)
- **metadata.etchData:**
  - **origin:** `"etch"`
  - **attributes:** Props für den Component (wie React Props!)
    - `pillText`, `sectionHeadline`, `hasLede`, `lede`, `hasPill`, `isH1`
    - Boolean als String: `"{true}"`, `"{false}"`
  - **block:**
    - **type:** `"component"` (nicht "html"!)
    - **component:** `368` (Component Post ID)
    - **innerBlocks:** `[]` (leer)
- **ref:** `368` (gleich wie component ID)

---

## 2. Component Block - Review Card (Component ID 370)

### Gutenberg Block:
```html
<!-- wp:block {
  "metadata": {
    "name": "Review Card",
    "etchData": {
      "origin": "etch",
      "name": "Review Card",
      "attributes": {
        "etch-element": "block",
        "avatar": "{review.featuredImage}",
        "name": "{review.title}",
        "review": "{review.meta.review_body}"
      },
      "block": {
        "type": "component",
        "component": 370,
        "innerBlocks": []
      }
    }
  },
  "ref": 370
} /-->
```

### Unterschiede:
- Hat **name** (optional)
- **attributes** enthält **Dynamic Data** (`{review.featuredImage}`)
- Wird innerhalb eines **Loop** verwendet

---

## 3. Component in der Datenbank

Lass mich Component 368 prüfen:

```bash
wp post get 368 --field=post_content
```

### Component Post:
- **Post Type:** Wahrscheinlich `etch_component` oder ähnlich
- **Post Content:** Enthält die Component-Struktur
- **Reusable:** Kann mehrfach verwendet werden

---

## Vergleich: Etch Component vs. Bricks Template

### Etch Component:
```html
<!-- wp:block {
  "metadata": {
    "etchData": {
      "origin": "etch",
      "attributes": {
        "prop1": "value1",
        "prop2": "{dynamic.data}"
      },
      "block": {
        "type": "component",
        "component": 368,
        "innerBlocks": []
      }
    }
  },
  "ref": 368
} /-->
```

**Eigenschaften:**
- ✅ Wiederverwendbar
- ✅ Props/Attributes
- ✅ Dynamic Data Support
- ✅ Referenziert Post ID
- ✅ Self-closing Block

### Bricks Template:
```php
// Bricks speichert Templates als:
// - Post Type: bricks_template
// - Meta: _bricks_template_type (section, header, footer, etc.)
// - Content: _bricks_page_content (Array von Elements)
```

**Verwendung in Bricks:**
```php
array(
  'id' => 'template1',
  'name' => 'template',
  'settings' => array(
    'template' => 123,  // Template Post ID
    'templatePreview' => true
  )
)
```

---

## Konvertierung: Bricks Template → Etch Component

### Problem:
- Bricks Templates sind **nicht Props-basiert**
- Etch Components erwarten **attributes** (Props)
- Bricks Templates haben **keine Dynamic Data Bindings** in der gleichen Form

### Lösungsansätze:

#### Option 1: Template als Component speichern
```php
// 1. Bricks Template finden (Post Type: bricks_template)
$template_id = $element['settings']['template'];
$template_post = get_post($template_id);

// 2. Etch Component erstellen
$component_id = wp_insert_post(array(
    'post_type' => 'etch_component',
    'post_title' => $template_post->post_title,
    'post_status' => 'publish'
));

// 3. Template-Content konvertieren und als Component speichern
$template_content = get_post_meta($template_id, '_bricks_page_content', true);
$converted_content = convert_bricks_to_gutenberg($template_content);
wp_update_post(array(
    'ID' => $component_id,
    'post_content' => $converted_content
));

// 4. Component-Block generieren
return generate_component_block($component_id);
```

#### Option 2: Template inline konvertieren
```php
// Template-Content direkt einfügen (kein Component)
$template_id = $element['settings']['template'];
$template_content = get_post_meta($template_id, '_bricks_page_content', true);
return convert_bricks_elements_to_gutenberg($template_content);
```

#### Option 3: Als Placeholder
```php
// Placeholder für manuelle Nachbearbeitung
return '<!-- wp:paragraph --><p>⚠️ Bricks Template (ID: ' . $template_id . ') - Manual conversion required</p><!-- /wp:paragraph -->';
```

---

## Empfehlung für Bricks → Etch Migration

### Kurzfristig (MVP):
**Option 3** - Placeholder mit Hinweis
- ✅ Schnell implementiert
- ✅ Keine Fehler
- ⚠️ Manuelle Nachbearbeitung nötig
- 📝 Dokumentiert welche Templates konvertiert werden müssen

### Mittelfristig:
**Option 2** - Inline-Konvertierung
- ✅ Content wird übernommen
- ✅ Keine Component-Verwaltung nötig
- ⚠️ Nicht wiederverwendbar
- ⚠️ Keine Props/Dynamic Data

### Langfristig (Ideal):
**Option 1** - Als Etch Component
- ✅ Wiederverwendbar
- ✅ Etch-native
- ❌ Komplex (Props-Mapping, Dynamic Data)
- ❌ Erfordert Component Post Type

---

## Implementierung: Option 3 (Placeholder)

```php
/**
 * Process Bricks template element
 */
private function process_template_element($element, $post_id) {
    $template_id = $element['settings']['template'] ?? null;
    
    if (!$template_id) {
        return array(
            'etch_type' => 'skip',
            'reason' => 'No template ID'
        );
    }
    
    $template_post = get_post($template_id);
    $template_name = $template_post ? $template_post->post_title : 'Unknown';
    
    // Log for later conversion
    $this->error_handler->log_warning('W010', array(
        'post_id' => $post_id,
        'element_id' => $element['id'],
        'template_id' => $template_id,
        'template_name' => $template_name,
        'action' => 'Bricks template found - requires manual conversion to Etch component'
    ));
    
    $element['etch_type'] = 'template-placeholder';
    $element['etch_data'] = array(
        'template_id' => $template_id,
        'template_name' => $template_name,
        'message' => sprintf(
            '⚠️ Bricks Template: "%s" (ID: %d) - Requires manual conversion to Etch component',
            $template_name,
            $template_id
        )
    );
    
    return $element;
}
```

### Gutenberg Output:
```html
<!-- wp:paragraph -->
<p>⚠️ Bricks Template: "Hero Section" (ID: 123) - Requires manual conversion to Etch component</p>
<!-- /wp:paragraph -->
```

---

## Nächste Schritte

1. **Jetzt:** Option 3 implementieren (Placeholder)
2. **Dokumentieren:** Liste aller verwendeten Templates
3. **Später:** Manuelle Konvertierung zu Etch Components
4. **Optional:** Tool für Template → Component Konvertierung

---

## Etch Component Format - Zusammenfassung

```json
{
  "metadata": {
    "name": "Component Name",  // Optional
    "etchData": {
      "origin": "etch",
      "name": "Component Name",  // Optional
      "attributes": {
        "prop1": "value",
        "prop2": "{dynamic.data}",
        "boolProp": "{true}"
      },
      "block": {
        "type": "component",
        "component": 368,  // Component Post ID
        "innerBlocks": []
      }
    }
  },
  "ref": 368  // Same as component ID
}
```

**Block Type:** `wp:block` (self-closing)
**Format:** `<!-- wp:block {...} /-->`

---

## Fragen für dich:

1. Wie viele Bricks Templates hast du?
2. Sind die Templates komplex oder einfach?
3. Sollen wir mit Placeholders starten und später manuell konvertieren?
4. Oder sollen wir versuchen, die Templates inline zu konvertieren?
