# Frontend-Rendering Fix - FINAL (Nach Etch-Entwickler Feedback)

## 🎯 Wichtige Info vom Etch-Entwickler

> "Wenn du das über den Gutenberg `className` machst funktioniert das leider nicht. Die Klassen müssten mit ihrer Unique ID in `block.attr.metadata.etchData.styles = ["unique-Id-hier", "unique-ID-von-class-2"]`"

## ✅ Korrigierte Lösung

**Wir verwenden KEINE `className`!** Nur `etchData.styles` mit Style-IDs.

### Vorher (FALSCH):
```php
$block_attrs = array(
    'className' => 'feature-card-frankfurt__heading',  // ❌ Funktioniert nicht!
    'metadata' => array(
        'etchData' => array(
            'styles' => ['16bcf9e']  // ✅ Richtig
        )
    )
);
```

### Nachher (RICHTIG):
```php
$block_attrs = array(
    // KEIN className!
    'metadata' => array(
        'etchData' => array(
            'styles' => ['16bcf9e', '16bceb7']  // ✅ Nur Style-IDs hier!
        )
    )
);
```

## 📝 Änderungen

### 1. Headings
```php
// Build class attribute for HTML (wp-block-heading only)
$class_attr = ' class="wp-block-heading"';

// Build block attributes - NO className, only etchData.styles!
$block_attrs = array(
    'level' => intval(str_replace('h', '', $level)),
    // KEIN className!
    'metadata' => array(
        'name' => $element_label,
        'etchData' => array(
            'origin' => 'etch',
            'name' => $element_label,
            'styles' => $style_ids,  // ✅ Nur hier!
            'attributes' => array(),
            'block' => array(
                'type' => 'html',
                'tag' => $level,
            ),
        )
    )
);
```

### 2. Paragraphs
```php
// No class attribute for paragraphs
$class_attr = '';

// Build block attributes - NO className, only etchData.styles!
$block_attrs = array(
    // KEIN className!
    'metadata' => array(
        'name' => $element_label,
        'etchData' => array(
            'origin' => 'etch',
            'name' => $element_label,
            'styles' => $style_ids,  // ✅ Nur hier!
            'attributes' => array(),
            'block' => array(
                'type' => 'html',
                'tag' => 'p',
            ),
        )
    )
);
```

### 3. Images
```php
// No class attribute for images (Etch handles this)
$img_class_attr = '';

// Build block attributes - NO className, only etchData.styles!
$block_attrs = array(
    // KEIN className!
    'metadata' => array(
        'name' => $element_label,
        'etchData' => array(
            'origin' => 'etch',
            'name' => $element_label,
            'styles' => $img_style_ids,  // ✅ Nur hier!
            'attributes' => array(),
            'block' => array(
                'type' => 'html',
                'tag' => 'img',
            ),
        )
    )
);
```

## 🔧 Was wurde entfernt

1. ❌ `convert_style_ids_to_selectors()` Funktion - nicht mehr benötigt
2. ❌ `className` Attribut in Block-Attributen
3. ❌ CSS-Selektoren in HTML `class` Attributen

## 📊 Erwartetes Ergebnis

### Datenbank (post_content):
```html
<!-- wp:heading {
  "level": 3,
  "metadata": {
    "name": "Heading",
    "etchData": {
      "origin": "etch",
      "name": "Heading",
      "styles": ["16bcf9e"],
      "attributes": [],
      "block": {
        "type": "html",
        "tag": "h3"
      }
    }
  }
} -->
<h3 class="wp-block-heading">Feature heading</h3>
<!-- /wp:heading -->
```

### Frontend:
Etch liest `etchData.styles = ["16bcf9e"]` und:
1. Schaut in `etch_styles` Option nach Style `16bcf9e`
2. Findet Selektor `.feature-card-frankfurt__heading`
3. Fügt Selektor als CSS-Klasse ins HTML ein
4. Generiert CSS-Regeln im `<head>`

**Ergebnis:**
```html
<h3 class="wp-block-heading feature-card-frankfurt__heading">Feature heading</h3>
```

## 🧪 Nächste Schritte

1. ✅ Plugin aktualisiert und synchronisiert
2. ⏳ **Alte Posts löschen** (haben falsche Struktur)
3. ⏳ **Migration erneut durchführen**
4. ⏳ **Frontend testen** - Etch sollte jetzt die Klassen korrekt rendern

## 📝 Test-Kommandos

```bash
# 1. Alte Posts auf Etch löschen
docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=post,page --format=ids --allow-root) --force --allow-root

# 2. Migration über Browser durchführen
# http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration

# 3. Post-Content in DB prüfen
docker exec b2e-etch wp post get 3286 --field=post_content --allow-root | head -50

# 4. Frontend prüfen
curl -s 'http://localhost:8081/feature-section-frankfurt/' | grep -o '<h3[^>]*>.*</h3>' | head -3

# 5. CSS im Head prüfen
curl -s 'http://localhost:8081/feature-section-frankfurt/' | grep 'etch-page-styles' -A 20
```

## 🎉 Erwartetes Ergebnis

Nach der erneuten Migration sollten:
- ✅ `etchData.styles` enthält Style-IDs (z.B. `["16bcf9e"]`)
- ✅ **KEIN** `className` in Block-Attributen
- ✅ Etch rendert die Klassen automatisch im Frontend
- ✅ CSS wird im `<head>` generiert
- ✅ Design wird korrekt angezeigt

## 📚 Geänderte Dateien

- `bricks-etch-migration/includes/gutenberg_generator.php`
  - Entfernt: `convert_style_ids_to_selectors()` Funktion
  - Entfernt: `className` aus Block-Attributen
  - Vereinfacht: HTML `class` Attribute (nur Gutenberg-Defaults)
  - Behalten: `etchData.styles` mit Style-IDs ✅

## 💡 Wichtige Erkenntnis

**Etch ist KEIN Standard-Gutenberg!**

- Standard Gutenberg: `className` → CSS-Klassen im HTML
- **Etch**: `etchData.styles` → Etch rendert die Klassen automatisch

Wir müssen Etch's Mechanismus verwenden, nicht Gutenberg's!
