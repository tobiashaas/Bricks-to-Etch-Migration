# CSS Frontend Rendering - Aktueller Stand

**Datum:** 20. Oktober 2025, 10:30 Uhr  
**Status:** 🟡 In Arbeit - className-Problem gelöst, Selector-Bug wird debuggt

---

## 🎯 Ziel

CSS-Styles sollen im Frontend gerendert werden, nachdem Posts von Bricks zu Etch migriert wurden.

---

## ✅ Was funktioniert

### 1. ID-Generierung
- ✅ Nutzt jetzt `substr(uniqid(), -7)` wie Etch (statt MD5)
- ✅ IDs haben das richtige Format (7 Zeichen, z.B. `7b5a2e3`)
- ✅ IDs werden in den Gutenberg-Blöcken gespeichert

### 2. Style-Map
- ✅ Wird während CSS-Migration erstellt
- ✅ Mappt Bricks-IDs zu Etch-IDs korrekt
- ✅ Wird für Content-Migration verwendet

### 3. Content-Migration
- ✅ Generiert neue Gutenberg-Blöcke
- ✅ Fügt Style-IDs in `etchData.styles` Arrays ein
- ✅ Klassennamen werden korrekt extrahiert (auch aus Strings)
- ✅ **WICHTIG:** Nutzt `metadata.etchData.styles` statt `className` (Entwickler-Info)
- ✅ Entfernt `className` und `attributes.class` aus Blöcken

### 4. CSS-Konvertierung
- ✅ Bricks Global Classes werden konvertiert
- ✅ CSS-Properties werden korrekt umgewandelt
- ✅ Responsive Breakpoints funktionieren
- ✅ Selectors werden korrekt generiert (`.klassenname`)

---

## ❌ Das Problem

### Symptom
CSS-Styles werden **nicht im Frontend gerendert**, obwohl:
- Posts migriert sind
- Style-IDs im Content vorhanden sind
- Styles in `etch_styles` gespeichert sind

### Root Cause 1: className statt etchData.styles (✅ GELÖST)
**Problem:** Klassen wurden über Gutenberg `className` hinzugefügt, nicht über `etchData.styles`!

**Entwickler-Info (20.10.2025):**
> "Wie fügst die die klassen zu den blöcken hinzu? Wenn du das über den Gutenberg 'className' machst funktioniert das leider nicht.
> Die klassen müssten mit ihrer Unique ID in block.attr.metadata.etchData.styles = ["unique-Id-hier", "unique-ID-von-class-2"]"

**Lösung:**
- ❌ NICHT: `{"className": "hero-barcelona bg--ultra-dark"}`
- ✅ RICHTIG: `{"metadata": {"etchData": {"styles": ["7b5a2e3", "8ff1c7f"]}}}`

### Root Cause 2: Selectors in etch_styles sind null (🔍 IN ARBEIT)
**Die Selectors in `etch_styles` sind `null`!**

```json
{
  "8f166f7": {
    "type": "class",
    "selector": null,  // ❌ Sollte ".klassenname" sein!
    "css": "...",
    "readonly": false
  }
}
```

### Warum das ein Problem ist
Etch's `StylesRegister` kann die Styles nicht rendern, weil:
1. Die Style-IDs werden aus dem Content gelesen
2. Die Styles werden in `etch_styles` gesucht
3. Aber der `selector` ist `null` → CSS kann nicht generiert werden

---

## 🔍 Debugging-Erkenntnisse

### Was wir getestet haben

1. **ID-Format:** ✅ Korrekt (uniqid statt MD5)
2. **Content-Generierung:** ✅ Funktioniert (neue IDs bei jeder Migration)
3. **Style-Map:** ✅ Wird erstellt und verwendet
4. **CSS-Converter:** ✅ Generiert Selectors korrekt (`.klassenname`)
5. **className vs etchData.styles:** ✅ **GELÖST** - Nutzt jetzt etchData.styles
6. **Etch API:** ❓ **Hier liegt das Problem!**

### Der Bug

**Vor API-Call (im PHP-Code):**
```php
$etch_styles['8f166f7'] = [
    'type' => 'class',
    'selector' => '.fr-intro-alpha',  // ✅ Korrekt!
    'css' => '...',
    'readonly' => false
];
```

**Nach API-Call (in Datenbank):**
```json
{
  "8f166f7": {
    "type": "class",
    "selector": null,  // ❌ Wurde zu null!
    "css": "...",
    "readonly": false
  }
}
```

### Vermutung

Die Etch API (`StylesRoutes::update_styles()`) macht irgendwas mit den Daten, das die Selectors auf `null` setzt.

Mögliche Ursachen:
1. JSON-Encoding/Decoding Problem
2. Unicode-Escape-Sequenzen werden falsch verarbeitet
3. Validation-Logic überschreibt Selectors
4. Array-Merge überschreibt mit alten Daten

---

## 📝 Nächste Schritte

### 1. className-Problem (✅ GELÖST)
- [x] Entwickler-Info erhalten über etchData.styles
- [x] `className` aus allen Block-Attributen entfernt
- [x] `attributes.class` aus `etchData.attributes` entfernt
- [x] Nur noch `metadata.etchData.styles` mit Style-IDs verwendet

### 2. Etch API Debug (PRIORITÄT 1)
- [x] Logging hinzugefügt (BEFORE/AFTER API call)
- [ ] JSON-Encoding/Decoding testen
- [ ] Prüfen, was mit den Selectors passiert
- [ ] Testen, ob direktes `update_option()` funktioniert (ohne API)

### 3. Workaround testen
- [ ] CSS-Migration ohne Etch API durchführen
- [ ] Direkt `update_option('etch_styles', $styles)` verwenden
- [ ] Prüfen, ob Selectors dann erhalten bleiben

### 4. Alternative Ansätze
- [ ] Styles nach API-Call nochmal updaten (Selectors nachträglich setzen)
- [ ] Eigene API-Route erstellen, die Selectors nicht überschreibt
- [ ] Etch Plugin-Code patchen (falls nötig)

---

## 🗂️ Betroffene Dateien

### Haupt-Dateien
- `css_converter.php` - CSS-Konvertierung und Import
- `gutenberg_generator.php` - Content-Generierung mit Style-IDs
- `api_endpoints.php` - API-Endpunkte auf Etch-Seite

### Wichtige Funktionen
- `css_converter.php::convert_bricks_classes_to_etch()` - Konvertiert Bricks Classes
- `css_converter.php::import_etch_styles()` - Importiert via Etch API ⚠️ **HIER IST DER BUG**
- `gutenberg_generator.php::get_element_style_ids()` - Findet Style-IDs für Content

---

## 🧪 Test-Kommandos

### Styles in DB prüfen
```bash
docker exec b2e-etch wp option get etch_styles --format=json --allow-root | jq 'to_entries | .[0:5] | .[] | {key, selector, type}'
```

### Post-Content prüfen
```bash
docker exec b2e-etch wp post get POST_ID --field=post_content --allow-root | grep -o '"styles":\[[^]]*\]' | head -5
```

### Style-Map prüfen (Bricks-Seite)
```bash
docker exec b2e-bricks wp option get b2e_style_map --format=json --allow-root | jq '. | to_entries | .[0:5]'
```

### Debug-Logs anschauen
```bash
docker exec b2e-bricks tail -100 /var/www/html/wp-content/debug.log | grep "B2E:"
```

---

## 💡 Erkenntnisse für die Zukunft

### Was wir gelernt haben

1. **Etch nutzt `uniqid()` für IDs**, nicht MD5-Hashes
2. **Bricks nutzt `_cssClasses` als String**, nicht als Array
3. **Style-IDs müssen im Content UND in etch_styles sein**
4. **Etch nutzt `metadata.etchData.styles`, NICHT `className`!** ⭐ WICHTIG!
5. **Etch API kann Daten verändern** - direktes `update_option()` ist sicherer
6. **OpCache muss geleert werden** nach Code-Änderungen (Container-Restart)

### Best Practices

1. **Immer Container neu starten** nach Code-Änderungen
2. **Style-Map löschen** vor neuer CSS-Migration (`b2e_style_map`)
3. **Etch cleanup** vor jeder Test-Migration
4. **Debug-Logging** ist essentiell für komplexe Bugs
5. **Etch-Code lesen** um zu verstehen, wie es intern funktioniert

---

## 📊 Statistiken

- **Debugging-Zeit:** ~8 Stunden
- **Identifizierte Bugs:** 6
  1. MD5 statt uniqid für IDs (✅ gelöst)
  2. _cssClasses als Array statt String behandelt (✅ gelöst)
  3. Style-Map wurde nicht verwendet (✅ gelöst)
  4. className statt etchData.styles verwendet (✅ gelöst)
  5. Selectors werden zu null (🔍 in Arbeit)
  6. OpCache-Problem (✅ gelöst)
- **Code-Änderungen:** 8 Dateien
- **Tests durchgeführt:** ~30 Migrationen

---

## 🎯 Erfolgsmetriken

**Migration gilt als erfolgreich, wenn:**
- [ ] Posts sind migriert
- [ ] Style-IDs sind im Content (`etchData.styles`)
- [ ] Styles sind in `etch_styles` mit korrekten Selectors
- [ ] CSS wird im Frontend `<head>` gerendert
- [ ] Seiten sehen korrekt gestylt aus

**Aktueller Status:** 3/5 ✅ (60%)

---

**Letztes Update:** 20. Oktober 2025, 10:30 Uhr  
**Nächster Schritt:** JSON-Encoding/Decoding testen, Selector-Bug fixen
