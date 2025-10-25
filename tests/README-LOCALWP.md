# Testing with LocalWP

## Setup

1. **LocalWP Sites erstellt:**
   - https://bricks.test (Bricks Builder installiert)
   - https://etch.test (Etch Theme installiert)

2. **Plugin installieren:**
   - Kopiere `etch-fusion-suite` nach `C:\Users\[YourName]\Local Sites\bricks\app\public\wp-content\plugins\`
   - Aktiviere das Plugin in beiden Sites

## Tests ausführen

### Option 1: Mit Environment Variable (empfohlen)

```powershell
# Für Bricks Site
$env:WP_PATH="C:\Users\[YourName]\Local Sites\bricks\app\public"
php C:\Github\Bricks2Etch\tests\run-local-tests.php

# Für Etch Site
$env:WP_PATH="C:\Users\[YourName]\Local Sites\etch\app\public"
php C:\Github\Bricks2Etch\tests\run-local-tests.php
```

### Option 2: Direkt aus LocalWP Site

```powershell
cd "C:\Users\[YourName]\Local Sites\bricks\app\public"
php C:\Github\Bricks2Etch\tests\run-local-tests.php
```

### Option 3: Einzelne Tests

```powershell
# Element Converter Test
$env:WP_PATH="C:\Users\[YourName]\Local Sites\bricks\app\public"
php C:\Github\Bricks2Etch\tests\test-element-converters-local.php

# AJAX Handler Test
php C:\Github\Bricks2Etch\tests\test-ajax-handlers-local.php
```

## Troubleshooting

### Plugin nicht gefunden
- Stelle sicher, dass das Plugin aktiviert ist
- Prüfe den Pfad: `wp-content/plugins/etch-fusion-suite/etch-fusion-suite.php`

### WordPress nicht gefunden
- Prüfe den WP_PATH
- Standard LocalWP Pfad: `C:\Users\[Username]\Local Sites\[sitename]\app\public`

### PHP Version
- LocalWP nutzt eigenes PHP
- Alternativ: Nutze LocalWP's "Open Site Shell" und führe dort `php` aus

## Erwartete Ausgabe

```
✅ Found WordPress at: C:\Users\...\Local Sites\bricks\app\public
✅ Etch Fusion Suite v0.11.0 loaded

============================================================
Running: test-element-converters-local.php
============================================================

=== Testing Element Converters ===

Style map loaded: 0 entries

--- Test 1: Container with ul tag ---
✅ PASS: tagName is 'ul'
✅ PASS: block.tag is 'ul'

...

============================================================
✅ All tests completed!
============================================================
```
