# CSS-Klassen Migration - Finale Lösung

## 🎯 Problem-Übersicht

**Ursprüngliches Problem:** CSS-Klassen wurden nicht im Frontend-HTML gerendert, obwohl die Styles korrekt migriert wurden.

**Ursache:** Etch rendert CSS-Klassen aus `etchData.attributes.class`, **NICHT** aus den Style-IDs in `etchData.styles`.

## ✅ Finale Lösung

### Kern-Prinzip

Für **jedes** Element müssen CSS-Klassen in `etchData.attributes.class` gesetzt werden:

```json
{
  "metadata": {
    "etchData": {
      "styles": ["abc123"],           // ← Für CSS-Generierung im <head>
      "attributes": {
        "class": "my-css-class"       // ← Für Frontend-Rendering im HTML
      }
    }
  }
}
```

## 📋 Element-spezifische Implementierung

### 1. **Headings** (h1-h6)

**Datei:** `gutenberg_generator.php` (Zeile ~1175-1200)

**Implementierung:**
```php
case 'heading':
    // Get style IDs
    $style_ids = $this->get_element_style_ids($element);
    
    // Convert style IDs to CSS class names
    $css_classes = $this->get_css_classes_from_style_ids($style_ids);
    
    // Build block attributes
    $block_attrs = array(
        'level' => intval(str_replace('h', '', $level)),
        'metadata' => array(
            'name' => $element_label,
            'etchData' => array(
                'origin' => 'etch',
                'name' => $element_label,
                'styles' => $style_ids,
                'attributes' => !empty($css_classes) ? array('class' => $css_classes) : array(),
                'block' => array(
                    'type' => 'html',
                    'tag' => $level,
                ),
            )
        )
    );
```

**Ergebnis:**
- **Datenbank:** `"attributes":{"class":"fr-intro-alpha__heading"}`
- **Frontend:** `<h2 class="fr-intro-alpha__heading">Section heading</h2>`

---

### 2. **Paragraphs** (p)

**Datei:** `gutenberg_generator.php` (Zeile ~1210-1235)

**Implementierung:**
```php
case 'paragraph':
    // Get style IDs
    $style_ids = $this->get_element_style_ids($element);
    
    // Convert style IDs to CSS class names
    $css_classes = $this->get_css_classes_from_style_ids($style_ids);
    
    // Build block attributes
    $block_attrs = array(
        'metadata' => array(
            'name' => $element_label,
            'etchData' => array(
                'origin' => 'etch',
                'name' => $element_label,
                'styles' => $style_ids,
                'attributes' => !empty($css_classes) ? array('class' => $css_classes) : array(),
                'block' => array(
                    'type' => 'html',
                    'tag' => 'p',
                ),
            )
        )
    );
```

**Ergebnis:**
- **Datenbank:** `"attributes":{"class":"fr-accent-heading fr-intro-alpha__accent-heading"}`
- **Frontend:** `<p class="fr-accent-heading fr-intro-alpha__accent-heading">Accent heading</p>`

---

### 3. **Images** (figure + img)

**Datei:** `gutenberg_generator.php` (Zeile ~1280-1315)

**Besonderheit:** Images verwenden `<figure>` als Container, nicht `<img>` direkt!

**Implementierung:**
```php
case 'image':
    // Get style IDs
    $img_style_ids = $this->get_element_style_ids($element);
    
    // Convert style IDs to CSS class names
    $img_css_classes = $this->get_css_classes_from_style_ids($img_style_ids);
    
    // Build block attributes
    $block_attrs = array(
        'metadata' => array(
            'name' => $element_label,
            'etchData' => array(
                'origin' => 'etch',
                'name' => $element_label,
                'styles' => $img_style_ids,
                'attributes' => !empty($img_css_classes) ? array('class' => $img_css_classes) : array(),
                'block' => array(
                    'type' => 'html',
                    'tag' => 'figure',  // ← WICHTIG: figure, nicht img!
                ),
            )
        )
    );
    
    // HTML: Klasse auf <figure>, nicht auf <img>
    return '<!-- wp:image ' . json_encode($block_attrs) . ' -->' . "\n" .
           '<figure' . $img_class_attr . '><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '"/></figure>' . "\n" .
           '<!-- /wp:image -->';
```

**Wichtig:**
- ✅ `block.tag = 'figure'` (nicht `'img'`)
- ✅ CSS-Klasse auf `<figure>` im HTML
- ✅ CSS-Klasse in `etchData.attributes.class`

**Ergebnis:**
- **Datenbank:** `"attributes":{"class":"feature-section-frankfurt__media"}`
- **Frontend:** `<figure class="feature-section-frankfurt__media"><img src="..."></figure>`

**Fehler vermeiden:**
- ❌ `block.tag = 'img'` → Etch rendert leeres `<img class="...">` + echtes `<img src="...">`
- ❌ Klasse nur im HTML → Etch entfernt sie beim Rendern
- ❌ Klasse auf `<img>` → Sollte auf `<figure>` sein

---

### 4. **Sections** (section)

**Datei:** `gutenberg_generator.php` (Zeile ~162-195)

**Implementierung:**
```php
private function convert_etch_section($element, $children, $element_map) {
    // Get style IDs
    $style_ids = $this->get_element_style_ids($element);
    
    // Add default section style
    array_unshift($style_ids, 'etch-section-style');
    
    // Convert style IDs to CSS class names
    $css_classes = $this->get_css_classes_from_style_ids($style_ids);
    
    // Build Etch-compatible attributes
    $etch_attributes = array(
        'data-etch-element' => 'section'
    );
    
    // Add CSS classes if available
    if (!empty($css_classes)) {
        $etch_attributes['class'] = $css_classes;
    }
    
    // Build attributes JSON
    $attrs = array(
        'metadata' => array(
            'name' => $label ?: 'Section',
            'etchData' => array(
                'origin' => 'etch',
                'name' => $label ?: 'Section',
                'styles' => $style_ids,
                'attributes' => $etch_attributes,
                'block' => array(
                    'type' => 'html',
                    'tag' => 'section'
                )
            )
        )
    );
}
```

**Zusätzlich:** `process_section_element()` (Zeile ~1481-1495)

```php
private function process_section_element($element, $post_id) {
    // Get style IDs
    $style_ids = $this->get_element_style_ids($element);
    
    // Convert to CSS classes
    $css_classes = $this->get_css_classes_from_style_ids($style_ids);
    
    $element['etch_type'] = 'section';
    $element['etch_data'] = array(
        'data-etch-element' => 'section',
        'class' => $css_classes,
    );
    
    return $element;
}
```

**Ergebnis:**
- **Datenbank:** `"attributes":{"data-etch-element":"section","class":"feature-section-frankfurt"}`
- **Frontend:** `<section data-etch-element="section" class="feature-section-frankfurt">...</section>`

---

### 5. **Containers** (div mit data-etch-element="container")

**Datei:** `gutenberg_generator.php` (Zeile ~227-260)

**Implementierung:**
```php
private function convert_etch_container($element, $children, $element_map) {
    // Get style IDs
    $style_ids = $this->get_element_style_ids($element);
    
    // Add default container style
    array_unshift($style_ids, 'etch-container-style');
    
    // Convert style IDs to CSS class names
    $css_classes = $this->get_css_classes_from_style_ids($style_ids);
    
    // Build Etch-compatible attributes
    $etch_attributes = array(
        'data-etch-element' => 'container'
    );
    
    // Add CSS classes if available
    if (!empty($css_classes)) {
        $etch_attributes['class'] = $css_classes;
    }
    
    // Build attributes JSON
    $attrs = array(
        'metadata' => array(
            'name' => $label ?: 'Container',
            'etchData' => array(
                'origin' => 'etch',
                'name' => $label ?: 'Container',
                'styles' => $style_ids,
                'attributes' => $etch_attributes,
                'block' => array(
                    'type' => 'html',
                    'tag' => 'div'
                )
            )
        )
    );
}
```

**Zusätzlich:** `process_container_element()` (Zeile ~1500-1514)

```php
private function process_container_element($element, $post_id) {
    // Get style IDs
    $style_ids = $this->get_element_style_ids($element);
    
    // Convert to CSS classes
    $css_classes = $this->get_css_classes_from_style_ids($style_ids);
    
    $element['etch_type'] = 'container';
    $element['etch_data'] = array(
        'data-etch-element' => 'container',
        'class' => $css_classes,
    );
    
    return $element;
}
```

**Ergebnis:**
- **Datenbank:** `"attributes":{"data-etch-element":"container","class":"fr-intro-alpha"}`
- **Frontend:** `<div data-etch-element="container" class="fr-intro-alpha">...</div>`

---

### 6. **Flex-Divs** (div mit data-etch-element="flex-div")

**Datei:** `gutenberg_generator.php` (Zeile ~290-330)

**Implementierung:** Analog zu Containers, aber mit `'data-etch-element' => 'flex-div'`

**Ergebnis:**
- **Datenbank:** `"attributes":{"data-etch-element":"flex-div","class":"feature-section-frankfurt__media-wrapper"}`
- **Frontend:** `<div data-etch-element="flex-div" class="feature-section-frankfurt__media-wrapper">...</div>`

---

## 🔧 Kern-Funktionen

### `get_css_classes_from_style_ids()`

**Datei:** `gutenberg_generator.php` (Zeile ~823-863)

**Zweck:** Konvertiert Etch Style-IDs zu CSS-Klassen-Namen

**Implementierung:**
```php
private function get_css_classes_from_style_ids($style_ids) {
    if (empty($style_ids)) {
        return '';
    }
    
    // Get style map which contains both IDs and selectors
    $style_map = get_option('b2e_style_map', array());
    $class_names = array();
    
    foreach ($style_ids as $style_id) {
        // Skip Etch internal styles
        if (in_array($style_id, ['etch-section-style', 'etch-container-style', 'etch-block-style'])) {
            continue;
        }
        
        // Find the Bricks ID for this Etch style ID
        foreach ($style_map as $bricks_id => $style_data) {
            // Handle both old format (string) and new format (array)
            $etch_id = is_array($style_data) ? $style_data['id'] : $style_data;
            
            if ($etch_id === $style_id) {
                // Get selector from style_data
                if (is_array($style_data) && !empty($style_data['selector'])) {
                    $selector = $style_data['selector'];
                    // Remove leading dot: ".my-class" => "my-class"
                    $class_name = ltrim($selector, '.');
                    // Remove pseudo-selectors and attribute selectors
                    $class_name = preg_replace('/[\[\]:].+$/', '', $class_name);
                    if (!empty($class_name)) {
                        $class_names[] = $class_name;
                    }
                }
                break;
            }
        }
    }
    
    return !empty($class_names) ? implode(' ', array_unique($class_names)) : '';
}
```

**Wichtige Features:**
- ✅ Überspringt Etch-interne Styles (`etch-section-style`, etc.)
- ✅ Verwendet `b2e_style_map` (verfügbar auf Bricks-Seite)
- ✅ Entfernt führenden Punkt von Selektoren
- ✅ Entfernt Pseudo-Selektoren (`:hover`, etc.)
- ✅ Gibt mehrere Klassen als String zurück

---

### Erweiterte Style-Map

**Datei:** `css_converter.php` (Zeile ~92-99)

**Alte Format:**
```php
$style_map[$class['id']] = $style_id;
// Ergebnis: ['bTySc123' => 'abc123']
```

**Neues Format:**
```php
$style_map[$class['id']] = array(
    'id' => $style_id,
    'selector' => $converted_class['selector']
);
// Ergebnis: ['bTySc123' => ['id' => 'abc123', 'selector' => '.my-class']]
```

**Warum wichtig:**
- Die Style-Map wird auf der **Bricks-Seite** gespeichert
- `etch_styles` existiert nur auf der **Etch-Seite**
- Beim Generieren der Gutenberg-Blöcke (Bricks-Seite) brauchen wir die Selektoren
- Lösung: Selektoren in der Style-Map speichern!

---

### Kritischer Fix: `unset()` entfernt

**Datei:** `gutenberg_generator.php` (Zeile ~1129-1132)

**Alter Code (FALSCH):**
```php
// NOTE: Do NOT include class in etchData.attributes!
// Only use etchData.styles for styling
$etch_data_attributes = $etch_data;
unset($etch_data_attributes['class']); // ← LÖSCHT die Klasse!
```

**Neuer Code (KORREKT):**
```php
// IMPORTANT: Keep 'class' in etchData.attributes!
// Etch renders CSS classes from attributes.class, not from style IDs
$etch_data_attributes = $etch_data;
// ← KEIN unset() mehr!
```

**Warum kritisch:**
- `process_container_element()` setzte `$etch_data['class']` korrekt
- Aber Zeile 1132 **löschte** die Klasse wieder!
- Result: Container ohne CSS-Klassen im Frontend

---

## 🎯 Workflow-Übersicht

### 1. CSS-Migration (Etch-Seite)

```
Bricks Global Classes
  ↓
css_converter.php
  ↓
Etch Styles (etch_styles Option)
  ↓
Style-Map (b2e_style_map Option)
  → ['bricks_id' => ['id' => 'etch_id', 'selector' => '.css-class']]
```

### 2. Content-Migration (Bricks-Seite)

```
Bricks Element
  ↓
get_element_style_ids()
  → Findet Bricks Global Classes
  → Konvertiert zu Etch Style-IDs
  ↓
get_css_classes_from_style_ids()
  → Liest b2e_style_map
  → Extrahiert Selektoren
  → Entfernt führenden Punkt
  ↓
Gutenberg Block mit etchData.attributes.class
```

### 3. Frontend-Rendering (Etch-Seite)

```
Gutenberg Block
  ↓
Etch liest etchData.attributes.class
  ↓
Rendert CSS-Klassen im HTML
  ↓
<div class="my-css-class">...</div>
```

---

## 📊 Vergleich: Vorher vs. Nachher

### Vorher (FALSCH)

**Annahme:** Etch rendert Klassen aus `etchData.styles`

```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {}  // ← Leer!
  }
}
```

**Frontend:**
```html
<div data-etch-element="container">...</div>
<!-- Keine CSS-Klasse! -->
```

### Nachher (KORREKT)

**Erkenntnis:** Etch rendert Klassen aus `etchData.attributes.class`

```json
{
  "etchData": {
    "styles": ["abc123"],           // ← Für CSS-Generierung
    "attributes": {
      "class": "my-css-class"       // ← Für Frontend-Rendering
    }
  }
}
```

**Frontend:**
```html
<div data-etch-element="container" class="my-css-class">...</div>
<!-- CSS-Klasse vorhanden! ✅ -->
```

---

## 🐛 Häufige Fehler & Lösungen

### Fehler 1: Klassen nur in `etchData.styles`

**Problem:**
```json
"etchData": {
  "styles": ["abc123"],
  "attributes": {}
}
```

**Lösung:**
```json
"etchData": {
  "styles": ["abc123"],
  "attributes": {
    "class": "my-css-class"
  }
}
```

---

### Fehler 2: Images mit `block.tag = 'img'`

**Problem:**
```json
"block": {
  "tag": "img"
}
```

**Frontend:**
```html
<img class="my-class">  <!-- Leer! -->
<img src="...">         <!-- Echtes Bild -->
```

**Lösung:**
```json
"block": {
  "tag": "figure"
}
```

**Frontend:**
```html
<figure class="my-class">
  <img src="...">
</figure>
```

---

### Fehler 3: `unset($etch_data_attributes['class'])`

**Problem:** Klasse wird gesetzt, aber dann wieder gelöscht

**Lösung:** `unset()` entfernen!

---

### Fehler 4: Etch-interne Styles in Style-Map suchen

**Problem:**
```php
$style_ids = ['etch-section-style', 'abc123'];
// Sucht nach 'etch-section-style' in b2e_style_map
// Findet nichts → gibt leeren String zurück
```

**Lösung:**
```php
// Skip Etch internal styles
if (in_array($style_id, ['etch-section-style', 'etch-container-style', 'etch-block-style'])) {
    continue;
}
```

---

## ✅ Checkliste für neue Elemente

Wenn du ein neues Element-Typ hinzufügst:

1. ✅ `get_element_style_ids()` aufrufen
2. ✅ `get_css_classes_from_style_ids()` aufrufen
3. ✅ CSS-Klassen in `etchData.attributes.class` setzen
4. ✅ Für Images: `block.tag = 'figure'` verwenden
5. ✅ Für Images: Klasse auf `<figure>` setzen, nicht auf `<img>`
6. ✅ **KEIN** `unset($attributes['class'])`!
7. ✅ Testen: Klasse im Frontend-HTML vorhanden?

---

## 🎉 Erfolgs-Kriterien

Eine erfolgreiche Migration zeigt:

### Datenbank
```json
{
  "metadata": {
    "etchData": {
      "styles": ["abc123"],
      "attributes": {
        "class": "my-css-class"
      }
    }
  }
}
```

### Frontend
```html
<div class="my-css-class">Content</div>
```

### CSS im `<head>`
```css
.my-css-class {
  /* Styles from Bricks */
}
```

---

## 📝 Zusammenfassung

**Kern-Erkenntnis:** Etch rendert CSS-Klassen aus `etchData.attributes.class`, **NICHT** aus `etchData.styles`!

**Lösung:**
1. Style-Map erweitert um Selektoren
2. `get_css_classes_from_style_ids()` konvertiert IDs → Klassen
3. Klassen in `etchData.attributes.class` setzen
4. Für **ALLE** Element-Typen implementiert
5. `unset()` entfernt

**Ergebnis:** ✅ Alle CSS-Klassen werden korrekt im Frontend gerendert!

---

**Datum:** 21. Oktober 2025, 22:06 Uhr  
**Status:** ✅ Vollständig funktionsfähig  
**Getestet:** Headings, Paragraphs, Images, Sections, Containers, Flex-Divs
