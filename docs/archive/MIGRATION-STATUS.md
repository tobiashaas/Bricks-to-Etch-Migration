# Migration Status & Troubleshooting

## ✅ Was funktioniert:

1. **CSS-Konvertierung:** 105 Styles werden korrekt generiert
2. **Content-Parsing:** Bricks-Elemente werden erkannt
3. **Hierarchie:** Verschachtelung funktioniert
4. **Klassennamen:** Werden korrekt extrahiert (nicht mehr IDs)
5. **JSON-Encoding:** Keine Unicode-Escapes mehr

## ❌ Aktuelles Problem:

**CSS wird nicht auf Etch-Seite gespeichert!**

- Bricks: 2211 Klassen vorhanden
- Etch: Nur 5 Default-Styles (keine User-Klassen)

## 🔍 Mögliche Ursachen:

### 1. Migration wurde nicht vollständig durchgeführt
**Prüfen:**
- Wurde der CSS-Schritt in der Migration UI angezeigt?
- Gab es Fehler-Meldungen?

### 2. API-Übertragung schlägt fehl
**Prüfen:**
```bash
# Auf Bricks-Seite (Source):
docker exec b2e-bricks wp option get b2e_error_log --format=json --allow-root | grep -i css

# Auf Etch-Seite (Target):
docker exec b2e-etch wp option get b2e_error_log --format=json --allow-root | grep -i css
```

### 3. Etch-Seite speichert Styles nicht
**Prüfen:**
```bash
# Manuell CSS senden:
curl -X POST http://localhost:8081/wp-json/bricks-etch-migration/v1/import/css-classes \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"test": {"type": "class", "selector": ".test", "css": "color: red;"}}'
```

## 🚀 Nächste Schritte:

### Option A: Migration erneut starten
1. Etch bereinigen: `./cleanup-etch.sh`
2. Neuen API-Key generieren
3. Migration im Browser starten
4. **Genau beobachten:** Wird CSS-Schritt ausgeführt?

### Option B: CSS manuell migrieren
```bash
# Test-Skript auf Bricks ausführen:
docker exec b2e-bricks php /var/www/html/test-css-migration.php

# Dann manuell auf Etch importieren (wenn API funktioniert)
```

### Option C: Debug-Modus aktivieren
```php
// In migration_manager.php, Zeile 137:
error_log('CSS Migration Result: ' . print_r($css_result, true));
```

## 📊 Erwartete Ergebnisse nach erfolgreicher Migration:

```bash
docker exec b2e-etch wp option get etch_styles --format=json --allow-root | \
  python3 -c "import sys, json; data=json.load(sys.stdin); print(f'Total: {len(data)}')"
```

**Sollte zeigen:** ~105 Styles (nicht nur 5!)

## 🐛 Bekannte Bugs (bereits gefixt):

1. ✅ `_bricks_page_content_2` Support
2. ✅ Klassennamen statt IDs
3. ✅ Custom CSS bereinigt
4. ✅ Hierarchische Verschachtelung
5. ✅ `text-basic` Paragraphen
6. ✅ Klassen im HTML + className
7. ✅ JSON ohne Unicode-Escaping

## 💡 Warum Etch die Klassen nicht anzeigt:

**Etch benötigt die Styles in `etch_styles`!**

Wenn die Klassen im HTML sind (`class="content-grid"`), aber nicht in `etch_styles`, dann:
- ❌ Etch kann das CSS nicht finden
- ❌ Keine Styles werden angewendet
- ⚠️ Du musst die Klasse manuell ändern, damit Etch sie "registriert"

**Lösung:** CSS-Migration muss erfolgreich laufen!
