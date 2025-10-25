# Migrator API

**Updated:** 2025-10-24 09:05

---

## 1. Overview

The migrator plugin system provides an extensible architecture for running individual migration tasks (Custom Post Types, ACF, MetaBox, etc.) in a consistent way. Core ideas:

- A common contract (`Migrator_Interface`) ensures every migrator implements `supports()`, `validate()`, `export()`, `import()`, `migrate()`, and `get_stats()`.
- `Abstract_Migrator` supplies shared helpers for logging, API access, and property definitions.
- `B2E_Migrator_Registry` stores migrators, handles priority sorting, and filters by support status.
- `B2E_Migrator_Discovery` registers built-in migrators, exposes hooks for third parties, and supports directory auto-discovery.
- `B2E_Migration_Service` requests migrators from the registry during `start_migration()` and executes them dynamically.
- REST endpoints expose migrator data (`/b2e/v1/export/migrators`, `/b2e/v1/export/migrator/{type}`).

## 2. Creating a Custom Migrator

1. **Implement the interface**
   ```php
   use Bricks2Etch\Migrators\Interfaces\Migrator_Interface;
   
   class My_Migrator implements Migrator_Interface {
       // implement required methods...
   }
   ```
2. **Extend `Abstract_Migrator`** to inherit shared helpers and property storage:
   ```php
   use Bricks2Etch\Migrators\Abstract_Migrator;
   
   class My_Migrator extends Abstract_Migrator {
       public function __construct($error_handler, $api_client = null) {
           parent::__construct($error_handler, $api_client);
           $this->name = 'My Plugin Migrator';
           $this->type = 'my_plugin';
           $this->priority = 50;
       }

       // implement interface methods...
   }
   ```
3. **Populate required methods** (see section 4 for details) and set `$name`, `$type`, `$priority` in the constructor.
4. **Register the migrator** using the action hook or container binding (section 3).
5. **Document and test** the migrator thoroughly.

## 3. Registering a Migrator

### Via Hook (recommended)
```php
add_action('b2e_register_migrators', function(\Bricks2Etch\Migrators\B2E_Migrator_Registry $registry) {
    $registry->register(new My_Migrator(
        b2e_container()->get('error_handler'),
        b2e_container()->get('api_client')
    ));
});
```

### Modifying Discovered Migrators
```php
add_filter('b2e_migrators_discovered', function(array $migrators, $registry) {
    // Reorder, remove, or replace migrators
    return $migrators;
}, 10, 2);
```

### Service Container (optional)
You can bind migrators as container singletons and let discovery resolve them by handle.

## 4. Interface Reference

```php
interface Migrator_Interface {
    public function supports();              // bool
    public function get_name();              // string
    public function get_type();              // string (unique)
    public function get_priority();          // int (lower runs first)
    public function validate();              // [ 'valid' => bool, 'errors' => string[] ]
    public function export();                // array
    public function import($data);           // array|WP_Error
    public function migrate($target_url, $api_key); // bool|WP_Error
    public function get_stats();             // array
}
```

- **supports()** — Check plugin availability or environment requirements.
- **get_type()** — Lowercase identifier (e.g., `acf`, `metabox`, `custom_fields`).
- **get_priority()** — Lower values execute earlier; default is `10` if not overridden.
- **validate()** — Pre-flight validation. Return `['valid' => false, 'errors' => [...]]` to skip execution.
- **export() / import()** — Optional for full round-trip migrations (array data preferred).
- **migrate($target_url, $api_key)** — Primary execution. Return `true` or `WP_Error`.
- **get_stats()** — Return counts/details for UI and monitoring.

## 5. Abstract Base Class

Available helpers in `Abstract_Migrator`:

- Protected properties `$error_handler`, `$api_client`, `$name`, `$type`, `$priority`.
- `check_plugin_active($function_or_class)` — Wrapper for `function_exists`/`class_exists`.
- `log_info($message, array $context = [])` — Debug log to `B2E_MIGRATOR` channel (respects WP_DEBUG).
- `log_warning($code, array $context = [])` — Proxy to `B2E_Error_Handler::log_warning`.
- `log_error($code, array $context = [])` — Proxy to `B2E_Error_Handler::log_error`.

Subclasses must still implement all interface methods.

## 6. Priority System

Execution order is determined by ascending priority. Defaults:

| Migrator            | Type           | Priority |
|---------------------|----------------|----------|
| Custom Post Types   | `cpt`          | 10       |
| ACF Field Groups    | `acf`          | 20       |
| MetaBox             | `metabox`      | 30       |
| Custom Fields       | `custom_fields`| 40       |

Third-party migrators should choose a non-conflicting priority. Lower numbers run first, enabling prerequisites (e.g., CPTs before field groups).

## 7. Error Handling

- Use `log_warning()` for recoverable issues (unsupported structures, skipped items).
- Use `log_error()` with relevant codes (e.g., `E201`, `E302`) for failures.
- Return `WP_Error` from `migrate()` or `import()` to abort or surface problems.
- Provide descriptive context arrays for diagnostics.

## 8. Testing Strategy

- **Unit tests:** Mock `B2E_Error_Handler` and `B2E_API_Client` to verify logic.
- **Integration tests:** Register the migrator on wp-env or staging and trigger migrations.
- **Regression tests:** Ensure stats and validation logic behave consistently after changes.
- Use the REST endpoints to inspect migration outputs (`/export/migrator/{type}`).

## 9. Example: JetEngine Migrator

```php
class B2E_JetEngine_Migrator extends Abstract_Migrator {
    public function __construct($error_handler, $api_client = null) {
        parent::__construct($error_handler, $api_client);
        $this->name = 'JetEngine';
        $this->type = 'jetengine';
        $this->priority = 35;
    }

    public function supports() {
        return $this->check_plugin_active('Jet_Engine');
    }

    public function validate() {
        $errors = [];
        if (!$this->supports()) {
            $errors[] = 'JetEngine plugin is not active on the source site.';
        }
        return [ 'valid' => empty($errors), 'errors' => $errors ];
    }

    public function export() {
        return jet_engine()->meta_boxes->get_meta_boxes();
    }

    public function import($data) {
        // Handle import on target site if needed
        return [ 'imported' => count($data) ];
    }

    public function migrate($target_url, $api_key) {
        $configs = $this->export();
        if (empty($configs)) {
            $this->log_warning('W002', ['migrator' => 'jetengine', 'message' => 'No JetEngine configs found.']);
            return true;
        }
        // Example: send through API client
        $response = $this->api_client->send_request($target_url, $api_key, '/import/jetengine-configs', 'POST', $configs);
        return is_wp_error($response) ? $response : true;
    }

    public function get_stats() {
        return [ 'meta_boxes' => count($this->export()) ];
    }
}
```

## 10. Hooks Reference

| Hook                       | Type    | Description |
|----------------------------|---------|-------------|
| `b2e_register_migrators`   | action  | Receive the registry instance to register custom migrators.
| `b2e_migrators_discovered` | filter  | Modify the migrator array after discovery. Parameters: `array $migrators`, `B2E_Migrator_Registry $registry`.

## 11. REST API Access

- `GET /b2e/v1/export/migrators` — Returns all registered migrators (name, type, priority, support status).
- `GET /b2e/v1/export/migrator/{type}` — Exports data for specific migrator type (includes stats and payload).

Typical use cases:

- Pre-flight validation on target site before running the actual migration.
- Custom dashboards that display available migrators and their status.
- Debugging third-party migrator outputs.

## 12. Troubleshooting

- **Migrator missing:** Ensure registration happens on or before `plugins_loaded` (priority ≥ 20) and package autoloading is configured.
- **Migrator skipped:** Check `supports()` and `validate()` logs for warnings. Unsupported migrators are ignored.
- **Priority conflicts:** Two migrators with identical priorities will maintain registration order. Adjust priorities if a specific sequence is required.
- **REST endpoint returns 404:** Confirm the migrator type matches `get_type()` exactly and that discovery ran (`init_migrators()` on `plugins_loaded` priority 20).
- **API client missing:** Ensure the migrator receives an instance via dependency injection or construct your own client.

---

Happy migrating! External developers can plug into the migration pipeline with minimal boilerplate while staying fully aligned with the core plugin workflow.
