# Todos - Bricks to Etch Migration

**Last Updated:** 2025-10-21 23:20

---

## 🔴 High Priority

- [✅] Complete Plugin Refactoring - Phase 1 - **Completed:** 2025-10-22 00:38
  - ✅ Modulare Struktur mit separaten Element-Convertern
  - ⏳ AJAX-Handler trennen (Phase 2 - PENDING)
  - ⏳ Admin-Interface aufteilen (Phase 3 - PENDING)
  - ✅ Ein Element = Eine Datei

- [ ] Fix Custom CSS migration - CSS not being merged with existing styles - **Added:** 2025-10-21 23:20
  - Problem: `ajax_migrate_css()` not being called
  - Need to debug AJAX request flow
  - Check browser console logs

## ✅ Completed (Recent)

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
