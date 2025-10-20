# Etch Style-Map Implementation Guide

## Übersicht

Dieses Dokument beschreibt, wie die Style-Map-Funktionalität für Etch implementiert wurde, um CSS-Styles korrekt zwischen Bricks und Etch zu migrieren.

---

## Problem

Bricks und Etch verwenden unterschiedliche ID-Systeme für CSS-Klassen:
- **Bricks:** `bTySccocilw` (interne IDs)
- **Etch:** `83573d3` (generiert mit `uniqid()`)

Ohne Mapping stimmen die IDs im Content nicht mit den IDs in `etch_styles` überein → CSS wird nicht gerendert.

---

## Lösung: Style-Map

Eine Mapping-Tabelle, die Bricks-IDs zu Etch-IDs zuordnet:

```php
[
    'bTySccocilw' => '83573d3',  // Bricks ID → Etch ID
    'bTyScxgmzei' => '8357aa6'
]
```

---

## Implementation

### 1. CSS-Konvertierung (css_converter.php)

**ID-Generierung:**
```php
// Generiere Etch-kompatible ID
$style_id = substr(uniqid(), -7);

// Speichere Mapping
$style_map[$bricks_class['id']] = $style_id;

// Speichere Style mit ID als Key
$etch_styles[$style_id] = $converted_class;
```

**Rückgabe:**
```php
return array(
    'styles' => $etch_styles,      // Array mit allen Styles
    'style_map' => $style_map      // Mapping-Tabelle
);
```

### 2. API-Endpoint (api_endpoints.php)

**Empfangen und Speichern:**
```php
public function import_etch_styles($data) {
    // Extrahiere beide Arrays
    $etch_styles = $data['styles'] ?? array();
    $style_map = $data['style_map'] ?? array();
    
    // Speichere Styles
    update_option('etch_styles', $etch_styles);
    
    // Speichere Style-Map
    update_option('b2e_style_map', $style_map);
}
```

**API-Response:**
```php
return new WP_REST_Response(array(
    'message' => 'Styles updated',
    'style_map' => $style_map  // Zurück an Bricks-Seite
), 200);
```

### 3. Content-Migration (gutenberg_generator.php)

**Style-IDs aus Map holen:**
```php
private function get_element_style_ids($element) {
    $style_ids = array();
    
    // Hole Style-Map
    $style_map = get_option('b2e_style_map', array());
    
    // Prüfe _cssGlobalClasses
    if (isset($element['settings']['_cssGlobalClasses'])) {
        foreach ($element['settings']['_cssGlobalClasses'] as $bricks_id) {
            // Lookup in Map
            if (isset($style_map[$bricks_id])) {
                $style_ids[] = $style_map[$bricks_id];
            }
        }
    }
    
    return $style_ids;
}
```

**Verwendung:**
```php
// In generate_etch_group_block()
$style_ids = $this->get_element_style_ids($element);

// Füge in Block-Metadata ein
$metadata = array(
    'etchData' => array(
        'styles' => $style_ids  // Etch-IDs!
    )
);
```

---

## Datenfluss

```
1. CSS-Migration (Bricks-Seite)
   ├─ Generiere Etch-IDs mit uniqid()
   ├─ Erstelle Style-Map: Bricks-ID → Etch-ID
   └─ Sende {styles, style_map} an Etch API

2. Etch API (Etch-Seite)
   ├─ Empfange {styles, style_map}
   ├─ Speichere in etch_styles
   ├─ Speichere in b2e_style_map
   └─ Gebe style_map zurück

3. Bricks-Seite
   ├─ Empfange style_map aus API-Response
   └─ Speichere in b2e_style_map (lokal)

4. Content-Migration (Bricks-Seite)
   ├─ Lese b2e_style_map
   ├─ Lookup: Bricks-ID → Etch-ID
   └─ Füge Etch-IDs in Content ein
```

---

## Wichtige Punkte

### IDs müssen als Keys verwendet werden
```php
// ✅ RICHTIG
$etch_styles[$style_id] = $style_data;

// ❌ FALSCH
$etch_styles[] = $style_data;  // Numerische Indizes!
```

**Warum?** Etch überschreibt existierende IDs nicht. Wenn keine ID vorhanden ist, generiert Etch eine neue → IDs stimmen nicht überein.

### Style-Map muss übertragen werden
```php
// ✅ RICHTIG - Beide Arrays senden
$api_client->send_css_styles($url, $api_key, array(
    'styles' => $etch_styles,
    'style_map' => $style_map
));

// ❌ FALSCH - Nur Styles senden
$api_client->send_css_styles($url, $api_key, $etch_styles);
```

### Richtige Funktion verwenden
```php
// ✅ RICHTIG - Nutzt Style-Map
$style_ids = $this->get_element_style_ids($element);

// ❌ FALSCH - Generiert MD5-Hashes
$style_ids = $this->extract_style_ids($element['settings']);
```

---

## Verifizierung

### 1. Style-Map prüfen
```bash
docker exec b2e-bricks wp option get b2e_style_map --format=json --allow-root
```

**Erwartetes Ergebnis:**
```json
{
  "bTySccocilw": "83573d3",
  "bTyScxgmzei": "8357aa6"
}
```

### 2. Content-IDs prüfen
```bash
docker exec b2e-etch wp post get 2042 --field=post_content --allow-root | grep -o '"styles":\[[^]]*\]'
```

**Erwartetes Ergebnis:**
```
"styles":["83573d3","8357aa6"]
```

### 3. etch_styles prüfen
```bash
docker exec b2e-etch wp option get etch_styles --format=json --allow-root | jq '.["83573d3"]'
```

**Erwartetes Ergebnis:**
```json
{
  "selector": ".hero-barcelona",
  "type": "class",
  "css": "..."
}
```

### 4. IDs müssen übereinstimmen
- IDs im Content: `83573d3`, `8357aa6`
- IDs in etch_styles: `83573d3`, `8357aa6` ✅
- Selectors vorhanden: `.hero-barcelona` ✅

---

## Häufige Fehler

### Fehler 1: IDs stimmen nicht überein
**Symptom:** Content hat `7b5a2e3`, etch_styles hat `83573d3`

**Ursache:** Alte Funktion `extract_style_ids()` wird verwendet

**Lösung:** Nutze `get_element_style_ids()` mit Style-Map

### Fehler 2: Style-Map ist leer
**Symptom:** `b2e_style_map` hat 0 Einträge

**Ursache:** Style-Map wird nicht in API-Response zurückgegeben

**Lösung:** Füge `'style_map' => $style_map` zur Response hinzu

### Fehler 3: Selectors sind null
**Symptom:** `"selector": null` in etch_styles

**Ursache:** Etch API überschreibt Daten falsch

**Lösung:** Nutze direktes `update_option()` statt Etch API

---

## Code-Referenz

### Geänderte Dateien
1. `bricks-etch-migration/includes/css_converter.php`
2. `bricks-etch-migration/includes/api_endpoints.php`
3. `bricks-etch-migration/includes/admin_interface.php`
4. `bricks-etch-migration/includes/gutenberg_generator.php`

### Wichtige Funktionen
- `convert_bricks_classes_to_etch()` - Erstellt Style-Map
- `import_etch_styles()` - Speichert Style-Map
- `get_element_style_ids()` - Nutzt Style-Map
- `generate_etch_group_block()` - Fügt IDs in Content ein

---

## Zusammenfassung

**3 Schritte für korrekte Style-Migration:**

1. **Generiere IDs mit `uniqid()`** - Kompatibel mit Etch
2. **Erstelle und übertrage Style-Map** - Bricks-ID → Etch-ID
3. **Nutze Style-Map bei Content-Migration** - Konsistente IDs

**Ergebnis:** CSS wird im Frontend korrekt gerendert! ✅
