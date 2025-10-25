# Framer Template Extraction

**Updated:** 2025-10-25 14:41

---

## Overview

This guide documents the Framer → Etch template extraction pipeline introduced in Etch Fusion Suite 0.10.0. The extractor ingests published Framer pages (via URL or raw HTML) and produces Etch-ready Gutenberg blocks, styles, and metadata suitable for direct import into the Etch template library.

### Key Capabilities

- Fetch Framer HTML from remote URLs or pasted markup
- Sanitize and normalise Framer-specific DOM structures for Etch
- Analyse sections, typography, and media to create metadata and stats
- Generate Gutenberg block payloads with `etchData` metadata and associated style declarations
- Persist extracted templates as Etch draft posts and expose them via REST and AJAX APIs

---

## Architecture

The extractor is built from specialised components registered in the service container:

| Component | Responsibility |
| --- | --- |
| `EFS_HTML_Parser` | Fetch and parse Framer HTML into `DOMDocument` instances |
| `EFS_Framer_HTML_Sanitizer` | Remove Framer artefacts, semanticise nodes, and extract CSS variables |
| `EFS_Framer_Template_Analyzer` | Derive sections, media inventory, component counts, and typography warnings |
| `EFS_Etch_Template_Generator` | Convert analysed DOM into Gutenberg-compatible Etch blocks + metadata |
| `EFS_Template_Extractor_Service` | Orchestrate parser → sanitizer → analyzer → generator flow |
| `EFS_Template_Controller` | Surface extractor operations to AJAX, REST, and admin UI layers |

Services are autowired via `EFS_Service_Provider` and exposed through both AJAX handlers and new REST REST routes (`/b2e/v1/template/*`).

---

## Pipeline Steps

1. **Fetch** – Retrieve HTML from URL (`wp_remote_get`) or accept pasted HTML; parse using `DOMDocument` with error-suppression and UTF-8 normalisation.
2. **Sanitize** – Strip Framer-specific scripts/attributes, convert semantic sections, unwrap single-child wrappers, extract inline CSS variables.
3. **Analyse** – Identify sections (hero/features/CTA/testimonials/footer), count components, collect layout depth, detect typography issues, and capture media references.
4. **Generate** – Produce Gutenberg block HTML strings with `etchData` metadata, assemble styles array from CSS variables, and compute stats (complexity score, section + media counts).
5. **Validate** – Run `validate_generated_template()` to enforce block wrapper expectations before returning the payload.
6. **Persist (optional)** – Save payload through the controller, creating Etch draft posts and storing styles/metadata/stats post meta.

Each step updates an internal progress tracker surfaced through `get_extraction_progress()` for UI polling.

---

## Framer-Specific Rules

- Removes `data-framer-*` attributes except `data-framer-name` and `data-framer-component-type` (retained for semantic hints).
- Generated class names exclude hashed Framer utility classes (`framer-xxxxx`).
- Semantic conversions:
  - `data-framer-name` heuristics map to `<section>`, `<header>`, `<nav>`, `<footer>` where applicable.
  - Text components become headings (`h1`/`h2`) or paragraphs based on word count and existing DOM hierarchy.
  - Image components convert to `<img>` with preserved `class`/`style`, auto-populating `alt` text.
  - Button-like elements (role="button" or Framer Button type) normalise to `<button>` or anchor with `role="button"`.
- CSS variables (`--framer-*`) are collected for use in Etch style definitions.

---

## Usage Examples

### Admin UI

1. Navigate to **Etch Fusion Suite → Dashboard → Template Extractor**.
2. Provide a Framer URL or paste raw HTML.
3. Monitor real-time progress; once complete, inspect preview and metadata.
4. Optionally save the template to Etch drafts and reuse it later from the Saved Templates list.

### REST API

```http
POST /wp-json/b2e/v1/template/extract
Authorization: Basic base64(user:application-password)
Content-Type: application/json

{
  "source": "https://your-site.framer.website/",
  "source_type": "url"
}
```

See [REST Endpoint Summary](#rest-endpoints) for full operations.

### AJAX (Admin)

AJAX actions mirror REST behaviour for WordPress admins:

- `b2e_extract_template`
- `b2e_get_extraction_progress`
- `b2e_save_template`
- `b2e_get_saved_templates`
- `b2e_delete_template`

---

## Output Format

Successful extraction returns an array structure:

```php
array(
  'blocks'   => array('<-- wp:group {...} --> ...'),
  'styles'   => array(
    array('name' => 'color-primary', 'value' => '#0044ff', 'origin' => 'framer-inline'),
  ),
  'metadata' => array(
    'title'            => 'Hero Landing Page',
    'description'      => 'Template generated from Framer import.',
    'complexity_score' => 68,
    'section_overview' => array('hero', 'features', 'cta'),
    'warnings'         => array('Multiple H1 tags detected.')
  ),
  'stats'    => array(
    'generated_at'    => '2025-10-25 14:41:00',
    'block_count'     => 5,
    'section_count'   => 3,
    'media_count'     => 4,
    'source_type'     => 'url'
  )
);
```

Blocks contain Gutenberg comment wrappers with embedded `etchData.block.tag` values (e.g., `section`) enabling semantic rendering in Etch.

---

## Error Handling

- Invalid URLs or empty HTML – `WP_Error('b2e_template_extractor_invalid_url'|'_empty_html')`
- Sanitizer/generator failures – Propagate as `WP_Error` with context metadata, logged via `EFS_Error_Handler`.
- Validation failures – `WP_Error('b2e_template_extractor_invalid_template')` containing validation errors for debugging.
- Rate limiting – REST endpoints enforce per-action limits (`template_extract` 15 req/min, `template_import` 10 req/min).
- Permission checks – REST endpoints require Application Password or API key authentication; AJAX requires `manage_options` capability and valid nonce.

---

## Extending

1. Implement additional sanitizer/analyzer classes for other sources (e.g., Webflow) and register them in the service provider.
2. Update `EFS_Template_Extractor_Service::$supported_sources` to include new identifiers.
3. Expose new options in UI/REST by extending controller logic to route to the appropriate implementation.
4. Document custom behaviour in this file to keep parity with new converters (see Project Rules for documentation guidelines).

---

## Testing

- **Unit Tests**: `TemplateExtractorServiceTest` validates service-level helpers and supported source metadata using PHPUnit mocks.
- **Integration Tests** (planned): upcoming tests will cover end-to-end HTML → Etch block conversion using fixtures stored in `tests/fixtures/`.
- **CI**: PHPUnit suites run via GitHub Actions across PHP 7.4–8.4; PHPCompatibilityWP ensures the extractor remains compatible with PHP 7.4.

Run locally:

```bash
composer test -- --testsuite=unit
```

---

## Troubleshooting

| Symptom | Resolution |
| --- | --- |
| Extraction returns `WP_Error` citing invalid URL | Ensure published Framer URL is publicly accessible and includes protocol (`https://`). |
| Preview shows empty blocks | Inspect sanitization warnings in logs; Framer markup may lack recognised component tags (verify DOM sanitisation rules). |
| Saved template missing styles | Confirm CSS variables were extracted; Framer inline styles may be minified without `--framer-*` definitions. |
| REST calls return 401/403 | Provide valid Application Password or API key, and ensure the origin passes CORS checks. |
| Rate limit errors (`429`) | Back off requests; default limits range from 10–30 req/min depending on operation. |

---

## REST Endpoints

| Method | Route | Description | Rate Limit |
| --- | --- | --- | --- |
| `POST` | `/b2e/v1/template/extract` | Run extraction for URL/HTML payload | 15 req/min |
| `GET` | `/b2e/v1/template/saved` | Retrieve saved templates | 30 req/min |
| `GET` | `/b2e/v1/template/preview/{id}` | Preview stored template | 25 req/min |
| `DELETE` | `/b2e/v1/template/{id}` | Delete stored template | 15 req/min |
| `POST` | `/b2e/v1/template/import` | Import existing payload into Etch drafts | 10 req/min |

Authentication uses Application Passwords (recommended) or legacy API keys. All routes inherit global CORS enforcement and audit logging.
