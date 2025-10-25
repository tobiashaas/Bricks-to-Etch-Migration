# Etch Fusion Suite

![CI](https://github.com/tobiashaas/EtchFusion-Suite/workflows/CI/badge.svg)
![CodeQL](https://github.com/tobiashaas/EtchFusion-Suite/workflows/CodeQL/badge.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.1%20%7C%208.2%20%7C%208.3%20%7C%208.4-blue)

**Version:** 0.8.0-beta  
**Status:** ✅ Production Ready

End-to-end migration and orchestration toolkit for transforming Bricks Builder sites into fully native Etch experiences. Automates content conversion, Gutenberg block generation, style remapping, asset handling, and API provisioning—backed by security logging, rate limiting, and deep WordPress integration.

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

1. Go to **Bricks Dashboard** → **Etch Fusion Suite**
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

## 🐳 Local Development

**Note:** The Docker Compose setup in `test-environment/` is deprecated. Use the npm-based wp-env workflow instead.

### Recommended: wp-env Workflow

See **[etch-fusion-suite/README.md](etch-fusion-suite/README.md)** for complete setup instructions.

```bash
cd etch-fusion-suite
npm install
npm run dev
```

**Access:**
- Bricks Site: http://localhost:8888
- Etch Site: http://localhost:8889

### Testing

```bash
# Clean up Etch site (manual cleanup script)
./cleanup-etch.sh

# Run migration
# Go to Bricks admin and click "Start Migration"
```

---

## Documentation

### Main Documentation
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes
- **[DOCUMENTATION.md](DOCUMENTATION.md)** - Technical documentation
- **[docs/MIGRATOR-API.md](etch-fusion-suite/docs/MIGRATOR-API.md)** - Developer guide for the migrator system
- **[docs/FRAMER-EXTRACTION.md](etch-fusion-suite/docs/FRAMER-EXTRACTION.md)** - Framer template extraction pipeline

---

## Technical Details

### Plugin Structure

```
etch-fusion-suite/
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
└── etch-fusion-suite.php     # Main plugin file
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

### Repository Pattern

The plugin uses Repository Pattern to abstract data access. Three repositories handle different data domains:

- **Settings_Repository** - Plugin settings, API keys, migration settings
- **Migration_Repository** - Progress, steps, stats, tokens, imported data
- **Style_Repository** - CSS styles, style maps, Etch-specific options

**Benefits:**
1. **Separation of concerns** - Business logic separated from data access
2. **Built-in caching** - Transient caching for performance (2-10 minute expiration)
3. **Easier testing** - Mock repositories for unit tests
4. **Future flexibility** - Easy to change data storage (e.g., custom tables)

**Example Usage:**
```php
// Inject repository into service
class B2E_Migration_Service {
    private $migration_repository;
    
    public function __construct(
        // ... other dependencies
        Migration_Repository_Interface $migration_repository
    ) {
        $this->migration_repository = $migration_repository;
    }
    
    public function save_progress($progress) {
        // Use repository instead of direct get_option/update_option
        $this->migration_repository->save_progress($progress);
    }
}
```

All repositories are registered in the DI container and automatically injected into services, controllers, and other components.

---

## 🐛 Troubleshooting

### Migration Fails

**Check logs:**
```bash
# Bricks site (development environment)
npm run logs:bricks

# Etch site (tests environment)
npm run logs:etch
```

Need to inspect files directly? Drop into a shell and run commands there:

```bash
# Open an interactive shell
npm run shell:bricks
# or for the Etch site
npm run shell:etch

# Once inside the shell
tail -n 100 /var/www/html/wp-content/debug.log
```

**Run WP-CLI commands:**

```bash
# Example: list plugins on the Bricks site
npm run wp:bricks -- plugin list

# Example: clear cache on the Etch site
npm run wp:etch -- cache flush
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
3. Test in wp-env environment first
4. Run code quality checks locally:
   ```bash
   cd etch-fusion-suite
   composer lint
   composer test
   ```
5. Document any changes

### Development Workflow

**Code Quality:**
```bash
# Run WordPress Coding Standards check
composer lint

# Auto-fix coding standards violations
composer lint:fix
```

**Testing:**
```bash
# Run PHPUnit tests
composer test

# Generate coverage report
composer test:coverage
```

**CI/CD:**
All pull requests automatically run:
- WordPress Coding Standards (WPCS)
- PHP Compatibility checks (PHP 7.4-8.4)
- PHPUnit tests across all PHP versions
- CodeQL security scanning
- Dependency vulnerability checks

See [`.github/workflows/README.md`](.github/workflows/README.md) for detailed CI/CD documentation.

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

**Last Updated:** October 24, 2025  
**Version:** 0.8.0
