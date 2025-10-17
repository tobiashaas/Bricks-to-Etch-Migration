# CSS Migration Status

## Aktueller Stand

### ✅ Was funktioniert
- CSS-Converter-Klasse implementiert
- API-Endpoint `/import/css-classes` vorhanden
- API-Client Methode `send_css_styles()` hinzugefügt
- Migration-Manager ruft CSS-Migration auf

### 📊 Daten
- **Bricks CSS-Klassen:** 2211 (!!)
- **Etch Styles (vor Migration):** 5 (nur Standard-Element-Styles)

### 🔧 Durchgeführte Fixes

1. **Migration-Manager aktualisiert**
   - Von "simulated" zu echter API-Übertragung
   - Ruft jetzt `send_css_styles()` auf

2. **API-Client erweitert**
   - Neue Methode: `send_css_styles()`
   - Sendet an `/import/css-classes` Endpoint

---

## Bricks CSS-Struktur

### Beispiel-Klasse:
```json
{
    "id": "bTySccocilw",
    "name": "fr-feature-section-sierra",
    "settings": {
        "_rowGap": "var(--container-gap)",
        "_cssCustom": ".fr-feature-section-sierra {\n  contain: paint;\n}"
    }
}
```

### Bricks Settings-Typen:
- `_rowGap`, `_columnGap` - Flexbox gaps
- `_alignItems`, `_justifyContent` - Flexbox alignment
- `_typography` - Font settings
- `_margin`, `_padding` - Spacing
- `_cssCustom` - Custom CSS
- Responsive variants: `:mobile_landscape`, `:tablet`, etc.

---

## Konvertierungs-Herausforderungen

### 1. Bricks verwendet spezielle Prefixes
```
_rowGap → row-gap
_alignItems → align-items
_typography → font-size, line-height, etc.
```

### 2. Responsive Varianten
```
_typography:mobile_landscape → @media query
```

### 3. Custom CSS
```
_cssCustom → Direkt übernehmen
```

---

## CSS-Converter Verbesserungen nötig

### Aktuell unterstützt:
- ✅ `background` (color, image)
- ✅ `border` (width, style, color, radius)
- ✅ `typography` (fontSize, fontWeight, etc.)
- ✅ `spacing` (margin, padding)
- ✅ `_cssCustom` (custom CSS)

### Noch nicht unterstützt:
- ❌ `_rowGap`, `_columnGap` (Flexbox)
- ❌ `_alignItems`, `_justifyContent` (Flexbox)
- ❌ `_order` (Flexbox order)
- ❌ Responsive Varianten (`:mobile_landscape`, etc.)
- ❌ Viele andere Bricks-spezifische Properties

---

## Nächste Schritte

### Option 1: CSS-Converter erweitern (Empfohlen)
1. Alle Bricks-Properties mappen
2. Responsive Varianten zu Media Queries konvertieren
3. Flexbox-Properties korrekt konvertieren

### Option 2: Nur Custom CSS übernehmen
1. Nur `_cssCustom` Felder extrahieren
2. Rest ignorieren (da Bricks-spezifisch)

### Option 3: Hybrid-Ansatz
1. Wichtigste Properties konvertieren
2. `_cssCustom` immer übernehmen
3. Rest dokumentieren aber ignorieren

---

## Test-Plan

### 1. Etch-Seite bereinigen
```bash
docker exec b2e-etch wp option delete etch_styles --allow-root
docker exec b2e-etch wp option update etch_styles '{}' --format=json --allow-root
```

### 2. Migration durchführen
- Browser: Migration starten
- Monitor: `./monitor-migration.sh`

### 3. CSS prüfen
```bash
# Anzahl Styles
docker exec b2e-etch wp option get etch_styles --format=json --allow-root | python3 -c "import sys, json; print(len(json.load(sys.stdin)))"

# Sample Styles
docker exec b2e-etch wp option get etch_styles --format=json --allow-root | python3 -m json.tool | head -100
```

---

## Empfehlung

**Für Production-Ready:**

1. **CSS-Converter erweitern** um alle wichtigen Bricks-Properties
2. **Responsive Varianten** zu Media Queries konvertieren
3. **Ausführlich testen** mit den 2211 Klassen
4. **Performance** beachten (2211 Klassen sind viel!)

**Für Quick-Win:**

1. Nur `_cssCustom` übernehmen
2. Standard-Properties (background, border, typography) konvertieren
3. Rest ignorieren und dokumentieren

---

## Aktueller Code-Status

### ✅ Bereit zum Testen:
- Migration-Manager: Sendet CSS an API
- API-Client: `send_css_styles()` implementiert
- API-Endpoint: `/import/css-classes` vorhanden
- CSS-Converter: Basis-Konvertierung implementiert

### ⏳ Noch zu tun:
- CSS-Converter erweitern für alle Bricks-Properties
- Responsive Varianten konvertieren
- Performance-Optimierung für 2211 Klassen
- Ausführliche Tests

---

**Sollen wir:**
1. Den CSS-Converter jetzt erweitern?
2. Erst mal mit Basis-Konvertierung testen?
3. Nur Custom CSS übernehmen?
