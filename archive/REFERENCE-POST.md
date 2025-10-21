# Referenz-Post für Etch-Migration

## 📌 Post-Informationen

- **Post ID:** 3411
- **Titel:** Claude Test
- **Slug:** `/claude-test/`
- **URL:** http://localhost:8081/claude-test/
- **Typ:** Native Etch-Seite

## 🎯 Zweck

Dieser Post dient als **Referenz** für die korrekte Etch-Struktur:
- ✅ Zeigt, wie Etch native Posts strukturiert
- ✅ Enthält `.test-*` Klassen zum Testen
- ✅ Wird vom Cleanup-Skript **NICHT** gelöscht
- ✅ Styles bleiben erhalten

## 🔍 Struktur-Beispiel

### Block-Attribute (aus DB):
```json
{
  "metadata": {
    "name": "Heading",
    "etchData": {
      "origin": "etch",
      "name": "Heading",
      "styles": ["q4ghb8n"],
      "attributes": {
        "class": "test-heading"  // ← WICHTIG!
      },
      "block": {
        "type": "html",
        "tag": "h2"
      }
    }
  }
}
```

### Frontend-HTML:
```html
<h2 class="test-heading">Insert your heading here…</h2>
```

## ✅ Was funktioniert

1. **Style-IDs in etchData.styles** - Für CSS-Generierung
2. **CSS-Klassen in etchData.attributes.class** - Für Frontend-Rendering
3. **CSS wird generiert** - Im `<head>` als `<style id="etch-page-styles">`
4. **Klassen werden gerendert** - Im Frontend-HTML

## 🔧 Cleanup-Verhalten

Das `cleanup-etch.sh` Skript:
- ✅ **Behält** Post 3411
- ✅ **Behält** `etch_styles` Option (für die Klassen)
- ✅ Löscht alle anderen Posts
- ✅ Löscht `b2e_style_map` (wird bei Migration neu erstellt)

## 📊 Vergleich: Migriert vs. Native

| Aspekt | Migrierte Posts | Native Etch (3411) |
|--------|----------------|-------------------|
| etchData.styles | ✅ Style-IDs | ✅ Style-IDs |
| etchData.attributes.class | ✅ CSS-Klassen | ✅ CSS-Klassen |
| Frontend-Rendering | ✅ Sollte funktionieren | ✅ Funktioniert |
| CSS-Generierung | ✅ Funktioniert | ✅ Funktioniert |

## 🧪 Test-Klassen

Der Referenz-Post enthält folgende Test-Klassen:
- `.test-section` - Section-Element
- `.test-container` - Container-Element
- `.test-heading` - Heading-Element
- `.test-text` - Text/Paragraph-Element

## 📝 Verwendung

### Struktur prüfen:
```bash
docker exec b2e-etch wp post get 3411 --field=post_content --allow-root | head -50
```

### Frontend prüfen:
```bash
curl -s 'http://localhost:8081/claude-test/' | grep -o '<h2[^>]*>.*</h2>'
```

### Mit migriertem Post vergleichen:
```bash
./compare-posts.sh
# Native Post ID: 3411
# Migrierter Post ID: 3388
```

## 🎉 Wichtige Erkenntnis

**Etch rendert CSS-Klassen aus `etchData.attributes.class`, NICHT aus den Style-IDs!**

Die Style-IDs in `etchData.styles` werden nur verwendet, um:
1. Das CSS im `<head>` zu generieren
2. Die Styles im Etch-Editor anzuzeigen

Aber die **tatsächlichen CSS-Klassen im HTML** kommen aus `etchData.attributes.class`!

---

**Erstellt:** 21. Oktober 2025, 20:47 Uhr
**Status:** ✅ Permanent (wird nicht gelöscht)
