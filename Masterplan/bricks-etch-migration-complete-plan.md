# Bricks to Etch Migration Plugin - Complete Development Plan (V0.1.0)

## Project Overview

### Plugin Name
`bricks-etch-migration`

### Primary Objective
Create a WordPress plugin that performs **one-time migration** of Bricks Builder websites to Etch (visual development environment) with automated CSS conversion from flat vanilla CSS to vanilla nested CSS format, including full dynamic data conversion.

### Architecture
Single WordPress plugin installed on both source (Bricks) and target (Etch) instances, communicating via REST API with secure key-based authentication. Designed as a **disposable tool** for one-time use - installed, used once, then deleted.

### Key Design Principles
- **Simplicity over complexity** - One-time use case, no need for enterprise features
- **Resume capability** - Handle large migrations that may take hours
- **Clear error reporting** - Error codes with documentation links
- **No backups** - Forward-only migration (backups handled externally)
- **HTML & CSS only** - No Bricks-specific functionality (sliders, accordions, etc.)
- **Complete automation** - Field Groups, CPTs, Custom Fields - everything automatic! ⭐

### Version Strategy
- **V0.1.0** - Initial development version (current)
- **V1.0.0** - First production release
- **V2.0.0** - Advanced features (Components migration, etc.)

### Migration Process (7 Steps - All Automatic!)

```
Step 0: Validation ⚡
├─ Detect plugins on source (ACF, MetaBox, JetEngine)
├─ Detect plugins on target
├─ Show warnings if mismatch (W001-W003)
└─ User decides: Proceed or Install plugins

Step 1: Custom Post Types 📦
├─ Export all CPTs from source
├─ Send to target site
├─ Register CPTs on target
└─ Save to b2e_registered_cpts option (persistent!)

Step 2: ACF Field Groups 🔧
├─ Export all ACF field groups via acf_get_field_groups()
├─ Export all fields for each group
├─ Send to target site
└─ Import via acf_import_field_group()

Step 3: MetaBox Configurations 🔧
├─ Export MB Builder configs (post_type: meta-box)
├─ Send to target site
└─ Create meta-box posts on target

Step 4: CSS Classes 🎨
├─ Export Bricks global classes
├─ Convert to nested CSS
├─ Extract CSS variables → :root
└─ Import to etch_styles option

Step 5: Posts & Content 📄
├─ Get all posts with _bricks_page_content_2
├─ For each post:
│   ├─ Parse Bricks elements
│   ├─ Convert dynamic data tags
│   ├─ Convert to Gutenberg blocks
│   ├─ Create post on target
│   └─ Migrate ALL wp_postmeta (custom fields!)
└─ Checkpoint every 10 posts

Step 6: Finalization ✅
├─ Flush rewrite rules
├─ Clear transients
└─ Show success report
```

### What Gets Migrated (Complete List)

#### ✅ Content & Structure
- Bricks Page Elements → Etch Gutenberg Blocks
- HTML Content with Dynamic Data
- Media Files (Images, Videos, Documents)
- Post Data (Title, Excerpt, Status, Date, etc.)

#### ✅ CSS & Styling
- Global CSS Classes → Etch Styles
- Flat CSS → Nested CSS (with `&` syntax)
- CSS Variables → :root
- Media Queries → Nested within selectors
- Custom CSS → Preserved

#### ✅ Dynamic Data & Modifiers
- 50+ Bricks Dynamic Tags → Etch Dynamic Keys
- 30+ Modifiers with Parameters
- Modifier Stacking/Chaining
- Custom Field References

#### ✅ Custom Fields (COMPLETE!)
- **ACF Field Groups** - Fully automatic migration! ⭐
- **ACF Field Values** - All wp_postmeta
- **ACF Field References** - _field_key preservation
- **MetaBox Configurations** - MB Builder only
- **MetaBox Field Values** - All wp_postmeta
- **JetEngine Field Values** - All wp_postmeta
- **Generic Post Meta** - Everything!

#### ✅ Custom Post Types (COMPLETE!)
- **Automatic Detection** - Finds all CPTs
- **Full Config Export** - Labels, args, taxonomies
- **Automatic Registration** - On target site!
- **Persistent Registration** - Saved in wp_options
- **Rewrite Rules Flush** - Automatic

#### ⚠️ Advanced Features (V1.1)
- ACF → MetaBox Conversion
- MetaBox → ACF Conversion
- Complex Field Types (Repeater, Flexible, Gallery)
- Bricks Query Loops
- Bricks Templates & Conditions

#### ❌ Not Migrated
- Bricks-specific elements (Sliders, Accordions, Tabs, Popups)
- MetaBox Code-based configs (only MB Builder)
- Bricks Components (V2.0)
- JetEngine Meta Box Definitions (V1.1)

## Core Technical Requirements

### CSS Conversion Specification
- **Source Format**: Flat vanilla CSS stored in serialized PHP arrays
- **Target Format**: Vanilla nested CSS with native CSS nesting syntax
- **Browser Support**: CSS Nesting Module (91%+ browser support as of 2025)
- **Nesting Syntax**: Use ampersand (`&`) prefix for maximum compatibility
- **CSS Variables**: Extract and move to `:root` selector
- **Media Queries**: Nest inside selectors for modern CSS structure
- **Framework Variables**: Skip/ignore (Bootstrap, Tailwind, etc.)

### Etch Format Specification (CRITICAL!)

Etch uses **standard Gutenberg `wp:group` blocks** with `metadata.etchData`, NOT custom block types!

#### **Key Differences from Other Page Builders:**

**Gutenberg (Editor):**
```html
<!-- wp:group {"metadata":{"name":"Section","etchData":{...}}} -->
<div class="wp-block-group">
  <!-- Inner blocks -->
</div>
<!-- /wp:group -->
```

**Frontend (Rendered):**
```html
<section data-etch-element="section" class="my-class">
  <!-- Content -->
</section>
```

#### **Bricks → Etch Element Mapping:**
```
brxe-section    → data-etch-element="section"
brxe-container  → data-etch-element="container"
brxe-block      → data-etch-element="flex-div"
brxe-div        → (empty) or optional: flex-div
iframe          → data-etch-element="iframe"
```

#### **Complete etchData Structure:**
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

#### **Complete Migration Example:**

**Bricks (Source):**
```php
// _bricks_page_content_2
array(
    array(
        'id' => 'abc123',
        'name' => 'section',
        'settings' => array(
            '_cssClasses' => 'hero-section',
            '_cssGlobalClasses' => array('primary-bg'),
            'tag' => 'section'
        ),
        'children' => array('def456')
    ),
    array(
        'id' => 'def456',
        'name' => 'container',
        'parent' => 'abc123',
        'settings' => array(
            '_cssClasses' => 'hero__container'
        )
    )
)
```

**Etch (Target) - Gutenberg Editor:**
```html
<!-- wp:group {"metadata":{"name":"Section","etchData":{"origin":"etch","name":"Section","styles":["primary-bg","hero-section"],"attributes":{"data-etch-element":"section","class":"hero-section"},"block":{"type":"html","tag":"section"}}}} -->
<div class="wp-block-group">
    <!-- wp:group {"metadata":{"name":"Container","etchData":{"origin":"etch","name":"Container","attributes":{"data-etch-element":"container","class":"hero__container"},"block":{"type":"html","tag":"div"}}}} -->
    <div class="wp-block-group"></div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

**Etch (Target) - Frontend Rendering:**
```html
<section data-etch-element="section" class="hero-section">
    <div data-etch-element="container" class="hero__container"></div>
</section>
```

#### **Real-World Etch Example (from Production):**

**Frontend HTML:**
```html
<section data-etch-element="section" class="test">
  <div data-etch-element="container" class="test__container">
    <h2 text__heading="">Test Section</h2>
    <p class="test__text">Test Test Test Test Test</p>
    <div data-etch-element="flex-div">
      <h2>Insert your heading here…</h2>
      <iframe src="" title="" data-etch-element="iframe"></iframe>
      <a href="#">Click me</a>
      <svg width="48" height="48">...</svg>
    </div>
    <div>
      <img decoding="async" src="https://placehold.co/600x400" alt="">
      <p>Insert your text here…</p>
    </div>
  </div>
</section>
```

**Key Observations:**
- ✅ `data-etch-element` attributes identify Etch elements
- ✅ User CSS classes preserved (`.test`, `.test__container`, `.test__text`)
- ✅ Standard HTML for heading, paragraph, image (NO etch attributes!)
- ✅ `flex-div` gets `data-etch-element="flex-div"`
- ✅ `iframe` gets `data-etch-element="iframe"`
- ✅ Plain `<div>` has NO `data-etch-element` (not an Etch element!)

#### **Etch Styles Structure (REAL DATA!):**

**Etch `etch_styles` Option (Serialized):**
```php
a:8:{
  s:18:"etch-section-style";a:5:{
    s:4:"type";s:7:"element";
    s:8:"selector";s:37:":where([data-etch-element="section"])";
    s:10:"collection";s:7:"default";
    s:3:"css";s:84:"inline-size: 100%; display: flex; flex-direction: column; align-items: center;";
    s:8:"readonly";b:1;
  }
  s:20:"etch-container-style";a:5:{
    s:4:"type";s:7:"element";
    s:8:"selector";s:39:":where([data-etch-element="container"])";
    s:10:"collection";s:7:"default";
    s:3:"css";s:126:"inline-size: 100%; display: flex; flex-direction: column; max-width: var(--content-width, 1366px); align-self: center;";
    s:8:"readonly";b:1;
  }
  s:26:"etch-global-variable-style";a:5:{
    s:4:"type";s:6:"custom";
    s:8:"selector";s:5:":root";
    s:10:"collection";s:7:"default";
    s:3:"css";s:32:"--test: #fff; --example: #000;";
    s:8:"readonly";b:0;
  }
  s:7:"054usim";a:5:{
    s:4:"type";s:5:"class";
    s:8:"selector";s:5:".test";
    s:10:"collection";s:7:"default";
    s:3:"css";s:0:"";
    s:8:"readonly";b:0;
  }
  s:7:"5l041td";a:5:{
    s:4:"type";s:5:"class";
    s:8:"selector";s:16:".test__container";
    s:10:"collection";s:7:"default";
    s:3:"css";s:49:"padding: 2em; background: lightgoldenrodyellow;";
    s:8:"readonly";b:0;
  }
}
```

**Style Types:**
1. **`element`** → `:where([data-etch-element="..."])` (readonly: true)
2. **`custom`** → `:root` (for CSS variables, readonly: false)  
3. **`class`** → `.class-name` (user classes, readonly: false)

**Style IDs:**
- **Element styles**: `etch-{element}-style` (e.g., `etch-section-style`)
- **CSS variables**: `etch-global-variable-style`
- **User classes**: 7-character hash (e.g., `054usim`, `5l041td`)

### Dynamic Data Conversion (CRITICAL)
Bricks uses `{post_title}`, `{acf_field}` syntax. Etch uses `{this.title}`, `{this.meta.field}` syntax.
Full mapping required for seamless content migration.

#### **Etch Custom Fields Integration (REAL DATA!)**

Based on [Etch Custom Fields Documentation](https://docs.etchwp.com/integrations/custom-fields/), Etch supports:

**1. Text Fields:**
```php
// ACF
{this.acf.headline}
{this.acf.description}

// MetaBox  
{this.metabox.headline}
{this.metabox.description}

// JetEngine
{this.jetengine.headline}
{this.jetengine.description}
```

**2. Image Fields:**
```php
// ACF (returns full object with properties)
<img src="{this.acf.image_field.url}" alt="{this.acf.image_field.alt}" />
<img src="{this.acf.image_field.sizes.medium.url}" width="{this.acf.image_field.sizes.medium.width}" />

// MetaBox
<img src="{this.metabox.image_field.url}" alt="{this.metabox.image_field.alt}" />

// JetEngine
<img src="{this.jetengine.image_field.url}" alt="{this.jetengine.image_field.alt}" />
```

**3. Gallery Fields (Loops):**
```php
// ACF
{#loop this.acf.gallery_field as image}
  <img src="{image.url}" alt="{image.alt}" />
{/loop}

// MetaBox
{#loop this.metabox.gallery_field as image}
  <img src="{image.url}" alt="{image.alt}" />
{/loop}
```

**4. Repeater Fields (Loops):**
```php
// ACF
{#loop this.acf.faq as faq}
  <div class="faq">
    <h3>{faq.question}</h3>
    <p>{faq.answer}</p>
  </div>
{/loop}

// MetaBox
{#loop this.metabox.testimonials as testimonial}
  <div class="testimonial">
    <p>{testimonial.content}</p>
    <cite>{testimonial.author}</cite>
  </div>
{/loop}
```

**5. Relationship Fields (Loops):**
```php
// ACF
{#loop this.acf.related_posts as post}
  <div class="related-post">
    <h3>{post.title}</h3>
    <p>{post.excerpt}</p>
  </div>
{/loop}
```

**ACF Image Field Data Structure:**
```json
{
  "acf": {
    "my_image_field": {
      "id": 123,
      "url": "https://example.com/image.jpg",
      "alt": "Image alt text",
      "title": "Image title",
      "caption": "Image caption",
      "sizes": {
        "thumbnail": {"url": "...", "width": 150, "height": 150},
        "medium": {"url": "...", "width": 300, "height": 200},
        "large": {"url": "...", "width": 1024, "height": 683}
      },
      "width": 1600,
      "height": 1067
    }
  }
}
```

### Database Mappings

#### **Bricks vs Etch - CRITICAL DIFFERENCES!**

**🔍 Key Discovery:** Bricks und Etch speichern Content **KOMPLETT VERSCHIEDEN**!

| **Aspect** | **Bricks (Source)** | **Etch (Target)** |
|------------|---------------------|-------------------|
| **post_content** | ❌ **LEER!** | ✅ **GEFÜLLT!** |
| **Content Storage** | `_bricks_page_content_2` (meta) | `post_content` (standard) |
| **Format** | Serialized PHP Array | Gutenberg Blocks |
| **Meta Keys** | `_bricks_template_type`, `_bricks_editor_mode` | Standard WordPress (`_edit_lock`, `_edit_last`) |
| **Styles** | `bricks_global_classes` (options) | `etch_styles` (options) |
| **Element IDs** | 6-char hashes (`953e49`) | Style hashes (`054usim`) |

**Migration Challenge:**
```
Bricks: post_content = "" + _bricks_page_content_2 = array()
   ↓
Etch:   post_content = "<!-- wp:group {...} -->" + no special meta
```

**Migration Strategy:**
1. **Read:** `_bricks_page_content_2` (serialized array)
2. **Parse:** Bricks elements structure  
3. **Convert:** To Etch Gutenberg blocks
4. **Write:** To `post_content` (standard WordPress)
5. **Cleanup:** Remove Bricks meta (optional)

#### **Bricks DB Structure (REAL DATA from Production!)**

**Key Discovery:** Bricks speichert **NICHTS** in `post_content`! Alles wird in `wp_postmeta` gespeichert!

**wp_posts Table:**
```sql
post_id: 3485
post_type: page  
post_title: Test
post_content: (LEER! Bricks speichert alles in meta!)
post_status: publish
```

**wp_postmeta Table:**
```sql
post_id: 3485, meta_key: '_bricks_template_type', meta_value: 'content'
post_id: 3485, meta_key: '_bricks_editor_mode', meta_value: 'bricks'  
post_id: 3485, meta_key: '_bricks_page_content_2', meta_value: 'a:13:{i:0;a:6:{s:2:"id";s:6:"953e49";s:4:"name";s:...}'
post_id: 3485, meta_key: '_edit_lock', meta_value: '1760380291:9'
```

**Critical Meta Keys:**
- `_bricks_template_type: content` → Identifiziert Bricks-Seiten
- `_bricks_editor_mode: bricks` → Bestätigt Bricks-Editor
- `_bricks_page_content_2` → Serialized Array mit allen Elementen
- `_edit_lock` → WordPress edit lock (ignorieren)

#### Bricks Builder (Source) - REAL DB STRUCTURE!
```php
// wp_posts table (REAL DATA from DB!)
post_id: 3485
post_type: page
post_title: Test
post_content: (LEER! Bricks speichert alles in meta!)
post_status: publish

// wp_postmeta table (REAL DATA from DB!)
'_bricks_template_type' => 'content'           // Identifiziert Bricks-Seiten
'_bricks_editor_mode' => 'bricks'              // Confirms Bricks Editor  
'_bricks_page_content_2' => 'a:13:{i:0;a:6:{s:2:"id";s:6:"953e49";s:4:"name";s:...}'  // Serialized Array!
'_edit_lock' => '1760380291:9'                 // WordPress edit lock

// wp_options table
'bricks_global_classes' => array(
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

// _bricks_page_content_2 (Serialized Array Structure - REAL DATA!)
'_bricks_page_content_2' => array(
    0 => array(
        'id' => '953e49',                      // 6-character hash ID
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

#### **Etch DB Structure (REAL DATA from Production!)**

**Key Discovery:** Etch speichert Content **STANDARD** in `post_content`! Keine speziellen meta keys!

**wp_posts Table:**
```sql
post_id: 603
post_author: 1
post_date: 2025-09-07 13:57:23
post_content: '<!-- wp:group {"metadata":{"name":"Hero Section","...'  // FILLED!
post_title: Home new
post_excerpt: (leer)
post_status: publish
post_type: page
comment_count: 0
```

**wp_postmeta Table:**
```sql
post_id: 603, meta_key: '_edit_lock', meta_value: '1757704421:1'
post_id: 603, meta_key: '_edit_last', meta_value: '1'
// KEINE Etch-spezifischen meta keys!
```

**Critical Differences from Bricks:**
- ✅ **`post_content` ist GEFÜLLT** - Standard Gutenberg blocks
- ✅ **Keine Etch meta keys** - Nur Standard WordPress meta
- ✅ **Standard WordPress** - Keine Page Builder spezifischen Felder

#### Etch PageBuilder (Target) - REAL DB STRUCTURE!
```php
// wp_posts table (REAL DATA from Etch DB!)
post_id: 603
post_author: 1
post_date: 2025-09-07 13:57:23
post_content: '<!-- wp:group {"metadata":{"name":"Hero Section","...'  // FILLED!
post_title: Home new
post_excerpt: (leer)
post_status: publish
post_type: page
comment_count: 0

// wp_postmeta table (REAL DATA from Etch DB!)
post_id: 603, meta_key: '_edit_lock', meta_value: '1757704421:1'
post_id: 603, meta_key: '_edit_last', meta_value: '1'
// KEINE Etch-spezifischen meta keys!

// wp_options table - REAL ETCH STRUCTURE!
'etch_styles' => array(
    // 1. ELEMENT STYLES (for data-etch-element)
    'etch-section-style' => array(
        'type' => 'element',
        'selector' => ':where([data-etch-element="section"])',
        'collection' => 'default',
        'css' => 'inline-size: 100%; display: flex; flex-direction: column; align-items: center;',
        'readonly' => true
    ),
    'etch-container-style' => array(
        'type' => 'element', 
        'selector' => ':where([data-etch-element="container"])',
        'collection' => 'default',
        'css' => 'inline-size: 100%; display: flex; flex-direction: column; max-width: var(--content-width, 1366px); align-self: center;',
        'readonly' => true
    ),
    'etch-flex-div-style' => array(
        'type' => 'element',
        'selector' => ':where([data-etch-element="flex-div"])', 
        'collection' => 'default',
        'css' => 'inline-size: 100%; display: flex; flex-direction: column;',
        'readonly' => true
    ),
    'etch-iframe-style' => array(
        'type' => 'element',
        'selector' => ':where([data-etch-element="iframe"])',
        'collection' => 'default', 
        'css' => 'inline-size: 100%; height: auto; aspect-ratio: 16/9;',
        'readonly' => true
    ),
    
    // 2. CSS VARIABLES (in :root)
    'etch-global-variable-style' => array(
        'type' => 'custom',
        'selector' => ':root',
        'collection' => 'default',
        'css' => '--test: #fff; --example: #000; --content-width: 1366px;',
        'readonly' => false
    ),
    
    // 3. USER CLASSES (normale CSS-Klassen mit Hash-IDs)
    '054usim' => array(
        'type' => 'class',
        'selector' => '.test',
        'collection' => 'default',
        'css' => '',
        'readonly' => false
    ),
    '5l041td' => array(
        'type' => 'class', 
        'selector' => '.test__container',
        'collection' => 'default',
        'css' => 'padding: 2em; background: lightgoldenrodyellow;',
        'readonly' => false
    ),
    'cgmtxig' => array(
        'type' => 'class',
        'selector' => '.test__text', 
        'collection' => 'default',
        'css' => '',
        'readonly' => false
    )
);

// wp_posts table (post_content) - CORRECTED ETCH FORMAT!
$gutenberg_blocks = '
<!-- wp:group {"metadata":{"name":"Section","etchData":{"origin":"etch","name":"Section","styles":["my-section-class"],"attributes":{"data-etch-element":"section","class":"my-section-class"},"block":{"type":"html","tag":"section"}}}} -->
<div class="wp-block-group">
    <!-- wp:group {"metadata":{"name":"Container","etchData":{"origin":"etch","name":"Container","attributes":{"data-etch-element":"container"},"block":{"type":"html","tag":"div"}}}} -->
    <div class="wp-block-group">
        <!-- wp:paragraph -->
        <p>Welcome {this.title}</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
';

// Frontend Rendering (what user sees):
<section data-etch-element="section" class="my-section-class">
    <div data-etch-element="container">
        <p>Welcome {this.title}</p>
    </div>
</section>
```

## Plugin Structure (Enhanced - V2.1)

```
bricks-etch-migration/
├── bricks-etch-migration.php           # Main plugin file
├── includes/
│   ├── class-migration-manager.php     # Core orchestrator (ENHANCED)
│   ├── class-auth-handler.php          # Simple API key auth
│   ├── class-css-converter.php         # CSS conversion engine
│   ├── class-css-nesting-parser.php    # Vanilla CSS to nested CSS
│   ├── class-dynamic-data-converter.php # Bricks tags → Etch keys + modifiers
│   ├── class-content-parser.php        # Bricks content parser
│   ├── class-gutenberg-generator.php   # Gutenberg block generator
│   ├── class-api-client.php            # REST API communication (ENHANCED)
│   ├── class-api-endpoints.php         # REST API routes (ENHANCED)
│   ├── class-plugin-detector.php       # Plugin detection & validation ⭐ NEW
│   ├── class-custom-fields-migrator.php # Custom field values migration ⭐ NEW
│   ├── class-acf-field-groups-migrator.php # ACF field groups migration ⭐ NEW
│   ├── class-metabox-migrator.php      # MetaBox configs migration ⭐ NEW
│   ├── class-cpt-migrator.php          # Custom Post Types migration ⭐ NEW
│   ├── class-cross-plugin-converter.php # ACF ↔ MetaBox conversion ⭐ NEW
│   ├── class-error-handler.php         # Error & warning codes
│   └── class-logger.php                # Transient-based logging
├── admin/
│   ├── class-admin-interface.php       # Admin panel
│   ├── views/
│   │   ├── dashboard.php               # Main dashboard (ENHANCED)
│   │   ├── validation-report.php       # Pre-migration validation ⭐ NEW
│   │   └── error-log.php               # Error & warning log viewer
│   ├── css/
│   │   └── admin-styles.css
│   └── js/
│       └── admin-scripts.js
├── docs/
│   ├── README.md
│   ├── ERROR-CODES.md                  # Error & warning codes
│   ├── DYNAMIC-DATA-MAPPING.md         # Bricks ↔ Etch mapping
│   ├── CUSTOM-FIELDS-MIGRATION.md      # ACF/MetaBox/JetEngine guide ⭐ NEW
│   └── CPT-MIGRATION.md                # Custom Post Types guide ⭐ NEW
└── tests/
    ├── test-data/                      # Real Bricks test sites
    ├── unit/
    │   ├── test-acf-migration.php      ⭐ NEW
    │   ├── test-metabox-migration.php  ⭐ NEW
    │   └── test-cpt-migration.php      ⭐ NEW
    └── integration/
```

**New Classes (6):**
- `B2E_Plugin_Detector` - Detect ACF, MetaBox, JetEngine
- `B2E_Custom_Fields_Migrator` - Migrate wp_postmeta
- `B2E_ACF_Field_Groups_Migrator` - Migrate ACF field groups
- `B2E_MetaBox_Migrator` - Migrate MetaBox configs
- `B2E_CPT_Migrator` - Migrate & register CPTs
- `B2E_Cross_Plugin_Converter` - ACF ↔ MetaBox conversion

## Implementation Details

### Phase 1: Core Plugin Setup & Authentication

#### 1.1 Main Plugin File
```php
<?php
/**
 * Plugin Name: Bricks to Etch Migration
 * Description: One-time migration tool for Bricks Builder to Etch PageBuilder.
 * Version: 1.0.0
 * Author: Tobias Haas
 * Text Domain: bricks-etch-migration
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('B2E_VERSION', '1.0.0');
define('B2E_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('B2E_PLUGIN_URL', plugin_dir_url(__FILE__));
define('B2E_API_VERSION', 'v1');

// Autoloader
spl_autoload_register(function($class) {
    if (strpos($class, 'B2E_') === 0) {
        $file = B2E_PLUGIN_DIR . 'includes/class-' . 
                strtolower(str_replace('_', '-', substr($class, 4))) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    new B2E_Migration_Manager();
});

// Initialize Custom Post Types (persistent registration)
add_action('init', function() {
    $cpt_migrator = new B2E_CPT_Migrator();
    $cpt_migrator->init_registered_cpts();
}, 0);

// Activation hook
register_activation_hook(__FILE__, function() {
    // Generate initial API key
    $api_key = wp_generate_password(32, false);
    update_option('b2e_api_key', $api_key);
    update_option('b2e_key_expires', time() + (8 * HOUR_IN_SECONDS));
    
    // Initialize error log
    set_transient('b2e_error_log', array(), 8 * HOUR_IN_SECONDS);
    
    // Set default settings
    update_option('b2e_settings', array(
        'chunk_size' => 50,
        'timeout' => 60,
        'dry_run' => false,
        'debug_mode' => false,
        'convert_div_to_flex' => false  // brxe-div → flex-div?
    ));
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup transients
    delete_transient('b2e_checkpoint');
    delete_transient('b2e_error_log');
    delete_transient('b2e_progress');
    
    // Optionally keep logs for review
    // User can manually delete after reviewing
});
```

#### 1.2 Authentication Handler (Simplified)
```php
<?php
class B2E_Auth_Handler {
    
    private $api_key_option = 'b2e_api_key';
    private $expires_option = 'b2e_key_expires';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_auth_routes'));
    }
    
    public function register_auth_routes() {
        // Generate new API key manually (via admin UI)
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/auth/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_api_key'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        // Verify connection
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/auth/verify', array(
            'methods' => 'GET',
            'callback' => array($this, 'verify_connection'),
            'permission_callback' => array($this, 'verify_api_key')
        ));
    }
    
    public function generate_api_key($request) {
        $api_key = wp_generate_password(32, false);
        update_option($this->api_key_option, $api_key);
        update_option($this->expires_option, time() + (8 * HOUR_IN_SECONDS));
        
        return new WP_REST_Response(array(
            'api_key' => $api_key,
            'site_url' => home_url(),
            'expires_in_seconds' => 8 * HOUR_IN_SECONDS,
            'expires_at' => date('Y-m-d H:i:s', time() + (8 * HOUR_IN_SECONDS))
        ), 200);
    }
    
    public function verify_connection($request) {
        return new WP_REST_Response(array(
            'status' => 'connected',
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'bricks_active' => $this->is_bricks_active(),
            'etch_active' => $this->is_etch_active()
        ), 200);
    }
    
    public function verify_api_key($request) {
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            return new WP_Error('no_auth', 'No authorization header provided', array('status' => 401));
        }
        
        $provided_key = substr($auth_header, 7);
        $stored_key = get_option($this->api_key_option);
        $expires = get_option($this->expires_option);
        
        // Check if key expired
        if ($expires && time() > $expires) {
            return new WP_Error('key_expired', 'API key has expired', array('status' => 401));
        }
        
        // Constant-time comparison
        if (!hash_equals($stored_key, $provided_key)) {
            return new WP_Error('invalid_key', 'Invalid API key', array('status' => 401));
        }
        
        return true;
    }
    
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
    
    private function is_bricks_active() {
        return defined('BRICKS_VERSION');
    }
    
    private function is_etch_active() {
        return defined('ETCH_VERSION');
    }
}
```

### Phase 2: Dynamic Data Conversion (CRITICAL - ENHANCED!)

#### 2.1 Dynamic Data Converter
```php
<?php
/**
 * Converts Bricks dynamic data tags to Etch dynamic data keys
 * 
 * Based on Etch documentation: 
 * - https://docs.etchwp.com/dynamic-data/dynamic-data-keys
 * - https://docs.etchwp.com/dynamic-data/dynamic-data-modifiers
 * - https://docs.etchwp.com/integrations/custom-fields
 */
class B2E_Dynamic_Data_Converter {
    
    /**
     * Complete Bricks → Etch dynamic data mapping
     */
    private $mapping = array(
        // Post Data
        '{post_id}'              => '{this.id}',
        '{post_title}'           => '{this.title}',
        '{post_content}'         => '{this.content}',
        '{post_excerpt}'         => '{this.excerpt}',
        '{post_permalink}'       => '{this.permalink.full}',
        '{post_url}'             => '{this.permalink.relative}',
        '{post_date}'            => '{this.date}',
        '{post_slug}'            => '{this.slug}',
        '{post_type}'            => '{this.type}',
        '{post_status}'          => '{this.status}',
        '{featured_image}'       => '{this.image.url}',
        '{featured_image_url}'   => '{this.image.url}',
        '{featured_image_alt}'   => '{this.image.alt}',
        
        // Author Data
        '{author_name}'          => '{this.author.name}',
        '{author_id}'            => '{this.author.id}',
        '{author_display_name}'  => '{this.author.name}',
        
        // User Data
        '{user_id}'              => '{user.id}',
        '{user_email}'           => '{user.email}',
        '{user_login}'           => '{user.login}',
        '{user_display_name}'    => '{user.displayName}',
        '{user_first_name}'      => '{user.firstName}',
        '{user_last_name}'       => '{user.lastName}',
        '{user_nickname}'        => '{user.nickname}',
        '{user_logged_in}'       => '{user.loggedIn}',
        
        // Site Data
        '{site_title}'           => '{site.name}',
        '{site_tagline}'         => '{site.description}',
        '{site_url}'             => '{site.home_url}',
        '{home_url}'             => '{site.home_url}',
        '{current_url}'          => '{url.full}',
        
        // Special
        '{current_date}'         => '{site.currentDate}',
    );
    
    /**
     * Bricks Modifiers → Etch Modifiers mapping
     * Based on: https://docs.etchwp.com/dynamic-data/dynamic-data-modifiers
     */
    private $modifier_mapping = array(
        // Date/Time
        'date'           => 'dateFormat',
        'date_format'    => 'dateFormat',
        
        // Number formatting
        'number_format'  => 'numberFormat',
        
        // String manipulation
        'uppercase'      => 'toUpperCase',
        'lowercase'      => 'toLowerCase',
        'ucfirst'        => 'toUpperCase', // Partial match
        'strtoupper'     => 'toUpperCase',
        'strtolower'     => 'toLowerCase',
        'trim'           => 'trim',
        'truncate'       => 'truncateChars',
        'words'          => 'truncateWords',
        'slug'           => 'toSlug',
        'slugify'        => 'toSlug',
        
        // Type conversion
        'string'         => 'toString',
        'int'            => 'toInt',
        'integer'        => 'toInt',
        'bool'           => 'toBool',
        'boolean'        => 'toBool',
        
        // URL
        'urlencode'      => 'urlEncode',
        'urldecode'      => 'urlDecode',
        
        // Math
        'round'          => 'round',
        'ceil'           => 'ceil',
        'floor'          => 'floor',
        
        // Array/String operations
        'length'         => 'length',
        'count'          => 'length',
        'reverse'        => 'reverse',
        'split'          => 'split',
        'join'           => 'join',
        'concat'         => 'concat',
    );
    
    /**
     * Convert all Bricks dynamic tags in content to Etch format
     */
    public function convert_content($content) {
        if (empty($content)) {
            return $content;
        }
        
        $converted = $content;
        
        // Convert simple mappings
        $converted = $this->convert_simple_tags($converted);
        
        // Convert tags WITH modifiers (e.g., {post_title|uppercase})
        $converted = $this->convert_tags_with_modifiers($converted);
        
        // Convert ACF fields
        $converted = $this->convert_acf_fields($converted);
        
        // Convert MetaBox fields
        $converted = $this->convert_metabox_fields($converted);
        
        // Convert JetEngine fields
        $converted = $this->convert_jetengine_fields($converted);
        
        // Convert custom fields / post meta
        $converted = $this->convert_meta_fields($converted);
        
        // Convert query loop tags
        $converted = $this->convert_query_tags($converted);
        
        // Convert URL parameters
        $converted = $this->convert_url_parameters($converted);
        
        // Log any unconverted tags
        $this->log_unconverted_tags($converted);
        
        return $converted;
    }
    
    /**
     * Convert tags with Bricks modifiers to Etch modifiers
     * Example: {post_title|uppercase} → {this.title.toUpperCase()}
     * Example: {post_date|date:Y-m-d} → {this.date.dateFormat("Y-m-d")}
     */
    private function convert_tags_with_modifiers($content) {
        // Pattern: {tag_name|modifier} or {tag_name|modifier:param}
        $pattern = '/\{([a-zA-Z0-9_:]+)\|([a-zA-Z0-9_]+)(?::([^}]+))?\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $tag = '{' . $matches[1] . '}';
            $bricks_modifier = $matches[2];
            $modifier_param = isset($matches[3]) ? $matches[3] : null;
            
            // First, convert the base tag
            $etch_tag = isset($this->mapping[$tag]) ? $this->mapping[$tag] : $tag;
            
            // Remove curly braces from etch tag for modifier chaining
            $etch_tag_clean = trim($etch_tag, '{}');
            
            // Convert modifier
            if (isset($this->modifier_mapping[$bricks_modifier])) {
                $etch_modifier = $this->modifier_mapping[$bricks_modifier];
                
                // Handle modifiers with parameters
                if ($modifier_param) {
                    // Quote string parameters
                    $quoted_param = '"' . $modifier_param . '"';
                    return '{' . $etch_tag_clean . '.' . $etch_modifier . '(' . $quoted_param . ')}';
                } else {
                    return '{' . $etch_tag_clean . '.' . $etch_modifier . '()}';
                }
            } else {
                // Modifier not found, log it
                $error_handler = new B2E_Error_Handler();
                $error_handler->log_error('E004', array(
                    'bricks_modifier' => $bricks_modifier,
                    'tag' => $matches[0],
                    'suggestion' => 'Check Etch modifiers documentation for equivalent'
                ));
                
                // Return original tag without modifier as fallback
                return $etch_tag;
            }
        }, $content);
    }
    
    /**
     * Convert simple 1:1 mappings
     */
    private function convert_simple_tags($content) {
        return str_replace(
            array_keys($this->mapping),
            array_values($this->mapping),
            $content
        );
    }
    
    /**
     * Convert ACF field tags (ENHANCED!)
     * Bricks: {acf_field_name} or {acf:field_name}
     * Etch: {this.acf.field_name}
     * 
     * Based on: https://docs.etchwp.com/integrations/custom-fields/
     * Supports: Text, Image, Gallery, Repeater, Relationship fields
     */
    private function convert_acf_fields($content) {
        // Pattern: {acf_field_name} or {acf:field_name}
        $pattern = '/\{acf[_:]([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Check if it's an image field (common patterns)
            if (preg_match('/_(image|img|photo|picture|logo|avatar)$/i', $field_name) || 
                preg_match('/^(image|img|photo|picture|logo|avatar)_/i', $field_name)) {
                // Image fields need .url property
                return '{this.acf.' . $field_name . '.url}';
            }
            
            // Check if it's a gallery field
            if (preg_match('/_(gallery|gallery_images|images)$/i', $field_name) || 
                preg_match('/^(gallery|gallery_images|images)_/i', $field_name)) {
                // Gallery fields need loop syntax
                return '{#loop this.acf.' . $field_name . ' as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
            }
            
            // Check if it's a repeater field
            if (preg_match('/_(repeater|items|list|rows)$/i', $field_name) || 
                preg_match('/^(repeater|items|list|rows)_/i', $field_name)) {
                // Repeater fields need loop syntax
                return '{#loop this.acf.' . $field_name . ' as item}<div>{item.sub_field}</div>{/loop}';
            }
            
            // Default: regular text field
            return '{this.acf.' . $field_name . '}';
        }, $content);
        
        // Also handle with modifiers: {acf:field|modifier}
        $pattern_with_modifier = '/\{acf[_:]([a-zA-Z0-9_-]+)\|([a-zA-Z0-9_]+)(?::([^}]+))?\}/';
        
        $content = preg_replace_callback($pattern_with_modifier, function($matches) {
            $field_name = $matches[1];
            $bricks_modifier = $matches[2];
            $modifier_param = isset($matches[3]) ? $matches[3] : null;
            
            $etch_modifier = isset($this->modifier_mapping[$bricks_modifier]) 
                ? $this->modifier_mapping[$bricks_modifier] 
                : $bricks_modifier;
            
            if ($modifier_param) {
                return '{this.acf.' . $field_name . '.' . $etch_modifier . '("' . $modifier_param . '")}';
            } else {
                return '{this.acf.' . $field_name . '.' . $etch_modifier . '()}';
            }
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert MetaBox field tags (ENHANCED!)
     * Bricks: {mb_field_name} or {metabox:field_name}
     * Etch: {this.metabox.field_name}
     * 
     * Based on: https://docs.etchwp.com/integrations/custom-fields/
     * Supports: Text, Image, Gallery, Repeater, Relationship fields
     */
    private function convert_metabox_fields($content) {
        // Pattern: {mb_field_name} or {metabox:field_name}
        $pattern = '/\{(?:mb|metabox)[_:]([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Check if it's an image field (common patterns)
            if (preg_match('/_(image|img|photo|picture|logo|avatar)$/i', $field_name) || 
                preg_match('/^(image|img|photo|picture|logo|avatar)_/i', $field_name)) {
                // Image fields need .url property
                return '{this.metabox.' . $field_name . '.url}';
            }
            
            // Check if it's a gallery field
            if (preg_match('/_(gallery|gallery_images|images)$/i', $field_name) || 
                preg_match('/^(gallery|gallery_images|images)_/i', $field_name)) {
                // Gallery fields need loop syntax
                return '{#loop this.metabox.' . $field_name . ' as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
            }
            
            // Check if it's a repeater field
            if (preg_match('/_(repeater|items|list|rows)$/i', $field_name) || 
                preg_match('/^(repeater|items|list|rows)_/i', $field_name)) {
                // Repeater fields need loop syntax
                return '{#loop this.metabox.' . $field_name . ' as item}<div>{item.sub_field}</div>{/loop}';
            }
            
            // Default: regular text field
            return '{this.metabox.' . $field_name . '}';
        }, $content);
        
        // Handle with modifiers
        $pattern_with_modifier = '/\{(?:mb|metabox)[_:]([a-zA-Z0-9_-]+)\|([a-zA-Z0-9_]+)(?::([^}]+))?\}/';
        
        $content = preg_replace_callback($pattern_with_modifier, function($matches) {
            $field_name = $matches[1];
            $bricks_modifier = $matches[2];
            $modifier_param = isset($matches[3]) ? $matches[3] : null;
            
            $etch_modifier = isset($this->modifier_mapping[$bricks_modifier]) 
                ? $this->modifier_mapping[$bricks_modifier] 
                : $bricks_modifier;
            
            if ($modifier_param) {
                return '{this.metabox.' . $field_name . '.' . $etch_modifier . '("' . $modifier_param . '")}';
            } else {
                return '{this.metabox.' . $field_name . '.' . $etch_modifier . '()}';
            }
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert JetEngine field tags (ENHANCED!)
     * Bricks: {jet_field_name} or {jetengine:field_name}
     * Etch: {this.jetengine.field_name}
     * 
     * Based on: https://docs.etchwp.com/integrations/custom-fields/
     * Supports: Text, Image, Gallery, Repeater, Relationship fields
     */
    private function convert_jetengine_fields($content) {
        // Pattern: {jet_field_name} or {jetengine:field_name}
        $pattern = '/\{(?:jet|jetengine)[_:]([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Check if it's an image field (common patterns)
            if (preg_match('/_(image|img|photo|picture|logo|avatar)$/i', $field_name) || 
                preg_match('/^(image|img|photo|picture|logo|avatar)_/i', $field_name)) {
                // Image fields need .url property
                return '{this.jetengine.' . $field_name . '.url}';
            }
            
            // Check if it's a gallery field
            if (preg_match('/_(gallery|gallery_images|images)$/i', $field_name) || 
                preg_match('/^(gallery|gallery_images|images)_/i', $field_name)) {
                // Gallery fields need loop syntax
                return '{#loop this.jetengine.' . $field_name . ' as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
            }
            
            // Check if it's a repeater field
            if (preg_match('/_(repeater|items|list|rows)$/i', $field_name) || 
                preg_match('/^(repeater|items|list|rows)_/i', $field_name)) {
                // Repeater fields need loop syntax
                return '{#loop this.jetengine.' . $field_name . ' as item}<div>{item.sub_field}</div>{/loop}';
            }
            
            // Default: regular text field
            return '{this.jetengine.' . $field_name . '}';
        }, $content);
        
        // Handle with modifiers
        $pattern_with_modifier = '/\{(?:jet|jetengine)[_:]([a-zA-Z0-9_-]+)\|([a-zA-Z0-9_]+)(?::([^}]+))?\}/';
        
        $content = preg_replace_callback($pattern_with_modifier, function($matches) {
            $field_name = $matches[1];
            $bricks_modifier = $matches[2];
            $modifier_param = isset($matches[3]) ? $matches[3] : null;
            
            $etch_modifier = isset($this->modifier_mapping[$bricks_modifier]) 
                ? $this->modifier_mapping[$bricks_modifier] 
                : $bricks_modifier;
            
            if ($modifier_param) {
                return '{this.jetengine.' . $field_name . '.' . $etch_modifier . '("' . $modifier_param . '")}';
            } else {
                return '{this.jetengine.' . $field_name . '.' . $etch_modifier . '()}';
            }
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert post meta / custom fields
     * Bricks: {post_meta:field_name}
     * Etch: {this.meta.field_name}
     */
    private function convert_meta_fields($content) {
        $pattern = '/\{post_meta:([a-zA-Z0-9_-]+)\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            return '{this.meta.' . $field_name . '}';
        }, $content);
    }
    
    /**
     * Convert query loop tags
     * Bricks: In query loops uses different context
     * Etch: Uses {item.key} in loops
     */
    private function convert_query_tags($content) {
        // Pattern: {query:field_name} or {query_field_name}
        $pattern = '/\{query[_:]([a-zA-Z0-9_-]+)\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Map common query fields
            $query_mapping = array(
                'id' => 'item.id',
                'title' => 'item.title',
                'content' => 'item.content',
                'excerpt' => 'item.excerpt',
                'permalink' => 'item.permalink.full',
                'image' => 'item.image.url',
                'author' => 'item.author.name',
            );
            
            if (isset($query_mapping[$field_name])) {
                return '{' . $query_mapping[$field_name] . '}';
            }
            
            // Default to item.field_name
            return '{item.' . $field_name . '}';
        }, $content);
    }
    
    /**
     * Convert URL parameter tags
     * Bricks: {url_parameter:name}
     * Etch: {url.parameter.name}
     */
    private function convert_url_parameters($content) {
        $pattern = '/\{url_parameter:([a-zA-Z0-9_-]+)\}/';
        
        return preg_replace_callback($pattern, function($matches) {
            $param_name = $matches[1];
            return '{url.parameter.' . $param_name . '}';
        }, $content);
    }
    
    /**
     * Log any tags that couldn't be converted
     */
    private function log_unconverted_tags($content) {
        // Find any remaining Bricks-style tags
        $pattern = '/\{[a-zA-Z0-9_:]+\}/';
        preg_match_all($pattern, $content, $matches);
        
        if (!empty($matches[0])) {
            $unconverted = array_unique($matches[0]);
            
            // Check if they look like Bricks tags (not Etch tags)
            $bricks_tags = array_filter($unconverted, function($tag) {
                // Etch tags use dot notation: {this.field}
                // Bricks tags use underscore/colon: {field_name} or {type:field}
                return !preg_match('/\{[a-z]+\.[a-zA-Z0-9_.]+\}/', $tag);
            });
            
            if (!empty($bricks_tags)) {
                $error_handler = new B2E_Error_Handler();
                $error_handler->log_error('E004', array(
                    'message' => 'Unconverted dynamic data tags found',
                    'tags' => $bricks_tags,
                    'suggestion' => 'These tags may need manual conversion'
                ));
            }
        }
    }
    
    /**
     * Convert element-specific dynamic data in settings
     */
    public function convert_element_settings($settings) {
        if (!is_array($settings)) {
            return $settings;
        }
        
        $converted = array();
        
        foreach ($settings as $key => $value) {
            if (is_string($value)) {
                $converted[$key] = $this->convert_content($value);
            } elseif (is_array($value)) {
                $converted[$key] = $this->convert_element_settings($value);
            } else {
                $converted[$key] = $value;
            }
        }
        
        return $converted;
    }
}
```

### Phase 3: CSS Migration Engine (Enhanced)

#### 3.1 CSS Converter Class (With Variables & Media Queries)
```php
<?php
class B2E_CSS_Converter {
    
    private $css_nesting_parser;
    private $error_handler;
    
    public function __construct() {
        $this->css_nesting_parser = new B2E_CSS_Nesting_Parser();
        $this->error_handler = new B2E_Error_Handler();
    }
    
    /**
     * Convert Bricks global classes to Etch styles format
     */
    public function convert_bricks_classes_to_etch($bricks_classes) {
        $etch_styles = array();
        $css_variables = array();
        
        foreach ($bricks_classes as $class_data) {
            $class_id = $class_data['id'];
            $class_settings = $class_data['settings'];
            
            // Generate vanilla CSS from Bricks settings
            $vanilla_css = $this->generate_css_from_bricks_settings($class_settings);
            
            // Extract CSS variables (skip framework variables)
            $extracted_vars = $this->extract_css_variables($vanilla_css);
            $css_variables = array_merge($css_variables, $extracted_vars['variables']);
            $vanilla_css = $extracted_vars['cleaned_css'];
            
            // Handle custom CSS from Bricks
            if (isset($class_settings['_cssCustom'])) {
                $custom_css = $class_settings['_cssCustom'];
                $vanilla_css .= ' ' . $custom_css;
            }
            
            // Validate CSS before nesting
            if (!$this->is_valid_css($vanilla_css)) {
                $this->error_handler->log_error('E002', array(
                    'class_id' => $class_id,
                    'css' => $vanilla_css,
                    'action' => 'Attempting to fix CSS syntax'
                ));
                $vanilla_css = $this->fix_css_syntax($vanilla_css);
            }
            
            // Convert to nested CSS
            $nested_css = $this->css_nesting_parser->convert_to_nested_css($vanilla_css, $class_id);
            
            // Generate hash ID for style (like Etch does: "054usim", "5l041td")
            $style_id = $this->generate_style_hash($class_id);
            
            $etch_styles[$style_id] = array(
                'type' => 'class',
                'selector' => '.' . $class_id,
                'collection' => 'default',
                'css' => $nested_css,
                'readonly' => false
            );
        }
        
        // Add CSS variables to :root if any were found (REAL ETCH FORMAT!)
        if (!empty($css_variables)) {
            $etch_styles['etch-global-variable-style'] = array(
                'type' => 'custom',
                'selector' => ':root',
                'collection' => 'default',
                'css' => $this->generate_root_css($css_variables),
                'readonly' => false
            );
        }
        
        // Add Etch element styles (ALWAYS present!)
        $etch_styles['etch-section-style'] = array(
            'type' => 'element',
            'selector' => ':where([data-etch-element="section"])',
            'collection' => 'default',
            'css' => 'inline-size: 100%; display: flex; flex-direction: column; align-items: center;',
            'readonly' => true
        );
        
        $etch_styles['etch-container-style'] = array(
            'type' => 'element',
            'selector' => ':where([data-etch-element="container"])',
            'collection' => 'default',
            'css' => 'inline-size: 100%; display: flex; flex-direction: column; max-width: var(--content-width, 1366px); align-self: center;',
            'readonly' => true
        );
        
        $etch_styles['etch-flex-div-style'] = array(
            'type' => 'element',
            'selector' => ':where([data-etch-element="flex-div"])',
            'collection' => 'default',
            'css' => 'inline-size: 100%; display: flex; flex-direction: column;',
            'readonly' => true
        );
        
        $etch_styles['etch-iframe-style'] = array(
            'type' => 'element',
            'selector' => ':where([data-etch-element="iframe"])',
            'collection' => 'default',
            'css' => 'inline-size: 100%; height: auto; aspect-ratio: 16/9;',
            'readonly' => true
        );
        
        return $etch_styles;
    }
    
    /**
     * Extract CSS variables and separate from regular CSS
     */
    private function extract_css_variables($css) {
        $variables = array();
        $cleaned_css = $css;
        
        // Pattern for CSS variables: --variable-name: value;
        $pattern = '/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/';
        
        preg_match_all($pattern, $css, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $var_name = $match[1];
            $var_value = trim($match[2]);
            
            // Skip framework variables (Bootstrap, Tailwind, etc.)
            if ($this->is_framework_variable($var_name)) {
                continue;
            }
            
            $variables[$var_name] = $var_value;
            
            // Remove from original CSS
            $cleaned_css = str_replace($match[0], '', $cleaned_css);
        }
        
        return array(
            'variables' => $variables,
            'cleaned_css' => $cleaned_css
        );
    }
    
    /**
     * Check if variable is from a CSS framework
     */
    private function is_framework_variable($var_name) {
        $framework_prefixes = array(
            '--bs-',      // Bootstrap
            '--tw-',      // Tailwind
            '--mdc-',     // Material Design
            '--wp--',     // WordPress
            '--fa-',      // Font Awesome
        );
        
        foreach ($framework_prefixes as $prefix) {
            if (strpos($var_name, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate :root CSS block with variables
     */
    private function generate_root_css($variables) {
        $css = '';
        
        foreach ($variables as $name => $value) {
            $css .= $name . ': ' . $value . '; ';
        }
        
        return trim($css);
    }
    
    /**
     * Generate Etch-style hash ID for CSS classes
     * Etch uses 7-character hashes like "054usim", "5l041td", "cgmtxig"
     */
    private function generate_style_hash($class_name) {
        // Generate a 7-character hash similar to Etch's style
        $hash = substr(md5($class_name . time()), 0, 7);
        return $hash;
    }
    
    /**
     * Validate CSS syntax
     */
    private function is_valid_css($css) {
        // Basic validation: check for balanced braces
        $open_braces = substr_count($css, '{');
        $close_braces = substr_count($css, '}');
        
        if ($open_braces !== $close_braces) {
            return false;
        }
        
        // Check for common syntax errors
        if (preg_match('/[;:]\s*[;:]/', $css)) {
            return false; // Double semicolons/colons
        }
        
        return true;
    }
    
    /**
     * Attempt to fix common CSS syntax errors
     */
    private function fix_css_syntax($css) {
        // Remove double semicolons
        $css = preg_replace('/;+/', ';', $css);
        
        // Remove double colons (except for pseudo-elements)
        $css = preg_replace('/(?<!:):+(?!:)/', ':', $css);
        
        // Remove trailing semicolons before closing braces
        $css = preg_replace('/;\s*}/', '}', $css);
        
        // Add missing semicolons
        $css = preg_replace('/([a-zA-Z0-9%])\s*}/', '$1;}', $css);
        
        return $css;
    }
    
    /**
     * Generate CSS from Bricks settings array
     */
    private function generate_css_from_bricks_settings($settings) {
        $css_rules = array();
        
        // Background properties
        if (isset($settings['background'])) {
            $css_rules = array_merge($css_rules, $this->parse_background_settings($settings['background']));
        }
        
        // Typography properties
        if (isset($settings['typography'])) {
            $css_rules = array_merge($css_rules, $this->parse_typography_settings($settings['typography']));
        }
        
        // Border properties
        if (isset($settings['border'])) {
            $css_rules = array_merge($css_rules, $this->parse_border_settings($settings['border']));
        }
        
        // Spacing properties
        if (isset($settings['spacing'])) {
            $css_rules = array_merge($css_rules, $this->parse_spacing_settings($settings['spacing']));
        }
        
        // Layout properties
        if (isset($settings['layout'])) {
            $css_rules = array_merge($css_rules, $this->parse_layout_settings($settings['layout']));
        }
        
        // Size properties
        if (isset($settings['size'])) {
            $css_rules = array_merge($css_rules, $this->parse_size_settings($settings['size']));
        }
        
        return implode('; ', $css_rules);
    }
    
    private function parse_background_settings($background) {
        $rules = array();
        
        if (isset($background['color'])) {
            $rules[] = 'background-color: ' . $background['color'];
        }
        
        if (isset($background['image'])) {
            if (is_array($background['image'])) {
                $url = isset($background['image']['url']) ? $background['image']['url'] : '';
                if ($url) {
                    $rules[] = 'background-image: url(' . esc_url($url) . ')';
                }
            } else {
                $rules[] = 'background-image: url(' . esc_url($background['image']) . ')';
            }
        }
        
        if (isset($background['size'])) {
            $rules[] = 'background-size: ' . $background['size'];
        }
        
        if (isset($background['position'])) {
            $rules[] = 'background-position: ' . $background['position'];
        }
        
        if (isset($background['repeat'])) {
            $rules[] = 'background-repeat: ' . $background['repeat'];
        }
        
        if (isset($background['attachment'])) {
            $rules[] = 'background-attachment: ' . $background['attachment'];
        }
        
        return $rules;
    }
    
    private function parse_typography_settings($typography) {
        $rules = array();
        
        $property_mapping = array(
            'font-family' => 'font-family',
            'font-size' => 'font-size',
            'font-weight' => 'font-weight',
            'font-style' => 'font-style',
            'line-height' => 'line-height',
            'letter-spacing' => 'letter-spacing',
            'text-align' => 'text-align',
            'text-transform' => 'text-transform',
            'text-decoration' => 'text-decoration',
            'color' => 'color',
        );
        
        foreach ($property_mapping as $bricks_prop => $css_prop) {
            if (isset($typography[$bricks_prop])) {
                $rules[] = $css_prop . ': ' . $typography[$bricks_prop];
            }
        }
        
        return $rules;
    }
    
    private function parse_border_settings($border) {
        $rules = array();
        
        if (isset($border['width'])) {
            $rules[] = 'border-width: ' . $border['width'];
        }
        
        if (isset($border['style'])) {
            $rules[] = 'border-style: ' . $border['style'];
        }
        
        if (isset($border['color'])) {
            $rules[] = 'border-color: ' . $border['color'];
        }
        
        if (isset($border['radius'])) {
            $rules[] = 'border-radius: ' . $border['radius'];
        }
        
        return $rules;
    }
    
    private function parse_spacing_settings($spacing) {
        $rules = array();
        
        if (isset($spacing['margin'])) {
            $rules[] = 'margin: ' . $spacing['margin'];
        }
        
        if (isset($spacing['padding'])) {
            $rules[] = 'padding: ' . $spacing['padding'];
        }
        
        return $rules;
    }
    
    private function parse_layout_settings($layout) {
        $rules = array();
        
        $property_mapping = array(
            'display' => 'display',
            'flex-direction' => 'flex-direction',
            'flex-wrap' => 'flex-wrap',
            'justify-content' => 'justify-content',
            'align-items' => 'align-items',
            'align-content' => 'align-content',
            'gap' => 'gap',
            'position' => 'position',
            'top' => 'top',
            'right' => 'right',
            'bottom' => 'bottom',
            'left' => 'left',
            'z-index' => 'z-index',
        );
        
        foreach ($property_mapping as $bricks_prop => $css_prop) {
            if (isset($layout[$bricks_prop])) {
                $rules[] = $css_prop . ': ' . $layout[$bricks_prop];
            }
        }
        
        return $rules;
    }
    
    private function parse_size_settings($size) {
        $rules = array();
        
        $property_mapping = array(
            'width' => 'width',
            'min-width' => 'min-width',
            'max-width' => 'max-width',
            'height' => 'height',
            'min-height' => 'min-height',
            'max-height' => 'max-height',
        );
        
        foreach ($property_mapping as $bricks_prop => $css_prop) {
            if (isset($size[$bricks_prop])) {
                $rules[] = $css_prop . ': ' . $size[$bricks_prop];
            }
        }
        
        return $rules;
    }
}
```

#### 3.2 CSS Nesting Parser (Enhanced)
```php
<?php
class B2E_CSS_Nesting_Parser {
    
    /**
     * Convert flat CSS to nested CSS format using CSS Nesting Module syntax
     */
    public function convert_to_nested_css($css_string, $base_selector) {
        if (empty($css_string)) {
            return '';
        }
        
        $nested_css = $css_string;
        
        // Step 1: Parse and extract media queries
        $media_queries = $this->extract_media_queries($nested_css);
        $nested_css = $media_queries['css_without_media'];
        
        // Step 2: Nest pseudo-selectors
        $nested_css = $this->nest_pseudo_selectors($nested_css, $base_selector);
        
        // Step 3: Add media queries as nested rules
        if (!empty($media_queries['media_rules'])) {
            $nested_css .= ' ' . $this->nest_media_queries($media_queries['media_rules']);
        }
        
        // Step 4: Clean up extra spaces
        $nested_css = $this->cleanup_css($nested_css);
        
        return $nested_css;
    }
    
    /**
     * Extract media queries from CSS
     */
    private function extract_media_queries($css) {
        $media_rules = array();
        $css_without_media = $css;
        
        // Pattern to find media queries
        $pattern = '/@media\s*([^{]+)\s*\{([^}]+)\}/';
        
        preg_match_all($pattern, $css, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $condition = trim($match[1]);
            $rules = trim($match[2]);
            
            $media_rules[] = array(
                'condition' => $condition,
                'rules' => $rules,
                'full_match' => $match[0]
            );
            
            // Remove from original CSS
            $css_without_media = str_replace($match[0], '', $css_without_media);
        }
        
        return array(
            'media_rules' => $media_rules,
            'css_without_media' => $css_without_media
        );
    }
    
    /**
     * Nest media queries using CSS Nesting Module syntax
     */
    private function nest_media_queries($media_rules) {
        $nested = '';
        
        foreach ($media_rules as $media) {
            $nested .= '@media ' . $media['condition'] . ' { ' . $media['rules'] . ' } ';
        }
        
        return $nested;
    }
    
    /**
     * Convert pseudo-selectors to nested format
     */
    private function nest_pseudo_selectors($css, $base_selector) {
        // If CSS already contains explicit selectors, parse them
        if (strpos($css, '{') !== false && strpos($css, '}') !== false) {
            return $this->nest_full_selectors($css, $base_selector);
        }
        
        // Otherwise, assume it's property: value; format
        return $css;
    }
    
    /**
     * Parse full CSS rules and nest them
     */
    private function nest_full_selectors($css, $base_selector) {
        $base_pattern = preg_quote('.' . $base_selector, '/');
        
        $patterns = array(
            // :hover
            '/(' . $base_pattern . ')\s*:hover\s*\{([^}]+)\}/' => '&:hover { $2 }',
            // :focus
            '/(' . $base_pattern . ')\s*:focus\s*\{([^}]+)\}/' => '&:focus { $2 }',
            // :active
            '/(' . $base_pattern . ')\s*:active\s*\{([^}]+)\}/' => '&:active { $2 }',
            // :visited
            '/(' . $base_pattern . ')\s*:visited\s*\{([^}]+)\}/' => '&:visited { $2 }',
            // ::before
            '/(' . $base_pattern . ')\s*::before\s*\{([^}]+)\}/' => '&::before { $2 }',
            // ::after
            '/(' . $base_pattern . ')\s*::after\s*\{([^}]+)\}/' => '&::after { $2 }',
            // Child selector
            '/(' . $base_pattern . ')\s+([a-zA-Z0-9_-]+)\s*\{([^}]+)\}/' => '& $2 { $3 }',
        );
        
        foreach ($patterns as $pattern => $replacement) {
            $css = preg_replace($pattern, $replacement, $css);
        }
        
        // Remove base selector if it wraps everything
        $css = preg_replace('/^' . $base_pattern . '\s*\{([^}]+)\}$/', '$1', $css);
        
        return $css;
    }
    
    /**
     * Clean up CSS formatting
     */
    private function cleanup_css($css) {
        // Remove multiple spaces
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove space before semicolons
        $css = preg_replace('/\s+;/', ';', $css);
        
        // Remove space after opening braces
        $css = preg_replace('/\{\s+/', '{ ', $css);
        
        // Remove space before closing braces
        $css = preg_replace('/\s+\}/', ' }', $css);
        
        return trim($css);
    }
}
```

### Phase 4: Custom Fields & Post Meta Migration (NEW!)

#### 4.0 Custom Fields Migration Strategy

**Important Decision: Field Values vs Field Definitions**

Custom Fields haben zwei Komponenten:
1. **Field Definitions** (ACF Field Groups, MetaBox Configs, etc.)
2. **Field Values** (wp_postmeta Einträge)

**Migration Strategy:**

##### **Field Values (wp_postmeta) - ALWAYS MIGRATED**
```php
// wp_postmeta table
post_id | meta_key | meta_value
--------|----------|------------
123     | company_name | "ACME Corp"
123     | _company_name | "field_abc123"  // ACF reference
```

✅ **Wir migrieren IMMER die Field Values**
- Alle post_meta Einträge werden kopiert
- ACF meta keys (_field_references) werden migriert
- MetaBox/JetEngine values werden migriert
- Generic post_meta wird migriert

##### **Field Definitions - USER'S RESPONSIBILITY**

❌ **Wir migrieren NICHT die Field Definitions**
- User muss ACF Field Groups auf Zielseite neu erstellen/importieren
- User muss MetaBox Configs auf Zielseite einrichten
- User muss JetEngine Meta Boxes auf Zielseite erstellen

**Reason:** Field Definitions sind komplex und plugin-spezifisch. Best Practice ist Export/Import über Plugin-eigene Tools.

**Empfohlener Workflow:**
1. **VOR Migration:** Field Groups auf Zielseite einrichten
2. **Migration:** Field Values automatically migrated
3. **Nach Migration:** Verifizieren dass Fields korrekt angezeigt werden

##### **Plugin Detection & Validation**

```php
class B2E_Plugin_Detector {
    
    public function get_installed_plugins() {
        return array(
            'acf' => $this->is_acf_installed(),
            'metabox' => $this->is_metabox_installed(),
            'jetengine' => $this->is_jetengine_installed(),
        );
    }
    
    public function is_acf_installed() {
        return class_exists('ACF') || function_exists('acf');
    }
    
    public function is_metabox_installed() {
        return function_exists('rwmb_meta');
    }
    
    public function is_jetengine_installed() {
        return class_exists('Jet_Engine');
    }
    
    public function validate_migration_requirements($source_plugins, $target_plugins) {
        $warnings = array();
        $errors = array();
        
        // Check if source has ACF but target doesn't
        if ($source_plugins['acf'] && !$target_plugins['acf']) {
            $warnings[] = array(
                'code' => 'W001',
                'message' => 'Source site uses ACF but target site does not have ACF installed',
                'severity' => 'warning',
                'suggestion' => 'Install ACF on target site or field values will be stored as generic post_meta'
            );
        }
        
        // Same for MetaBox
        if ($source_plugins['metabox'] && !$target_plugins['metabox']) {
            $warnings[] = array(
                'code' => 'W002',
                'message' => 'Source site uses MetaBox but target site does not',
                'severity' => 'warning',
                'suggestion' => 'Install MetaBox on target site'
            );
        }
        
        // Same for JetEngine
        if ($source_plugins['jetengine'] && !$target_plugins['jetengine']) {
            $warnings[] = array(
                'code' => 'W003',
                'message' => 'Source site uses JetEngine but target site does not',
                'severity' => 'warning',
                'suggestion' => 'Install JetEngine on target site'
            );
        }
        
        return array(
            'warnings' => $warnings,
            'errors' => $errors,
            'can_proceed' => empty($errors)
        );
    }
}
```

#### 4.1 Custom Field Values Migration

```php
class B2E_Custom_Fields_Migrator {
    
    private $plugin_detector;
    private $logger;
    
    public function __construct() {
        $this->plugin_detector = new B2E_Plugin_Detector();
        $this->logger = new B2E_Logger();
    }
    
    /**
     * Migrate all post_meta for a post
     */
    public function migrate_post_meta($post_id, $target_post_id, $api_client) {
        global $wpdb;
        
        // Get all post_meta from source
        $source_meta = $wpdb->get_results($wpdb->prepare("
            SELECT meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id = %d
        ", $post_id), ARRAY_A);
        
        if (empty($source_meta)) {
            $this->logger->log('info', "No post_meta found for post {$post_id}");
            return 0;
        }
        
        $migrated_count = 0;
        $skipped_meta = array();
        
        foreach ($source_meta as $meta) {
            $meta_key = $meta['meta_key'];
            $meta_value = $meta['meta_value'];
            
            // Skip Bricks-specific meta
            if ($this->should_skip_meta_key($meta_key)) {
                $skipped_meta[] = $meta_key;
                continue;
            }
            
            // Unserialize if needed
            $meta_value = maybe_unserialize($meta_value);
            
            // Send to target site
            $result = $api_client->send_post_meta($target_post_id, $meta_key, $meta_value);
            
            if (is_wp_error($result)) {
                $this->logger->log('error', "Failed to migrate meta {$meta_key}", array(
                    'post_id' => $post_id,
                    'error' => $result->get_error_message()
                ));
            } else {
                $migrated_count++;
            }
        }
        
        if (!empty($skipped_meta)) {
            $this->logger->log('info', "Skipped Bricks meta keys", array(
                'post_id' => $post_id,
                'skipped' => $skipped_meta
            ));
        }
        
        return $migrated_count;
    }
    
    /**
     * Determine if meta key should be skipped
     */
    private function should_skip_meta_key($meta_key) {
        $skip_patterns = array(
            '_bricks_',           // Bricks-specific
            '_elementor_',        // Elementor
            '_et_pb_',            // Divi
            '_vc_',               // Visual Composer
            '_fl_builder_',       // Beaver Builder
        );
        
        foreach ($skip_patterns as $pattern) {
            if (strpos($meta_key, $pattern) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect and report custom field usage
     */
    public function analyze_custom_fields($post_id) {
        global $wpdb;
        
        $meta = $wpdb->get_results($wpdb->prepare("
            SELECT meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id = %d
        ", $post_id), ARRAY_A);
        
        $analysis = array(
            'acf_fields' => array(),
            'metabox_fields' => array(),
            'jetengine_fields' => array(),
            'generic_meta' => array(),
        );
        
        foreach ($meta as $item) {
            $key = $item['meta_key'];
            
            // ACF fields have corresponding _field_key meta
            if (strpos($key, '_') !== 0) {
                // Check if there's an ACF reference
                $field_ref = get_post_meta($post_id, '_' . $key, true);
                if ($field_ref && strpos($field_ref, 'field_') === 0) {
                    $analysis['acf_fields'][$key] = array(
                        'field_key' => $field_ref,
                        'value' => $item['meta_value']
                    );
                    continue;
                }
            }
            
            // MetaBox fields (no special prefix typically)
            // JetEngine fields (jet-engine prefix sometimes)
            if (strpos($key, 'jet-') === 0 || strpos($key, '_jet_') === 0) {
                $analysis['jetengine_fields'][$key] = $item['meta_value'];
                continue;
            }
            
            // Generic meta (not from page builders)
            if (!$this->should_skip_meta_key($key)) {
                $analysis['generic_meta'][$key] = $item['meta_value'];
            }
        }
        
        return $analysis;
    }
}
```

#### 4.2 Custom Post Types Migration Strategy

**Custom Post Types (CPTs) are NOT automatically migrated.**

**Reason:**
- CPTs sind in Theme/Plugin Code definiert
- Migration würde Code-Änderungen erfordern
- User muss CPTs manuell auf Zielseite registrieren

**Migration Strategy:**

```php
class B2E_CPT_Handler {
    
    /**
     * Detect Custom Post Types used on source site
     */
    public function get_custom_post_types() {
        $post_types = get_post_types(array(
            'public' => true,
            '_builtin' => false
        ), 'objects');
        
        $cpts = array();
        
        foreach ($post_types as $post_type) {
            $cpts[$post_type->name] = array(
                'name' => $post_type->name,
                'label' => $post_type->label,
                'supports' => get_all_post_type_supports($post_type->name),
                'taxonomies' => get_object_taxonomies($post_type->name),
                'count' => wp_count_posts($post_type->name)->publish
            );
        }
        
        return $cpts;
    }
    
    /**
     * Validate CPTs exist on target site
     */
    public function validate_cpts_on_target($source_cpts, $target_cpts) {
        $missing = array();
        
        foreach ($source_cpts as $cpt_name => $cpt_data) {
            if (!isset($target_cpts[$cpt_name])) {
                $missing[] = array(
                    'name' => $cpt_name,
                    'label' => $cpt_data['label'],
                    'count' => $cpt_data['count']
                );
            }
        }
        
        return $missing;
    }
}
```

**User Workflow:**
1. Plugin zeigt Liste der CPTs auf Source Site
2. User wird gewarnt wenn CPTs auf Target Site fehlen
3. User muss CPTs auf Target Site registrieren (manuell)
4. Dann kann Migration fortgesetzt werden

**Alternative (V2.0):** Auto-Registration von CPTs via Plugin

#### 4.3 ACF Field Groups Migration (ENHANCED!)

**ACF Field Groups are FULLY automatically migrated!** 🎉

##### **Wie ACF Field Groups gespeichert werden:**

```php
// wp_posts table
post_type = 'acf-field-group'
post_title = 'Contact Information'
post_content = ''
post_excerpt = ''
post_name = 'group_abc123'
post_status = 'publish'

// wp_postmeta (field group settings)
meta_key = '_edit_lock'
meta_key = 'key' → 'group_abc123'

// ACF Fields (children)
post_type = 'acf-field'
post_title = 'Company Name'
post_name = 'company_name'
post_parent = 123 (field group post ID)
post_content = serialized field configuration
```

##### **ACF Field Groups Migrator:**

```php
class B2E_ACF_Field_Groups_Migrator {
    
    private $logger;
    private $api_client;
    
    /**
     * Export all ACF Field Groups from source site
     */
    public function export_field_groups() {
        if (!function_exists('acf_get_field_groups')) {
            $this->logger->log('warning', 'ACF not installed on source site');
            return array();
        }
        
        $field_groups = acf_get_field_groups();
        $exported = array();
        
        foreach ($field_groups as $group) {
            // Get full field group with all fields
            $fields = acf_get_fields($group['key']);
            
            $exported[] = array(
                'key' => $group['key'],
                'title' => $group['title'],
                'fields' => $fields,
                'location' => $group['location'],
                'menu_order' => $group['menu_order'],
                'position' => $group['position'],
                'style' => $group['style'],
                'label_placement' => $group['label_placement'],
                'instruction_placement' => $group['instruction_placement'],
                'hide_on_screen' => $group['hide_on_screen'],
                'active' => $group['active'],
                'description' => $group['description'],
            );
        }
        
        $this->logger->log('info', 'Exported ' . count($exported) . ' ACF Field Groups');
        
        return $exported;
    }
    
    /**
     * Import ACF Field Groups to target site
     */
    public function import_field_groups($field_groups) {
        if (!function_exists('acf_import_field_group')) {
            $this->logger->log('error', 'ACF not installed on target site');
            return false;
        }
        
        $imported_count = 0;
        
        foreach ($field_groups as $group) {
            // Check if field group already exists
            $existing = acf_get_field_group($group['key']);
            
            if ($existing) {
                $this->logger->log('info', "Field group {$group['key']} already exists, updating...");
                // Update existing
                $group['ID'] = $existing['ID'];
            }
            
            // Import field group
            $result = acf_import_field_group($group);
            
            if ($result) {
                $imported_count++;
                $this->logger->log('info', "Imported field group: {$group['title']}");
            } else {
                $this->logger->log('error', "Failed to import field group: {$group['title']}");
            }
        }
        
        return $imported_count;
    }
    
    /**
     * Migrate ACF Field Groups (complete process)
     */
    public function migrate_acf_field_groups($api_client) {
        // Step 1: Export from source
        $field_groups = $this->export_field_groups();
        
        if (empty($field_groups)) {
            return 0;
        }
        
        // Step 2: Send to target site
        $result = $api_client->send_acf_field_groups($field_groups);
        
        if (is_wp_error($result)) {
            $this->logger->log('error', 'Failed to send ACF field groups', array(
                'error' => $result->get_error_message()
            ));
            return false;
        }
        
        return $result['imported_count'];
    }
}
```

#### 4.4 MetaBox Field Groups Migration

**MetaBox Field Groups (mit MB Builder) werden auch migriert!**

##### **Wie MetaBox speichert:**

```php
// Option 1: MB Builder (Custom Post Type)
post_type = 'meta-box'
post_title = 'Product Information'
post_content = JSON configuration
post_status = 'publish'

// Option 2: PHP Code (in Theme/Plugin)
// We can only migrate MB Builder
```

```php
class B2E_MetaBox_Migrator {
    
    /**
     * Export MetaBox configurations (MB Builder only)
     */
    public function export_metabox_configs() {
        global $wpdb;
        
        // Get all meta-box post types
        $metaboxes = $wpdb->get_results("
            SELECT *
            FROM {$wpdb->posts}
            WHERE post_type = 'meta-box'
            AND post_status = 'publish'
        ");
        
        if (empty($metaboxes)) {
            $this->logger->log('info', 'No MetaBox configurations found (MB Builder not used)');
            return array();
        }
        
        $exported = array();
        
        foreach ($metaboxes as $metabox) {
            $config = json_decode($metabox->post_content, true);
            
            $exported[] = array(
                'id' => $metabox->post_name,
                'title' => $metabox->post_title,
                'config' => $config,
                'post_types' => get_post_meta($metabox->ID, 'post_types', true),
            );
        }
        
        return $exported;
    }
    
    /**
     * Import MetaBox configurations to target site
     */
    public function import_metabox_configs($metaboxes) {
        $imported_count = 0;
        
        foreach ($metaboxes as $metabox) {
            // Create meta-box post
            $post_data = array(
                'post_title' => $metabox['title'],
                'post_name' => $metabox['id'],
                'post_content' => json_encode($metabox['config']),
                'post_type' => 'meta-box',
                'post_status' => 'publish',
            );
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id) {
                // Save post types meta
                update_post_meta($post_id, 'post_types', $metabox['post_types']);
                $imported_count++;
                $this->logger->log('info', "Imported MetaBox: {$metabox['title']}");
            }
        }
        
        return $imported_count;
    }
}
```

#### 4.5 Custom Post Types Auto-Registration

**CPTs are automatically detected and registered on target site!**

##### **CPT Detection & Migration:**

```php
class B2E_CPT_Migrator {
    
    /**
     * Export Custom Post Types with full configuration
     */
    public function export_custom_post_types() {
        $cpts = get_post_types(array(
            'public' => true,
            '_builtin' => false
        ), 'objects');
        
        $exported = array();
        
        foreach ($cpts as $cpt) {
            // Get registration args
            $args = (array) $cpt;
            
            // Clean up args (remove objects, keep only serializable data)
            $clean_args = array(
                'label' => $args['label'],
                'labels' => (array) $args['labels'],
                'description' => $args['description'],
                'public' => $args['public'],
                'hierarchical' => $args['hierarchical'],
                'exclude_from_search' => $args['exclude_from_search'],
                'publicly_queryable' => $args['publicly_queryable'],
                'show_ui' => $args['show_ui'],
                'show_in_menu' => $args['show_in_menu'],
                'show_in_nav_menus' => $args['show_in_nav_menus'],
                'show_in_admin_bar' => $args['show_in_admin_bar'],
                'show_in_rest' => $args['show_in_rest'],
                'rest_base' => $args['rest_base'],
                'menu_position' => $args['menu_position'],
                'menu_icon' => $args['menu_icon'],
                'capability_type' => $args['capability_type'],
                'supports' => $args['supports'],
                'has_archive' => $args['has_archive'],
                'rewrite' => $args['rewrite'],
                'query_var' => $args['query_var'],
            );
            
            $exported[$cpt->name] = array(
                'name' => $cpt->name,
                'args' => $clean_args,
                'taxonomies' => get_object_taxonomies($cpt->name),
                'count' => wp_count_posts($cpt->name)->publish,
            );
        }
        
        return $exported;
    }
    
    /**
     * Register CPTs on target site
     */
    public function register_custom_post_types($cpts) {
        $registered_count = 0;
        
        foreach ($cpts as $cpt_name => $cpt_data) {
            // Check if already registered
            if (post_type_exists($cpt_name)) {
                $this->logger->log('info', "CPT {$cpt_name} already exists, skipping");
                continue;
            }
            
            // Register the CPT
            $result = register_post_type($cpt_name, $cpt_data['args']);
            
            if (!is_wp_error($result)) {
                $registered_count++;
                $this->logger->log('info', "Registered CPT: {$cpt_name}");
                
                // Store in option for persistence
                $this->save_cpt_registration($cpt_name, $cpt_data);
            } else {
                $this->logger->log('error', "Failed to register CPT: {$cpt_name}", array(
                    'error' => $result->get_error_message()
                ));
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        return $registered_count;
    }
    
    /**
     * Save CPT registration for persistence (via plugin)
     */
    private function save_cpt_registration($cpt_name, $cpt_data) {
        $registered_cpts = get_option('b2e_registered_cpts', array());
        $registered_cpts[$cpt_name] = $cpt_data;
        update_option('b2e_registered_cpts', $registered_cpts);
    }
    
    /**
     * Re-register CPTs on plugin load (for persistence)
     */
    public function init_registered_cpts() {
        $registered_cpts = get_option('b2e_registered_cpts', array());
        
        foreach ($registered_cpts as $cpt_name => $cpt_data) {
            if (!post_type_exists($cpt_name)) {
                register_post_type($cpt_name, $cpt_data['args']);
            }
        }
    }
}

// Hook for CPT re-registration
add_action('init', array(new B2E_CPT_Migrator(), 'init_registered_cpts'), 0);
```

#### 4.6 Advanced Cross-Plugin Migration (ACF ↔ MetaBox)

**Jetzt mit vollständiger Field Group Konvertierung!**

```php
class B2E_Cross_Plugin_Converter {
    
    /**
     * Convert ACF Field Group to MetaBox format
     */
    public function acf_field_group_to_metabox($acf_group) {
        $metabox_config = array(
            'id' => $acf_group['key'],
            'title' => $acf_group['title'],
            'post_types' => $this->extract_post_types_from_location($acf_group['location']),
            'context' => $this->map_position_to_context($acf_group['position']),
            'priority' => 'high',
            'fields' => array(),
        );
        
        // Convert each field
        foreach ($acf_group['fields'] as $acf_field) {
            $mb_field = $this->convert_acf_field_to_metabox($acf_field);
            if ($mb_field) {
                $metabox_config['fields'][] = $mb_field;
            }
        }
        
        return $metabox_config;
    }
    
    /**
     * Convert ACF field to MetaBox field
     */
    private function convert_acf_field_to_metabox($acf_field) {
        $field_type_map = array(
            'text' => 'text',
            'textarea' => 'textarea',
            'number' => 'number',
            'email' => 'email',
            'url' => 'url',
            'wysiwyg' => 'wysiwyg',
            'image' => 'single_image',
            'file' => 'file',
            'gallery' => 'image_advanced',
            'select' => 'select',
            'checkbox' => 'checkbox_list',
            'radio' => 'radio',
            'true_false' => 'checkbox',
            'post_object' => 'post',
            'relationship' => 'post',
            'taxonomy' => 'taxonomy',
            'user' => 'user',
            'date_picker' => 'date',
            'color_picker' => 'color',
            'repeater' => 'group',  // Complex conversion needed
            'flexible_content' => 'group',  // Very complex
        );
        
        $mb_type = isset($field_type_map[$acf_field['type']]) 
            ? $field_type_map[$acf_field['type']] 
            : 'text';
        
        $mb_field = array(
            'id' => $acf_field['name'],
            'name' => $acf_field['label'],
            'type' => $mb_type,
            'desc' => $acf_field['instructions'],
            'required' => $acf_field['required'],
        );
        
        // Type-specific conversions
        switch ($acf_field['type']) {
            case 'select':
            case 'checkbox':
            case 'radio':
                $mb_field['options'] = $acf_field['choices'];
                break;
                
            case 'repeater':
                $mb_field = $this->convert_acf_repeater_to_metabox_group($acf_field);
                break;
        }
        
        return $mb_field;
    }
    
    /**
     * Convert ACF Repeater to MetaBox Group
     */
    private function convert_acf_repeater_to_metabox_group($acf_repeater) {
        $mb_group = array(
            'id' => $acf_repeater['name'],
            'name' => $acf_repeater['label'],
            'type' => 'group',
            'clone' => true,  // Makes it repeatable
            'sort_clone' => true,
            'fields' => array(),
        );
        
        // Convert sub-fields
        foreach ($acf_repeater['sub_fields'] as $sub_field) {
            $mb_subfield = $this->convert_acf_field_to_metabox($sub_field);
            if ($mb_subfield) {
                $mb_group['fields'][] = $mb_subfield;
            }
        }
        
        return $mb_group;
    }
    
    /**
     * Extract post types from ACF location rules
     */
    private function extract_post_types_from_location($location_rules) {
        $post_types = array();
        
        foreach ($location_rules as $rule_group) {
            foreach ($rule_group as $rule) {
                if ($rule['param'] === 'post_type') {
                    $post_types[] = $rule['value'];
                }
            }
        }
        
        return array_unique($post_types);
    }
    
    /**
     * Convert MetaBox Field Group to ACF format
     */
    public function metabox_field_group_to_acf($mb_config) {
        // Reverse conversion
        // Similar logic but opposite direction
    }
}
```

**Recommended Strategy (UPDATED):**

```
V1.0: Full Migration ✅
ACF Site → ACF Site ✅
- Field Groups automatisch migriert
- Field Values automatisch migriert
- CPTs automatisch registriert

MetaBox Site → MetaBox Site ✅  
- MB Builder Configs automatisch migriert
- Field Values automatisch migriert
- CPTs automatisch registriert

V1.1: Advanced Cross-Plugin 🔶
ACF Site → MetaBox Site 🔶
- Field Groups automatisch konvertiert
- Field Values automatisch konvertiert
- CPTs automatisch registriert
- ⚠️ Complex fields (Flexible Content) need review

MetaBox Site → ACF Site 🔶
- MB Configs automatisch konvertiert
- Field Values automatisch konvertiert
- CPTs automatisch registriert
```

### Phase 5: Content Migration Engine (With Dynamic Data)

#### 5.1 Content Parser (Enhanced)
```php
<?php
class B2E_Content_Parser {
    
    private $gutenberg_generator;
    private $dynamic_data_converter;
    private $error_handler;
    
    public function __construct() {
        $this->gutenberg_generator = new B2E_Gutenberg_Generator();
        $this->dynamic_data_converter = new B2E_Dynamic_Data_Converter();
        $this->error_handler = new B2E_Error_Handler();
    }
    
    /**
     * Parse Bricks content structure from post meta (ENHANCED!)
     * 
     * REAL DB STRUCTURE:
     * - post_content is EMPTY!
     * - All content stored in _bricks_page_content_2 (serialized array)
     * - Additional meta: _bricks_template_type, _bricks_editor_mode
     */
    public function parse_bricks_content($post_id) {
        // Check if this is actually a Bricks page
        $template_type = get_post_meta($post_id, '_bricks_template_type', true);
        $editor_mode = get_post_meta($post_id, '_bricks_editor_mode', true);
        
        if ($template_type !== 'content' || $editor_mode !== 'bricks') {
            // Not a Bricks page, skip
            $this->error_handler->log_warning('W001', array(
                'post_id' => $post_id,
                'template_type' => $template_type,
                'editor_mode' => $editor_mode,
                'action' => 'Skipping non-Bricks page'
            ));
            return false;
        }
        
        // Get the actual Bricks content (serialized array)
        $bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);
        
        if (empty($bricks_content)) {
            $this->error_handler->log_error('E003', array(
                'post_id' => $post_id,
                'message' => 'No Bricks content found for this post'
            ));
            return false;
        }
        
        // Handle both serialized string and array
        if (is_string($bricks_content)) {
            $bricks_content = maybe_unserialize($bricks_content);
        }
        
        // Validate it's an array
        if (!is_array($bricks_content)) {
            $this->error_handler->log_error('E101', array(
                'post_id' => $post_id,
                'content_type' => gettype($bricks_content),
                'action' => 'Expected array, got ' . gettype($bricks_content)
            ));
            return false;
        }
        
        return $this->process_bricks_elements($bricks_content, $post_id);
    }
    
    /**
     * Process Bricks elements recursively
     */
    private function process_bricks_elements($elements, $post_id) {
        $processed_elements = array();
        
        foreach ($elements as $element) {
            // Skip unsupported element types
            if ($this->is_unsupported_element($element['name'])) {
                $this->error_handler->log_error('E003', array(
                    'post_id' => $post_id,
                    'element_type' => $element['name'],
                    'element_id' => $element['id'],
                    'message' => 'Unsupported Bricks element - taking HTML/CSS only'
                ));
                
                // Still process as generic element (HTML/CSS only)
            }
            
            $processed_element = array(
                'id' => $element['id'],
                'name' => $element['name'],
                'parent' => isset($element['parent']) ? $element['parent'] : 0,
                'children' => isset($element['children']) ? $element['children'] : array(),
                'settings' => isset($element['settings']) ? $element['settings'] : array(),
                'label' => isset($element['label']) ? $element['label'] : ''
            );
            
            // Convert dynamic data in settings
            $processed_element['settings'] = $this->dynamic_data_converter->convert_element_settings($processed_element['settings']);
            
            // Process specific element types
            $processed_element = $this->process_element_by_type($processed_element);
            
            $processed_elements[] = $processed_element;
        }
        
        return $processed_elements;
    }
    
    /**
     * Check if element is unsupported (Bricks-specific functionality)
     */
    private function is_unsupported_element($element_name) {
        $unsupported = array(
            'slider',
            'slider-nested',
            'carousel',
            'accordion',
            'accordion-nested',
            'tabs',
            'tabs-nested',
            'offcanvas',
            'popup',
            'countdown',
            'pricing-table',
            'team-members',
            'testimonials',
            'progress-bar',
            'counter',
            'animated-text',
            'flip-box',
            'before-after',
            'search',
            'sidebar',
            'posts',
            'related-posts',
        );
        
        return in_array($element_name, $unsupported);
    }
    
    /**
     * Process element based on its type
     * 
     * Bricks → Etch Element Mapping:
     * - brxe-section → data-etch-element="section"
     * - brxe-container → data-etch-element="container"
     * - brxe-block → data-etch-element="flex-div"
     * - brxe-div → empty (or optional: flex-div)
     * - iframe → data-etch-element="iframe"
     */
    private function process_element_by_type($element) {
        switch ($element['name']) {
            case 'section':
                return $this->process_section_element($element);
            case 'container':
                return $this->process_container_element($element);
            case 'block':
                return $this->process_block_element($element);
            case 'div':
                return $this->process_div_element($element);
            case 'heading':
                return $this->process_heading_element($element);
            case 'text':
            case 'text-basic':
                return $this->process_text_element($element);
            case 'image':
                return $this->process_image_element($element);
            case 'video':
                return $this->process_video_element($element);
            case 'video-iframe':
            case 'iframe':
                return $this->process_iframe_element($element);
            case 'button':
                return $this->process_button_element($element);
            case 'icon':
                return $this->process_icon_element($element);
            case 'list':
                return $this->process_list_element($element);
            case 'code':
                return $this->process_code_element($element);
            case 'shortcode':
                return $this->process_shortcode_element($element);
            case 'html':
                return $this->process_html_element($element);
            default:
                return $this->process_generic_element($element);
        }
    }
    
    private function process_section_element($element) {
        $element['etch_type'] = 'group';  // wp:group!
        $element['etch_data'] = array(
            'origin' => 'etch',
            'name' => 'Section',
            'styles' => $this->extract_style_ids($element['settings']),
            'attributes' => array(
                'data-etch-element' => 'section',
                'class' => $this->extract_css_classes($element['settings'])
            ),
            'block' => array(
                'type' => 'html',
                'tag' => isset($element['settings']['tag']) ? $element['settings']['tag'] : 'section'
            )
        );
        return $element;
    }
    
    private function process_container_element($element) {
        $element['etch_type'] = 'group';  // wp:group!
        $element['etch_data'] = array(
            'origin' => 'etch',
            'name' => 'Container',
            'styles' => $this->extract_style_ids($element['settings']),
            'attributes' => array(
                'data-etch-element' => 'container',
                'class' => $this->extract_css_classes($element['settings'])
            ),
            'block' => array(
                'type' => 'html',
                'tag' => isset($element['settings']['tag']) ? $element['settings']['tag'] : 'div'
            )
        );
        return $element;
    }
    
    private function process_block_element($element) {
        // brxe-block → data-etch-element="flex-div"
        $element['etch_type'] = 'group';
        $element['etch_data'] = array(
            'origin' => 'etch',
            'name' => 'Flex Div',
            'styles' => $this->extract_style_ids($element['settings']),
            'attributes' => array(
                'data-etch-element' => 'flex-div',
                'class' => $this->extract_css_classes($element['settings'])
            ),
            'block' => array(
                'type' => 'html',
                'tag' => isset($element['settings']['tag']) ? $element['settings']['tag'] : 'div'
            )
        );
        return $element;
    }
    
    private function process_div_element($element) {
        // brxe-div → empty (no data-etch-element)
        // Or optional: convert to flex-div
        $settings = get_option('b2e_settings', array());
        $convert_div_to_flex = isset($settings['convert_div_to_flex']) ? $settings['convert_div_to_flex'] : false;
        
        if ($convert_div_to_flex) {
            // Convert to flex-div
            return $this->process_block_element($element);
        }
        
        // Keep as plain div (no etch-element)
        $element['etch_type'] = 'html';  // core/html for plain divs
        $element['etch_data'] = array(
            'content' => '<div class="' . $this->extract_css_classes($element['settings']) . '">{{CHILDREN}}</div>'
        );
        return $element;
    }
    
    private function process_heading_element($element) {
        $element['etch_type'] = 'core/heading';
        $element['etch_attributes'] = array(
            'level' => $this->extract_heading_level($element['settings']),
            'content' => $this->extract_text_content($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_text_element($element) {
        $element['etch_type'] = 'core/paragraph';
        $element['etch_attributes'] = array(
            'content' => $this->extract_text_content($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_image_element($element) {
        $image_data = $this->extract_image_data($element['settings']);
        
        // Check if image exists
        if (empty($image_data['url'])) {
            $this->error_handler->log_error('E001', array(
                'element_id' => $element['id'],
                'message' => 'Missing featured image'
            ));
        }
        
        $element['etch_type'] = 'core/image';
        $element['etch_attributes'] = array(
            'id' => $image_data['id'],
            'url' => $image_data['url'],
            'alt' => $image_data['alt'],
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_video_element($element) {
        $element['etch_type'] = 'core/video';
        $element['etch_attributes'] = array(
            'src' => $this->extract_video_url($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_iframe_element($element) {
        // iframe → data-etch-element="iframe"
        $element['etch_type'] = 'group';
        $element['etch_data'] = array(
            'origin' => 'etch',
            'name' => 'Iframe',
            'styles' => $this->extract_style_ids($element['settings']),
            'attributes' => array(
                'data-etch-element' => 'iframe',
                'class' => $this->extract_css_classes($element['settings']),
                'src' => $this->extract_video_url($element['settings']),
                'title' => isset($element['settings']['title']) ? $element['settings']['title'] : ''
            ),
            'block' => array(
                'type' => 'html',
                'tag' => 'iframe'
            )
        );
        return $element;
    }
    
    private function process_button_element($element) {
        $element['etch_type'] = 'core/button';
        $element['etch_attributes'] = array(
            'text' => $this->extract_text_content($element['settings']),
            'url' => $this->extract_link_url($element['settings']),
            'linkTarget' => $this->extract_link_target($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_icon_element($element) {
        $element['etch_type'] = 'core/html';
        $element['etch_attributes'] = array(
            'content' => $this->extract_icon_html($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_list_element($element) {
        $element['etch_type'] = 'core/list';
        $element['etch_attributes'] = array(
            'ordered' => $this->is_ordered_list($element['settings']),
            'values' => $this->extract_list_content($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_code_element($element) {
        $element['etch_type'] = 'core/code';
        $element['etch_attributes'] = array(
            'content' => $this->extract_code_content($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_shortcode_element($element) {
        $element['etch_type'] = 'core/shortcode';
        $element['etch_attributes'] = array(
            'text' => $this->extract_shortcode_content($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_html_element($element) {
        $element['etch_type'] = 'core/html';
        $element['etch_attributes'] = array(
            'content' => $this->extract_html_content($element['settings']),
            'className' => $this->extract_css_classes($element['settings'])
        );
        return $element;
    }
    
    private function process_generic_element($element) {
        $element['etch_type'] = 'core/html';
        $element['etch_attributes'] = array(
            'content' => $this->extract_generic_content($element),
            'className' => $this->extract_css_classes($element['settings']),
            'originalType' => $element['name']
        );
        return $element;
    }
    
    // Helper methods for extracting settings
    private function extract_css_classes($settings) {
        return isset($settings['_cssClasses']) ? $settings['_cssClasses'] : '';
    }
    
    private function extract_style_ids($settings) {
        // Extract Bricks global class IDs and convert to Etch hash IDs
        $styles = array();
        
        if (isset($settings['_cssGlobalClasses']) && is_array($settings['_cssGlobalClasses'])) {
            foreach ($settings['_cssGlobalClasses'] as $class_id) {
                // Convert Bricks class ID to Etch hash ID
                $style_id = $this->generate_style_hash($class_id);
                $styles[] = $style_id;
            }
        }
        
        // Also include the main CSS class if defined
        if (isset($settings['_cssClasses']) && !empty($settings['_cssClasses'])) {
            $class_names = explode(' ', $settings['_cssClasses']);
            foreach ($class_names as $class_name) {
                $style_id = $this->generate_style_hash($class_name);
                $styles[] = $style_id;
            }
        }
        
        return array_unique(array_filter($styles));
    }
    
    /**
     * Generate Etch-style hash ID (same as CSS Converter)
     */
    private function generate_style_hash($class_name) {
        // Generate a 7-character hash similar to Etch's style
        $hash = substr(md5($class_name . time()), 0, 7);
        return $hash;
    }
    
    private function extract_background_color($settings) {
        return isset($settings['background']['color']) ? $settings['background']['color'] : '';
    }
    
    private function extract_background_image($settings) {
        if (isset($settings['background']['image'])) {
            if (is_array($settings['background']['image'])) {
                return isset($settings['background']['image']['url']) ? $settings['background']['image']['url'] : '';
            }
            return $settings['background']['image'];
        }
        return '';
    }
    
    private function extract_max_width($settings) {
        return isset($settings['_width']) ? $settings['_width'] : '';
    }
    
    private function extract_flex_direction($settings) {
        return isset($settings['direction']) ? $settings['direction'] : 'column';
    }
    
    private function extract_justify_content($settings) {
        return isset($settings['justifyContent']) ? $settings['justifyContent'] : '';
    }
    
    private function extract_align_items($settings) {
        return isset($settings['alignItems']) ? $settings['alignItems'] : '';
    }
    
    private function extract_heading_level($settings) {
        if (isset($settings['tag'])) {
            $tag = $settings['tag'];
            return intval(str_replace('h', '', $tag));
        }
        return 2;
    }
    
    private function extract_text_content($settings) {
        if (isset($settings['text'])) {
            return $settings['text'];
        }
        if (isset($settings['content'])) {
            return $settings['content'];
        }
        return '';
    }
    
    private function extract_image_data($settings) {
        $data = array(
            'id' => 0,
            'url' => '',
            'alt' => ''
        );
        
        if (isset($settings['image'])) {
            if (is_array($settings['image'])) {
                $data['id'] = isset($settings['image']['id']) ? $settings['image']['id'] : 0;
                $data['url'] = isset($settings['image']['url']) ? $settings['image']['url'] : '';
                $data['alt'] = isset($settings['image']['alt']) ? $settings['image']['alt'] : '';
            } else {
                $data['url'] = $settings['image'];
            }
        }
        
        return $data;
    }
    
    private function extract_video_url($settings) {
        if (isset($settings['video'])) {
            if (is_array($settings['video'])) {
                return isset($settings['video']['url']) ? $settings['video']['url'] : '';
            }
            return $settings['video'];
        }
        return '';
    }
    
    private function extract_link_url($settings) {
        if (isset($settings['link'])) {
            if (is_array($settings['link'])) {
        return isset($settings['link']['url']) ? $settings['link']['url'] : '';
            }
            return $settings['link'];
        }
        return '';
    }
    
    private function extract_link_target($settings) {
        if (isset($settings['link']) && is_array($settings['link'])) {
            return isset($settings['link']['target']) ? $settings['link']['target'] : '_self';
        }
        return '_self';
    }
    
    private function extract_icon_html($settings) {
        if (isset($settings['icon'])) {
            if (is_array($settings['icon'])) {
                // Render icon based on library (Font Awesome, etc.)
                return '<i class="' . $settings['icon']['library'] . ' ' . $settings['icon']['icon'] . '"></i>';
            }
            return $settings['icon'];
        }
        return '';
    }
    
    private function is_ordered_list($settings) {
        return isset($settings['tag']) && $settings['tag'] === 'ol';
    }
    
    private function extract_list_content($settings) {
        if (isset($settings['items']) && is_array($settings['items'])) {
            return implode('</li><li>', $settings['items']);
        }
        return '';
    }
    
    private function extract_code_content($settings) {
        return isset($settings['code']) ? $settings['code'] : '';
    }
    
    private function extract_shortcode_content($settings) {
        return isset($settings['shortcode']) ? $settings['shortcode'] : '';
    }
    
    private function extract_html_content($settings) {
        return isset($settings['html']) ? $settings['html'] : '';
    }
    
    private function extract_generic_content($element) {
        // For unsupported elements, try to extract any text/HTML content
        if (isset($element['settings']['content'])) {
            return $element['settings']['content'];
        }
        if (isset($element['settings']['text'])) {
            return $element['settings']['text'];
        }
        if (isset($element['settings']['html'])) {
            return $element['settings']['html'];
        }
        return '<!-- Bricks ' . $element['name'] . ' element (requires manual recreation in Etch) -->';
    }
    
    /**
     * Convert processed elements to Etch content
     */
    public function convert_to_etch_content($processed_elements) {
        return $this->gutenberg_generator->generate_gutenberg_blocks($processed_elements);
    }
}
```

#### 4.2 Gutenberg Generator (COMPLETELY REWRITTEN!)
```php
<?php
/**
 * Generate Gutenberg blocks in CORRECT Etch format
 * 
 * Etch uses wp:group blocks with metadata.etchData, NOT wp:etch/section!
 * Frontend rendering uses data-etch-element attributes.
 */
class B2E_Gutenberg_Generator {
    
    /**
     * Generate Gutenberg blocks from processed Bricks elements
     */
    public function generate_gutenberg_blocks($elements) {
        $blocks_html = '';
        $root_elements = $this->build_element_tree($elements);
        
        foreach ($root_elements as $element) {
            $blocks_html .= $this->generate_block_html($element, $elements);
        }
        
        return $blocks_html;
    }
    
    /**
     * Build hierarchical tree from flat elements array
     */
    private function build_element_tree($elements) {
        $element_map = array();
        $root_elements = array();
        
        // Create element map
        foreach ($elements as $element) {
            $element_map[$element['id']] = $element;
        }
        
        // Find root elements (parent = 0)
        foreach ($elements as $element) {
            if ($element['parent'] == 0) {
                $root_elements[] = $element;
            }
        }
        
        return $root_elements;
    }
    
    /**
     * Generate HTML for a single block (CORRECTED FORMAT!)
     */
    private function generate_block_html($element, $all_elements) {
        $block_type = $element['etch_type'];
        $children = $this->get_child_elements($element['id'], $all_elements);
        
        // Etch elements (section, container, flex-div) use wp:group with metadata
        if ($block_type === 'group' && isset($element['etch_data'])) {
            return $this->generate_etch_group_block($element, $children, $all_elements);
        }
        
        // Standard Gutenberg blocks (heading, paragraph, image, etc.)
        return $this->generate_standard_block($element, $children, $all_elements);
    }
    
    /**
     * Generate Etch group block with metadata
     */
    private function generate_etch_group_block($element, $children, $all_elements) {
        $etch_data = $element['etch_data'];
        
        // Build metadata object
        $metadata = array(
            'name' => $etch_data['name'],
            'etchData' => $etch_data
        );
        
        // Build block attributes
        $block_attrs = array(
            'metadata' => $metadata
        );
        
        $attrs_json = json_encode($block_attrs, JSON_UNESCAPED_SLASHES);
        
        // Opening comment
        $html = "<!-- wp:group {$attrs_json} -->\n";
        $html .= '<div class="wp-block-group">';
        
        // Children
        foreach ($children as $child) {
            $html .= $this->generate_block_html($child, $all_elements);
        }
        
        $html .= "</div>\n";
        $html .= "<!-- /wp:group -->\n\n";
        
        return $html;
    }
    
    /**
     * Generate standard Gutenberg block
     */
    private function generate_standard_block($element, $children, $all_elements) {
        $block_type = $element['etch_type'];
        $attributes = isset($element['etch_attributes']) ? $element['etch_attributes'] : array();
        
        // Generate attributes JSON
        $attrs_json = !empty($attributes) ? json_encode($attributes, JSON_UNESCAPED_SLASHES) : '';
        
        // Opening comment
        $html = "<!-- wp:{$block_type}";
        if (!empty($attrs_json)) {
            $html .= " {$attrs_json}";
        }
        $html .= " -->\n";
        
        // Generate block content
        $html .= $this->generate_block_content($element, $children, $all_elements);
        
        // Closing comment
        $html .= "<!-- /wp:{$block_type} -->\n\n";
        
        return $html;
    }
    
    /**
     * Generate block content based on type
     * 
     * Note: Etch elements (section, container, flex-div) are handled
     * separately via generate_etch_group_block()
     */
    private function generate_block_content($element, $children, $all_elements) {
        switch ($element['etch_type']) {
            case 'core/heading':
                return $this->generate_heading_content($element);
            case 'core/paragraph':
                return $this->generate_paragraph_content($element);
            case 'core/image':
                return $this->generate_image_content($element);
            case 'core/video':
                return $this->generate_video_content($element);
            case 'core/button':
                return $this->generate_button_content($element);
            case 'core/list':
                return $this->generate_list_content($element);
            case 'core/code':
                return $this->generate_code_content($element);
            case 'core/shortcode':
                return $this->generate_shortcode_content($element);
            case 'core/html':
                return $this->generate_html_content($element, $children, $all_elements);
            default:
                return $this->generate_generic_content($element, $children, $all_elements);
        }
    }
    
    private function generate_heading_content($element) {
        $level = $element['etch_attributes']['level'];
        $content = $element['etch_attributes']['content'];
        $className = $element['etch_attributes']['className'];
        
        return "<h{$level} class=\"{$className}\">{$content}</h{$level}>\n";
    }
    
    private function generate_paragraph_content($element) {
        $content = $element['etch_attributes']['content'];
        $className = $element['etch_attributes']['className'];
        
        return "<p class=\"{$className}\">{$content}</p>\n";
    }
    
    private function generate_image_content($element) {
        $url = $element['etch_attributes']['url'];
        $alt = $element['etch_attributes']['alt'];
        $className = $element['etch_attributes']['className'];
        $id = $element['etch_attributes']['id'];
        
        return "<figure class=\"wp-block-image {$className}\"><img src=\"{$url}\" alt=\"{$alt}\" class=\"wp-image-{$id}\"/></figure>\n";
    }
    
    private function generate_video_content($element) {
        $src = $element['etch_attributes']['src'];
        $className = $element['etch_attributes']['className'];
        
        return "<figure class=\"wp-block-video {$className}\"><video controls src=\"{$src}\"></video></figure>\n";
    }
    
    private function generate_button_content($element) {
        $text = $element['etch_attributes']['text'];
        $url = $element['etch_attributes']['url'];
        $target = isset($element['etch_attributes']['linkTarget']) ? $element['etch_attributes']['linkTarget'] : '_self';
        $className = $element['etch_attributes']['className'];
        
        $target_attr = $target !== '_self' ? ' target="' . esc_attr($target) . '"' : '';
        
        return "<div class=\"wp-block-button {$className}\"><a class=\"wp-block-button__link\" href=\"{$url}\"{$target_attr}>{$text}</a></div>\n";
    }
    
    private function generate_list_content($element) {
        $ordered = $element['etch_attributes']['ordered'];
        $values = $element['etch_attributes']['values'];
        $className = $element['etch_attributes']['className'];
        
        $tag = $ordered ? 'ol' : 'ul';
        
        return "<{$tag} class=\"{$className}\"><li>{$values}</li></{$tag}>\n";
    }
    
    private function generate_code_content($element) {
        $content = $element['etch_attributes']['content'];
        $className = $element['etch_attributes']['className'];
        
        return "<pre class=\"wp-block-code {$className}\"><code>" . esc_html($content) . "</code></pre>\n";
    }
    
    private function generate_shortcode_content($element) {
        $text = $element['etch_attributes']['text'];
        $className = $element['etch_attributes']['className'];
        
        return "<div class=\"wp-block-shortcode {$className}\">{$text}</div>\n";
    }
    
    private function generate_html_content($element) {
        $content = $element['etch_attributes']['content'];
        $className = $element['etch_attributes']['className'];
        
        return "<div class=\"wp-block-html {$className}\">{$content}</div>\n";
    }
    
    private function generate_generic_content($element, $children, $all_elements) {
        $className = $element['etch_attributes']['className'];
        $originalType = isset($element['etch_attributes']['originalType']) ? $element['etch_attributes']['originalType'] : 'unknown';
        $content = isset($element['etch_attributes']['content']) ? $element['etch_attributes']['content'] : '';
        
        $html = "<!-- Converted from Bricks element: {$originalType} -->\n";
        $html .= "<div class=\"{$className}\">\n";
        
        if (!empty($content)) {
            $html .= $content . "\n";
        }
        
        foreach ($children as $child) {
            $html .= $this->generate_block_html($child, $all_elements);
        }
        
        $html .= "</div>\n";
        return $html;
    }
    
    /**
     * Get child elements for a given parent ID
     */
    private function get_child_elements($parent_id, $all_elements) {
        $children = array();
        
        foreach ($all_elements as $element) {
            if ($element['parent'] == $parent_id) {
                $children[] = $element;
            }
        }
        
        return $children;
    }
}
```

### Phase 5: REST API & Communication (Simplified)

#### 5.1 API Client
```php
<?php
class B2E_API_Client {
    
    private $target_url;
    private $api_key;
    private $timeout;
    
    public function __construct($target_url, $api_key) {
        $this->target_url = trailingslashit($target_url);
        $this->api_key = $api_key;
        
        $settings = get_option('b2e_settings', array());
        $this->timeout = isset($settings['timeout']) ? $settings['timeout'] : 60;
    }
    
    /**
     * Export CSS classes from source site
     */
    public function export_css_classes() {
        $bricks_classes = get_option('bricks_global_classes', array());
        
        if (is_string($bricks_classes)) {
            $bricks_classes = maybe_unserialize($bricks_classes);
        }
        
        return $bricks_classes;
    }
    
    /**
     * Export post content from source site
     */
    public function export_post_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found');
        }
        
        $content_parser = new B2E_Content_Parser();
        $bricks_content = $content_parser->parse_bricks_content($post_id);
        
        return array(
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
            'post_date' => $post->post_date,
            'post_excerpt' => $post->post_excerpt,
            'bricks_content' => $bricks_content
        );
    }
    
    /**
     * Get list of posts with Bricks content
     */
    public function get_posts_list() {
        global $wpdb;
        
        $posts = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_type, p.post_status, p.post_date
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key = '_bricks_page_content_2'
            AND p.post_status IN ('publish', 'draft', 'private')
            ORDER BY p.post_date DESC
        ");
        
        return $posts;
    }
    
    /**
     * Send CSS classes to target site
     */
    public function send_css_classes($css_classes) {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/import/classes';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('classes' => $css_classes))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Send post content to target site
     */
    public function send_post_content($post_data) {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/import/content';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($post_data)
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Verify connection to target site
     */
    public function verify_connection() {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/auth/verify';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key
            )
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Get plugin status from target site
     */
    public function get_target_plugins() {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/validate/plugins';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key
            )
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Send Custom Post Types to target site
     */
    public function send_custom_post_types($cpts) {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/import/cpts';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('cpts' => $cpts))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Send ACF Field Groups to target site
     */
    public function send_acf_field_groups($field_groups) {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/import/acf-field-groups';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('field_groups' => $field_groups))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Send MetaBox Configurations to target site
     */
    public function send_metabox_configs($configs) {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/import/metabox-configs';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array('configs' => $configs))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Send post meta to target site
     */
    public function send_post_meta($post_id, $meta_key, $meta_value) {
        $endpoint = $this->target_url . 'wp-json/bricks-etch/' . B2E_API_VERSION . '/import/post-meta';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'post_id' => $post_id,
                'meta_key' => $meta_key,
                'meta_value' => $meta_value
            ))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Handle API response
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code >= 200 && $status_code < 300) {
            return $data;
        } else {
            return new WP_Error(
                'api_error',
                isset($data['message']) ? $data['message'] : 'API request failed',
                array('status' => $status_code, 'data' => $data)
            );
        }
    }
}
```

#### 5.2 API Endpoints
```php
<?php
class B2E_API_Endpoints {
    
    private $css_converter;
    private $content_parser;
    
    public function __construct() {
        $this->css_converter = new B2E_CSS_Converter();
        $this->content_parser = new B2E_Content_Parser();
        
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }
    
    public function register_endpoints() {
        $auth_handler = new B2E_Auth_Handler();
        
        // Validation endpoint
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/validate/plugins', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_plugin_status'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
        
        // Import endpoints (target site)
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/import/cpts', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_custom_post_types'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
        
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/import/acf-field-groups', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_acf_field_groups'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
        
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/import/metabox-configs', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_metabox_configs'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
        
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/import/classes', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_css_classes'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
        
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/import/content', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_post_content'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
        
        register_rest_route('bricks-etch/' . B2E_API_VERSION, '/import/post-meta', array(
            'methods' => 'POST',
            'callback' => array($this, 'import_post_meta'),
            'permission_callback' => array($auth_handler, 'verify_api_key')
        ));
    }
    
    /**
     * Get plugin status on target site
     */
    public function get_plugin_status($request) {
        $plugin_detector = new B2E_Plugin_Detector();
        $plugins = $plugin_detector->get_installed_plugins();
        
        return new WP_REST_Response(array(
            'plugins' => $plugins,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'site_url' => home_url()
        ), 200);
    }
    
    /**
     * Import Custom Post Types
     */
    public function import_custom_post_types($request) {
        $cpts = $request->get_param('cpts');
        
        if (empty($cpts)) {
            return new WP_Error('no_cpts', 'No Custom Post Types provided', array('status' => 400));
        }
        
        $cpt_migrator = new B2E_CPT_Migrator();
        $result = $cpt_migrator->register_custom_post_types($cpts);
        
        return new WP_REST_Response(array(
            'status' => 'success',
            'registered_count' => $result
        ), 200);
    }
    
    /**
     * Import ACF Field Groups
     */
    public function import_acf_field_groups($request) {
        $field_groups = $request->get_param('field_groups');
        
        if (empty($field_groups)) {
            return new WP_Error('no_field_groups', 'No ACF Field Groups provided', array('status' => 400));
        }
        
        if (!function_exists('acf_import_field_group')) {
            return new WP_Error('acf_not_installed', 'ACF not installed on target site', array('status' => 400));
        }
        
        $acf_migrator = new B2E_ACF_Field_Groups_Migrator();
        $result = $acf_migrator->import_field_groups($field_groups);
        
        return new WP_REST_Response(array(
            'status' => 'success',
            'imported_count' => $result
        ), 200);
    }
    
    /**
     * Import MetaBox Configurations
     */
    public function import_metabox_configs($request) {
        $mb_configs = $request->get_param('configs');
        
        if (empty($mb_configs)) {
            return new WP_Error('no_configs', 'No MetaBox configurations provided', array('status' => 400));
        }
        
        $mb_migrator = new B2E_MetaBox_Migrator();
        $result = $mb_migrator->import_metabox_configs($mb_configs);
        
        return new WP_REST_Response(array(
            'status' => 'success',
            'imported_count' => $result
        ), 200);
    }
    
    /**
     * Import post meta (custom fields)
     */
    public function import_post_meta($request) {
        $post_id = $request->get_param('post_id');
        $meta_key = $request->get_param('meta_key');
        $meta_value = $request->get_param('meta_value');
        
        if (!$post_id || !$meta_key) {
            return new WP_Error('invalid_data', 'Post ID and meta key required', array('status' => 400));
        }
        
        $result = update_post_meta($post_id, $meta_key, $meta_value);
        
        return new WP_REST_Response(array(
            'status' => 'success',
            'updated' => $result
        ), 200);
    }
    
    /**
     * Import CSS classes to Etch format
     */
    public function import_css_classes($request) {
        $bricks_classes = $request->get_param('classes');
        
        if (empty($bricks_classes)) {
            return new WP_Error('no_classes', 'No classes provided', array('status' => 400));
        }
        
        // Convert Bricks classes to Etch format
        $etch_styles = $this->css_converter->convert_bricks_classes_to_etch($bricks_classes);
        
        // Get existing Etch styles
        $existing_styles = get_option('etch_styles', array());
        
        // Merge with existing styles
        $merged_styles = array_merge($existing_styles, $etch_styles);
        
        // Update option
        $success = update_option('etch_styles', $merged_styles);
        
        if ($success) {
            return new WP_REST_Response(array(
                'status' => 'success',
                'imported_count' => count($etch_styles),
                'total_count' => count($merged_styles)
            ), 200);
        } else {
            return new WP_Error('import_failed', 'Failed to import classes', array('status' => 500));
        }
    }
    
    /**
     * Import post content and convert to Etch format
     */
    public function import_post_content($request) {
        $post_data = $request->get_json_params();
        
        if (empty($post_data)) {
            return new WP_Error('no_data', 'No post data provided', array('status' => 400));
        }
        
        // Create or update post
        $post_args = array(
            'post_title' => $post_data['post_title'],
            'post_type' => $post_data['post_type'],
            'post_status' => 'draft', // Import as draft
            'post_excerpt' => isset($post_data['post_excerpt']) ? $post_data['post_excerpt'] : '',
            'post_content' => ''
        );
        
        // Convert Bricks content to Etch format
        if (!empty($post_data['bricks_content'])) {
            $etch_content = $this->content_parser->convert_to_etch_content($post_data['bricks_content']);
            $post_args['post_content'] = $etch_content;
        }
        
        $post_id = wp_insert_post($post_args);
        
        if (is_wp_error($post_id)) {
            return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'status' => 'success',
            'post_id' => $post_id,
            'original_id' => $post_data['post_id']
        ), 200);
    }
}
```

### Phase 6: Progress Tracking & Error Handling (Simplified)

#### 6.1 Logger (Transient-Based)
```php
<?php
class B2E_Logger {
    
    private $log_key = 'b2e_migration_log';
    private $max_entries = 500;
    
    /**
     * Log a message
     */
    public function log($level, $message, $context = array()) {
        $log_entry = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql'),
            'memory' => $this->format_bytes(memory_get_usage(true))
        );
        
        $current_log = get_transient($this->log_key);
        if (!$current_log || !is_array($current_log)) {
            $current_log = array();
        }
        
        $current_log[] = $log_entry;
        
        // Keep only last N entries
        if (count($current_log) > $this->max_entries) {
            $current_log = array_slice($current_log, -$this->max_entries);
        }
        
        set_transient($this->log_key, $current_log, 8 * HOUR_IN_SECONDS);
        
        // Also log to error_log for debugging
        if (get_option('b2e_settings')['debug_mode']) {
            error_log("B2E [{$level}]: {$message}");
        }
    }
    
    /**
     * Get all log entries
     */
    public function get_log() {
        $log = get_transient($this->log_key);
        return is_array($log) ? array_reverse($log) : array();
    }
    
    /**
     * Clear log
     */
    public function clear_log() {
        delete_transient($this->log_key);
    }
    
    /**
     * Format bytes to human-readable format
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

#### 6.2 Error Handler (With Error Codes)
```php
<?php
class B2E_Error_Handler {
    
    /**
     * Error codes with descriptions and solutions
     */
    const ERROR_CODES = array(
        // Content Errors (E0xx)
        'E001' => array(
            'title' => 'Missing Media File',
            'description' => 'Image or media file referenced in Bricks content not found',
            'solution' => 'Check if the media file exists in the source site media library'
        ),
        'E002' => array(
            'title' => 'Invalid CSS Syntax',
            'description' => 'CSS syntax error detected in Bricks global class',
            'solution' => 'Auto-fix attempted. Review the migrated CSS for accuracy'
        ),
        'E003' => array(
            'title' => 'Unsupported Bricks Element',
            'description' => 'Bricks-specific element cannot be automatically migrated',
            'solution' => 'Recreate this element manually in Etch (slider, accordion, etc.)'
        ),
        'E004' => array(
            'title' => 'Dynamic Data Tag Not Mappable',
            'description' => 'Bricks dynamic data tag has no Etch equivalent',
            'solution' => 'Manually update the dynamic data reference in Etch'
        ),
        'E005' => array(
            'title' => 'Custom Field Not Found',
            'description' => 'ACF or custom field referenced but not found',
            'solution' => 'Ensure custom fields are migrated and field names match'
        ),
        
        // API Errors (E1xx)
        'E101' => array(
            'title' => 'Invalid Bricks Content Structure',
            'description' => 'Bricks page content is not in expected array format',
            'solution' => 'Check if _bricks_page_content_2 contains valid serialized array'
        ),
        'E102' => array(
            'title' => 'Bricks Page Validation Failed',
            'description' => 'Page does not have required Bricks meta keys',
            'solution' => 'Verify _bricks_template_type and _bricks_editor_mode are set'
        ),
        'E103' => array(
            'title' => 'API Connection Failed',
            'description' => 'Unable to connect to target site API',
            'solution' => 'Check API URL, verify plugin is installed on target site'
        ),
        'E104' => array(
            'title' => 'API Key Expired',
            'description' => 'API key has exceeded 8-hour validity period',
            'solution' => 'Generate a new API key and retry the migration'
        ),
        'E105' => array(
            'title' => 'API Request Timeout',
            'description' => 'API request exceeded timeout limit',
            'solution' => 'Increase timeout setting or check server resources'
        ),
        
        // Migration Process Errors (E2xx)
        'E201' => array(
            'title' => 'Post Creation Failed',
            'description' => 'Failed to create post on target site',
            'solution' => 'Check post type exists on target site and user has permissions'
        ),
        'E202' => array(
            'title' => 'Style Import Failed',
            'description' => 'Failed to import CSS styles to target site',
            'solution' => 'Check etch_styles option in target site database'
        ),
        'E203' => array(
            'title' => 'Checkpoint Save Failed',
            'description' => 'Unable to save migration checkpoint for resume',
            'solution' => 'Check WordPress transient/option permissions'
        ),
        
        // Custom Post Types & Fields Errors (E3xx)
        'E301' => array(
            'title' => 'Custom Post Type Not Found',
            'description' => 'Custom Post Type from source site does not exist on target site',
            'solution' => 'Register the Custom Post Type on target site before migration'
        ),
        'E302' => array(
            'title' => 'Custom Field Meta Migration Failed',
            'description' => 'Failed to migrate post_meta values',
            'solution' => 'Check database permissions and field compatibility'
        ),
    );
    
    /**
     * Warning codes (W0xx)
     */
    const WARNING_CODES = array(
        'W001' => array(
            'title' => 'ACF Plugin Not Installed on Target',
            'description' => 'Source site uses ACF but target site does not have it installed',
            'suggestion' => 'Install ACF on target site or field values will be stored as generic post_meta'
        ),
        'W002' => array(
            'title' => 'MetaBox Plugin Not Installed on Target',
            'description' => 'Source site uses MetaBox but target site does not have it installed',
            'suggestion' => 'Install MetaBox on target site'
        ),
        'W003' => array(
            'title' => 'JetEngine Plugin Not Installed on Target',
            'description' => 'Source site uses JetEngine but target site does not have it installed',
            'suggestion' => 'Install JetEngine on target site'
        ),
    );
    
    private $logger;
    private $error_log_key = 'b2e_error_log';
    
    public function __construct() {
        $this->logger = new B2E_Logger();
    }
    
    /**
     * Log an error with error code
     */
    public function log_error($code, $context = array()) {
        if (!isset(self::ERROR_CODES[$code])) {
            $code = 'E999';
            $context['original_code'] = $code;
        }
        
        $error_info = self::ERROR_CODES[$code];
        
        $error = array(
            'code' => $code,
            'title' => $error_info['title'],
            'description' => $error_info['description'],
            'solution' => $error_info['solution'],
            'context' => $context,
            'timestamp' => current_time('mysql'),
            'doc_link' => 'https://docs.example.com/errors/' . $code
        );
        
        // Add to error log
        $error_log = get_transient($this->error_log_key);
        if (!$error_log || !is_array($error_log)) {
            $error_log = array();
        }
        
        $error_log[] = $error;
        
        // Keep only last 100 errors
        if (count($error_log) > 100) {
            $error_log = array_slice($error_log, -100);
        }
        
        set_transient($this->error_log_key, $error_log, 8 * HOUR_IN_SECONDS);
        
        // Also log to main logger
        $this->logger->log('error', "{$code}: {$error_info['title']}", $context);
    }
    
    /**
     * Get error log
     */
    public function get_error_log() {
        $log = get_transient($this->error_log_key);
        return is_array($log) ? array_reverse($log) : array();
    }
    
    /**
     * Get error info by code
     */
    public function get_error_info($code) {
        return isset(self::ERROR_CODES[$code]) ? self::ERROR_CODES[$code] : null;
    }
    
    /**
     * Clear error log
     */
    public function clear_error_log() {
        delete_transient($this->error_log_key);
    }
}
```

#### 6.3 Migration Manager with Resume Capability
```php
<?php
class B2E_Migration_Manager {
    
    private $checkpoint_key = 'b2e_checkpoint';
    private $progress_key = 'b2e_progress';
    private $logger;
    private $error_handler;
    
    public function __construct() {
        $this->logger = new B2E_Logger();
        $this->error_handler = new B2E_Error_Handler();
        
        // Initialize admin interface
        if (is_admin()) {
            new B2E_Admin_Interface();
        }
        
        // Initialize API endpoints
        new B2E_API_Endpoints();
    }
    
    /**
     * Start migration process (ENHANCED with Field Groups & CPTs)
     */
    public function start_migration($source_url, $target_url, $api_key, $dry_run = false) {
        $this->logger->log('info', 'Starting migration', array(
            'source' => $source_url,
            'target' => $target_url,
            'dry_run' => $dry_run
        ));
        
        // Check if we can resume
        $checkpoint = get_transient($this->checkpoint_key);
        if ($checkpoint && !$dry_run) {
            $this->logger->log('info', 'Resuming from checkpoint', $checkpoint);
            return $this->resume_migration($checkpoint, $target_url, $api_key);
        }
        
        // Initialize progress
        $this->init_progress();
        
        // Step 0: Pre-Migration Validation
        $this->update_progress('validation', 'in_progress');
        $validation_result = $this->validate_migration_requirements($target_url, $api_key);
        
        if (is_wp_error($validation_result)) {
            $this->handle_migration_error('validation', $validation_result);
            return $validation_result;
        }
        
        $this->update_progress('validation', 'completed', $validation_result);
        
        // Step 1: Migrate Custom Post Types
        $this->update_progress('cpts', 'in_progress');
        $cpts_result = $this->migrate_custom_post_types($target_url, $api_key, $dry_run);
        
        if (is_wp_error($cpts_result)) {
            $this->handle_migration_error('cpts', $cpts_result);
            return $cpts_result;
        }
        
        $this->update_progress('cpts', 'completed', array('count' => $cpts_result));
        
        // Step 2: Migrate ACF Field Groups
        $this->update_progress('acf_field_groups', 'in_progress');
        $acf_result = $this->migrate_acf_field_groups($target_url, $api_key, $dry_run);
        
        if (is_wp_error($acf_result)) {
            $this->handle_migration_error('acf_field_groups', $acf_result);
            return $acf_result;
        }
        
        $this->update_progress('acf_field_groups', 'completed', array('count' => $acf_result));
        
        // Step 3: Migrate MetaBox Configurations
        $this->update_progress('metabox_configs', 'in_progress');
        $mb_result = $this->migrate_metabox_configs($target_url, $api_key, $dry_run);
        
        if (is_wp_error($mb_result)) {
            $this->handle_migration_error('metabox_configs', $mb_result);
            return $mb_result;
        }
        
        $this->update_progress('metabox_configs', 'completed', array('count' => $mb_result));
        
        // Step 4: Migrate CSS Classes
        $this->update_progress('css_classes', 'in_progress');
        $css_result = $this->migrate_css_classes($target_url, $api_key, $dry_run);
        
        if (is_wp_error($css_result)) {
            $this->handle_migration_error('css_classes', $css_result);
            return $css_result;
        }
        
        $this->update_progress('css_classes', 'completed', array('count' => $css_result));
        $this->save_checkpoint('posts', array());
        
        // Step 5: Migrate Posts (with Custom Fields)
        $this->update_progress('posts', 'in_progress');
        $posts_result = $this->migrate_posts($target_url, $api_key, $dry_run);
        
        if (is_wp_error($posts_result)) {
            $this->handle_migration_error('posts', $posts_result);
            return $posts_result;
        }
        
        $this->update_progress('posts', 'completed', array('count' => $posts_result));
        
        // Step 6: Finalization
        $this->update_progress('complete', 'completed');
        delete_transient($this->checkpoint_key);
        
        $this->logger->log('info', 'Migration completed successfully');
        
        return array(
            'status' => 'success',
            'validation' => $validation_result,
            'cpts' => $cpts_result,
            'acf_field_groups' => $acf_result,
            'metabox_configs' => $mb_result,
            'css_classes' => $css_result,
            'posts' => $posts_result
        );
    }
    
    /**
     * Validate migration requirements
     */
    private function validate_migration_requirements($target_url, $api_key) {
        $api_client = new B2E_API_Client($target_url, $api_key);
        $plugin_detector = new B2E_Plugin_Detector();
        
        // Get plugin status on both sites
        $source_plugins = $plugin_detector->get_installed_plugins();
        $target_plugins = $api_client->get_target_plugins();
        
        if (is_wp_error($target_plugins)) {
            return $target_plugins;
        }
        
        // Validate requirements
        $validation = $plugin_detector->validate_migration_requirements($source_plugins, $target_plugins);
        
        // Log warnings
        if (!empty($validation['warnings'])) {
            foreach ($validation['warnings'] as $warning) {
                $this->logger->log('warning', $warning['message'], $warning);
            }
        }
        
        return $validation;
    }
    
    /**
     * Migrate Custom Post Types
     */
    private function migrate_custom_post_types($target_url, $api_key, $dry_run) {
        $cpt_migrator = new B2E_CPT_Migrator();
        $api_client = new B2E_API_Client($target_url, $api_key);
        
        // Export CPTs
        $cpts = $cpt_migrator->export_custom_post_types();
        
        if (empty($cpts)) {
            $this->logger->log('info', 'No Custom Post Types found');
            return 0;
        }
        
        $this->logger->log('info', 'Found ' . count($cpts) . ' Custom Post Types');
        
        if ($dry_run) {
            return count($cpts);
        }
        
        // Send to target site
        $result = $api_client->send_custom_post_types($cpts);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['registered_count'];
    }
    
    /**
     * Migrate ACF Field Groups
     */
    private function migrate_acf_field_groups($target_url, $api_key, $dry_run) {
        $acf_migrator = new B2E_ACF_Field_Groups_Migrator();
        $api_client = new B2E_API_Client($target_url, $api_key);
        
        // Export field groups
        $field_groups = $acf_migrator->export_field_groups();
        
        if (empty($field_groups)) {
            $this->logger->log('info', 'No ACF Field Groups found');
            return 0;
        }
        
        $this->logger->log('info', 'Found ' . count($field_groups) . ' ACF Field Groups');
        
        if ($dry_run) {
            return count($field_groups);
        }
        
        // Send to target site
        $result = $api_client->send_acf_field_groups($field_groups);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['imported_count'];
    }
    
    /**
     * Migrate MetaBox Configurations
     */
    private function migrate_metabox_configs($target_url, $api_key, $dry_run) {
        $mb_migrator = new B2E_MetaBox_Migrator();
        $api_client = new B2E_API_Client($target_url, $api_key);
        
        // Export MetaBox configs
        $mb_configs = $mb_migrator->export_metabox_configs();
        
        if (empty($mb_configs)) {
            $this->logger->log('info', 'No MetaBox configurations found (MB Builder not used)');
            return 0;
        }
        
        $this->logger->log('info', 'Found ' . count($mb_configs) . ' MetaBox configurations');
        
        if ($dry_run) {
            return count($mb_configs);
        }
        
        // Send to target site
        $result = $api_client->send_metabox_configs($mb_configs);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['imported_count'];
    }
    
    /**
     * Resume migration from checkpoint
     */
    private function resume_migration($checkpoint, $target_url, $api_key) {
        $this->logger->log('info', 'Resuming from step: ' . $checkpoint['step']);
        
        $step = $checkpoint['step'];
        $processed_posts = $checkpoint['processed_posts'];
        
        if ($step === 'posts') {
            $this->update_progress('posts', 'in_progress');
            $posts_result = $this->migrate_posts($target_url, $api_key, false, $processed_posts);
            
            if (is_wp_error($posts_result)) {
                return $posts_result;
            }
            
            $this->update_progress('posts', 'completed', array('count' => $posts_result));
            $this->update_progress('complete', 'completed');
            delete_transient($this->checkpoint_key);
        }
        
        return array('status' => 'success', 'resumed' => true);
    }
    
    /**
     * Migrate CSS classes
     */
    private function migrate_css_classes($target_url, $api_key, $dry_run) {
        $api_client = new B2E_API_Client($target_url, $api_key);
        $css_classes = $api_client->export_css_classes();
        
        if (empty($css_classes)) {
            $this->logger->log('warning', 'No CSS classes found to migrate');
            return 0;
        }
        
        $this->logger->log('info', 'Found ' . count($css_classes) . ' CSS classes');
        
        if ($dry_run) {
            return count($css_classes);
        }
        
        $result = $api_client->send_css_classes($css_classes);
        
        if (is_wp_error($result)) {
            $this->error_handler->log_error('E202', array(
                'error' => $result->get_error_message()
            ));
            return $result;
        }
        
        return $result['imported_count'];
    }
    
    /**
     * Migrate posts
     */
    private function migrate_posts($target_url, $api_key, $dry_run, $skip_posts = array()) {
        $api_client = new B2E_API_Client($target_url, $api_key);
        $posts = $api_client->get_posts_list();
        
        if (empty($posts)) {
            $this->logger->log('warning', 'No posts found to migrate');
            return 0;
        }
        
        $total_posts = count($posts);
        $migrated = 0;
        $skipped = count($skip_posts);
        
        foreach ($posts as $index => $post) {
            // Skip already processed posts
            if (in_array($post->ID, $skip_posts)) {
                continue;
            }
            
            $this->logger->log('info', "Migrating post {$post->ID}: {$post->post_title}");
            
            // Update progress percentage
            $percentage = round((($index + 1) / $total_posts) * 100);
            $this->update_progress('posts', 'in_progress', array(
                'current' => $index + 1,
                'total' => $total_posts,
                'percentage' => $percentage
            ));
            
            if ($dry_run) {
                $migrated++;
                continue;
            }
            
            // Export post content
            $post_data = $api_client->export_post_content($post->ID);
            
            if (is_wp_error($post_data)) {
                $this->error_handler->log_error('E003', array(
                    'post_id' => $post->ID,
                    'error' => $post_data->get_error_message()
                ));
                continue;
            }
            
            // Send to target site
            $result = $api_client->send_post_content($post_data);
            
            if (is_wp_error($result)) {
                $this->error_handler->log_error('E201', array(
                    'post_id' => $post->ID,
                    'error' => $result->get_error_message()
                ));
                continue;
            }
            
            $target_post_id = $result['post_id'];
            
            // Migrate post meta / custom fields
            $cf_migrator = new B2E_Custom_Fields_Migrator();
            $meta_count = $cf_migrator->migrate_post_meta($post->ID, $target_post_id, $api_client);
            
            $this->logger->log('info', "Migrated {$meta_count} custom fields for post {$post->ID}");
            
            $migrated++;
            $skip_posts[] = $post->ID;
            
            // Save checkpoint every 10 posts
            if ($migrated % 10 === 0) {
                $this->save_checkpoint('posts', $skip_posts);
            }
        }
        
        return $migrated;
    }
    
    /**
     * Save checkpoint for resume
     */
    private function save_checkpoint($step, $processed_posts) {
        $checkpoint = array(
            'step' => $step,
            'processed_posts' => $processed_posts,
            'timestamp' => time()
        );
        
        set_transient($this->checkpoint_key, $checkpoint, 8 * HOUR_IN_SECONDS);
        $this->logger->log('info', 'Checkpoint saved', $checkpoint);
    }
    
    /**
     * Initialize progress tracking (ENHANCED with new steps)
     */
    private function init_progress() {
        $progress = array(
            'started_at' => current_time('mysql'),
            'steps' => array(
                'validation' => array('status' => 'pending'),
                'cpts' => array('status' => 'pending', 'count' => 0),
                'acf_field_groups' => array('status' => 'pending', 'count' => 0),
                'metabox_configs' => array('status' => 'pending', 'count' => 0),
                'css_classes' => array('status' => 'pending', 'count' => 0),
                'posts' => array('status' => 'pending', 'count' => 0, 'current' => 0, 'total' => 0),
                'complete' => array('status' => 'pending')
            )
        );
        
        set_transient($this->progress_key, $progress, 8 * HOUR_IN_SECONDS);
    }
    
    /**
     * Update progress
     */
    private function update_progress($step, $status, $data = array()) {
        $progress = get_transient($this->progress_key);
        
        if (!$progress) {
            $this->init_progress();
            $progress = get_transient($this->progress_key);
        }
        
        $progress['steps'][$step]['status'] = $status;
        $progress['steps'][$step] = array_merge($progress['steps'][$step], $data);
        $progress['updated_at'] = current_time('mysql');
        
        set_transient($this->progress_key, $progress, 8 * HOUR_IN_SECONDS);
    }
    
    /**
     * Get current progress
     */
    public function get_progress() {
        return get_transient($this->progress_key);
    }
    
    /**
     * Handle migration error
     */
    private function handle_migration_error($step, $error) {
        $this->update_progress($step, 'failed', array(
            'error' => $error->get_error_message()
        ));
        
        $this->logger->log('error', "Migration failed at step: {$step}", array(
            'error' => $error->get_error_message()
        ));
    }
}
```

### Phase 7: Admin Interface

#### 7.1 Admin Interface Class
```php
<?php
class B2E_Admin_Interface {
    
    private $migration_manager;
    private $logger;
    private $error_handler;
    
    public function __construct() {
        $this->migration_manager = new B2E_Migration_Manager();
        $this->logger = new B2E_Logger();
        $this->error_handler = new B2E_Error_Handler();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_b2e_start_migration', array($this, 'ajax_start_migration'));
        add_action('wp_ajax_b2e_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_b2e_generate_api_key', array($this, 'ajax_generate_api_key'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Bricks to Etch Migration',
            'Bricks→Etch',
            'manage_options',
            'bricks-etch-migration',
            array($this, 'render_dashboard'),
            'dashicons-update',
            65
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_bricks-etch-migration') {
            return;
        }
        
        wp_enqueue_script(
            'b2e-admin',
            B2E_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery'),
            B2E_VERSION,
            true
        );
        
        wp_enqueue_style(
            'b2e-admin',
            B2E_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            B2E_VERSION
        );
        
        wp_localize_script('b2e-admin', 'b2eData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2e_nonce'),
            'siteUrl' => home_url(),
            'i18n' => array(
                'startMigration' => __('Start Migration', 'bricks-etch-migration'),
                'confirmStart' => __('Are you sure you want to start the migration?', 'bricks-etch-migration'),
                'migrating' => __('Migrating...', 'bricks-etch-migration'),
                'completed' => __('Migration Completed!', 'bricks-etch-migration'),
                'failed' => __('Migration Failed', 'bricks-etch-migration'),
            )
        ));
    }
    
    public function render_dashboard() {
        $progress = $this->migration_manager->get_progress();
        $error_log = $this->error_handler->get_error_log();
        $api_key = get_option('b2e_api_key');
        $key_expires = get_option('b2e_key_expires');
        
        include B2E_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * AJAX: Start migration
     */
    public function ajax_start_migration() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $target_url = sanitize_text_field($_POST['target_url']);
        $api_key = sanitize_text_field($_POST['api_key']);
        $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';
        
        $result = $this->migration_manager->start_migration(
            home_url(),
            $target_url,
            $api_key,
            $dry_run
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get migration progress
     */
    public function ajax_get_progress() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        $progress = $this->migration_manager->get_progress();
        wp_send_json_success($progress);
    }
    
    /**
     * AJAX: Generate API key
     */
    public function ajax_generate_api_key() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $api_key = wp_generate_password(32, false);
        update_option('b2e_api_key', $api_key);
        update_option('b2e_key_expires', time() + (8 * HOUR_IN_SECONDS));
        
        wp_send_json_success(array(
            'api_key' => $api_key,
            'expires_at' => date('Y-m-d H:i:s', time() + (8 * HOUR_IN_SECONDS))
        ));
    }
}
```

#### 7.2 Dashboard View
```php
<?php
// admin/views/dashboard.php
defined('ABSPATH') || exit;

$is_bricks_site = defined('BRICKS_VERSION');
$is_etch_site = defined('ETCH_VERSION');
$is_migrating = $progress && isset($progress['steps']['posts']['status']) && $progress['steps']['posts']['status'] === 'in_progress';
?>

<div class="wrap b2e-dashboard">
    <h1><?php _e('Bricks to Etch Migration', 'bricks-etch-migration'); ?></h1>
    
    <?php if ($is_bricks_site && $is_etch_site): ?>
        <div class="notice notice-error">
            <p><?php _e('Both Bricks and Etch are active! This plugin should be used on either source (Bricks only) or target (Etch only) site.', 'bricks-etch-migration'); ?></p>
                        </div>
                    <?php endif; ?>
    
    <?php if ($is_bricks_site): ?>
        <!-- SOURCE SITE (BRICKS) -->
        <div class="b2e-card b2e-source-site">
            <h2><?php _e('Source Site Configuration', 'bricks-etch-migration'); ?></h2>
            <p><?php _e('This site has Bricks Builder active. Generate an API key and provide it to the target site.', 'bricks-etch-migration'); ?></p>
            
            <div class="b2e-api-key-section">
                <h3><?php _e('API Key', 'bricks-etch-migration'); ?></h3>
                <?php if ($api_key && $key_expires && time() < $key_expires): ?>
                    <div class="b2e-api-key-display">
                        <code id="b2e-api-key"><?php echo esc_html($api_key); ?></code>
                        <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($api_key); ?>')">
                            <?php _e('Copy', 'bricks-etch-migration'); ?>
                        </button>
                    </div>
                    <p class="description">
                        <?php printf(
                            __('Key expires: %s (in %d minutes)', 'bricks-etch-migration'),
                            date('Y-m-d H:i:s', $key_expires),
                            ceil(($key_expires - time()) / 60)
                        ); ?>
                    </p>
                <?php else: ?>
                    <p><?php _e('No active API key', 'bricks-etch-migration'); ?></p>
                <?php endif; ?>
                
                <button type="button" id="b2e-generate-key" class="button button-primary">
                    <?php _e('Generate New API Key', 'bricks-etch-migration'); ?>
                </button>
                </div>
                
            <div class="b2e-stats">
                    <h3><?php _e('Migration Statistics', 'bricks-etch-migration'); ?></h3>
                    <?php
                    global $wpdb;
                $posts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_bricks_page_content_2'");
                $classes = get_option('bricks_global_classes', array());
                $classes_count = is_array($classes) ? count($classes) : 0;
                ?>
                <ul>
                    <li><strong><?php echo $posts_count; ?></strong> <?php _e('posts with Bricks content', 'bricks-etch-migration'); ?></li>
                    <li><strong><?php echo $classes_count; ?></strong> <?php _e('global CSS classes', 'bricks-etch-migration'); ?></li>
                </ul>
                </div>
            </div>
        
    <?php elseif ($is_etch_site): ?>
        <!-- TARGET SITE (ETCH) -->
        <div class="b2e-card b2e-target-site">
            <h2><?php _e('Target Site Configuration', 'bricks-etch-migration'); ?></h2>
            <p><?php _e('This site has Etch active. Enter the source site URL and API key to start migration.', 'bricks-etch-migration'); ?></p>
            
            <form id="b2e-migration-form" class="b2e-migration-form">
            <table class="form-table">
                <tr>
                        <th scope="row">
                            <label for="b2e-source-url"><?php _e('Source Site URL', 'bricks-etch-migration'); ?></label>
                        </th>
                    <td>
                            <input type="url" id="b2e-source-url" name="source_url" class="regular-text" placeholder="https://source-site.com" required>
                            <p class="description"><?php _e('The URL of your Bricks site', 'bricks-etch-migration'); ?></p>
                    </td>
                </tr>
                <tr>
                        <th scope="row">
                            <label for="b2e-api-key"><?php _e('API Key', 'bricks-etch-migration'); ?></label>
                        </th>
                    <td>
                            <input type="text" id="b2e-api-key-input" name="api_key" class="regular-text" required>
                            <p class="description"><?php _e('The API key generated on the source site', 'bricks-etch-migration'); ?></p>
                    </td>
                </tr>
                <tr>
                        <th scope="row">
                            <label for="b2e-dry-run"><?php _e('Dry Run', 'bricks-etch-migration'); ?></label>
                        </th>
                    <td>
                        <label>
                                <input type="checkbox" id="b2e-dry-run" name="dry_run" value="1">
                                <?php _e('Test migration without making changes', 'bricks-etch-migration'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
                <p class="submit">
                    <button type="submit" id="b2e-start-btn" class="button button-primary button-hero">
                        <?php _e('Start Migration', 'bricks-etch-migration'); ?>
                    </button>
                </p>
        </form>
            
            <?php if ($progress): ?>
                <div class="b2e-progress-section">
                    <h3><?php _e('Migration Progress', 'bricks-etch-migration'); ?></h3>
                    
                    <div class="b2e-progress-bar">
                        <div class="b2e-progress-fill" style="width: 0%"></div>
                    </div>
                    
                    <div class="b2e-progress-steps">
                        <?php foreach ($progress['steps'] as $step_name => $step_data): ?>
                            <div class="b2e-step b2e-step-<?php echo esc_attr($step_data['status']); ?>">
                                <span class="b2e-step-name"><?php echo esc_html(ucwords(str_replace('_', ' ', $step_name))); ?></span>
                                <span class="b2e-step-status"><?php echo esc_html($step_data['status']); ?></span>
                                <?php if (isset($step_data['count'])): ?>
                                    <span class="b2e-step-count"><?php echo esc_html($step_data['count']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="notice notice-warning">
            <p><?php _e('Neither Bricks Builder nor Etch is active on this site. Please install and activate the appropriate page builder.', 'bricks-etch-migration'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_log)): ?>
        <div class="b2e-card b2e-error-log">
            <h2><?php _e('Error Log', 'bricks-etch-migration'); ?></h2>
            <div class="b2e-errors">
                <?php foreach (array_slice($error_log, 0, 10) as $error): ?>
                    <div class="b2e-error">
                        <strong><?php echo esc_html($error['code']); ?>:</strong>
                        <?php echo esc_html($error['title']); ?>
                        <p class="description"><?php echo esc_html($error['description']); ?></p>
                        <?php if (!empty($error['context'])): ?>
                            <details>
                                <summary><?php _e('Details', 'bricks-etch-migration'); ?></summary>
                                <pre><?php echo esc_html(print_r($error['context'], true)); ?></pre>
                            </details>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($error['doc_link']); ?>" target="_blank">
                            <?php _e('View Documentation', 'bricks-etch-migration'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
```

### Phase 8: Admin JavaScript
```javascript
// admin/js/admin-scripts.js
(function($) {
    'use strict';
    
    const B2E = {
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            $('#b2e-generate-key').on('click', this.generateApiKey);
            $('#b2e-migration-form').on('submit', this.startMigration);
        },
        
        generateApiKey(e) {
            e.preventDefault();
            
            $.ajax({
                url: b2eData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'b2e_generate_api_key',
                    nonce: b2eData.nonce
                },
                success(response) {
                    if (response.success) {
                        alert('API Key generated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        },
        
        startMigration(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const sourceUrl = form.find('#b2e-source-url').val();
            const apiKey = form.find('#b2e-api-key-input').val();
            const dryRun = form.find('#b2e-dry-run').is(':checked');
            
            if (!confirm(b2eData.i18n.confirmStart)) {
                return;
            }
            
            const btn = $('#b2e-start-btn');
            btn.prop('disabled', true).text(b2eData.i18n.migrating);
            
            $.ajax({
                url: b2eData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'b2e_start_migration',
                    nonce: b2eData.nonce,
                    target_url: b2eData.siteUrl,
                    source_url: sourceUrl,
                    api_key: apiKey,
                    dry_run: dryRun ? 'true' : 'false'
                },
                success(response) {
                    if (response.success) {
                        alert(b2eData.i18n.completed);
                        B2E.startProgressPolling();
                    } else {
                        alert(b2eData.i18n.failed + ': ' + response.data);
                        btn.prop('disabled', false).text(b2eData.i18n.startMigration);
                    }
                },
                error() {
                    alert(b2eData.i18n.failed);
                    btn.prop('disabled', false).text(b2eData.i18n.startMigration);
                }
            });
        },
        
        startProgressPolling() {
            const interval = setInterval(() => {
                $.ajax({
                    url: b2eData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'b2e_get_progress',
                        nonce: b2eData.nonce
                    },
                    success(response) {
                        if (response.success && response.data) {
                            B2E.updateProgress(response.data);
                            
                            // Check if completed
                            if (response.data.steps.complete.status === 'completed') {
                                clearInterval(interval);
                                location.reload();
                            }
                        }
                    }
                });
            }, 2000);
        },
        
        updateProgress(progress) {
            // Update progress bar
            let totalSteps = Object.keys(progress.steps).length;
            let completedSteps = 0;
            
            Object.values(progress.steps).forEach(step => {
                if (step.status === 'completed') {
                    completedSteps++;
                }
            });
            
            const percentage = (completedSteps / totalSteps) * 100;
            $('.b2e-progress-fill').css('width', percentage + '%');
            
            // Update step statuses
            Object.keys(progress.steps).forEach(stepName => {
                const step = progress.steps[stepName];
                const $step = $(`.b2e-step[data-step="${stepName}"]`);
                
                $step.removeClass('b2e-step-pending b2e-step-in_progress b2e-step-completed b2e-step-failed');
                $step.addClass(`b2e-step-${step.status}`);
                $step.find('.b2e-step-status').text(step.status);
                
                if (step.count !== undefined) {
                    $step.find('.b2e-step-count').text(step.count);
                }
            });
        }
    };
    
    $(document).ready(() => B2E.init());
    
})(jQuery);
```

### Phase 9: Admin CSS
```css
/* admin/css/admin-styles.css */
.b2e-dashboard {
    max-width: 1200px;
}

.b2e-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.b2e-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.b2e-api-key-display {
    display: flex;
    gap: 10px;
    align-items: center;
    margin: 15px 0;
}

.b2e-api-key-display code {
    flex: 1;
    padding: 10px;
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    font-size: 14px;
    word-break: break-all;
}

.b2e-stats ul {
    list-style: none;
    padding: 0;
}

.b2e-stats li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.b2e-progress-bar {
    width: 100%;
    height: 30px;
    background: #f0f0f1;
    border-radius: 4px;
    overflow: hidden;
    margin: 20px 0;
}

.b2e-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1 0%, #135e96 100%);
    transition: width 0.3s ease;
}

.b2e-progress-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.b2e-step {
    padding: 15px;
    border: 2px solid #ddd;
  border-radius: 4px;
    background: #fff;
}

.b2e-step-pending {
    border-color: #ddd;
    background: #f9f9f9;
}

.b2e-step-in_progress {
    border-color: #2271b1;
    background: #e7f3fa;
}

.b2e-step-completed {
    border-color: #00a32a;
    background: #edfaef;
}

.b2e-step-failed {
    border-color: #d63638;
    background: #fcf0f1;
}

.b2e-step-name {
  display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.b2e-step-status {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
}

.b2e-step-count {
    display: block;
    font-size: 24px;
    font-weight: 700;
    margin-top: 10px;
    color: #2271b1;
}

.b2e-error-log {
    margin-top: 30px;
}

.b2e-errors {
    max-height: 400px;
    overflow-y: auto;
}

.b2e-error {
    padding: 15px;
    margin: 10px 0;
    background: #fcf0f1;
    border-left: 4px solid #d63638;
  border-radius: 4px;
}

.b2e-error strong {
    color: #d63638;
}

.b2e-error details {
    margin-top: 10px;
}

.b2e-error pre {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
}
```

## Testing Strategy

### Unit Tests (Optional but Recommended)
```php
<?php
class B2E_Test_Dynamic_Data extends WP_UnitTestCase {
    
    private $converter;
    
    public function setUp() {
        parent::setUp();
        $this->converter = new B2E_Dynamic_Data_Converter();
    }
    
    public function test_convert_post_title() {
        $input = 'Hello {post_title}!';
        $expected = 'Hello {this.title}!';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
    }
    
    public function test_convert_acf_fields() {
        // Test ACF text field conversion
        $input = 'Welcome {acf:headline} and {acf:description}';
        $expected = 'Welcome {this.acf.headline} and {this.acf.description}';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
        
        // Test ACF image field conversion
        $input = '<img src="{acf:hero_image}" alt="Hero" />';
        $expected = '<img src="{this.acf.hero_image.url}" alt="Hero" />';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
        
        // Test ACF gallery field conversion
        $input = '{acf:gallery_images}';
        $expected = '{#loop this.acf.gallery_images as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
        
        // Test ACF repeater field conversion
        $input = '{acf:faq_items}';
        $expected = '{#loop this.acf.faq_items as item}<div>{item.sub_field}</div>{/loop}';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
    }
    
    public function test_convert_metabox_fields() {
        // Test MetaBox text field conversion
        $input = 'Title: {mb:title} and {metabox:description}';
        $expected = 'Title: {this.metabox.title} and {this.metabox.description}';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
        
        // Test MetaBox image field conversion
        $input = '<img src="{mb:logo_image}" alt="Logo" />';
        $expected = '<img src="{this.metabox.logo_image.url}" alt="Logo" />';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
    }
    
    public function test_convert_jetengine_fields() {
        // Test JetEngine text field conversion
        $input = 'Content: {jet:content} and {jetengine:excerpt}';
        $expected = 'Content: {this.jetengine.content} and {this.jetengine.excerpt}';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
        
        // Test JetEngine image field conversion
        $input = '<img src="{jet:featured_image}" alt="Featured" />';
        $expected = '<img src="{this.jetengine.featured_image.url}" alt="Featured" />';
        $result = $this->converter->convert_content($input);
        $this->assertEquals($expected, $result);
  }
}
```

### Integration Tests (Critical)
1. **Small Site Test** (1-5 pages)
   - Create test site with all element types
   - Run migration
   - Compare visual output
   - Check error log

2. **Medium Site Test** (20-50 pages)
   - Real-world content
   - Various post types
   - Dynamic data usage
   - Performance measurement

3. **Large Site Test** (100+ pages)
   - Stress test
   - Resume functionality
   - Memory usage monitoring
   - Error recovery

### Test Checklist
- [ ] Layout integrity maintained
- [ ] CSS styles correctly converted
- [ ] Images present and correct
- [ ] Links functional
- [ ] Dynamic data working
- [ ] Responsive design intact
- [ ] No PHP errors
- [ ] No JavaScript errors
- [ ] Error log reviewed
- [ ] Performance acceptable

## MVP Definition

### V1.0 - Must Have (Launch)
✅ Basic Elements (Section, Container, Div, Heading, Text, Image, Button)
✅ CSS Classes Migration with Nesting
✅ CSS Variables & Media Queries
✅ Dynamic Data Conversion (Post, Author, Site, User, URL)
✅ Dynamic Data Modifiers (30+ modifiers)
✅ **Custom Field VALUES Migration (wp_postmeta)** ⭐ NEW
✅ **Plugin Detection (ACF, MetaBox, JetEngine)** ⭐ NEW
✅ **Custom Field Analysis & Reporting** ⭐ NEW
✅ **Custom Post Types Detection & Validation** ⭐ NEW
✅ Media File Migration
✅ Error Logging with Codes
✅ Warning System (W001-W003) ⭐ NEW
✅ Resume Functionality
✅ Admin Interface
✅ Progress Tracking

### V1.1 - Nice to Have (Future Enhancement)
🔶 **Cross-Plugin Migration (ACF ↔ MetaBox)** ⭐ NEW
🔶 **Complex Field Types Conversion (Repeater, Gallery, etc.)** ⭐ NEW
🔶 Template & Conditions Migration
🔶 Query Loops Migration
🔶 Dry-Run Mode (Analysis only)
🔶 Batch Processing Optimization
🔶 Export/Import Migration Reports
🔶 **ACF Field Groups Export/Import Helper** ⭐ NEW

### V2.0 - Future Features
🔮 Bricks Components → Etch Components
🔮 **Custom Post Types Auto-Registration** ⭐ NEW
🔮 **Advanced Cross-Plugin Field Conversion** ⭐ NEW
🔮 Multisite Support
🔮 CLI Commands
🔮 Rollback Functionality

## System Requirements

### WordPress Environment
- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Memory**: Minimum 128MB (256MB recommended)
- **Max Execution Time**: 300 seconds recommended

### Required Plugins
- **Source Site**: Bricks Builder active
- **Target Site**: Etch installed and active

### Browser Support for Migrated Sites
- **CSS Nesting Module**: 91%+ browser support (2025)
- Chrome 112+
- Firefox 117+
- Safari 16.5+
- Edge 112+

## Error Code Reference

See full lists in `B2E_Error_Handler::ERROR_CODES` and `B2E_Error_Handler::WARNING_CODES`

### Common Error Codes
- **E001**: Missing Media File
- **E002**: Invalid CSS Syntax
- **E003**: Unsupported Bricks Element
- **E004**: Dynamic Data Tag Not Mappable
- **E005**: Custom Field Not Found
- **E101**: API Connection Failed
- **E102**: API Key Expired
- **E103**: API Request Timeout
- **E201**: Post Creation Failed
- **E202**: Style Import Failed
- **E203**: Checkpoint Save Failed
- **E301**: Custom Post Type Not Found ⭐ NEW
- **E302**: Custom Field Meta Migration Failed ⭐ NEW

### Warning Codes
- **W001**: ACF Plugin Not Installed on Target ⭐ NEW
- **W002**: MetaBox Plugin Not Installed on Target ⭐ NEW
- **W003**: JetEngine Plugin Not Installed on Target ⭐ NEW

## Documentation Structure

### User Documentation
1. **Installation Guide**
2. **Quick Start Guide**
3. **Step-by-Step Migration Tutorial**
4. **Troubleshooting Guide**
5. **Error Code Reference**
6. **FAQ**

### Developer Documentation
1. **Architecture Overview**
2. **API Reference**
3. **Filter & Action Hooks**
4. **Extending the Plugin**
5. **Dynamic Data Mapping Guide**

## Deployment Checklist

- [ ] All PHP files follow WordPress Coding Standards
- [ ] All strings are internationalized
- [ ] Error codes are documented
- [ ] Admin interface is responsive
- [ ] API endpoints are secured
- [ ] Transients have proper expiration
- [ ] Memory usage is optimized
- [ ] No hardcoded URLs
- [ ] Activation/deactivation hooks work
- [ ] Uninstall cleanup implemented

## Summary

This updated plan provides a complete, production-ready solution for migrating Bricks Builder websites to Etch PageBuilder with:

### ✅ Key Improvements from V2.1
1. **Dynamic Data Conversion** - Complete Bricks → Etch tag mapping with modifiers
2. **Dynamic Data Modifiers** - Full support for 30+ Etch modifiers with automatic conversion
3. **Custom Fields Integration** - Native support for ACF, MetaBox, and JetEngine
4. **Custom Field VALUES Migration** - Automatic migration aller wp_postmeta Einträge ⭐ NEW
5. **Plugin Detection** - Automatische Erkennung von ACF, MetaBox, JetEngine ⭐ NEW
6. **Warning System** - Warnungen bei fehlenden Plugins (W001-W003) ⭐ NEW
7. **Custom Post Types Detection** - Erkennung & Validierung von CPTs ⭐ NEW
8. **Modifier Stacking** - Support for chained modifiers
9. **Simplified Architecture** - One-time use focus
10. **Enhanced CSS Handling** - Variables, media queries, validation
11. **Resume Capability** - Handle large migrations with checkpoints
12. **Error Code System** - Clear, documented errors with solutions
13. **Transient-Based Storage** - Simpler, no custom tables needed

### 🔥 NEW: Advanced Dynamic Data Features

#### **Modifier Conversion Examples**
```
Bricks → Etch

{post_title|uppercase} → {this.title.toUpperCase()}
{post_date|date:F j, Y} → {this.date.dateFormat("F j, Y")}
{acf:price|number_format:2} → {this.acf.price.numberFormat(2)}
{post_title|slug} → {this.title.toSlug()}
{user_email|lowercase} → {user.email.toLowerCase()}
```

#### **Custom Fields Support**
```php
// ACF Fields
Bricks: {acf:company_name}
Etch:   {this.acf.company_name}

// MetaBox Fields
Bricks: {mb:project_date}
Etch:   {this.metabox.project_date}

// JetEngine Fields
Bricks: {jet:custom_field}
Etch:   {this.jetengine.custom_field}

// Generic Meta
Bricks: {post_meta:custom_field}
Etch:   {this.meta.custom_field}
```

#### **Custom Field VALUES Migration** ⭐ NEW
```php
// wp_postmeta wird automatisch migriert!

Source Site (Bricks):
wp_postmeta:
  post_id: 123
  meta_key: company_name
  meta_value: "ACME Corp"
  
Target Site (Etch):
wp_postmeta:
  post_id: 456 (neuer Post)
  meta_key: company_name
  meta_value: "ACME Corp"  // Automatisch kopiert!

// ACF References werden auch migriert
Source:
  meta_key: _company_name
  meta_value: "field_abc123"  // ACF Field Key
  
Target:
  meta_key: _company_name
  meta_value: "field_abc123"  // Gleicher Key!
```

#### **Plugin Detection & Warnings** ⭐ NEW
```
Migration Pre-Check:

Source Site:
✅ Bricks Builder
✅ ACF Pro
❌ MetaBox (not installed)
✅ Etch

Target Site:
✅ Etch
⚠️ ACF (not installed)
❌ MetaBox (not installed)

Warning W001:
"Source site uses ACF but target site does not have ACF installed.
Field values will be stored as generic post_meta.
Suggestion: Install ACF on target site before migration."

User Decision:
[ ] Proceed anyway (values stored as post_meta)
[ ] Install ACF first (recommended)
[ ] Cancel migration
```

#### **Custom Post Types Detection** ⭐ NEW
```
Detected Custom Post Types:

Source Site CPTs:
✅ portfolio (15 posts)
✅ team (8 posts)
✅ testimonial (23 posts)

Target Site CPTs:
✅ portfolio (registered)
⚠️ team (NOT registered)
⚠️ testimonial (NOT registered)

Error E301:
"Custom Post Type 'team' not found on target site.
8 posts of this type cannot be migrated.
Solution: Register 'team' CPT on target site first."

User must:
1. Register missing CPTs on target site
2. Retry validation
3. Proceed with migration
```

#### **Complex Example with Modifiers**
```
Bricks: 
Posted on {post_date|date:F j, Y} by {author_name|uppercase}
Price: {acf:price|number_format:2}

Etch:
Posted on {this.date.dateFormat("F j, Y")} by {this.author.name.toUpperCase()}
Price: {this.acf.price.numberFormat(2)}
```

### 🎯 Ready for Development
The plan is now actionable with:
- Complete code structure
- All major classes defined
- Dynamic data converter with modifiers
- ACF/MetaBox/JetEngine support
- Admin interface designed
- API endpoints specified
- Error handling system
- Testing strategy
- Documentation outline

### 📚 Key References
- [Etch Dynamic Data Keys](https://docs.etchwp.com/dynamic-data/dynamic-data-keys)
- [Etch Dynamic Data Modifiers](https://docs.etchwp.com/dynamic-data/dynamic-data-modifiers) ⭐ NEW
- [Etch Custom Fields Integration](https://docs.etchwp.com/integrations/custom-fields) ⭐ NEW
- CSS Nesting Module Specification
- WordPress REST API
- WordPress Coding Standards

### 🚀 Migration Capabilities

**Supported Dynamic Data:**
- ✅ Post Data (title, content, excerpt, date, etc.)
- ✅ Author Data (name, ID, email, etc.)
- ✅ User Data (logged in user info, roles, etc.)
- ✅ Site Data (site name, URL, language, etc.)
- ✅ URL Data (current URL, parameters, etc.)
- ✅ ACF Fields (all field types)
- ✅ MetaBox Fields (all field types)
- ✅ JetEngine Fields (all field types)
- ✅ Generic Post Meta

**Supported Modifiers:**
- ✅ Date Formatting (.dateFormat)
- ✅ Number Formatting (.numberFormat)
- ✅ String Manipulation (upper, lower, trim, truncate, slug, etc.)
- ✅ Type Conversion (toString, toInt, toBool)
- ✅ Math Operations (round, ceil, floor)
- ✅ Array Operations (length, reverse, slice, at, includes)
- ✅ URL Operations (urlEncode, urlDecode)
- ✅ String Operations (concat, split, join, startsWith, endsWith)

**Migration Features:**
- ✅ HTML & CSS Migration (nested CSS support)
- ✅ CSS Variables Detection & Root Placement
- ✅ Media Query Nesting
- ✅ Framework Variable Detection (skip Bootstrap, Tailwind, etc.)
- ✅ Invalid CSS Auto-Fix
- ✅ Resume from Checkpoint (8h window)
- ✅ Error Codes with Documentation Links
- ✅ Progress Tracking with Real-time Updates
- ✅ Dry-Run Mode for Testing

### ⚠️ Migration Limitations

**Not Migrated (Bricks-Specific):**
- ❌ Sliders, Carousels
- ❌ Accordions, Tabs
- ❌ Popups, Offcanvas
- ❌ Countdown Timers
- ❌ Progress Bars
- ❌ Flip Boxes, Before/After
- ❌ Bricks Query Builder (needs manual recreation)
- ❌ Bricks Template Conditions (needs manual setup)
- ❌ Bricks Components (V2.0 feature)

**Users must recreate these elements manually in Etch.**

### 📊 Testing Requirements

Before going live with the migration:

1. **Small Test** (1-5 pages)
   - All element types present
   - Dynamic data usage
   - Custom fields (ACF/MetaBox/JetEngine)
   - Modifiers in use

2. **Medium Test** (20-50 pages)
   - Real-world content
   - Various post types
   - Complex layouts
   - Performance check

3. **Large Test** (100+ pages)
   - Stress test
   - Resume functionality
   - Memory usage
   - Error recovery

### 🎓 Documentation Deliverables

1. **User Guide**
   - Installation steps
   - Migration wizard walkthrough
   - Error code reference
   - Troubleshooting guide

2. **Developer Guide**
   - Architecture overview
   - Dynamic data mapping reference
   - Custom fields integration
   - Modifier conversion table
   - API documentation

3. **Migration Checklist**
   - Pre-migration requirements
   - Post-migration verification
   - Manual tasks (sliders, accordions, etc.)
   - Known limitations

**Next Step**: Begin Phase 1 implementation with plugin foundation and authentication system.

---

## 📝 **Answers to Your Questions**

### **Question 1: Are Custom Fields and Custom Post Types migrated?**

**Answer:**

#### **Custom Field VALUES** ✅ YES - Automatically migrated
```
✅ wp_postmeta entries are fully copied
✅ ACF Field Keys (_field_references) are migrated
✅ MetaBox Values are migrated
✅ JetEngine Values are migrated
✅ Generic post_meta wird migriert
✅ Serialized data wird korrekt behandelt
```

#### **Custom Field DEFINITIONS** ❌ NO - User's Responsibility
```
❌ ACF Field Groups (User must export/import)
❌ MetaBox Configurations
❌ JetEngine Meta Boxes
```

**Why?** Field Definitions are plugin-specific and complex. Best Practice: Export/Import via plugin's own tools.

#### **Custom Post Types** ❌ NO - Only Detection & Validation
```
✅ Plugin detects all CPTs on Source Site
✅ Plugin checks if CPTs exist on Target Site
⚠️ Warning if CPTs are missing (Error E301)
❌ Automatic Registration (V2.0 Feature)
```

**User must:** Manually register CPTs on Target Site (Theme/Plugin Code)

---

### **Question 2: ACF to ACF, MetaBox to MetaBox - or Cross-Plugin?**

**Answer:**

#### **V1.0: Same-to-Same Migration** ✅ Fully supported
```php
ACF Site → ACF Site ✅
- Field Values are copied
- ACF Field Keys remain intact
- User must manually migrate Field Groups

MetaBox Site → MetaBox Site ✅
- Field Values are copied
- User must manually set up MetaBox Configs

JetEngine Site → JetEngine Site ✅
- Field Values are copied
- User must recreate JetEngine Meta Boxes
```

#### **V1.0: Cross-Plugin Migration** ⚠️ Basic Support
```php
ACF Site → MetaBox Site ⚠️
- Simple fields: ✅ Funktioniert (als post_meta)
- Complex fields (Repeater, Gallery): ❌ Nicht konvertiert
- User muss: Komplexe Fields manuell neu aufbauen

MetaBox Site → ACF Site ⚠️
- Simple fields: ✅ Funktioniert (als post_meta)
- Complex fields: ❌ Nicht konvertiert
```

**Empfehlung:** Gleiche Plugins auf beiden Sites verwenden!

#### **V1.1: Advanced Cross-Plugin** 🔶 Future Feature
```php
// Automatische Konvertierung:
ACF Repeater → MetaBox Group
ACF Gallery → MetaBox Image Advanced
ACF Relationship → MetaBox Post
// ... etc.
```

---

### **Question 3: Plugin Detection before Migration?**

**Answer:** ✅ YES - Comprehensive Pre-Migration Checks!

```php
class B2E_Plugin_Detector {
    
    // Detects installed plugins on both sites
    public function get_installed_plugins() {
        return array(
            'acf' => class_exists('ACF'),
            'metabox' => function_exists('rwmb_meta'),
            'jetengine' => class_exists('Jet_Engine'),
        );
    }
    
    // Validiert Requirements vor Migration
    public function validate_migration_requirements($source, $target) {
        if ($source['acf'] && !$target['acf']) {
            // Warning W001 anzeigen
            return array('warning' => 'ACF not installed on target');
        }
    }
}
```

**Migration Workflow:**
```
1. User startet Migration
2. Plugin erkennt Source & Target Plugins
3. Plugin zeigt Warnings an:
   ⚠️ W001: ACF fehlt auf Target
   ⚠️ W002: MetaBox fehlt auf Target
4. User entscheidet:
   [ ] Proceed anyway (values als post_meta)
   [ ] Install missing plugins first (recommended)
   [ ] Cancel migration
5. Migration startet oder wird abgebrochen
```

---

### **Question 4: Does it work without plugins (DB only)?**

**Answer:** ✅ YES - But with limitations!

```php
// Scenario 1: Source has ACF, Target has no ACF
Source wp_postmeta:
  company_name = "ACME Corp"
  _company_name = "field_abc123"  // ACF Reference

Target wp_postmeta:
  company_name = "ACME Corp"      // ✅ Kopiert!
  _company_name = "field_abc123"  // ✅ Kopiert!

Result:
- Values sind in DB ✅
- Aber: Nicht als ACF Fields erkannt ❌
- Gelöst: User installiert ACF → Fields funktionieren! ✅
```

**Warum funktioniert es?**
- wp_postmeta ist nur eine DB-Tabelle
- ACF/MetaBox lesen aus wp_postmeta
- Wenn Field Keys vorhanden sind, erkennt ACF die Fields automatisch!

**Aber:**
```
✅ Simple Fields funktionieren ohne Plugin (als generic meta)
⚠️ Complex Fields (Repeater, Gallery) benötigen Plugin
⚠️ Field Groups müssen vorhanden sein für Admin UI
```

**Empfehlung:**
```
Plugins VOR Migration installieren:
1. Gleiche Plugins auf beiden Sites installieren
2. Field Groups auf Target Site importieren
3. DANN Migration starten
4. → Everything works perfectly! ✅
```

---

### **Zusammenfassung: Was wird migriert?**

#### ✅ **Automatisch migriert (V1.0):**
- ✅ Bricks Page Content → Etch Gutenberg Blocks
- ✅ CSS Classes → Etch Styles (mit Nesting)
- ✅ CSS Variables → :root
- ✅ Media Queries → Nested
- ✅ **Custom Post Types (Automatic Registration!)** ⭐ UPDATED
- ✅ **ACF Field Groups (Automatic Migration!)** ⭐ UPDATED
- ✅ **MetaBox Configurations (MB Builder only)** ⭐ UPDATED
- ✅ **wp_postmeta (alle Custom Field VALUES)** ⭐
- ✅ **ACF Field Keys & References** ⭐ NEW
- ✅ Dynamic Data Tags → Etch Dynamic Data Keys
- ✅ Dynamic Data Modifiers (30+ modifiers)
- ✅ Media Files (Images, Videos, etc.)
- ✅ Post Data (title, excerpt, status, date, etc.)

#### ⚠️ **Mit Warnings/Validation:**
- Plugin Compatibility Check (W001-W003)
- CPT Existence Validation
- Cross-Plugin Field Migration (simple fields only)

#### 🔶 **V1.1 Features (Future):**
- Advanced ACF ↔ MetaBox Field Conversion
- Complex Field Types (Repeater, Flexible Content)
- Bricks Templates & Conditions
- Query Loops Migration

#### ❌ **NICHT migriert:**
- MetaBox Code-based Configs (nur MB Builder)
- JetEngine Meta Boxes (noch nicht implementiert)
- Bricks-specific Elements (Sliders, Accordions)
- Bricks Components (V2.0 feature)

---

### **Recommended Migration Workflow (SIMPLIFIED!):**

```
Phase 1: Preparation (Target Site) - NUR 3 SCHRITTE! 🎉
1. ✅ Install Etch
2. ✅ Install same plugins as source (ACF, MetaBox, JetEngine)
3. ✅ Install Migration Plugin

❌ NICHT MEHR NÖTIG:
   Export & Import ACF Field Groups → AUTOMATISCH! ⭐
   Setup MetaBox configurations → AUTOMATISCH! ⭐
   Register Custom Post Types → AUTOMATISCH! ⭐

Phase 2: Migration (AUTOMATISCH!)
4. ✅ Generate API Key on Source Site
5. ✅ Enter API Key on Target Site
6. ✅ Run Pre-Migration Validation
7. ✅ Review Warnings (W001-W003)
8. ✅ Click "Start Migration"
9. ✅ Watch Progress Bar 🍿

Plugin does EVERYTHING automatically:
  ✅ Migrate Custom Post Types
  ✅ Migrate ACF Field Groups
  ✅ Migrate MetaBox Configs
  ✅ Migrate CSS Classes
  ✅ Migrate Posts + Content
  ✅ Migrate Custom Fields (wp_postmeta)
  ✅ Convert Dynamic Data
  ✅ Convert Modifiers

Phase 3: Verification
10. ✅ Check migrated posts
11. ✅ Verify Custom Fields display correctly
12. ✅ Test Dynamic Data
13. ✅ Check CSS styles
14. ✅ Manually rebuild Sliders/Accordions in Etch
15. ✅ Test thoroughly
16. ✅ Go Live!
17. ✅ Delete Migration Plugin
```

**IMPORTANT:** CPTs remain PERMANENTLY registered (via `b2e_registered_cpts` option)!

**After Migration:**
- Plugin can be deleted
- ACF Field Groups remain in Target DB
- MetaBox Configs remain in Target DB  
- CPTs remain registered (or User ports to own code)
- Custom Fields work out-of-the-box! ✅

**Alles klar?** 🚀