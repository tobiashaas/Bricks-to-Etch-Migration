# Bricks to Etch wp-env Testing Guide

**Updated:** 2025-10-24 00:39

## 1. Pre-Flight Checks

1. Verify Node.js version:
   ```bash
   node -v
   ```
   Ensure the version is **>= 18**.
2. Verify npm is available:
   ```bash
   npm -v
   ```
3. Confirm Docker Desktop is running and healthy:
   ```bash
   docker ps
   ```
4. Confirm ports 8888/8889 are free:
   ```bash
   netstat -an | findstr 8888
   netstat -an | findstr 8889
   ```
   No results = ports available.

## 2. Environment Setup Tests

1. Install dependencies:
   ```bash
   npm install
   ```
   Expect a populated `node_modules/` directory.
2. Start environments:
   ```bash
   npm run dev
   ```
   The command should complete without errors and print the Bricks/Etch URLs.
3. Browser smoke-test:
   - Visit http://localhost:8888 and http://localhost:8889.
   - Log in with **admin / password** on both.
4. Verify plugin activation via WP-CLI:
   ```bash
   npm run wp plugin status bricks-etch-migration
   npm run wp:etch plugin status bricks-etch-migration
   ```
5. Confirm Composer artifacts exist:
   ```bash
   npm run wp "eval 'echo file_exists(WP_PLUGIN_DIR . "/bricks-etch-migration/vendor/autoload.php") ? "yes" : "no";'"
   ```
   Expected output: `yes`.

## 3. Plugin Functional Tests

1. Load the Bricks to Etch admin screen:
   - Navigate to **Bricks to Etch → Dashboard** in the Bricks site.
   - Confirm there are no PHP warnings/notices in the UI.
2. Validate AJAX endpoints:
   - Open browser console → Network tab.
   - Trigger an action (e.g., refresh status) and ensure 200 responses.
3. Confirm REST API endpoint:
   ```bash
   curl -u admin:$(npm run wp:etch user application-password list admin --silent -- --fields=password --format=csv | tail -n +2) \
     http://localhost:8889/wp-json/b2e/v1/status
   ```
   Response should include `{"status":"ok", "version":"..."}`.

## 4. Migration Smoke Test

1. Seed content:
   ```bash
   npm run create-test-content
   ```
   Expect confirmation that posts, pages, classes, and media were created.
2. Generate Etch credentials (auto-run by `npm run dev`, but can be repeated):
   ```bash
   npm run wp:etch user application-password create admin smoke-test --porcelain
   ```
3. Configure plugin settings on Bricks:
   ```bash
   npm run wp option update b2e_migration_settings '{"target_url":"http://localhost:8889","api_username":"admin","api_key":"<app-password>"}'
   ```
4. Trigger migration:
   ```bash
   npm run test:migration
   ```
   The command completes when the migration status is `completed`.
5. Validate record counts:
   ```bash
   npm run wp post list --post_type=post --format=count
   npm run wp:etch post list --post_type=post --format=count
   ```
   Counts should match after migration.

## 5. Performance Spot Checks

1. Measure migration duration:
   ```bash
   time npm run test:migration
   ```
   Record the total runtime.
2. Capture memory usage on Etch:
   ```bash
   npm run wp:etch "eval 'echo memory_get_peak_usage(true);'"
   ```

## 6. Error Handling Scenarios

1. Invalid API key:
   ```bash
   npm run wp option update b2e_migration_settings '{"target_url":"http://localhost:8889","api_username":"admin","api_key":"invalid"}'
   npm run test:migration
   ```
   Expect the migration to fail gracefully with a descriptive error.
2. Network interruption:
   - Run `npm run stop` during a migration and confirm retry/timeout messaging is clear.
3. Database error simulation:
   ```bash
   npm run wp:etch "eval 'global $wpdb; $wpdb->query("SET SESSION sql_mode='STRICT_ALL_TABLES'");'"
   npm run test:migration
   ```
   Ensure errors are logged to `wp-content/debug.log`.

## 7. Cleanup

1. Destroy environments:
   ```bash
   npm run destroy
   ```
2. Restart for verification:
   ```bash
   npm run dev
   ```
   Ensures clean re-provisioning works repeatedly.

3. Optional database exports:
   ```bash
   npm run db:export:bricks
   npm run db:export:etch
   ```

Document test results in `DOCUMENTATION.md` with timestamps after each run.
