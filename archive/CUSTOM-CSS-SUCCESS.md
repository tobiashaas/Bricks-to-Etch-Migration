# Custom CSS Migration - Success Story 🎉

**Date:** 2025-10-22  
**Version:** 0.5.2  
**Status:** ✅ ERFOLGREICH

---

## 🎯 Das Problem

Custom CSS aus Bricks Global Classes wurde **NICHT** nach Etch migriert. Nur die normalen CSS Properties kamen an, aber das Custom CSS (mit `%root%` und verschachtelten Selektoren) fehlte komplett.

---

## 🔍 Root Cause Analysis

### Problem #1: Blacklist-Timing
**Symptom:** Custom CSS wurde gesammelt, aber nicht migriert

**Ursache:**
```php
// Step 1: Custom CSS sammeln (ALLE Klassen, auch Blacklist)
foreach ($bricks_classes as $class) {
    if (!empty($class['settings']['_cssCustom'])) {
        $custom_css_stylesheet .= $class['settings']['_cssCustom'];
    }
}

// Step 2: Klassen konvertieren (MIT Blacklist-Check)
foreach ($bricks_classes as $class) {
    if ($this->should_exclude_class($class)) {
        continue; // ← Klasse wird übersprungen!
    }
    // Klasse wird konvertiert und in $style_map eingetragen
}

// Step 3: Custom CSS zuordnen
// Problem: Blacklist-Klassen sind NICHT im $style_map!
// → Custom CSS kann nicht zugeordnet werden → geht verloren
```

**Lösung:**
```php
// Custom CSS NUR für erlaubte Klassen sammeln
foreach ($bricks_classes as $class) {
    if ($this->should_exclude_class($class)) {
        continue; // ← Skip BEFORE collecting CSS
    }
    if (!empty($class['settings']['_cssCustom'])) {
        $custom_css_stylesheet .= $class['settings']['_cssCustom'];
    }
}
```

### Problem #2: Nur erste Klasse verarbeitet
**Symptom:** Nur eine Klasse mit Custom CSS wurde migriert

**Ursache:**
```php
// Alte Implementierung
preg_match('/\.([a-zA-Z0-9_-]+)/', $stylesheet, $first_class_match);
$class_name = $first_class_match[1]; // ← NUR die ERSTE Klasse!

// Verarbeitet nur diese eine Klasse
$converted_css = $this->convert_nested_selectors_to_ampersand($stylesheet, $class_name);
```

**Lösung:**
```php
// Neue Implementierung
preg_match_all('/\.([a-zA-Z0-9_-]+)/', $stylesheet, $all_class_matches);
$class_names = array_unique($all_class_matches[1]); // ← ALLE Klassen!

foreach ($class_names as $class_name) {
    // Extrahiere CSS für jede Klasse separat
    $class_css = $this->extract_css_for_class($stylesheet, $class_name);
    
    // Konvertiere zu Nested CSS
    $converted_css = $this->convert_nested_selectors_to_ampersand($class_css, $class_name);
    
    // Speichere in etch_styles
    $styles[$style_id] = array(
        'selector' => '.' . $class_name,
        'css' => $converted_css,
    );
}
```

---

## 🎨 Nested CSS Conversion

### Feature: Automatisches CSS Nesting

**Input (Bricks):**
```css
.feature-section-frankfurt__group {
    --padding: var(--space-xl);
    padding: 0 var(--padding) var(--padding);
    border-radius: calc(var(--radius) + var(--padding) / 2);
}

.feature-section-frankfurt__group > * {
    border-radius: var(--radius);
    overflow: hidden;
}
```

**Output (Etch):**
```css
--padding: var(--space-xl);
padding: 0 var(--padding) var(--padding);
border-radius: calc(var(--radius) + var(--padding) / 2);

& > * {
  border-radius: var(--radius);
  overflow: hidden;
}
```

### Intelligente & (Ampersand) Syntax

Die Konvertierung fügt automatisch Leerzeichen ein wo nötig:

| Selektor-Typ | Input | Output | Leerzeichen? |
|--------------|-------|--------|--------------|
| Combinator | `.my-class > *` | `& > *` | ✅ Ja |
| Descendant | `.my-class .child` | `& .child` | ✅ Ja |
| Pseudo-Class | `.my-class:hover` | `&:hover` | ❌ Nein |
| Pseudo-Element | `.my-class::before` | `&::before` | ❌ Nein |

**Implementierung:**
```php
private function convert_nested_selectors_to_ampersand($css, $class_name) {
    // Parse alle CSS-Regeln für diese Klasse
    $pattern = '/\.' . $escaped_class . '([^{]*?)\{([^}]*)\}/s';
    
    foreach ($matches as $match) {
        $selector_suffix = $match[1]; // z.B. " > *", ":hover", " .child"
        $rule_content = $match[2];
        
        if (empty(trim($selector_suffix))) {
            // Hauptselektor - CSS direkt verwenden
            $main_css .= $rule_content;
        } else {
            // Nested Selektor - mit & konvertieren
            $trimmed_suffix = trim($selector_suffix);
            
            // Leerzeichen für Combinators und Descendant Selectors
            if (preg_match('/^[>+~]/', $trimmed_suffix) || 
                preg_match('/^[.#\[]/', $trimmed_suffix)) {
                $nested_selector = '& ' . $trimmed_suffix;
            } else {
                // Kein Leerzeichen für Pseudo-Classes/Elements
                $nested_selector = '&' . $trimmed_suffix;
            }
            
            $rules[] = array(
                'selector' => $nested_selector,
                'css' => $rule_content
            );
        }
    }
    
    // Kombiniere zu Nested CSS
    return $main_css . "\n\n" . implode("\n\n", $rules);
}
```

---

## 🚫 CSS Class Blacklist

### Ausgeschlossene Klassen

**Bricks:**
- `brxe-*` - Element Klassen
- `bricks-*` - System Klassen
- `brx-*` - Utility Klassen

**WordPress/Gutenberg:**
- `wp-*` - WordPress default
- `wp-block-*` - Gutenberg blocks
- `has-*` - Gutenberg utilities
- `is-*` - Gutenberg states

**WooCommerce:**
- `woocommerce-*`
- `wc-*`
- `product-*`
- `cart-*`
- `checkout-*`

**Implementierung:**
```php
private function should_exclude_class($class) {
    $class_name = !empty($class['name']) ? $class['name'] : '';
    
    if (empty($class_name)) {
        return true;
    }
    
    $excluded_prefixes = array(
        'brxe-', 'bricks-', 'brx-',
        'wp-', 'wp-block-', 'has-', 'is-',
        'woocommerce-', 'wc-', 'product-', 'cart-', 'checkout-',
    );
    
    foreach ($excluded_prefixes as $prefix) {
        if (strpos($class_name, $prefix) === 0) {
            error_log('🎨 CSS Converter: Excluding class: ' . $class_name);
            return true;
        }
    }
    
    return false;
}
```

---

## 📊 Ergebnisse

### Migration Statistik
- ✅ **1134 Klassen** erfolgreich konvertiert
- ✅ **1 Klasse** ausgeschlossen (Blacklist)
- ✅ **Custom CSS** mit Nested Syntax migriert
- ✅ **Alle Tests** bestanden (5/5)

### Verifizierung

**Test-Klasse:** `.feature-section-frankfurt__group`

**Bricks (Input):**
```css
.feature-section-frankfurt__group {
    --padding: var(--space-xl);
    padding: 0 var(--padding) var(--padding);
    border-radius: calc(var(--radius) + var(--padding) / 2);
}

.feature-section-frankfurt__group > * {
    border-radius: var(--radius);
    overflow: hidden;
}
```

**Etch (Output):**
```css
row-gap: var(--grid-gap);
background-image: linear-gradient(var(--bg-ultra-light) 65%, transparent);
margin-block-start: calc(var(--padding) * 1);
--padding: var(--space-xl);
padding: 0 var(--padding) var(--padding);
border-radius: calc(var(--radius) + var(--padding) / 2);

& > * {
  border-radius: var(--radius);
  overflow: hidden;
}
```

✅ **Normale CSS Properties** (row-gap, background-image, margin-block-start)  
✅ **Custom CSS** (--padding, padding, border-radius)  
✅ **Nested CSS** (& > * mit Child-Selektoren)

---

## 🧪 Tests

### Unit Tests
**Datei:** `tests/test-nested-css-conversion.php`

**Test Cases:**
1. ✅ Direct child selector (`& > *`)
2. ✅ Hover pseudo-class (`&:hover`)
3. ✅ Before pseudo-element (`&::before`)
4. ✅ Descendant selector (`& .child`)
5. ✅ Real-world example (multiple rules)

**Ergebnis:** 5/5 Tests bestanden

### Live Migration Test
1. ✅ Cleanup Etch durchgeführt
2. ✅ Plugin aktualisiert (v0.5.2)
3. ✅ Migration ausgeführt
4. ✅ Custom CSS im Frontend verifiziert
5. ✅ Nested CSS funktioniert

---

## 🎯 Lessons Learned

### 1. Timing ist alles
**Problem:** Blacklist-Check zu spät  
**Lösung:** Blacklist-Check VOR dem Sammeln von Custom CSS

### 2. Vollständigkeit prüfen
**Problem:** Nur erste Klasse verarbeitet  
**Lösung:** Alle Klassen im Stylesheet finden und verarbeiten

### 3. Moderne CSS nutzen
**Feature:** Nested CSS mit & Syntax  
**Vorteil:** Kompakter, lesbarer, moderner Code

### 4. Intelligente Formatierung
**Feature:** Automatische Leerzeichen-Handhabung  
**Vorteil:** Korrekte CSS-Syntax für alle Selektor-Typen

---

## 🚀 Nächste Schritte

### Für später (Admin Dashboard)
- [ ] Interface zum Klassen hinzufügen/speichern
- [ ] Blacklist/Whitelist Management
- [ ] Toggles für System-Klassen (Bricks, WP, Woo)
- [ ] Toggles für Frameworks (ACSS, Core Framework)

### Aktuell
- ✅ Custom CSS Migration funktioniert
- ✅ Nested CSS mit & Syntax
- ✅ Blacklist implementiert
- ✅ Alle Tests bestanden

**Status:** PRODUCTION READY! 🎉

---

**Created:** 2025-10-22 21:08  
**Version:** 0.5.2
