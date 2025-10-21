# CSS-Klassen Migration - Quick Reference

## 🎯 Kern-Prinzip

**Etch rendert CSS-Klassen aus `etchData.attributes.class`, NICHT aus `etchData.styles`!**

## ✅ Korrekte Struktur

```json
{
  "metadata": {
    "etchData": {
      "styles": ["abc123"],           // ← Für CSS-Generierung im <head>
      "attributes": {
        "class": "my-css-class"       // ← Für Frontend-Rendering
      }
    }
  }
}
```

## 📋 Element-Typen

| Element | `block.tag` | Klasse auf | Besonderheit |
|---------|-------------|------------|--------------|
| Heading | `h2`, `h3`, etc. | `<h2>` | - |
| Paragraph | `p` | `<p>` | - |
| **Image** | **`figure`** | **`<figure>`** | **NICHT `img`!** |
| Section | `section` | `<section>` | + `data-etch-element` |
| Container | `div` | `<div>` | + `data-etch-element` |
| Flex-Div | `div` | `<div>` | + `data-etch-element` |

## 🔧 Code-Template

```php
// 1. Get style IDs
$style_ids = $this->get_element_style_ids($element);

// 2. Convert to CSS classes
$css_classes = $this->get_css_classes_from_style_ids($style_ids);

// 3. Set in etchData.attributes
'etchData' => array(
    'styles' => $style_ids,
    'attributes' => !empty($css_classes) ? array('class' => $css_classes) : array(),
    'block' => array(
        'type' => 'html',
        'tag' => 'div',  // oder 'section', 'figure', etc.
    )
)
```

## ⚠️ Häufige Fehler

### ❌ FALSCH: Klasse nur in `styles`
```json
{
  "styles": ["abc123"],
  "attributes": {}  // ← Leer!
}
```

### ✅ KORREKT: Klasse in `attributes.class`
```json
{
  "styles": ["abc123"],
  "attributes": {
    "class": "my-css-class"
  }
}
```

---

### ❌ FALSCH: Image mit `tag = 'img'`
```json
{
  "block": {
    "tag": "img"
  }
}
```
**Result:** Leeres `<img class="...">` + echtes `<img src="...">`

### ✅ KORREKT: Image mit `tag = 'figure'`
```json
{
  "block": {
    "tag": "figure"
  }
}
```
**Result:** `<figure class="..."><img src="..."></figure>`

---

### ❌ FALSCH: `unset()` verwendet
```php
$etch_data_attributes = $etch_data;
unset($etch_data_attributes['class']); // ← LÖSCHT die Klasse!
```

### ✅ KORREKT: Klasse behalten
```php
$etch_data_attributes = $etch_data;
// Kein unset()!
```

## 🔍 Debugging

### Prüfe Datenbank
```bash
docker exec b2e-etch wp post get POST_ID --field=post_content --allow-root | grep "attributes"
```

**Erwartung:** `"attributes":{"class":"my-css-class"}`

### Prüfe Frontend
```bash
curl -s 'http://localhost:8081/post-slug/' | grep "my-css-class"
```

**Erwartung:** `<div class="my-css-class">...</div>`

### Prüfe Style-Map
```bash
docker exec b2e-etch wp option get b2e_style_map --format=json --allow-root | python3 -c "import sys, json; data = json.load(sys.stdin); print(list(data.items())[:3])"
```

**Erwartung:** `[('bTySc123', {'id': 'abc123', 'selector': '.my-class'})]`

## 📊 Workflow

```
1. CSS-Migration (Etch-Seite)
   → Erstellt b2e_style_map mit Selektoren

2. Content-Migration (Bricks-Seite)
   → get_element_style_ids() → Style-IDs
   → get_css_classes_from_style_ids() → CSS-Klassen
   → Setzt in etchData.attributes.class

3. Frontend-Rendering (Etch-Seite)
   → Etch liest etchData.attributes.class
   → Rendert CSS-Klassen im HTML
```

## ✅ Checkliste

Für jedes Element:
- [ ] `get_element_style_ids()` aufgerufen?
- [ ] `get_css_classes_from_style_ids()` aufgerufen?
- [ ] Klasse in `etchData.attributes.class` gesetzt?
- [ ] Für Images: `block.tag = 'figure'`?
- [ ] Für Images: Klasse auf `<figure>`?
- [ ] **KEIN** `unset($attributes['class'])`?
- [ ] Frontend-Test: Klasse vorhanden?

## 🎉 Erfolg

**Datenbank:**
```json
"attributes": {"class": "my-css-class"}
```

**Frontend:**
```html
<div class="my-css-class">Content</div>
```

**CSS:**
```css
.my-css-class { /* Styles */ }
```

---

**Siehe auch:** `CSS-CLASSES-FINAL-SOLUTION.md` für Details
