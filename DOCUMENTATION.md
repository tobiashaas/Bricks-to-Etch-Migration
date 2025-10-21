# Technical Documentation - Bricks to Etch Migration

**Last Updated:** 2025-10-21 23:20  
**Version:** 0.4.0

---

## 📋 Table of Contents

1. [Architecture](#architecture)
2. [CSS Migration](#css-migration)
3. [Content Migration](#content-migration)
4. [Media Migration](#media-migration)
5. [API Communication](#api-communication)
6. [Frontend Rendering](#frontend-rendering)

---

## Architecture

**Updated:** 2025-10-21 23:20

### Plugin Structure

```
bricks-etch-migration/
├── includes/
│   ├── admin_interface.php      # Admin UI and AJAX handlers
│   ├── css_converter.php         # CSS conversion logic
│   ├── gutenberg_generator.php   # Content conversion
│   ├── media_migrator.php        # Media transfer
│   ├── api_client.php            # Etch API client
│   └── ...
└── bricks-etch-migration.php     # Main plugin file
```

### Data Flow

```
Bricks Site                    Etch Site
    ↓                              ↓
1. CSS Converter          →   Etch Styles
2. Media Migrator         →   Media Library
3. Content Converter      →   Gutenberg Blocks
```

---

## CSS Migration

**Updated:** 2025-10-21 23:20

### Overview

Converts Bricks Global Classes to Etch Styles with CSS class names in `etchData.attributes.class`.

### Key Components

#### 1. CSS Converter (`css_converter.php`)

**Function:** `convert_bricks_classes_to_etch()`

**Process:**
1. Fetch Bricks Global Classes
2. Convert CSS properties to logical properties
3. Collect custom CSS from `_cssCustom`
4. Generate Etch style IDs
5. Create style map with selectors
6. Merge custom CSS with normal styles

**Style Map Format:**
```php
[
  'bricks_id' => [
    'id' => 'etch_id',
    'selector' => '.css-class'
  ]
]
```

#### 2. Custom CSS Migration

**Updated:** 2025-10-21 23:20

**Function:** `parse_custom_css_stylesheet()`

**Process:**
1. Extract class name from custom CSS
2. Find existing style ID from style map
3. Use existing ID (not generate new one)
4. Store entire custom CSS as-is
5. Merge with existing styles

**Example:**
```css
/* Custom CSS from Bricks */
.my-class {
  --padding: var(--space-xl);
  padding: 0 var(--padding);
  border-radius: calc(var(--radius) + var(--padding) / 2);
}

.my-class > * {
  border-radius: var(--radius);
  overflow: hidden;
}
```

#### 3. CSS Class Extraction

**Function:** `get_css_classes_from_style_ids()`

**Process:**
1. Get style IDs for element
2. Skip Etch-internal styles (`etch-section-style`, etc.)
3. Look up selectors in style map
4. Extract class names (remove leading dot)
5. Return space-separated string

**Example:**
```php
Input:  ['abc123', 'def456']
Output: "my-class another-class"
```

---

## Content Migration

**Updated:** 2025-10-21 23:40

### Overview

Converts Bricks elements to Gutenberg blocks with Etch metadata.

### Element Types

#### 0. Listen (ul, ol, li)

**Updated:** 2025-10-21 23:40

**Block Type:** `core/group` (Container mit custom tag)

**Bricks:**
```php
'name' => 'container',
'settings' => ['tag' => 'ul']
```

**Etch Data:**
```json
{
  "tagName": "ul",
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "data-etch-element": "container",
      "class": "my-list-class"
    },
    "block": {
      "type": "html",
      "tag": "ul"
    }
  }
}
```

**Frontend:**
```html
<ul data-etch-element="container" class="my-list-class">
  <li>Item 1</li>
  <li>Item 2</li>
</ul>
```

**Unterstützte Tags:**
- `ul` - Unordered List
- `ol` - Ordered List
- `li` - List Item (via Div element)

#### 1. Headings (h1-h6)

**Block Type:** `core/heading`

**Etch Data:**
```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "class": "my-heading-class"
    },
    "block": {
      "type": "html",
      "tag": "h2"
    }
  }
}
```

#### 2. Paragraphs

**Block Type:** `core/paragraph`

**Etch Data:**
```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "class": "my-paragraph-class"
    },
    "block": {
      "type": "html",
      "tag": "p"
    }
  }
}
```

#### 3. Images

**Updated:** 2025-10-21 22:24

**Block Type:** `core/image`

**Important:** Use `block.tag = 'figure'`, not `'img'`!

**Etch Data:**
```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "class": "my-image-class"
    },
    "block": {
      "type": "html",
      "tag": "figure"
    }
  }
}
```

**HTML:**
```html
<figure class="wp-block-image my-image-class">
  <img src="..." alt="...">
</figure>
```

#### 4. Sections

**Block Type:** `core/group`

**Etch Data:**
```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "data-etch-element": "section",
      "class": "my-section-class"
    },
    "block": {
      "type": "html",
      "tag": "section"
    }
  }
}
```

#### 5. Containers

**Block Type:** `core/group`

**Etch Data:**
```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "data-etch-element": "container",
      "class": "my-container-class"
    },
    "block": {
      "type": "html",
      "tag": "div"
    }
  }
}
```

---

## Media Migration

**Updated:** 2025-10-21 23:20

### Overview

Transfers images and attachments from Bricks to Etch site.

### Process

1. Get all media attachments from Bricks
2. Download media file
3. Upload to Etch via REST API
4. Map Bricks media ID → Etch media ID
5. Update image URLs in content

---

## API Communication

**Updated:** 2025-10-21 23:20

### Authentication

Uses WordPress Application Passwords for secure API access.

### Endpoints

#### 1. Validate Token
```
POST /wp-json/bricks-etch-migration/v1/validate-token
```

#### 2. Receive Post
```
POST /wp-json/bricks-etch-migration/v1/receive-post
```

#### 3. Receive Media
```
POST /wp-json/bricks-etch-migration/v1/receive-media
```

#### 4. Import Styles
```
POST /wp-json/bricks-etch-migration/v1/import-styles
```

---

## Frontend Rendering

**Updated:** 2025-10-21 22:24

### Key Insight

**Etch renders CSS classes from `etchData.attributes.class`, NOT from `etchData.styles`!**

### Correct Structure

```json
{
  "etchData": {
    "styles": ["abc123"],           // For CSS generation in <head>
    "attributes": {
      "class": "my-css-class"       // For frontend HTML rendering
    }
  }
}
```

### Frontend Output

```html
<div data-etch-element="container" class="my-css-class">
  Content
</div>
```

### CSS in `<head>`

```css
.my-css-class {
  /* Styles from Bricks */
  padding: 1rem;
  background: var(--bg-color);
}
```

---

## Testing

**Updated:** 2025-10-21 23:20

### Test Scripts Location

All test scripts are in `/tests` folder.

### Running Tests

```bash
# CSS Migration
php tests/test-css-converter.php

# Content Migration
php tests/test-content-conversion.php

# API
./tests/test-api-comprehensive.sh
```

---

## Troubleshooting

**Updated:** 2025-10-21 23:20

### CSS Classes Missing

1. Check `etch_styles` exists
2. Check `b2e_style_map` exists
3. Verify `get_css_classes_from_style_ids()` is called
4. Check logs for "B2E CSS Classes:"

### Custom CSS Not Merged

1. Check `parse_custom_css_stylesheet()` receives style_map
2. Verify existing style ID is found
3. Check logs for "B2E CSS: Found existing style ID"
4. Verify custom CSS is in final style

### Migration Fails

1. Check logs: `docker exec b2e-bricks tail -100 /var/www/html/wp-content/debug.log`
2. Verify API connection
3. Check Application Password
4. Test individual steps

---

## References

- [CSS-CLASSES-FINAL-SOLUTION.md](CSS-CLASSES-FINAL-SOLUTION.md) - Detailed CSS implementation
- [CSS-CLASSES-QUICK-REFERENCE.md](CSS-CLASSES-QUICK-REFERENCE.md) - Quick reference
- [CHANGELOG.md](CHANGELOG.md) - Version history

---

**Last Updated:** 2025-10-21 23:20  
**Version:** 0.4.0
