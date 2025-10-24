# Technical Documentation - Bricks to Etch Migration

**Last Updated:** 2025-10-24 13:30  
**Version:** 0.8.0

---

## 📋 Table of Contents

1. [Architecture](#architecture)
2. [Security Configuration](#security-configuration)
3. [CSS Migration](#css-migration)
4. [Content Migration](#content-migration)
5. [Media Migration](#media-migration)
6. [API Communication](#api-communication)
7. [Frontend Rendering](#frontend-rendering)
8. [Test Environment](#test-environment)

---

## Architecture

**Updated:** 2025-10-23 23:40

### Plugin Structure

```
bricks-etch-migration/
├── includes/
│   ├── container/               # Dependency injection
│   │   ├── class-service-container.php
│   │   └── class-service-provider.php
│   ├── repositories/            # Data access layer
│   │   ├── interfaces/
│   │   ├── class-wordpress-style-repository.php
│   │   ├── class-wordpress-settings-repository.php
│   │   └── class-wordpress-migration-repository.php
│   ├── api/                     # API communication
│   │   ├── api_client.php
│   │   └── api_endpoints.php
│   ├── parsers/                 # Data parsing
│   │   ├── css_converter.php
│   │   └── content_parser.php
│   ├── converters/              # Data conversion
│   │   └── gutenberg_generator.php
│   └── ...
└── bricks-etch-migration.php    # Main plugin file
```

### Service Container

**Updated:** 2025-10-23 23:40

The plugin uses a dependency injection container for service management:

**Key Services:**
- `css_converter` → `\Bricks2Etch\Parsers\B2E_CSS_Converter`
- `api_client` → `\Bricks2Etch\Api\B2E_API_Client`
- `style_repository` → `\Bricks2Etch\Repositories\B2E_WordPress_Style_Repository`
- `settings_repository` → `\Bricks2Etch\Repositories\B2E_WordPress_Settings_Repository`
- `migration_repository` → `\Bricks2Etch\Repositories\B2E_WordPress_Migration_Repository`

**Important:** All service bindings use fully qualified class names (FQCN) with correct namespaces.

### Autoloading & Namespaces

**Updated:** 2025-10-24 11:26

- Composer (`vendor/autoload.php`) wird eingebunden, sobald vorhanden.
- Zusätzlich bleibt der WordPress-optimierte Autoloader (`includes/autoloader.php`) immer aktiv, damit Legacy-Dateinamen (`class-*.php`) weiterhin funktionieren.
- Namespace-Mappings decken Sicherheitsklassen (`Bricks2Etch\Security\...`) sowie Repository-Interfaces (`Bricks2Etch\Repositories\Interfaces\...`) ab.
- Dateinamens-Erkennung schließt Interface-Dateien (`interface-*.php`) mit ein, damit Admin-Aufrufe ohne CLI-Kontext sauber funktionieren.

### Repository Pattern

**Updated:** 2025-10-23 23:40

All data access goes through repository interfaces:

**Style Repository Methods:**
- `get_etch_styles()` - Retrieve Etch styles with caching
- `save_etch_styles($styles)` - Save Etch styles
- `get_style_map()` - Get Bricks→Etch style ID mapping
- `save_style_map($map)` - Save style map
- `invalidate_style_cache()` - Clear style-related caches (targeted, not global)

**Cache Strategy:**
- Uses WordPress transients for 5-minute cache
- Targeted cache invalidation (no `wp_cache_flush()`)
- Prevents site-wide performance impact

### Data Flow

```
Bricks Site                    Etch Site
    ↓                              ↓
1. CSS Converter          →   Etch Styles
2. Media Migrator         →   Media Library
3. Content Converter      →   Gutenberg Blocks
```

---

## Security Configuration

**Updated:** 2025-10-24 07:56

### CORS (Cross-Origin Resource Sharing)

The plugin implements whitelist-based CORS for secure cross-origin API requests with comprehensive enforcement across all REST endpoints.

#### Configuration via WP-CLI

```bash
# Get current CORS origins
wp option get b2e_cors_allowed_origins --format=json

# Set CORS origins
wp option update b2e_cors_allowed_origins '["http://localhost:8888","http://localhost:8889","https://yourdomain.com"]' --format=json

# Add single origin (append to existing)
wp option patch insert b2e_cors_allowed_origins end "https://newdomain.com"
```

#### Default Origins

If no origins are configured, the following development defaults are used:
- `http://localhost:8888`
- `http://localhost:8889`
- `http://127.0.0.1:8888`
- `http://127.0.0.1:8889`

#### CORS Behavior

- **Allowed origins**: Receive proper CORS headers and can access the API
- **Disallowed origins**: Requests are denied with 403 status and logged as security violations
- **No Origin header**: Treated as same-origin request (allowed)

#### CORS Enforcement

**Updated:** 2025-10-24 07:56

The plugin enforces CORS validation at multiple levels:

1. **Per-endpoint checks**: Each endpoint handler calls `check_cors_origin()` early
2. **Global enforcement filter**: A `rest_request_before_callbacks` filter provides a safety net for all `/b2e/v1/*` routes
3. **Header injection**: The `B2E_CORS_Manager::add_cors_headers()` method sets appropriate headers via `rest_pre_serve_request`

**Public endpoints** (e.g., `/b2e/v1/migrate`, `/b2e/v1/validate`) now enforce CORS validation despite using `permission_callback => '__return_true'`. This ensures:
- Server actively rejects disallowed origins with 403 JSON error (not just browser-level blocking)
- All CORS violations are logged with route, method, and origin information
- Future endpoints cannot bypass origin validation

**Authenticated endpoints** continue to use CORS checks within their `permission_callback` for defense-in-depth.

### Content Security Policy (CSP)

The plugin applies relaxed CSP headers to accommodate WordPress behavior.

#### Current Policy

**Admin Pages:**
```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval';
style-src 'self' 'unsafe-inline';
img-src 'self' data: https:;
font-src 'self' data:;
connect-src 'self'
```

**Frontend:**
```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval';
style-src 'self' 'unsafe-inline';
img-src 'self' data: https:;
font-src 'self' data:;
connect-src 'self'
```

#### Configuration via Settings Repository

```php
// Get security settings
$settings_repo = b2e_container()->get('settings_repository');
$security_settings = $settings_repo->get_security_settings();

// Modify settings
$security_settings['csp_enabled'] = true;
$settings_repo->save_security_settings($security_settings);
```

### Rate Limiting

Rate limiting is applied to all AJAX and REST API endpoints.

#### Default Limits

**AJAX Endpoints:**
- Authentication: 10 requests/minute
- Read operations: 30-60 requests/minute
- Write operations: 20-30 requests/minute
- Sensitive operations (cleanup, logs): 5-10 requests/minute

**REST API Endpoints:**
- Authentication: 10 requests/minute
- Export (read): 30 requests/minute
- Import (write): 10-20 requests/minute

#### Configuration

Rate limiting settings can be configured via the settings repository:

```php
$settings_repo = b2e_container()->get('settings_repository');
$security_settings = $settings_repo->get_security_settings();

// Modify rate limits
$security_settings['rate_limit_enabled'] = true;
$security_settings['rate_limit_requests'] = 60;
$security_settings['rate_limit_window'] = 60; // seconds

$settings_repo->save_security_settings($security_settings);
```

### API Key Validation

API keys must meet the following requirements:
- **Minimum length**: 20 characters
- **Allowed characters**: Letters (a-z, A-Z), numbers (0-9), underscore (_), hyphen (-), dot (.)
- **Format**: Alphanumeric with common safe characters

### Audit Logging

All security events are logged with severity levels:
- **Low**: Routine operations
- **Medium**: Authentication failures, rate limit exceeded
- **High**: Authorization failures, suspicious activity
- **Critical**: Destructive operations (cleanup, log clearing)

#### View Audit Logs

```bash
# Via WP-CLI
wp option get b2e_security_log --format=json

# Via PHP
$audit_logger = b2e_container()->get('audit_logger');
$logs = $audit_logger->get_security_logs(100); // Last 100 events
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

## Continuous Integration

**Updated:** 2025-10-24 13:30

### Automated Checks

All code changes are automatically validated through GitHub Actions:

**Code Quality:**
- WordPress Coding Standards (WPCS) via PHP_CodeSniffer
- PHP Compatibility checks (PHPCompatibilityWP) for PHP 7.4-8.4
- Security scanning via CodeQL
- Dependency vulnerability checks

**Testing:**
- PHPUnit unit and integration tests
- Multi-PHP version matrix (7.4, 8.1, 8.2, 8.3, 8.4)
- WordPress Test Suite integration

**Automation:**
- Dependabot for automated dependency updates
- Automated plugin packaging on Git tags
- GitHub Release creation with changelog extraction

### PHP Version Matrix

The plugin is tested against 5 PHP versions:
- **PHP 7.4** (legacy, EOL but still widely used)
- **PHP 8.1** (current stable)
- **PHP 8.2** (current stable)
- **PHP 8.3** (current stable)
- **PHP 8.4** (latest)

### Security Scanning

CodeQL runs automatically on:
- Every push to `main` branch
- All pull requests
- Weekly schedule (Monday 6:00 UTC)

**Configuration:** `.github/codeql/codeql-config.yml`
- Uses `security-extended` query suite
- Scans only plugin code (excludes vendor/tests)
- Results appear in GitHub Security tab

### Dependency Management

Dependabot automatically creates PRs for:
- Composer dependencies (weekly)
- npm dependencies (weekly)
- GitHub Actions (weekly)

**Configuration:** `.github/dependabot.yml`
- Groups minor/patch updates
- Ignores PHP major version updates
- Labels PRs appropriately

### Release Automation

Creating a release is automated:

```bash
# 1. Update version in bricks-etch-migration.php
# 2. Add CHANGELOG.md entry
# 3. Commit and tag
git tag v1.0.0
git push origin v1.0.0

# 4. GitHub Actions automatically:
#    - Validates plugin headers
#    - Creates plugin ZIP
#    - Extracts changelog
#    - Creates GitHub Release
```

### Badge Status

CI/CD status is visible via badges in README.md:
- CI workflow status
- CodeQL security status
- PHP version compatibility

See [`.github/workflows/README.md`](.github/workflows/README.md) for detailed workflow documentation.

---

## Testing

**Updated:** 2025-10-21 23:20

### Test Scripts Location

All active, non-redundant test scripts are in `/tests` folder.

### Key Test Files

- `test-cors-enforcement.sh` - CORS validation
- `test-element-converters.php` - Element conversion testing
- `test-css-converter.php` - CSS conversion testing
- `test-content-conversion.php` - Content migration testing
- `test-api-comprehensive.sh` - API endpoint testing
- `test-ajax-handlers.php` - AJAX handler testing
- `test-etch-api.php` - Etch API integration
- `test-integration.php` - Integration tests
- `test-complete-migration.sh` - Complete migration flow
- `test-production-migration.sh` - Production migration testing
- `test-token-validation.sh` - Token validation testing

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

1. Check logs:
   - `npm run logs:bricks`
   - `npm run logs:etch`
2. Need direct shell access? Use `npm run shell:bricks` or `npm run shell:etch`, then run `tail -n 100 /var/www/html/wp-content/debug.log` inside the shell.
3. Run WP-CLI diagnostics:
   - `npm run wp:bricks -- b2e migration status`
   - `npm run wp:etch -- b2e migration status`
4. Verify API connection and credentials
5. Test individual migration steps if issues persist

---

## Migrator Plugin System

**Updated:** 2025-10-24 09:05

### Architecture

- **Migrator Interface (`includes/migrators/interfaces/interface-migrator.php`)** — Defines the contract for all migrators (`supports()`, `validate()`, `export()`, `import()`, `migrate()`, `get_stats()`, etc.).
- **Abstract Base (`includes/migrators/abstract-class-migrator.php`)** — Provides shared helpers for logging, plugin checks, and property storage.
- **Registry (`includes/migrators/class-migrator-registry.php`)** — Singleton responsible for registering migrators, sorting by priority, and returning supported instances.
- **Discovery (`includes/migrators/class-migrator-discovery.php`)** — Populates the registry with built-in migrators, runs hooks for third-party registration, and supports directory scanning.
- **Execution Flow:** `B2E_Migration_Service::start_migration()` resolves supported migrators via the registry and executes them sequentially with dynamic progress reporting.

### Built-in Migrators (Default Priority Order)

| Name               | Type            | Priority |
|--------------------|-----------------|----------|
| Custom Post Types  | `cpt`           | 10       |
| ACF Field Groups   | `acf`           | 20       |
| MetaBox            | `metabox`       | 30       |
| Custom Fields      | `custom_fields` | 40       |

### Extending with Custom Migrators

1. Extend `Abstract_Migrator` or implement `Migrator_Interface` directly.
2. Set `$name`, `$type`, and `$priority` within the constructor.
3. Implement interface methods (`supports`, `validate`, `export`, `import`, `migrate`, `get_stats`).
4. Register the migrator via the `b2e_register_migrators` action hook or via custom discovery logic.
5. Thoroughly test using unit/integration tests and REST inspection.

Detailed developer guidance is available in **[docs/MIGRATOR-API.md](bricks-etch-migration/docs/MIGRATOR-API.md)**.

### Hooks

```php
add_action('b2e_register_migrators', function(\Bricks2Etch\Migrators\B2E_Migrator_Registry $registry) {
    $registry->register(new My_Custom_Migrator(
        b2e_container()->get('error_handler'),
        b2e_container()->get('api_client')
    ));
});

add_filter('b2e_migrators_discovered', function(array $migrators) {
    // Modify registry entries (reorder/remove) if necessary
    return $migrators;
}, 10, 2);
```

### Registry Access

```php
$registry = b2e_container()->get('migrator_registry');
$supported = $registry->get_supported();
```

- `get_all()` — Returns all registered migrators sorted by priority.
- `get_supported()` — Filters migrators whose `supports()` returns `true`.
- `get($type)` — Retrieve a specific migrator instance.
- `count()` / `get_types()` — Introspection utilities.

### Migration Workflow

1. **Discovery (`plugins_loaded` priority 20):** Built-in migrators are registered and hooks fire for third parties.
2. **Validation:** Each migrator’s `validate()` runs before migration to gather errors or skip unsupported integrations.
3. **Execution:** Registry-driven loop in `B2E_Migration_Service` triggers `migrate()` per migrator and updates progress dynamically.
4. **Stats:** `get_stats()` results can be exposed via REST or UI for reporting.

### REST API

- `GET /b2e/v1/export/migrators` — List all registered migrators with support status.
- `GET /b2e/v1/export/migrator/{type}` — Retrieve export payload and stats for a specific migrator.

---

## Test Environment

**Updated:** 2025-10-24 00:45

### wp-env Workflow

The local development and testing stack now relies on `@wordpress/env` instead of Docker Compose. Running `npm run dev` from the plugin root spins up two WordPress instances:

- **development** (http://localhost:8888) – Bricks source site
- **tests** (http://localhost:8889) – Etch target site

Both instances mount the plugin directory directly, automatically install WordPress core, and provide integrated WP-CLI access through npm scripts.

### Configuration Files

- `.wp-env.json` – Canonical configuration for both environments (core version, PHP 7.4, required plugin/theme ZIPs, debug constants).
- `.wp-env.override.json.example` – Template for local overrides (ports, PHP version, Xdebug, extra plugins). Copy to `.wp-env.override.json` to customize without affecting version control.
- `package.json` – Defines all npm scripts used to operate the environment (`dev`, `stop`, `destroy`, `wp`, `wp:etch`, `create-test-content`, `test:connection`, `test:migration`, `debug`, etc.).
- `scripts/` – Node-based automation utilities (WordPress readiness polling, plugin activation, test content creation, migration smoke tests, debug report generation).
- `test-environment/PLUGIN-SETUP.md` – Instructions for supplying proprietary plugin/theme archives required by wp-env.

### Required Assets

Place vendor ZIPs in the test-environment folders before running `npm run dev`:

```
test-environment/
  plugins/
    bricks.2.1.2.zip
    frames-1.5.11.zip
    automatic.css-3.3.5.zip
    etch-1.0.0-alpha-5.zip
    automatic.css-4.0.0-dev-27.zip
  themes/
    bricks-child.zip
    etch-theme-0.0.2.zip
```

wp-env extracts these archives into the appropriate instance and the activation script (`npm run activate`) ensures everything is enabled.

### Operational Commands

| Command | Purpose |
| --- | --- |
| `npm run dev` | Start environments, wait for readiness, install Composer dependencies, activate plugins/themes, create Etch application password |
| `npm run stop` | Stop all wp-env containers |
| `npm run destroy` | Remove environments and data (clean reset) |
| `npm run wp [cmd]` | WP-CLI against the Bricks instance |
| `npm run wp:etch [cmd]` | WP-CLI against the Etch instance |
| `npm run create-test-content` | Seed Bricks with posts, pages, global classes, optional media |
| `npm run test:connection` | Validate REST connectivity and token handling |
| `npm run test:migration` | End-to-end migration test with progress monitoring |
| `npm run debug` | Collects diagnostics into a timestamped report |

### Testing & Documentation

- `bricks-etch-migration/TESTING.md` – Step-by-step wp-env testing plan (pre-flight, setup validation, migration smoke tests, performance checks, failure scenarios).
- `test-environment/README.md` – Overview of the wp-env workflow, credentials, troubleshooting tips, and migration checklist.
- `test-environment/PLUGIN-SETUP.md` – Detailed instructions for sourcing and installing proprietary packages.

### Legacy Docker Resources

The previous Docker Compose setup remains in `test-environment/docker-compose.yml` and `test-environment/Makefile`, but both files are explicitly marked as deprecated and retained only for reference. **All new development must use the npm/wp-env workflow described above.** The legacy Docker scripts and configuration files are no longer maintained or supported.

---

## Development Workflow

**Updated:** 2025-10-24 13:45

### Code Quality Checks

The plugin enforces code quality through CI workflows. All checks run automatically on push and pull requests:

- **PHPCS (WordPress Coding Standards)** - Enforced in CI lint job
- **PHPCompatibility** - Enforced in CI compatibility job (PHP 7.4-8.4)
- **PHPUnit** - Enforced in CI test job with WordPress test suite

### Manual Git Hooks (Optional)

While CI enforces all checks, you can optionally set up local Git hooks for faster feedback:

**Pre-commit hook** (`.git/hooks/pre-commit`):
```bash
#!/bin/bash
# Run PHPCS on staged PHP files

STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep ".php$")

if [ -z "$STAGED_FILES" ]; then
    exit 0
fi

cd bricks-etch-migration
vendor/bin/phpcs --standard=phpcs.xml.dist $STAGED_FILES

if [ $? -ne 0 ]; then
    echo "PHPCS failed. Fix errors before committing."
    exit 1
fi
```

Make it executable: `chmod +x .git/hooks/pre-commit`

**Note:** Husky is not used in this project. Manual Git hooks are optional since CI enforces all checks.

---

## References

- [CHANGELOG.md](CHANGELOG.md) - Version history
- [README.md](README.md) - Main documentation
- [bricks-etch-migration/README.md](bricks-etch-migration/README.md) - Plugin setup and wp-env workflow
- [bricks-etch-migration/TESTING.md](bricks-etch-migration/TESTING.md) - Comprehensive testing documentation
- [test-environment/README.md](test-environment/README.md) - Test environment overview

---

**Last Updated:** 2025-10-24 13:30  
**Version:** 0.8.0
