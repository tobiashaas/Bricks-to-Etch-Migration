# 🔄 Migration Flow: Bricks → Etch

## Technische Dokumentation der Datenübertragung

Diese Dokumentation beschreibt **exakt**, wie Daten von Bricks zu Etch übertragen werden.

---

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Architektur](#architektur)
3. [Migration Flow](#migration-flow)
4. [API-Kommunikation](#api-kommunikation)
5. [Datenstrukturen](#datenstrukturen)
6. [Module & Verantwortlichkeiten](#module--verantwortlichkeiten)

---

## 🎯 Übersicht

### Grundprinzip

```
┌─────────────┐                    ┌─────────────┐
│   BRICKS    │  ─── REST API ───> │    ETCH     │
│  (Quelle)   │                    │   (Ziel)    │
└─────────────┘                    └─────────────┘
```

**Ablauf:**
1. Bricks liest eigene Daten aus
2. Konvertiert Bricks-Format → Etch-Format
3. Sendet via REST API zu Etch
4. Etch empfängt und speichert Daten

---

## 🏗️ Architektur

### Zentrale Module

```
┌─────────────────────────────────────────────┐
│  B2E_API_Service (Singleton)                │
│  - Zentrale API-Kommunikation               │
│  - URL-Konvertierung (Docker)               │
│  - API-Key-Verwaltung                       │
└─────────────────────────────────────────────┘
                    ▲
                    │ nutzt
        ┌───────────┼───────────┐
        │           │           │
┌───────▼─────┐ ┌──▼──────┐ ┌──▼──────────┐
│ Migration   │ │  Media  │ │    CPT      │
│  Manager    │ │Migrator │ │  Migrator   │
└─────────────┘ └─────────┘ └─────────────┘
```

### Kommunikations-Stack

```
Bricks Plugin
    ↓
B2E_API_Service (Singleton)
    ↓
B2E_API_Client (HTTP Layer)
    ↓
wp_remote_request() (WordPress HTTP API)
    ↓
REST API Endpoint auf Etch
    ↓
B2E_API_Endpoints (Empfang)
    ↓
WordPress Datenbank (Etch)
```

---

## 🔄 Migration Flow

### Phase 1: Initialisierung

```php
// 1. User startet Migration in Bricks
// admin_interface.php - JavaScript

async function startMigration() {
    // Validierung
    const isValid = await validateConnection(apiDomain, apiKey);
    
    // Hole Posts-Liste
    const posts = await getBricksPosts();
    
    // Starte Migration
    await migrateAllPosts(posts, apiDomain, apiKey);
}
```

### Phase 2: CSS-Migration

```php
// migration_manager.php

public function migrate_css($target_url, $api_key) {
    // 1. Extrahiere CSS aus Bricks
    $bricks_styles = $this->css_converter->extract_bricks_styles();
    
    // 2. Konvertiere zu Etch-Format
    $etch_styles = $this->css_converter->convert_to_etch_format($bricks_styles);
    
    // 3. Sende via API Service
    $this->api_service->init($target_url, $api_key);
    $result = $this->api_service->send_css($etch_styles);
    
    return $result;
}
```

**API-Call:**
```http
POST http://etch-site/wp-json/b2e/v1/import/css-classes
Headers:
  X-API-Key: {api_key}
  Content-Type: application/json
Body:
  {
    "styles": {
      "class-name": {
        "css": "...",
        "breakpoints": {...}
      }
    }
  }
```

### Phase 3: Media-Migration

```php
// media_migrator.php

public function migrate_media($target_url, $api_key) {
    // 1. Hole alle Attachments
    $media_files = $this->get_media_files();
    
    foreach ($media_files as $media_id => $media_data) {
        // 2. Download Datei (Docker-URL-Konvertierung!)
        $file_url = $media_data['file_url'];
        $file_url = $api_service->convert_media_url_for_docker($file_url);
        // localhost:8080 → b2e-bricks (Container-Name)
        
        $file_content = $this->download_file($file_url);
        
        // 3. Prepare Payload
        $media_payload = array(
            'title' => $media_data['title'],
            'filename' => basename($media_data['file_path']),
            'mime_type' => $media_data['mime_type'],
            'file_content' => base64_encode($file_content), // Base64!
            'metadata' => $media_data['metadata']
        );
        
        // 4. Sende via API Service
        $api_service->init($target_url, $api_key);
        $result = $api_service->send_media($media_payload);
    }
}
```

**API-Call:**
```http
POST http://etch-site/wp-json/b2e/v1/import/media
Headers:
  X-API-Key: {api_key}
  Content-Type: application/json
Body:
  {
    "title": "Image Title",
    "filename": "image.jpg",
    "mime_type": "image/jpeg",
    "file_content": "base64_encoded_content...",
    "metadata": {
      "width": 1920,
      "height": 1080
    }
  }
```

**Etch empfängt:**
```php
// api_endpoints.php

public static function import_media_file($request) {
    $media_data = $request->get_json_params();
    
    // 1. Decode Base64
    $file_content = base64_decode($media_data['file_content']);
    
    // 2. Speichere Datei in wp-content/uploads
    $upload = wp_upload_bits(
        $media_data['filename'],
        null,
        $file_content
    );
    
    // 3. Erstelle Attachment
    $attachment_id = wp_insert_attachment(array(
        'post_title' => $media_data['title'],
        'post_mime_type' => $media_data['mime_type'],
        'guid' => $upload['url']
    ), $upload['file']);
    
    // 4. Generiere Thumbnails
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    return array('media_id' => $attachment_id);
}
```

### Phase 4: Post-Migration

```php
// migration_manager.php

public function migrate_single_post($post) {
    // 1. Parse Bricks Content
    $bricks_content = get_post_meta($post->ID, '_bricks_page_content_2', true);
    $parsed_elements = $this->content_parser->parse_bricks_content($bricks_content);
    
    // 2. Konvertiere zu Gutenberg
    $etch_content = $this->gutenberg_generator->generate_gutenberg_blocks($parsed_elements);
    
    // 3. Prepare Post Data
    $post_data = array(
        'post' => array(
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'post_date' => $post->post_date,
            'post_status' => $post->post_status
        ),
        'etch_content' => $etch_content
    );
    
    // 4. Sende via API Service
    $this->api_service->init($target_url, $api_key);
    $result = $this->api_service->send_post($post_data);
    
    return $result;
}
```

**API-Call:**
```http
POST http://etch-site/wp-json/b2e/v1/receive-post
Headers:
  X-API-Key: {api_key}
  Content-Type: application/json
Body:
  {
    "post": {
      "ID": 123,
      "post_title": "My Page",
      "post_type": "page",
      "post_date": "2025-10-19 12:00:00",
      "post_status": "publish"
    },
    "etch_content": "<!-- wp:etch/section -->..."
  }
```

**Etch empfängt:**
```php
// api_endpoints.php

public static function receive_migrated_post($request) {
    $post_data = $request->get_json_params();
    $post_info = $post_data['post'];
    $etch_content = $post_data['etch_content'];
    
    // 1. Prüfe ob Post bereits existiert (Duplicate-Check!)
    $existing_posts = get_posts(array(
        'meta_key' => '_b2e_original_post_id',
        'meta_value' => $post_info['ID'],
        'post_status' => 'any'
    ));
    
    // 2. Prepare WordPress Post Data
    $wp_post_data = array(
        'post_title' => $post_info['post_title'],
        'post_content' => $etch_content, // Gutenberg Blocks!
        'post_status' => $post_info['post_status'],
        'post_type' => $post_info['post_type'],
        'post_date' => $post_info['post_date'],
        'meta_input' => array(
            '_b2e_migrated_from_bricks' => true,
            '_b2e_original_post_id' => $post_info['ID'],
            '_b2e_migration_date' => current_time('mysql')
        )
    );
    
    // 3. Update oder Insert
    if (!empty($existing_posts)) {
        // Update existing
        $post_id = $existing_posts[0]->ID;
        $wp_post_data['ID'] = $post_id;
        wp_update_post($wp_post_data);
    } else {
        // Insert new
        $post_id = wp_insert_post($wp_post_data);
    }
    
    return array('post_id' => $post_id);
}
```

---

## 🔌 API-Kommunikation

### B2E_API_Service (Zentral)

```php
class B2E_API_Service {
    private static $instance = null;
    private $target_url = '';
    private $api_key = '';
    
    // Singleton Pattern
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Initialisierung
    public function init($target_url, $api_key) {
        // Docker URL-Konvertierung
        $this->target_url = $this->convert_url_for_docker($target_url);
        $this->api_key = $api_key;
        
        // Speichere in Options
        update_option('b2e_settings', array(
            'target_url' => $this->target_url,
            'api_key' => $this->api_key
        ));
        
        return $this;
    }
    
    // Zentrale Send-Methoden
    public function send_post($post_data) {
        return $this->api_client->send_post(
            $this->get_target_url(),
            $this->get_api_key(),
            $post_data
        );
    }
    
    public function send_media($media_data) {
        return $this->api_client->send_media_file(
            $this->get_target_url(),
            $this->get_api_key(),
            $media_data
        );
    }
    
    // Docker URL-Konvertierung
    private function convert_url_for_docker($url) {
        $is_source_localhost = (strpos(get_site_url(), 'localhost') !== false);
        $is_target_localhost = (strpos($url, 'localhost') !== false);
        
        // Nur konvertieren wenn BEIDE localhost sind (Docker)
        if ($is_source_localhost && $is_target_localhost) {
            // localhost:8081 → b2e-etch (Container-Name)
            if (strpos($url, 'localhost:8081') !== false) {
                $url = str_replace('localhost:8081', 'b2e-etch', $url);
            }
        }
        
        return $url;
    }
    
    // Media URL-Konvertierung (für Downloads)
    public function convert_media_url_for_docker($url) {
        $is_localhost_url = (strpos($url, 'localhost') !== false);
        $site_url = get_site_url();
        $is_localhost_site = (strpos($site_url, 'localhost') !== false);
        
        // localhost:8080 → b2e-bricks (Container-Name)
        if (($is_localhost_url || $is_localhost_site) && strpos($url, 'localhost:8080') !== false) {
            $url = str_replace('localhost:8080', 'b2e-bricks', $url);
        }
        
        return $url;
    }
}
```

### B2E_API_Client (HTTP Layer)

```php
class B2E_API_Client {
    
    private function send_request($url, $api_key, $endpoint, $method = 'GET', $data = null) {
        // Build Full URL
        $full_url = rtrim($url, '/') . '/wp-json/b2e/v1' . $endpoint;
        
        // Prepare Args
        $args = array(
            'method' => $method,
            'timeout' => 120, // 2 Minuten für große Dateien
            'headers' => array(
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json',
            ),
        );
        
        // Add Body (JSON)
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        // Send Request
        $response = wp_remote_request($full_url, $args);
        
        // Error Handling
        if (is_wp_error($response)) {
            return new WP_Error('request_failed', $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Parse Response
        if ($response_code >= 200 && $response_code < 300) {
            return json_decode($response_body, true);
        } else {
            return new WP_Error('api_error', "API request failed with code: {$response_code}");
        }
    }
}
```

---

## 📦 Datenstrukturen

### Bricks Content Format

```json
{
  "elements": [
    {
      "id": "abc123",
      "name": "section",
      "settings": {
        "_cssClasses": "my-class",
        "_background": {
          "color": "#ffffff"
        }
      },
      "children": [
        {
          "id": "def456",
          "name": "text-basic",
          "settings": {
            "text": "Hello World"
          }
        }
      ]
    }
  ]
}
```

### Etch Content Format (Gutenberg)

```html
<!-- wp:etch/section {"etchData":{"name":"Section","origin":"etch"}} -->
<section class="my-class">
  <!-- wp:paragraph -->
  <p>Hello World</p>
  <!-- /wp:paragraph -->
</section>
<!-- /wp:etch/section -->
```

### Media Payload

```json
{
  "title": "My Image",
  "filename": "image.jpg",
  "mime_type": "image/jpeg",
  "file_content": "iVBORw0KGgoAAAANSUhEUgAA...", // Base64
  "alt_text": "Alt text",
  "caption": "Caption",
  "metadata": {
    "width": 1920,
    "height": 1080,
    "sizes": {
      "thumbnail": {...},
      "medium": {...}
    }
  }
}
```

### Post Payload

```json
{
  "post": {
    "ID": 123,
    "post_title": "My Page",
    "post_type": "page",
    "post_date": "2025-10-19 12:00:00",
    "post_status": "publish"
  },
  "etch_content": "<!-- wp:etch/section -->..."
}
```

---

## 🔧 Module & Verantwortlichkeiten

### 1. B2E_API_Service
**Verantwortung:** Zentrale API-Kommunikation
- Singleton Pattern
- URL-Konvertierung (Docker)
- API-Key-Verwaltung
- Einheitliche Schnittstelle für alle Module

**Verwendet von:**
- `migration_manager.php`
- `media_migrator.php`
- `cpt_migrator.php`
- `admin_interface.php`

### 2. B2E_Migration_Manager
**Verantwortung:** Orchestrierung der Migration
- Koordiniert alle Migrations-Schritte
- CSS → Media → Posts
- Error Handling
- Progress Tracking

### 3. B2E_Media_Migrator
**Verantwortung:** Media-Migration
- Download von Bricks
- Base64-Encoding
- Upload zu Etch
- Thumbnail-Generierung

### 4. B2E_Content_Parser
**Verantwortung:** Bricks-Content parsen
- Liest `_bricks_page_content_2` Meta
- Parsed JSON-Struktur
- Extrahiert Elemente

### 5. B2E_Gutenberg_Generator
**Verantwortung:** Gutenberg-Blocks generieren
- Konvertiert Bricks-Elemente
- Generiert Etch-Blocks
- Erhält CSS-Klassen
- Erstellt `etchData` Struktur

### 6. B2E_CSS_Converter
**Verantwortung:** CSS-Migration
- Extrahiert Bricks-Styles
- Konvertiert zu Etch-Format
- Breakpoints-Handling

### 7. B2E_API_Endpoints
**Verantwortung:** REST API auf Etch-Seite
- Empfängt Daten
- Validiert API-Key
- Speichert in WordPress
- Duplicate-Check

---

## 🐳 Docker-Spezifika

### Problem: Container-Kommunikation

```
┌─────────────────────────────────────────────┐
│  Browser (localhost:8080)                   │
│    ↓                                        │
│  Bricks Container (b2e-bricks)              │
│    ↓ API Call zu "localhost:8081"          │
│    ❌ FEHLER: localhost ist Container selbst│
└─────────────────────────────────────────────┘
```

### Lösung: URL-Konvertierung

```php
// Für API-Calls (Bricks → Etch)
localhost:8081 → b2e-etch (Container-Name)

// Für Media-Downloads (Bricks → Bricks)
localhost:8080 → b2e-bricks (Container-Name)
```

**Implementierung:**
```php
// api_service.php

private function convert_url_for_docker($url) {
    // Prüfe ob beide Seiten localhost sind
    $is_source_localhost = (strpos(get_site_url(), 'localhost') !== false);
    $is_target_localhost = (strpos($url, 'localhost') !== false);
    
    // Nur in Docker-Umgebung konvertieren
    if ($is_source_localhost && $is_target_localhost) {
        $url = str_replace('localhost:8081', 'b2e-etch', $url);
    }
    
    return $url;
}
```

---

## 🔐 Sicherheit

### API-Key-Validierung

```php
// api_endpoints.php

public static function check_api_key($request) {
    // 1. Hole API-Key aus Header
    $api_key = $request->get_header('X-API-Key');
    
    // 2. Prüfe ob vorhanden
    if (empty($api_key)) {
        return new WP_Error('missing_api_key', 'API key is required', 
            array('status' => 401));
    }
    
    // 3. Hole gespeicherten Key
    $valid_key = get_option('b2e_api_key');
    
    // 4. Vergleiche
    if ($api_key !== $valid_key) {
        return new WP_Error('invalid_api_key', 'Invalid API key', 
            array('status' => 401));
    }
    
    return true;
}
```

### Migration Token

```php
// migration_token_manager.php

public function generate_token() {
    $token = array(
        'api_key' => get_option('b2e_api_key'),
        'site_url' => get_site_url(),
        'expires' => time() + (24 * 60 * 60), // 24h
        'version' => B2E_VERSION
    );
    
    // Base64 encode
    return base64_encode(json_encode($token));
}
```

---

## 📊 Duplicate-Check

### Problem
Posts könnten mehrfach migriert werden (z.B. bei Fehler und Retry).

### Lösung
```php
// api_endpoints.php - receive_migrated_post()

// Prüfe ob Post bereits existiert
$existing_posts = get_posts(array(
    'post_type' => 'any',
    'meta_key' => '_b2e_original_post_id',
    'meta_value' => $post_info['ID'], // Original Bricks ID
    'posts_per_page' => 1,
    'post_status' => 'any'
));

if (!empty($existing_posts)) {
    // UPDATE existing post
    $post_id = $existing_posts[0]->ID;
    $wp_post_data['ID'] = $post_id;
    wp_update_post($wp_post_data);
} else {
    // INSERT new post
    $post_id = wp_insert_post($wp_post_data);
}
```

**Meta-Felder für Tracking:**
```php
'meta_input' => array(
    '_b2e_migrated_from_bricks' => true,
    '_b2e_original_post_id' => 123,        // Original Bricks ID
    '_b2e_migration_date' => '2025-10-19 12:00:00'
)
```

---

## 🔄 Vollständiger Flow (Zusammenfassung)

```
1. USER startet Migration in Bricks
   ↓
2. Validierung (API-Key, Plugins)
   ↓
3. CSS-Migration
   - Extrahiere Bricks CSS
   - Konvertiere zu Etch
   - POST /import/css-classes
   ↓
4. Media-Migration (für jedes Medium)
   - Download von Bricks (Docker URL!)
   - Base64-Encode
   - POST /import/media
   - Etch speichert Datei
   - Generiert Thumbnails
   ↓
5. Post-Migration (für jeden Post)
   - Parse Bricks Content
   - Konvertiere zu Gutenberg
   - POST /receive-post
   - Etch prüft Duplicate
   - Insert oder Update
   ↓
6. Fertig! 🎉
```

---

## 🛠️ Debugging

### Logs prüfen

**Bricks:**
```bash
docker exec b2e-bricks wp eval 'error_log("Test");'
docker logs b2e-bricks 2>&1 | grep "B2E:"
```

**Etch:**
```bash
docker exec b2e-etch wp eval 'error_log("Test");'
docker logs b2e-etch 2>&1 | grep "B2E:"
```

### API-Call testen

```bash
curl -X POST http://localhost:8081/wp-json/b2e/v1/receive-post \
  -H "X-API-Key: your-key" \
  -H "Content-Type: application/json" \
  -d '{"post":{"ID":1,"post_title":"Test"},"etch_content":"..."}'
```

### Container-Kommunikation testen

```bash
docker exec b2e-bricks curl http://b2e-etch/wp-json/b2e/v1/validate
```

---

## 📚 Wichtige Dateien

| Datei | Verantwortung |
|-------|---------------|
| `api_service.php` | Zentrale API-Kommunikation (Singleton) |
| `api_client.php` | HTTP-Layer (wp_remote_request) |
| `api_endpoints.php` | REST API Endpoints (Etch-Seite) |
| `migration_manager.php` | Orchestrierung |
| `media_migrator.php` | Media-Migration |
| `content_parser.php` | Bricks-Content parsen |
| `gutenberg_generator.php` | Gutenberg-Blocks generieren |
| `css_converter.php` | CSS-Migration |
| `admin_interface.php` | UI & AJAX-Handler |

---

## ✅ Checkliste für erfolgreiche Migration

- [ ] Beide Sites erreichbar
- [ ] Plugin auf beiden Seiten installiert
- [ ] API-Key in Etch generiert
- [ ] Migration-Token in Bricks eingegeben
- [ ] Validierung erfolgreich
- [ ] Docker-Container laufen
- [ ] Genug Speicherplatz für Medien
- [ ] PHP memory_limit ausreichend (256M+)
- [ ] max_execution_time ausreichend (300s+)

---

**Erstellt:** 2025-10-19  
**Version:** 1.0  
**Plugin Version:** 0.3.7
