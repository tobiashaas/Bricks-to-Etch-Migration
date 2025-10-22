# Todos - Bricks to Etch Migration

**Last Updated:** 2025-10-22 21:08

---

## 🔴 High Priority

- [✅] Complete Plugin Refactoring - Phase 1 - **Completed:** 2025-10-22 00:38
  - ✅ Modulare Struktur mit separaten Element-Convertern
  - ✅ AJAX-Handler trennen (Phase 2 - COMPLETE 19:20)
  - ⏳ Admin-Interface aufteilen (Phase 3 - PENDING)
  - ✅ Ein Element = Eine Datei

- [✅] Fix Custom CSS migration - **Completed:** 2025-10-22 21:08
  - ✅ Custom CSS wird jetzt korrekt migriert
  - ✅ Nested CSS mit & Syntax funktioniert
  - ✅ Blacklist für System-Klassen implementiert
  - ✅ Alle Klassen im Stylesheet werden verarbeitet

## ✅ Completed (Recent)

- [✅] **Phase 2: AJAX-Handler Refactoring** - **Completed:** 2025-10-22 19:20
  - Modulare AJAX-Handler Struktur erstellt
  - Base AJAX Handler mit gemeinsamer Logik
  - 4 Handler-Klassen: CSS, Content, Media, Validation
  - Integration in Plugin-Hauptdatei
  - Docker URL Conversion automatisch

- [✅] **Phase 1: Element-Converter Refactoring** - **Completed:** 2025-10-22 00:38
  - Modulare Struktur mit separaten Element-Convertern erstellt
  - Factory Pattern implementiert
  - Integration in Gutenberg Generator
  - Alle Tests bestanden (Unit + Integration)
  - Cleanup-Script gefixed
  - Umfassender Refactoring-Bericht erstellt (`REFACTORING-STATUS.md`)

- [✅] Listen-Elemente (ul, ol, li) Support - **Completed:** 2025-10-21 23:40
  - Container mit custom tags werden jetzt korrekt gerendert
  - Div-Elemente mit li-Tag funktionieren
  - Frontend zeigt `<ul>` und `<li>` korrekt

---

## 🟡 Medium Priority

- [ ] Icon Element Converter - **Added:** 2025-10-22 22:45
  - Placeholder erstellt (zeigt `[Icon: library:name]`)
  - Icon Library extrahieren (FontAwesome, etc.)
  - Icon Name/Value extrahieren
  - Zu Etch Icon Format konvertieren
  - Icon Size, Color, etc. übernehmen

- [ ] Code Block Converter - **Added:** 2025-10-22 22:45
  - Aktuell übersprungen (skip_elements)
  - Code Syntax Highlighting
  - Language Detection
  - Etch hat vermutlich keinen Code Block Support

- [ ] Form Converter - **Added:** 2025-10-22 22:45
  - Aktuell übersprungen (skip_elements)
  - Etch hat keine Forms
  - Evtl. zu Contact Form 7 / Gravity Forms migrieren?

- [ ] Map Converter - **Added:** 2025-10-22 22:45
  - Aktuell übersprungen (skip_elements)
  - Etch hat keine Maps
  - Evtl. zu Google Maps Block migrieren?

- [✅] Button Element Converter - **Completed:** 2025-10-22 22:33
  - ✅ Button zu Link/Anchor konvertiert
  - ✅ Text aus `settings.text` extrahiert
  - ✅ Link aus `settings.link.url` extrahiert
  - ✅ CSS Klassen-Mapping: `primary` → `btn--primary`
  - [ ] Toggle im Admin Dashboard für Klassen-Mapping (später)

- [ ] Admin Dashboard: CSS Class Management - **Added:** 2025-10-22 20:00
  - Interface zum selbst Klassen hinzufügen/speichern
  - Blacklist Management (Klassen ausschließen)
  - Whitelist Management (Klassen erzwingen)
  - Toggle für Bricks Klassen (brxe-*, bricks-*, brx-*)
  - Toggle für WooCommerce Klassen (woocommerce-*, wc-*, product-*, etc.)
  - Toggle für Gutenberg Klassen (wp-*, wp-block-*, has-*, is-*)
  - Toggle für ACSS Framework (Automatic CSS)
  - Toggle für Core Framework
  - Blacklists für Frameworks müssen noch erstellt werden

- [ ] Test migration with production data - **Added:** 2025-10-21 23:20
- [ ] Verify all element types render correctly - **Added:** 2025-10-21 23:20
- [ ] Check responsive breakpoints - **Added:** 2025-10-21 23:20

---

## 🟢 Low Priority

- [ ] Add progress bar for CSS migration - **Added:** 2025-10-21 23:20
- [ ] Improve error messages - **Added:** 2025-10-21 23:20
- [ ] Add retry logic for failed requests - **Added:** 2025-10-21 23:20

---

## ✅ Completed

- [✅] CSS classes in frontend rendering - **Completed:** 2025-10-21 22:24
- [✅] Image rendering fix (figure instead of img) - **Completed:** 2025-10-21 22:24
- [✅] Extended style map with selectors - **Completed:** 2025-10-21 22:24
- [✅] Project cleanup and organization - **Completed:** 2025-10-21 23:20

---

## 📝 Notes

- Custom CSS migration needs investigation
- All basic features are working
- Ready for production testing

---

**Format:**
```markdown
- [ ] Task description - **Added:** YYYY-MM-DD HH:MM
- [✅] Task description - **Completed:** YYYY-MM-DD HH:MM
```
