# Etch Gutenberg Block Structure - Analyse

## Quelle: Post ID 350 (Feature Section Frankfurt)

---

## 1. Section Block (Hero)

### Gutenberg Block:
```html
<!-- wp:group {
  "tagName":"section",
  "metadata":{
    "name":"Hero",
    "etchData":{
      "origin":"etch",
      "name":"Hero",
      "styles":["etch-section-style","qb7ju96","vyw70p7","27kg6bk"],
      "attributes":{
        "data-etch-element":"section",
        "class":"hero overlay-dots texture-arc"
      },
      "block":{
        "type":"html",
        "tag":"section"
      }
    }
  }
} -->
<div class="wp-block-group">
  <!-- Inner blocks here -->
</div>
<!-- /wp:group -->
```

### Struktur:
- **Block Type:** `wp:group`
- **tagName:** `"section"` (wichtig! Überschreibt das Standard-div)
- **metadata:**
  - **name:** `"Hero"` (Display-Name)
  - **etchData:**
    - **origin:** `"etch"` (immer "etch")
    - **name:** `"Hero"` (gleich wie metadata.name)
    - **styles:** Array von Style-IDs `["etch-section-style","qb7ju96",...]`
    - **attributes:** HTML-Attribute `{"data-etch-element":"section","class":"..."}`
    - **block:**
      - **type:** `"html"` (Block-Typ)
      - **tag:** `"section"` (HTML-Tag)

---

## 2. Container Block

### Gutenberg Block:
```html
<!-- wp:group {
  "metadata":{
    "name":"Container",
    "etchData":{
      "origin":"etch",
      "name":"Container",
      "styles":["etch-container-style","za4vx9e"],
      "attributes":{
        "data-etch-element":"container",
        "class":"buttons"
      },
      "block":{
        "type":"html",
        "tag":"div"
      }
    }
  }
} -->
<div class="wp-block-group">
  <!-- Inner blocks -->
</div>
<!-- /wp:group -->
```

### Unterschied zu Section:
- **KEIN tagName** (verwendet Standard-div)
- Sonst gleiche Struktur

---

## 3. Component Block (Etch-spezifisch)

### Gutenberg Block:
```html
<!-- wp:block {
  "metadata":{
    "etchData":{
      "origin":"etch",
      "attributes":{
        "pillText":"Visual Development Environment for WordPress",
        "sectionHeadline":"Build WordPress Websites Visually...",
        "hasLede":"{true}",
        "lede":"Etch is a next-era...",
        "hasPill":"{false}",
        "isH1":"{true}"
      },
      "block":{
        "type":"component",
        "component":368,
        "innerBlocks":[]
      }
    }
  },
  "ref":368
} /-->
```

### Struktur:
- **Block Type:** `wp:block` (nicht wp:group!)
- **Self-closing** (endet mit `/-->`)
- **ref:** Component-ID
- **etchData.block.type:** `"component"`
- **etchData.block.component:** Component-ID

---

## 4. Paragraph Block (mit nestedData)

### Gutenberg Block:
```html
<!-- wp:paragraph {
  "metadata":{
    "name":"Text",
    "etchData":{
      "origin":"etch",
      "name":"Text",
      "block":{
        "type":"html",
        "tag":"span"
      },
      "removeWrapper":true,
      "nestedData":{
        "h8p014h":{
          "origin":"etch",
          "name":"",
          "attributes":{
            "aria-label":"Version {item.title} Changelog",
            "href":"{item.permalink.relative}"
          },
          "block":{
            "type":"html",
            "tag":"a"
          }
        },
        "higkt9o":{
          "origin":"etch",
          "name":"Text",
          "block":{
            "type":"html",
            "tag":"span"
          }
        }
      }
    }
  }
} -->
<p>
  <span data-etch-ref="higkt9o">
    Latest: <a data-etch-ref="h8p014h">{item.title}</a>
  </span>
</p>
<!-- /wp:paragraph -->
```

### Struktur:
- **Block Type:** `wp:paragraph`
- **removeWrapper:** `true` (entfernt das <p> wrapper)
- **nestedData:** Verschachtelte Elemente mit refs
- **data-etch-ref:** Verknüpft HTML mit nestedData

---

## 5. Loop Block (Etch-spezifisch)

### Gutenberg Block:
```html
<!-- wp:group {
  "metadata":{
    "name":"Loop",
    "etchData":{
      "origin":"etch",
      "name":"Loop",
      "block":{
        "type":"loop",
        "loop":{
          "target":"625efce",
          "itemId":"item"
        }
      }
    }
  }
} -->
<div class="wp-block-group">
  <!-- Loop content -->
</div>
<!-- /wp:group -->
```

### Struktur:
- **block.type:** `"loop"` (nicht "html"!)
- **block.loop.target:** Query-ID
- **block.loop.itemId:** Variable-Name für Loop-Item

---

## Zusammenfassung: Etch-Struktur

### Für Section/Container/Flex-Div (wp:group):

```json
{
  "tagName": "section",  // Optional, nur für non-div tags
  "metadata": {
    "name": "Section Name",
    "etchData": {
      "origin": "etch",
      "name": "Section Name",
      "styles": ["style-id-1", "style-id-2"],
      "attributes": {
        "data-etch-element": "section",
        "class": "custom-classes"
      },
      "block": {
        "type": "html",
        "tag": "section"
      }
    }
  }
}
```

### Für Heading/Paragraph (Standard Gutenberg):

```json
{
  "metadata": {
    "name": "Heading",
    "etchData": {
      "origin": "etch",
      "name": "Heading",
      "styles": ["style-id"],
      "attributes": {
        "class": "custom-class"
      },
      "block": {
        "type": "html",
        "tag": "h2"
      }
    }
  }
}
```

### HTML Output:
```html
<!-- wp:heading {"level":2,"metadata":{...}} -->
<h2 class="custom-class">Heading Text</h2>
<!-- /wp:heading -->
```

---

## Wichtige Erkenntnisse:

1. **tagName** wird nur für non-div Elemente verwendet (section, article, etc.)
2. **styles** ist ein Array von Style-IDs (nicht CSS!)
3. **attributes** enthält HTML-Attribute (class, data-*, etc.)
4. **block.type** ist meist "html", aber kann auch "component" oder "loop" sein
5. **origin** ist immer "etch"
6. **name** erscheint zweimal (metadata.name und etchData.name)
7. **Standard Gutenberg Blocks** (heading, paragraph) werden verwendet, nicht custom blocks
8. **Content** steht im HTML zwischen den Block-Kommentaren

---

## Was wir ändern müssen:

### Aktuell (unser Code):
```json
{
  "metadata": {
    "name": "Section",
    "etchData": {
      "origin": "etch",
      "name": "Section",
      "styles": ["1e8041c"],
      "attributes": {
        "data-etch-element": "section",
        "class": "test-section"
      },
      "block": {
        "type": "html",
        "tag": "section"
      }
    }
  }
}
```

### Problem:
- ✅ Struktur ist korrekt!
- ❌ Aber: **tagName fehlt** für Section!
- ❌ Content wird nicht eingefügt

### Fix:
1. `tagName` hinzufügen für Section/Article/etc.
2. Content zwischen den Block-Tags einfügen
3. Standard Gutenberg Blocks verwenden (wp:heading, wp:paragraph)
