# Production Migration Test - Manuelle Anleitung

## Status: Bereit zum Testen! 🚀

### ✅ Vorbereitung abgeschlossen
- Etch-Seite bereinigt (0 Posts, 0 Pages, 0 Media)
- Bricks-Seite bereit (17 Posts, 8 Pages, 19 Media)
- API getestet (96% Pass Rate)

---

## Schritt-für-Schritt Anleitung

### 1. Migration Key generieren (Etch-Seite)

**Browser öffnen:** http://localhost:8081/wp-admin

1. Login: admin / admin
2. Menü → **B2E Migration** → **Etch Site**
3. Klick auf **"Generate Migration Key"**
4. Key kopieren (Strg+C / Cmd+C)

**Erwartetes Format:**
```
http://localhost:8081?domain=http://localhost:8081&token=...&expires=...
```

---

### 2. Migration starten (Bricks-Seite)

**Browser öffnen:** http://localhost:8080/wp-admin

1. Login: admin / admin
2. Menü → **B2E Migration**
3. Migration Key einfügen (Strg+V / Cmd+V)
4. Klick auf **"🔗 Validate Key"**
   - Warte auf: ✅ "Migration token validated successfully!"
5. Klick auf **"🚀 Start Migration"**

---

### 3. Progress überwachen

**Terminal öffnen:**
```bash
cd /Users/tobiashaas/bricks-etch-migration
./monitor-migration.sh
```

**Erwartete Ausgabe:**
```
[HH:MM:SS] 🔄 Status: running | Progress: 10% | Step: validation
[HH:MM:SS] 🔄 Status: running | Progress: 20% | Step: analyzing
[HH:MM:SS] 🔄 Status: running | Progress: 30% | Step: cpts
...
[HH:MM:SS] ✅ Status: completed | Progress: 100% | Step: completed
```

---

### 4. Ergebnisse verifizieren

**Nach Abschluss:**
```bash
./verify-migration.sh
```

**Erwartete Ergebnisse:**
```
Bricks Site (Source):
  Posts: 17
  Pages: 8
  Media: 19

Etch Site (Target):
  Posts: 17
  Pages: 8
  Media: 0-19 (abhängig von Media-Migration)

✅ Posts: Migration successful (17 >= 17)
✅ Pages: Migration successful (8 >= 8)
```

---

### 5. Content prüfen (Etch-Seite)

**Browser:** http://localhost:8081/wp-admin

1. Menü → **Posts** → **All Posts**
   - Prüfe: Sind alle 17 Posts da?
   - Prüfe: Sind Titel korrekt?
   - Öffne einen Post: Ist Content lesbar?

2. Menü → **Pages** → **All Pages**
   - Prüfe: Sind alle 8 Pages da?
   - Prüfe: Sind Titel korrekt?
   - Öffne eine Page: Ist Content lesbar?

3. Menü → **Media** → **Library**
   - Prüfe: Wie viele Media-Dateien sind da?

---

## Erfolgs-Kriterien

### ✅ Migration erfolgreich wenn:
- Alle Posts übertragen (17/17)
- Alle Pages übertragen (8/8)
- Migration Status: Completed
- Keine Fehler im Monitor
- Content ist auf Etch-Seite sichtbar

### ⚠️ Bekannte Einschränkungen:
- Content ist noch nicht konvertiert (Bricks → Gutenberg)
- CSS ist noch nicht konvertiert
- Media-Migration funktioniert API-seitig, aber Integration noch zu testen

---

## Troubleshooting

### Problem: Token-Validierung schlägt fehl
```bash
# API-Test
curl -X POST "http://localhost:8081/wp-json/b2e/v1/generate-key" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Problem: Migration startet nicht
```bash
# Logs prüfen
docker logs b2e-bricks --tail=50
docker logs b2e-etch --tail=50
```

### Problem: Progress hängt
```bash
# Migration-Status prüfen
docker exec b2e-bricks wp option get b2e_migration_progress --format=json --allow-root
```

---

## Nach dem Test

### Wenn erfolgreich:
1. ✅ Screenshots machen
2. ✅ Ergebnisse dokumentieren
3. ✅ Nächste Schritte planen:
   - Content-Konvertierung implementieren
   - CSS-Konvertierung implementieren
   - Media-Migration vollständig integrieren

### Wenn Probleme auftreten:
1. Logs sammeln
2. Fehler dokumentieren
3. Debugging starten

---

## Quick Commands

```bash
# Status prüfen
./verify-migration.sh

# Migration überwachen
./monitor-migration.sh

# Etch-Seite bereinigen (für erneuten Test)
docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=post --format=ids --allow-root) --force --allow-root
docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=page --format=ids --allow-root) --force --allow-root
docker exec b2e-etch wp post delete $(docker exec b2e-etch wp post list --post_type=attachment --format=ids --allow-root) --force --allow-root
```

---

**Bereit zum Testen! 🚀**

Öffne jetzt:
1. http://localhost:8081/wp-admin (Etch - Key generieren)
2. http://localhost:8080/wp-admin (Bricks - Migration starten)
3. Terminal: `./monitor-migration.sh`
