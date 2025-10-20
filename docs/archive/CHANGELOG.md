# Changelog - Bricks to Etch Migration

## [0.3.9] - 2025-10-17 (20:50)

### 🐛 Critical Fix: API-Key nicht bei Migration verwendet

**Problem:** Obwohl die Token-Validierung funktionierte und den API-Key zurückgab, wurde dieser nicht bei der tatsächlichen Migration verwendet. Stattdessen wurde der Token fälschlicherweise als API-Key gesendet, was zu 401-Fehlern bei allen `/receive-post` und `/receive-media` Requests führte.

**Lösung:** 
- API-Key wird jetzt aus `sessionStorage` gelesen (wurde dort bei Token-Validierung gespeichert)
- `startMigrationProcess()` verwendet den echten API-Key statt des Tokens
- Validierung vor Migration-Start: Fehler wenn kein API-Key in sessionStorage

**Geänderte Dateien:**
- `includes/admin_interface.php` - Zeilen 542-577

---

## [0.3.8] - 2025-10-17 (20:45)

### 🎉 Major Fix: Token-Based Validation System

**Problem gelöst:** Migration Keys enthielten fälschlicherweise den Token als API-Key, was zu 401-Fehlern führte.

### ✨ Neue Features

#### Token-Validierung statt API-Key in URL
- Migration Keys enthalten jetzt nur noch `domain`, `token` und `expires`
- API-Key wird **nicht mehr** in der URL übertragen
- Sicherer und sauberer Ansatz

#### Automatische API-Key-Generierung
- API-Key wird automatisch auf der Etch-Seite generiert
- Bei Token-Validierung wird der API-Key in der Response zurückgegeben
- Bricks-Seite speichert den API-Key automatisch in sessionStorage

### 🔧 Technische Änderungen

#### Frontend (`includes/admin_interface.php`)
- **Neue AJAX-Action:** `b2e_validate_migration_token`
  - Ersetzt die fehlerhafte `b2e_validate_api_key` für Migration-Keys
  - Sendet `token`, `domain` und `expires` statt `api_key`
  - Extrahiert API-Key aus Response und speichert in sessionStorage

- **Verbesserte UI-Meldungen:**
  - "Migration token validated successfully!" statt "API key validated"
  - Zeigt Token-Ablaufzeit an
  - Klarere Fehlermeldungen

#### Backend (`includes/api_client.php`)
- **Neue Methode:** `validate_migration_token()`
  - Sendet POST-Request an `/wp-json/b2e/v1/validate`
  - Überträgt Token-Daten als JSON
  - Gibt vollständige Response mit API-Key zurück

#### API Endpoints (`includes/api_endpoints.php`)
- **Erweitert:** `validate_migration_token()`
  - Generiert automatisch API-Key falls nicht vorhanden
  - Verwendet `B2E_API_Client::create_api_key()`
  - Gibt API-Key in Response zurück
  - Logging für Debugging

### 📊 Validierungs-Flow

```
1. Etch-Seite: Migration Key generieren
   ↓
   URL: http://localhost:8081?domain=...&token=...&expires=...
   
2. Bricks-Seite: Migration Key validieren
   ↓
   AJAX: b2e_validate_migration_token
   ↓
   POST /wp-json/b2e/v1/validate
   {
     "token": "...",
     "source_domain": "...",
     "expires": 1234567890
   }
   
3. Etch-Seite: Token validieren + API-Key generieren
   ↓
   Response:
   {
     "success": true,
     "api_key": "b2e_...",
     "message": "Token validation successful",
     "target_domain": "...",
     "site_name": "...",
     "etch_active": true
   }
   
4. Bricks-Seite: API-Key speichern
   ↓
   sessionStorage.setItem('b2e_api_key', api_key)
   ↓
   ✅ Bereit für Migration
```

### 🧪 Testing

- **Automatisiertes Test-Script:** `test-token-validation.sh`
  - Generiert Token
  - Speichert in Datenbank
  - Testet Validierung
  - Verifiziert API-Key-Rückgabe

- **Manuelles Test-Script:** `test-migration-flow.sh`
  - Prüft WordPress-Sites
  - Testet API-Endpoints
  - Zeigt Test-Checkliste

### 🐛 Behobene Bugs

1. **401 Unauthorized bei Token-Validierung**
   - Ursache: Token wurde als API-Key behandelt
   - Lösung: Separater Validierungs-Endpoint mit Token-Parameter

2. **API-Key-Mismatch**
   - Ursache: Jeder Migration Key hatte anderen "API-Key" (war eigentlich Token)
   - Lösung: API-Key wird serverseitig generiert und übertragen

3. **Fehlende API-Key-Synchronisation**
   - Ursache: Keine automatische Übertragung des API-Keys
   - Lösung: API-Key in Validierungs-Response enthalten

### 📝 Migrations-Hinweise

**Für bestehende Installationen:**
1. Plugin auf Version 0.3.8 aktualisieren
2. Alte Migration Keys sind ungültig
3. Neue Migration Keys auf Etch-Seite generieren
4. Token-Validierung auf Bricks-Seite durchführen

**Wichtig:** Die alte `b2e_validate_api_key` AJAX-Action existiert noch für Kompatibilität, wird aber nicht mehr für Migration-Keys verwendet.

### 🔒 Sicherheit

- Token-Validierung mit Ablaufzeit (8 Stunden)
- API-Key wird nicht in URL übertragen
- Sichere Token-Generierung mit `wp_generate_password(64, false)`
- API-Key wird nur bei erfolgreicher Token-Validierung zurückgegeben

### 🚀 Performance

- Keine Änderungen an der Performance
- Zusätzlicher API-Call für Token-Validierung (einmalig)
- API-Key wird in sessionStorage gecacht

### 📚 Dokumentation

- `todo.md` aktualisiert mit gelöstem Problem
- Test-Scripts für automatisierte Validierung
- Detaillierte Changelog-Einträge

---

## [0.3.7] - 2025-10-16

### Vorherige Version
- Basis-Implementierung der Migration
- AJAX-Handler für verschiedene Aktionen
- REST API Endpoints
- Docker-Setup für Testing

---

**Hinweis:** Vollständige Versionshistorie in Git verfügbar.
