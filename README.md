# Bricks to Etch Migration Plugin

**Version:** 0.4.0  
**Status:** ✅ Production Ready

One-time migration tool for converting Bricks Builder websites to Etch PageBuilder with complete automation.

---

## 🎯 Features

- ✅ **CSS Migration:** Converts 1135+ Bricks Global Classes to Etch Styles
- ✅ **Content Migration:** Migrates posts, pages, and Gutenberg content
- ✅ **Media Migration:** Transfers images and attachments
- ✅ **CSS Classes:** Frontend rendering with correct class names
- ✅ **Custom CSS:** Supports custom CSS from Global Classes
- ✅ **Batch Processing:** Efficient migration of large sites

---

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- Bricks Builder (source site)
- Etch PageBuilder (target site)
- Docker (for local testing)

---

## 🚀 Quick Start

### 1. Installation

#### On Bricks Site (Source):
```bash
# Upload plugin to wp-content/plugins/
# Activate plugin in WordPress admin
```

#### On Etch Site (Target):
```bash
# Upload plugin to wp-content/plugins/
# Activate plugin in WordPress admin
# Generate Application Password in Etch admin
```

### 2. Configuration

1. Go to **Bricks Dashboard** → **Bricks to Etch Migration**
2. Enter **Etch Site URL** (e.g., `https://your-etch-site.com`)
3. Enter **Application Password** from Etch site
4. Click **Test Connection** to verify
5. Click **Start Migration**

### 3. Migration Process

The migration runs in 3 steps:

1. **CSS Migration** - Converts Bricks Global Classes to Etch Styles
2. **Media Migration** - Transfers images and attachments
3. **Content Migration** - Migrates posts and pages

Progress is shown in real-time with detailed logs.

---

## 🐳 Local Development (Docker)

### Setup

```bash
cd test-environment
docker-compose up -d
```

**Access:**
- Bricks Site: http://localhost:8080
- Etch Site: http://localhost:8081

### Testing

```bash
# Clean up Etch site
./cleanup-etch.sh

# Run migration
# Go to: http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration
# Click "Start Migration"
```

---

## 📚 Documentation

### Main Documentation
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes
- **[CSS-CLASSES-FINAL-SOLUTION.md](CSS-CLASSES-FINAL-SOLUTION.md)** - Complete CSS classes documentation
- **[CSS-CLASSES-QUICK-REFERENCE.md](CSS-CLASSES-QUICK-REFERENCE.md)** - Quick reference guide
- **[MIGRATION-SUCCESS-SUMMARY.md](MIGRATION-SUCCESS-SUMMARY.md)** - Project status and statistics

### Archive
Old documentation and test scripts are in `archive/` folder.

---

## 🔧 Technical Details

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
├── assets/
│   ├── css/                      # Admin styles
│   └── fonts/                    # Custom fonts
└── bricks-etch-migration.php     # Main plugin file
```

### Key Features

#### CSS Classes in Frontend
Etch renders CSS classes from `etchData.attributes.class`:

```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "class": "my-css-class"
    }
  }
}
```

#### Custom CSS Support
Custom CSS from Bricks Global Classes is merged with normal styles:

```css
.my-class {
  /* Normal CSS */
  padding: 1rem;
  
  /* Custom CSS */
  --my-var: value;
  border-radius: var(--radius);
}
```

#### Element Support
- ✅ Headings (h1-h6)
- ✅ Paragraphs (p)
- ✅ Images (figure + img)
- ✅ Sections (section)
- ✅ Containers (div)
- ✅ Flex-Divs (div)

---

## 🐛 Troubleshooting

### Migration Fails

**Check logs:**
```bash
# Bricks site
docker exec b2e-bricks tail -100 /var/www/html/wp-content/debug.log

# Etch site
docker exec b2e-etch tail -100 /var/www/html/wp-content/debug.log
```

### CSS Classes Missing

1. Verify CSS migration completed successfully
2. Check `etch_styles` option exists
3. Check `b2e_style_map` option exists
4. Re-run migration

### API Connection Issues

1. Verify Application Password is correct
2. Check Etch site is accessible
3. Test connection before migration
4. Check firewall/security settings

---

## 📊 Migration Statistics

| Category | Count | Status |
|----------|-------|--------|
| Global Classes | 1135+ | ✅ Migrated |
| Etch Styles | 1141+ | ✅ Generated |
| Element Types | 6+ | ✅ Supported |

---

## 🎉 Success Criteria

A successful migration shows:

### Database
```json
{
  "etchData": {
    "styles": ["abc123"],
    "attributes": {
      "class": "my-css-class"
    }
  }
}
```

### Frontend
```html
<div class="my-css-class">Content</div>
```

### CSS
```css
.my-css-class {
  /* Styles from Bricks */
}
```

---

## 🤝 Contributing

This is a one-time migration tool. For issues or improvements:

1. Check existing documentation
2. Review CHANGELOG.md
3. Test in Docker environment first
4. Document any changes

---

## 📝 License

GPL v2 or later

---

## 👤 Author

**Tobias Haas**

---

## 🔗 Links

- [Bricks Builder](https://bricksbuilder.io/)
- [Etch PageBuilder](https://etchtheme.com/)
- [GitHub Repository](https://github.com/tobiashaas/Bricks-to-Etch-Migration)

---

**Last Updated:** October 21, 2025  
**Version:** 0.4.0
