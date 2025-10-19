# Etch Migration Guide

## Overview

This plugin migrates Bricks Builder content to Etch (Gutenberg-based page builder) with full CSS conversion to Logical Properties and complete metadata structure preservation.

## Migration Process

### 1. Element Conversion

All Bricks elements are converted to their Etch equivalents with proper metadata structure:

#### **Section**
```php
Bricks: <section>
Etch:   wp:group with etchData
        - data-etch-element="section"
        - Default style: etch-section-style
```

#### **Container**
```php
Bricks: <div class="container">
Etch:   wp:group with etchData
        - data-etch-element="container"
        - Default style: etch-container-style
```

#### **Div / Block**
```php
Bricks: <div>
Etch:   wp:group with etchData
        - data-etch-element="flex-div"
        - Default style: etch-flex-div-style
        - display: flex; flex-direction: column;
```

#### **Text**
```php
Bricks: <p>
Etch:   wp:paragraph with etchData
        - Metadata includes style IDs
        - Classes in both HTML and attributes
        - block: {type: "html", tag: "p"}
```

**Example:**
```json
{
  "metadata": {
    "name": "Text",
    "etchData": {
      "origin": "etch",
      "name": "Text",
      "styles": ["abc1234"],
      "attributes": {
        "class": "my-text-class"
      },
      "block": {
        "type": "html",
        "tag": "p"
      }
    }
  },
  "className": "my-text-class"
}
```

#### **Heading**
```php
Bricks: <h1-h6>
Etch:   wp:heading with etchData
        - Metadata includes style IDs
        - level attribute (1-6)
        - block: {type: "html", tag: "h2"}
```

**Example:**
```json
{
  "metadata": {
    "name": "Heading",
    "etchData": {
      "origin": "etch",
      "name": "Heading",
      "styles": ["def5678"],
      "attributes": {
        "class": "my-heading"
      },
      "block": {
        "type": "html",
        "tag": "h2"
      }
    }
  },
  "level": 2,
  "className": "my-heading"
}
```

#### **Image**
```php
Bricks: <img class="my-class">
Etch:   wp:image with nestedData.img
        - Classes on <img>, NOT on <figure>
        - Styles linked via nestedData
```

**Example:**
```json
{
  "metadata": {
    "name": "Image",
    "etchData": {
      "removeWrapper": true,
      "origin": "etch",
      "name": "Image",
      "nestedData": {
        "img": {
          "origin": "etch",
          "name": "Image",
          "styles": ["ghi9012"],
          "attributes": {
            "src": "image.jpg",
            "class": "my-image"
          },
          "block": {
            "type": "html",
            "tag": "img"
          }
        }
      }
    }
  }
}
```

**HTML Output:**
```html
<figure class="wp-block-image">
  <img class="my-image" src="image.jpg" alt="" />
</figure>
```

#### **Icon**
```php
Bricks: Icon element with library
Etch:   wp:html with FontAwesome class
        - <i class="fas fa-icon-name"></i>
```

#### **Button**
```php
Bricks: Button with link
Etch:   wp:button (core/button)
        - Link with target support
        - Classes preserved
```

#### **Unsupported Elements**
```php
code, filter-radio, form, map, custom elements
→ Converted to Flex-Div (wp:group)
```

---

### 2. CSS Conversion to Logical Properties

All physical CSS properties are automatically converted to their logical equivalents:

#### **Margin & Padding**
```css
/* Bricks */
margin-top: 20px;
margin-right: 10px;
margin-bottom: 20px;
margin-left: 10px;

/* Etch (Logical Properties) */
margin-block-start: 20px;
margin-inline-end: 10px;
margin-block-end: 20px;
margin-inline-start: 10px;
```

#### **Position (Inset)**
```css
/* Bricks */
top: 0;
right: 0;
bottom: 0;
left: 0;

/* Etch (Logical Properties) */
inset-block-start: 0;
inset-inline-end: 0;
inset-block-end: 0;
inset-inline-start: 0;
```

**Important:** Uses `isset()` instead of `!empty()` to preserve `"0"` values!

#### **Sizing**
```css
/* Bricks */
width: 100%;
height: 100%;
min-width: 300px;
max-width: 1200px;

/* Etch (Logical Properties) */
inline-size: 100%;
block-size: 100%;
min-inline-size: 300px;
max-inline-size: 1200px;
```

#### **Grid Properties**
```css
/* Bricks */
_gridGap: var(--space-s);
_justifyContentGrid: center;
_alignItemsGrid: stretch;

/* Etch */
gap: var(--space-s);
justify-content: center;
align-items: stretch;
```

#### **Flexbox Properties**
```css
/* Bricks */
_rowGap: var(--space-m);
_columnGap: var(--space-s);
_justifyContent: space-between;
_alignItems: center;

/* Etch */
row-gap: var(--space-m);
column-gap: var(--space-s);
justify-content: space-between;
align-items: center;
```

---

### 3. Custom CSS Handling

Custom CSS from Bricks classes is parsed and merged with standard properties:

#### **Simple Selectors**
```css
/* Bricks Custom CSS */
.my-class {
  --custom-var: value;
  border-radius: 10px;
}

/* Merged with Bricks settings */
.my-class {
  /* From Bricks settings: */
  margin-block-start: 20px;
  padding: var(--space-m);
  
  /* From Custom CSS: */
  --custom-var: value;
  border-radius: 10px;
}
```

#### **Child Selectors**
```css
/* Bricks Custom CSS with %root% */
%root% {
  --padding: var(--space-xl);
  border-radius: calc(var(--radius) + var(--padding) / 2);
}

%root% > * {
  border-radius: var(--radius);
  overflow: hidden;
}

/* Converted to Etch */
.my-class {
  /* Bricks settings + Custom CSS properties */
  margin-block-start: 20px;
  --padding: var(--space-xl);
  border-radius: calc(var(--radius) + var(--padding) / 2);
  
  /* Child selector preserved as full rule */
  .my-class > * {
    border-radius: var(--radius);
    overflow: hidden;
  }
}
```

#### **Negative Values**
```css
/* Bricks */
_margin: {
  top: "calc(var(--padding) * -1)"
}

/* Etch - Preserved! */
margin-block-start: calc(var(--padding) * -1);
```

---

### 4. Style ID Generation

Each Bricks class gets a unique 7-character hash ID:

```php
// Hash generation
$style_id = substr(md5($class_name), 0, 7);

// Example
"feature-section-frankfurt__media" → "66b5d30"
```

**Style Structure:**
```php
[
  'type' => 'class',
  'selector' => '.my-class',
  'collection' => 'default',
  'css' => 'margin-block-start: 20px; padding: var(--space-m);',
  'readonly' => false
]
```

---

### 5. Class Assignment

Classes are assigned to elements with proper Etch metadata:

#### **Global Classes**
```php
// Bricks
_cssGlobalClasses: ["class-id-1", "class-id-2"]

// Etch
className: "class-name-1 class-name-2"
etchData.styles: ["abc1234", "def5678"]
```

#### **Custom Classes**
```php
// Bricks
_cssClasses: "custom-class another-class"

// Etch
className: "custom-class another-class"
```

---

## Key Implementation Details

### 1. Image Classes via nestedData

**Why:** Etch applies `className` from block attributes to the outer element (`<figure>`), but we need classes on `<img>`.

**Solution:** Use `nestedData.img` to specify classes and styles for the inner `<img>` element.

### 2. Text/Heading Styles

**Why:** Classes alone don't link to styles in Etch.

**Solution:** Include `etchData.styles` array with style IDs + `block` definition.

### 3. Position Properties with "0"

**Why:** `!empty("0")` returns `false`, dropping `top: 0` values.

**Solution:** Use `isset($value) && $value !== ''` instead.

### 4. Custom CSS Parsing

**Why:** Bricks stores custom CSS with `%root%` placeholder and child selectors.

**Solution:** 
- Replace `%root%` with actual class name
- Parse individual rules with regex
- Generate same hash ID for same class
- Merge with existing styles

### 5. Flex-Div for Divs

**Why:** Etch uses `flex-div` as default container with `display: flex`.

**Solution:** Set `data-etch-element="flex-div"` and add `etch-flex-div-style`.

---

## Migration Flow

```
1. Load Bricks Classes
   ↓
2. Convert to Etch Styles
   - Parse Bricks settings
   - Convert to Logical Properties
   - Parse Custom CSS
   - Merge styles with same selector
   ↓
3. Send Styles to Etch API
   - POST to /wp-json/etch/v1/styles
   ↓
4. Convert Content
   - Parse Bricks elements
   - Build element hierarchy
   - Convert to Gutenberg blocks
   - Add etchData metadata
   ↓
5. Create Posts in Etch
   - POST to /wp-json/wp/v2/pages
   - Include full Gutenberg HTML
```

---

## Testing Checklist

- [ ] Sections render correctly
- [ ] Containers have proper structure
- [ ] Divs are Flex-Divs
- [ ] Text has classes and styles
- [ ] Headings have correct level
- [ ] Images have classes on `<img>`
- [ ] Icons display FontAwesome
- [ ] Buttons have working links
- [ ] Custom CSS is merged
- [ ] Child selectors work
- [ ] Negative margins preserved
- [ ] Grid properties applied
- [ ] Position with "0" works
- [ ] CSS variables maintained

---

## Common Issues & Solutions

### Issue: Styles not appearing on frontend
**Cause:** Missing `etchData.styles` array  
**Solution:** Ensure `get_element_style_ids()` returns style IDs

### Issue: Image classes on figure instead of img
**Cause:** Using `className` in block attributes  
**Solution:** Use `nestedData.img.attributes.class`

### Issue: Duplicate classes
**Cause:** Classes in both HTML and block attributes  
**Solution:** For images, only use nestedData (no className)

### Issue: Position properties missing
**Cause:** `!empty("0")` returns false  
**Solution:** Use `isset() && !== ''`

### Issue: Custom CSS not merged
**Cause:** Different style IDs for same class  
**Solution:** Use same hash generation for both

---

## API Endpoints

### Send Styles
```
POST /wp-json/etch/v1/styles
Body: {
  "styles": {
    "abc1234": {
      "type": "class",
      "selector": ".my-class",
      "css": "...",
      ...
    }
  }
}
```

### Create Page
```
POST /wp-json/wp/v2/pages
Body: {
  "title": "Page Title",
  "content": "<!-- wp:group ... -->",
  "status": "publish"
}
```

---

## Code References

### Main Files
- `css_converter.php` - CSS conversion and logical properties
- `gutenberg_generator.php` - Element to block conversion
- `api_client.php` - Etch API communication
- `migration_manager.php` - Orchestration

### Key Functions
- `convert_bricks_classes_to_etch()` - Main CSS conversion
- `convert_to_logical_properties()` - Physical → Logical
- `parse_custom_css_stylesheet()` - Custom CSS parsing
- `convert_etch_*()` - Element converters
- `get_element_style_ids()` - Style ID extraction

---

## Success Metrics

✅ **1:1 Visual Match** - Etch page looks identical to Bricks  
✅ **All Styles Applied** - No missing CSS  
✅ **Proper Structure** - Correct element hierarchy  
✅ **Editable in Etch** - Full editor support  
✅ **Logical Properties** - Modern CSS standards  

---

## Next Steps

1. **Media Migration** - Copy attachments to Etch
2. **Dynamic Data** - Convert Bricks dynamic tags
3. **Responsive Styles** - Handle breakpoints
4. **Advanced Elements** - Sliders, accordions, etc.
5. **Batch Migration** - Multiple posts at once
