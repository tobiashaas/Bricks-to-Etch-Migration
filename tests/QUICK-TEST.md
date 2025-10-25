# 🚀 Schnelltest für LocalWP

## Problem: System-PHP hat keine MySQL Extension

## ✅ Lösung: LocalWP's Site Shell nutzen

### Schritt 1: LocalWP öffnen
1. Öffne **Local by Flywheel**
2. Wähle deine **"bricks"** Site aus
3. Klicke auf **"Open Site Shell"** (rechts oben)

### Schritt 2: Plugin aktivieren
- Plugin ist bereits als Symlink vorhanden ✅
- Gehe zu https://bricks.test/wp-admin/plugins.php
- Aktiviere "Etch Fusion Suite"

### Schritt 3: Tests ausführen
```cmd
# Im LocalWP Site Shell (nutze Windows-Pfade!):
php C:\Github\Bricks2Etch\tests\run-local-tests.php
```

## 🎯 Erwartete Ausgabe

```
✅ Found WordPress at: /app/public
✅ Etch Fusion Suite v0.11.0 loaded

============================================================
Running: test-element-converters-local.php
============================================================

=== Testing Element Converters ===

--- Test 1: Container with ul tag ---
✅ PASS: tagName is 'ul'
✅ PASS: block.tag is 'ul'

--- Test 2: Div with li tag ---
✅ PASS: tagName is 'li'
✅ PASS: block.tag is 'li'

--- Test 3: Heading (h2) ---
✅ PASS: Is heading block
✅ PASS: Level is 2
✅ PASS: Text content is correct

============================================================
Running: test-ajax-handlers-local.php
============================================================

=== Testing AJAX Handlers (EFS) ===

--- Test 1: Class Loading ---
✅ PASS: EFS_Ajax_Handler class loaded
✅ PASS: EFS_Base_Ajax_Handler class loaded
...

============================================================
✅ All tests completed!
============================================================
```

## 🔧 Alternative: LocalWP PHP direkt nutzen

Falls du es außerhalb der Site Shell ausführen willst:

```powershell
# Finde LocalWP's PHP
$localPhp = "C:\Program Files (x86)\Local\resources\extraResources\lightning-services\php-8.2.10+6\bin\win64\php.exe"

# Setze WP_PATH
$env:WP_PATH="C:\Users\haast\Local Sites\bricks\app\public"

# Führe Tests aus
& $localPhp tests\run-local-tests.php
```

## ✅ Commit & Push

Wenn die Tests erfolgreich sind:
```powershell
git add tests/
git commit -m "Add LocalWP test suite"
git push
```
