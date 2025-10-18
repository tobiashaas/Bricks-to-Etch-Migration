# Etch AJAX Analysis

## Ziel
Herausfinden, welche AJAX-Requests Etch macht, wenn Styles gespeichert werden, um zu verstehen, ob wir etwas triggern müssen.

## Wie man das testet

### Option 1: Browser DevTools (EMPFOHLEN)

1. **Öffne Etch im Browser** (http://localhost:8081)
2. **Öffne DevTools** (F12 oder Rechtsklick → Inspect)
3. **Gehe zum Network Tab**
4. **Filter auf "Fetch/XHR"**
5. **Erstelle/Bearbeite eine Klasse**
6. **Speichere die Klasse**
7. **Schaue, welche Requests gemacht werden**

### Was wir suchen:

- **Endpoint:** z.B. `/wp-admin/admin-ajax.php` oder `/wp-json/etch/v1/...`
- **Action:** z.B. `action=etch_save_styles` oder ähnlich
- **Payload:** Welche Daten werden gesendet?
- **Response:** Was kommt zurück?

### Option 2: Docker Logs

```bash
# Terminal 1: Log Monitor starten
./monitor-etch-ajax.sh

# Terminal 2: Etch öffnen und Klasse speichern
# Schaue in Terminal 1 für Requests
```

## Mögliche Szenarien

### Szenario 1: Etch speichert automatisch
- Styles werden in `etch_styles` Option gespeichert
- Kein zusätzlicher Trigger nötig
- **Problem:** Warum werden unsere Styles nicht geladen?

### Szenario 2: Etch braucht einen Trigger
- Nach dem Speichern wird ein AJAX-Request gemacht
- Dieser Request "aktiviert" die Styles
- **Lösung:** Wir müssen diesen Request nachahmen!

### Szenario 3: Cache-Invalidierung
- Etch hat einen Cache-Mechanismus
- Wir müssen `etch_svg_version` erhöhen (MACHEN WIR SCHON!)
- Oder einen anderen Cache-Key invalidieren

## Nächste Schritte

1. **DevTools checken** - Welche Requests werden gemacht?
2. **Request nachahmen** - Können wir den gleichen Request machen?
3. **Code analysieren** - Etch-Plugin-Code nach AJAX-Handlern durchsuchen

## Bekannte Etch-Mechanismen

### Cache-Invalidierung (bereits implementiert)
```php
// In css_converter.php
$current_version = get_option('etch_svg_version', 1);
update_option('etch_svg_version', $current_version + 1);
```

### Mögliche weitere Trigger

- `do_action('etch_styles_updated')` - WordPress Action Hook?
- `wp_cache_flush()` - WordPress Cache leeren?
- Transients löschen?
- CSS-Datei neu generieren?

## Fragen an Etch-Developer

1. Gibt es einen Hook/Action nach dem Speichern von Styles?
2. Muss ein Cache manuell invalidiert werden?
3. Gibt es eine "Compile"-Funktion für Styles?
4. Werden Styles in eine CSS-Datei geschrieben oder inline ausgegeben?
