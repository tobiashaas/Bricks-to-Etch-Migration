# ⚠️ WICHTIG: CSS-Migration erneut durchführen!

## Problem
Das Custom CSS fehlt, weil die **CSS-Migration** noch nicht mit dem neuen Code durchgeführt wurde!

## Lösung

### 1. Cleanup durchführen
```bash
cd /Users/tobiashaas/bricks-etch-migration
./cleanup-etch.sh
```

### 2. Migration durchführen
```
http://localhost:8080/wp-admin/admin.php?page=bricks-etch-migration
```

**WICHTIG:** Alle 3 Schritte durchführen:
1. ✅ **Migrate CSS** ← WICHTIG! Hier wird Custom CSS verarbeitet
2. ✅ **Migrate Media**
3. ✅ **Migrate Content**

### 3. Prüfen
```bash
# Custom CSS prüfen
docker exec b2e-etch wp option get etch_styles --format=json --allow-root 2>/dev/null | python3 -c "import sys, json; data = json.load(sys.stdin); style = [v for k, v in data.items() if 'feature-section-frankfurt__group' in v.get('selector', '')][0]; print('CSS:', style.get('css', ''))"
```

**Erwartung:** Custom CSS sollte enthalten sein:
```css
row-gap: var(--grid-gap);
background-image: linear-gradient(var(--bg-ultra-light) 65%, transparent);
margin-block-start: calc(var(--padding) * 1);
/* Custom CSS: */
--padding: var(--space-xl);
padding: 0 var(--padding) var(--padding);
border-radius: calc(var(--radius) + var(--padding) / 2);
```

## Was wurde geändert

### css_converter.php
1. `parse_custom_css_stylesheet()` bekommt jetzt `$style_map` als Parameter
2. Sucht nach existierendem Style-ID für die Klasse
3. Verwendet diesen ID statt einen neuen zu generieren
4. Custom CSS wird mit normalem CSS zusammengeführt

### Logging
- `B2E CSS: Custom CSS stylesheet length: XXX`
- `B2E CSS: Parsing custom CSS stylesheet...`
- `B2E CSS: Found X custom styles`
- `B2E CSS: Found existing style ID XXX for custom CSS class YYY`
- `B2E CSS: Merged custom CSS for .class-name`

## Debugging

### Logs prüfen
```bash
docker exec b2e-bricks tail -200 /var/www/html/wp-content/debug.log | grep "B2E CSS"
```

**Erwartung:**
```
B2E CSS: Custom CSS stylesheet length: 500
B2E CSS: Parsing custom CSS stylesheet...
B2E CSS: Found 1 custom styles
B2E CSS: Found existing style ID abc123 for custom CSS class feature-section-frankfurt__group
B2E CSS: Merged custom CSS for .feature-section-frankfurt__group
```

### Wenn keine Logs erscheinen
- OPcache leeren: `docker restart b2e-bricks`
- Plugin neu aktivieren
- CSS-Migration erneut durchführen
