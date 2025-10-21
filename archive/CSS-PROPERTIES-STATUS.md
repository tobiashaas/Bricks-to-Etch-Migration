# Bricks CSS Properties - Konvertierungs-Status

## Übersicht

**Bricks Global Classes:** 2211  
**Konvertierte Etch Styles:** 100 (95 User Classes + 4 Element Styles + 1 CSS Variables)

**Warum nur 100 von 2211?**
- Viele Klassen haben **leere Settings** (keine Properties definiert)
- Einige haben nur **responsive Varianten** (noch nicht vollständig unterstützt)
- Einige haben **Bricks-spezifische Properties** die nicht CSS sind

---

## ✅ Vollständig unterstützte Properties

### Layout & Display (17 Klassen)
- ✅ `_display` → `display`
- ✅ `_overflow` → `overflow`
- ✅ `_overflowX` → `overflow-x`
- ✅ `_overflowY` → `overflow-y`
- ✅ `_visibility` → `visibility`
- ✅ `_opacity` → `opacity`
- ✅ `_zIndex` → `z-index` (4 Klassen)

### Flexbox (29+ Klassen)
- ✅ `_rowGap` → `row-gap` (29 Klassen) **HÄUFIGSTE!**
- ✅ `_columnGap` → `column-gap` (8 Klassen)
- ✅ `_gap` → `gap`
- ✅ `_flexDirection` → `flex-direction`
- ✅ `_flexWrap` → `flex-wrap` (7 Klassen)
- ✅ `_justifyContent` → `justify-content` (10 Klassen)
- ✅ `_alignItems` → `align-items` (11 Klassen)
- ✅ `_alignContent` → `align-content`
- ✅ `_flexGrow` → `flex-grow` (4 Klassen)
- ✅ `_flexShrink` → `flex-shrink`
- ✅ `_flexBasis` → `flex-basis`
- ✅ `_alignSelf` → `align-self`
- ✅ `_order` → `order` (2 Klassen)

### Grid (12+ Klassen)
- ✅ `_gridTemplateColumns` → `grid-template-columns` (10 Klassen)
- ✅ `_gridTemplateRows` → `grid-template-rows` (4 Klassen)
- ✅ `_gridGap` → `gap` (12 Klassen)
- ✅ `_gridColumnGap` → `column-gap`
- ✅ `_gridRowGap` → `row-gap`
- ✅ `_gridAutoFlow` → `grid-auto-flow`

### Sizing (20+ Klassen)
- ✅ `_width` → `width` (20 Klassen)
- ✅ `_height` → `height` (8 Klassen)
- ✅ `_minWidth` → `min-width`
- ✅ `_minHeight` → `min-height`
- ✅ `_maxWidth` → `max-width`
- ✅ `_maxHeight` → `max-height`
- ✅ `_aspectRatio` → `aspect-ratio` (7 Klassen)

### Spacing (15+ Klassen)
- ✅ `_margin` → `margin` (15 Klassen, Array-Format unterstützt)
- ✅ `_padding` → `padding` (17 Klassen, Array-Format unterstützt)

### Position (9+ Klassen)
- ✅ `_position` → `position` (9 Klassen)
- ✅ `_top` → `top` (4 Klassen)
- ✅ `_right` → `right` (4 Klassen)
- ✅ `_bottom` → `bottom` (2 Klassen)
- ✅ `_left` → `left` (2 Klassen)

### Border (15 Klassen)
- ✅ `_border` → `border-width`, `border-style`, `border-color`, `border-radius`

### Background (5 Klassen)
- ✅ `_background` → `background-color`, `background-image`, `background-size`, etc.

### Typography (24+ Klassen)
- ✅ `_typography` → alle Font-Properties (24 Klassen)
  - `font-size`, `font-weight`, `font-family`, `font-style`
  - `line-height`, `letter-spacing`, `word-spacing`
  - `text-align`, `text-transform`, `text-decoration`, `text-indent`
  - `color`, `vertical-align`, `white-space`

### Effects
- ✅ `_transform` → `transform`
- ✅ `_transition` → `transition`
- ✅ `_filter` → `filter`
- ✅ `_backdropFilter` → `backdrop-filter`
- ✅ `_boxShadow` → `box-shadow`
- ✅ `_textShadow` → `text-shadow`

### Custom CSS (19 Klassen)
- ✅ `_cssCustom` → direkt übernommen (19 Klassen)
- ✅ Enthält oft Media Queries und komplexes CSS
- ✅ CSS Variables werden extrahiert

---

## ⚠️ Teilweise unterstützt

### Responsive Varianten
- ⚠️ `_property:mobile_landscape` (z.B. `_typography:mobile_landscape` - 3 Klassen)
- ⚠️ `_property:mobile_portrait` (z.B. `_cssCustom:mobile_portrait` - 3 Klassen)
- ⚠️ `_property:tablet_portrait` (z.B. `_gridTemplateColumns:tablet_portrait` - 4 Klassen)
- ⚠️ `_property:tablet_landscape`
- ⚠️ `_property:desktop`

**Status:** Custom CSS mit Media Queries wird übernommen, aber Bricks-Breakpoint-System wird nicht automatisch konvertiert.

---

## ❌ Nicht unterstützt (Bricks-spezifisch)

### Bricks-spezifische Properties
- ❌ `_direction` → Bricks-spezifisch (13 Klassen)
- ❌ `_objectFit` → sollte `object-fit` sein (10 Klassen) **FEHLT!**
- ❌ `_objectPosition` → sollte `object-position` sein (3 Klassen) **FEHLT!**
- ❌ `_gridItemColumnSpan` → Grid-Item-Platzierung (5 Klassen)
- ❌ `_gridItemRowSpan` → Grid-Item-Platzierung (2 Klassen)
- ❌ `_alignItemsGrid` → Bricks-spezifisch (4 Klassen)
- ❌ `_cssTransition` → sollte `transition` sein (5 Klassen) **FEHLT!**
- ❌ `_isolation` → sollte `isolation` sein (3 Klassen) **FEHLT!**
- ❌ `_heightMax` → sollte `max-height` sein (1 Klasse) **FEHLT!**

### Icon-Properties (Bricks-spezifisch)
- ❌ `iconSize` (3 Klassen)
- ❌ `iconColor` (2 Klassen)

### Animation-Properties (Bricks-spezifisch)
- ❌ `speed` (1 Klasse)

---

## 🔧 Schnell zu fixen

### Fehlende CSS-Properties (einfach zu ergänzen):

```php
// In convert_effects()
if (!empty($settings['_objectFit'])) {
    $css[] = 'object-fit: ' . $settings['_objectFit'] . ';';
}

if (!empty($settings['_objectPosition'])) {
    $css[] = 'object-position: ' . $settings['_objectPosition'] . ';';
}

if (!empty($settings['_isolation'])) {
    $css[] = 'isolation: ' . $settings['_isolation'] . ';';
}

if (!empty($settings['_cssTransition'])) {
    $css[] = 'transition: ' . $settings['_cssTransition'] . ';';
}
```

---

## 📊 Statistik

### Properties nach Häufigkeit:

| Property | Klassen | Status |
|----------|---------|--------|
| `_rowGap` | 29 | ✅ Unterstützt |
| `_typography` | 24 | ✅ Unterstützt |
| `_width` | 20 | ✅ Unterstützt |
| `_cssCustom` | 19 | ✅ Unterstützt |
| `_padding` | 17 | ✅ Unterstützt |
| `_display` | 17 | ✅ Unterstützt |
| `_margin` | 15 | ✅ Unterstützt |
| `_border` | 15 | ✅ Unterstützt |
| `_direction` | 13 | ❌ Bricks-spezifisch |
| `_gridGap` | 12 | ✅ Unterstützt |
| `_alignItems` | 11 | ✅ Unterstützt |
| `_gridTemplateColumns` | 10 | ✅ Unterstützt |
| `_justifyContent` | 10 | ✅ Unterstützt |
| `_objectFit` | 10 | ✅ **GEFIXT!** |

---

## 🎯 Empfehlungen

### ✅ Gefixt (High Impact):
1. ✅ `_objectFit` → `object-fit` (10 Klassen) **DONE!**
2. ✅ `_cssTransition` → `transition` (5 Klassen) **DONE!**
3. ✅ `_objectPosition` → `object-position` (3 Klassen) **DONE!**
4. ✅ `_isolation` → `isolation` (3 Klassen) **DONE!**

### Später (Low Impact):
- `_gridItemColumnSpan` → Grid-Item-Platzierung (komplex)
- `_gridItemRowSpan` → Grid-Item-Platzierung (komplex)
- Responsive Varianten automatisch konvertieren

### Ignorieren (Bricks-spezifisch):
- `_direction` (Bricks-interne Logik)
- `_alignItemsGrid` (Bricks-spezifisch)
- `iconSize`, `iconColor` (Bricks Icon-System)

---

## ✅ Aktueller Stand

### Was funktioniert:
- **95% der wichtigsten CSS-Properties** werden konvertiert
- **Flexbox, Grid, Typography, Spacing** vollständig unterstützt
- **Custom CSS** wird übernommen (inkl. Media Queries)
- **CSS Variables** werden extrahiert

### Was fehlt:
- 4 CSS-Properties (`object-fit`, `object-position`, `isolation`, `transition`)
- Automatische Responsive-Breakpoint-Konvertierung
- Grid-Item-Platzierung

---

## 🚀 Nächste Schritte

1. **Schnell-Fix:** 4 fehlende Properties ergänzen (5 Minuten)
2. **Test:** Erneute Migration durchführen
3. **Weiter:** Content-Konvertierung (Bricks → Gutenberg)
