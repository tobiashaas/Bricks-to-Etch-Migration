# Bricks2Etch Testing Documentation

**Last Updated:** 2025-10-23

---

## 📋 Table of Contents

1. [Pre-Flight Checks](#pre-flight-checks)
2. [Setup Tests](#setup-tests)
3. [Unit Tests](#unit-tests)
4. [Integration Tests](#integration-tests)
5. [End-to-End Tests](#end-to-end-tests)
6. [Performance Tests](#performance-tests)
7. [Error Handling Tests](#error-handling-tests)
8. [Rollback Tests](#rollback-tests)

---

## 1. Pre-Flight Checks

### 1.1 Docker Installation

**Objective:** Verify Docker is installed and running correctly.

**Steps:**
```bash
# Check Docker version
docker --version
# Expected: Docker version 20.10+ or higher

# Check Docker Compose version
docker-compose --version
# Expected: docker-compose version 1.27+ or higher

# Check Docker is running
docker info
# Expected: No errors, shows system info
```

**Validation:**
- Docker daemon is running
- Docker Compose is available
- Minimum version requirements met

**Troubleshooting:**
- Windows: Ensure Docker Desktop is running
- Linux: `sudo systemctl start docker`
- Mac: Start Docker Desktop application

### 1.2 Available Ports

**Objective:** Ensure ports 8080 and 8081 are available.

**Steps:**
```bash
# Windows
netstat -an | findstr "8080"
netstat -an | findstr "8081"

# Linux/Mac
netstat -an | grep 8080
netstat -an | grep 8081
```

**Expected Result:** No output (ports are free)

**Troubleshooting:**
- If ports are in use, modify `docker-compose.override.yml` to use different ports

### 1.3 Disk Space

**Objective:** Verify sufficient disk space for Docker volumes.

**Steps:**
```bash
# Check available disk space
df -h

# Expected: At least 5GB free space
```

**Validation:**
- Minimum 5GB free disk space
- Docker has access to the directory

---

## 2. Setup Tests

### 2.1 Container Startup

**Objective:** Verify all Docker containers start successfully.

**Steps:**
```bash
cd test-environment
make start
```

**Expected Result:**
```
[start] Starting Docker containers...
Creating network "b2e-network" with driver "bridge"
Creating volume "test-environment_bricks-db-data" with default driver
Creating volume "test-environment_etch-db-data" with default driver
Creating volume "test-environment_bricks-wp-data" with default driver
Creating volume "test-environment_etch-wp-data" with default driver
Creating b2e-bricks-db ... done
Creating b2e-etch-db   ... done
Creating b2e-bricks-wp ... done
Creating b2e-etch-wp   ... done
Creating b2e-wpcli     ... done
[start] Containers started.
```

**Validation:**
```bash
docker-compose ps
# Expected: All 5 containers show "Up" status
```

**Troubleshooting:**
- Check logs: `docker-compose logs`
- Verify no port conflicts
- Ensure Docker has sufficient resources (4GB RAM minimum)

### 2.2 WordPress Installation

**Objective:** Verify WordPress is installed on both instances.

**Steps:**
```bash
make setup
```

**Expected Result:**
- WordPress installed on Bricks site (http://localhost:8080)
- WordPress installed on Etch site (http://localhost:8081)
- Admin credentials: admin/admin

**Validation:**
```bash
make wp-bricks ARGS='core is-installed'
# Expected: Exit code 0 (no output)

make wp-etch ARGS='core is-installed'
# Expected: Exit code 0 (no output)

make wp-bricks ARGS='core version'
# Expected: WordPress version number (e.g., 6.4.2)
```

**Troubleshooting:**
- Check MySQL connectivity: `make wp-bricks ARGS='db check'`
- Review setup logs in terminal output
- Verify containers are running: `docker-compose ps`

### 2.3 Plugin Activation

**Objective:** Verify Bricks2Etch plugin is activated on both instances.

**Steps:**
```bash
make validate
```

**Expected Result:**
```
[6/9] Checking plugin activation...
✓ Plugin activated on Bricks site (version 0.5.3)
✓ Plugin activated on Etch site (version 0.5.3)
```

**Validation:**
```bash
make wp-bricks ARGS='plugin is-active bricks-etch-migration'
# Expected: Exit code 0

make wp-etch ARGS='plugin is-active bricks-etch-migration'
# Expected: Exit code 0
```

**Troubleshooting:**
- Install Composer dependencies: `make composer-install`
- Check plugin directory is mounted: `make wp-bricks ARGS='plugin list'`
- Review activation errors: `make logs-bricks`

### 2.4 Composer Dependencies

**Objective:** Verify Composer autoloader is installed.

**Steps:**
```bash
make composer-install
```

**Expected Result:**
```
[install-composer-deps] Composer already installed in wpcli.
[install-composer-deps] Installing Composer dependencies in /var/www/html/bricks/wp-content/plugins/bricks-etch-migration.
Loading composer repositories with package information
Installing dependencies from lock file
Nothing to install or update
Generating optimized autoload files
[install-composer-deps] Autoloader verified at /var/www/html/bricks/wp-content/plugins/bricks-etch-migration/vendor/autoload.php
[install-composer-deps] Installed packages: 0
```

**Validation:**
```bash
make validate
# Check for: ✓ Composer autoloader exists
```

**Troubleshooting:**
- Check internet connectivity in container
- Manually install: `docker-compose exec wpcli sh -c "cd /var/www/html/bricks/wp-content/plugins/bricks-etch-migration && composer install"`
- Verify composer.json exists in plugin directory

---

## 3. Unit Tests

### 3.1 Service Container Initialization

**Objective:** Verify service container is initialized correctly.

**Steps:**
```bash
make wp-bricks ARGS='eval "var_dump(function_exists(\"efs_container\"));"'
```

**Expected Result:**
```
bool(true)
```

**Validation:**
```bash
make wp-bricks ARGS='eval "var_dump(efs_container() instanceof Bricks2Etch\\Container\\EFS_Service_Container);"'
# Expected: bool(true)
```

**Troubleshooting:**
- Check plugin activation
- Verify autoloader is loaded
- Review debug.log: `make logs-bricks`

### 3.2 Repository Pattern

**Objective:** Verify repositories are registered in service container.

**Steps:**
```bash
make wp-bricks ARGS='eval "var_dump(efs_container()->has(\"settings_repository\"));"'
```

**Expected Result:**
```
bool(true)
```

**Validation:**
```bash
# Check all repositories
make wp-bricks ARGS='eval "
  $repos = ["settings_repository", "migration_repository", "styles_repository"];
  foreach ($repos as $repo) {
    echo $repo . ": " . (efs_container()->has($repo) ? "✓" : "✗") . "\n";
  }
"'
```

**Expected Output:**
```
settings_repository: ✓
migration_repository: ✓
styles_repository: ✓
```

### 3.3 AJAX Handler Registration

**Objective:** Verify AJAX handlers are registered.

**Steps:**
```bash
make wp-bricks ARGS='eval "
  global \$wp_filter;
  $actions = ["wp_ajax_efs_start_migration", "wp_ajax_efs_validate_migration_token"];
  foreach (\$actions as \$action) {
    echo \$action . \": \" . (isset(\$wp_filter[\$action]) ? \"✓\" : \"✗\") . \"\\n\";
  }
"'
```

**Expected Output:**
```
wp_ajax_efs_start_migration: ✓
wp_ajax_efs_validate_migration_token: ✓
```

---

## 4. Integration Tests

### 4.1 API Connection

**Objective:** Verify Bricks instance can connect to Etch instance.

**Steps:**
```bash
make quick-test
```

**Expected Result:**
```
[1/6] Fetching Application Password from Etch...
✓ Application Password retrieved

[2/6] Testing REST API Status Endpoint...
✓ REST API endpoint reachable (HTTP 200)

[3/6] Generating Migration Token...
✓ Migration token generated

[4/6] Validating Token on Bricks Instance...
✓ Token validation successful

[5/6] Testing CORS Headers...
✓ CORS headers present

[6/6] Testing Container-to-Container Communication...
✓ Bricks container can reach Etch container (HTTP 200)

✓ All connection tests passed!
```

**Troubleshooting:**
- Check network connectivity: `docker-compose exec bricks-wp ping -c 3 etch-wp`
- Verify REST API endpoints are registered
- Check Application Passwords are enabled

### 4.2 Token Generation

**Objective:** Verify migration token can be generated on Etch instance.

**Steps:**
```bash
make wp-etch ARGS='eval "
  \$request = new WP_REST_Request(\"POST\", \"/b2e/v1/generate-key\");
  \$response = apply_filters(\"rest_pre_dispatch\", null, null, \$request);
  echo json_encode(\$response, JSON_PRETTY_PRINT);
"'
```

**Expected Result:**
```json
{
    "token": "abc123...",
    "expires": 1234567890
}
```

**Validation:**
- Token is a non-empty string
- Expires timestamp is in the future

### 4.3 Migration Start

**Objective:** Verify migration can be started via AJAX.

**Steps:**
```bash
# First, get a valid token
TOKEN=$(make wp-etch ARGS='eval "
  \$request = new WP_REST_Request(\"POST\", \"/b2e/v1/generate-key\");
  \$response = apply_filters(\"rest_pre_dispatch\", null, null, \$request);
  echo \$response[\"token\"];
"')

# Then start migration
make wp-bricks ARGS='eval "
  \$_POST[\"migration_token\"] = \"'$TOKEN'\";
  \$_POST[\"batch_size\"] = 10;
  \$_REQUEST[\"action\"] = \"b2e_start_migration\";
  \$_REQUEST[\"_wpnonce\"] = wp_create_nonce(\"b2e_ajax_nonce\");
  do_action(\"wp_ajax_b2e_start_migration\");
"' --user=admin
```

**Expected Result:**
- Migration starts without errors
- Progress option is created

**Validation:**
```bash
make wp-bricks ARGS='option get b2e_migration_progress'
# Expected: "in_progress" or "completed"
```

---

## 5. End-to-End Tests

### 5.1 Full Migration Test

**Objective:** Perform complete migration from Bricks to Etch.

**Steps:**
```bash
# 1. Create test content
make create-test-content

# 2. Run migration
make test-migration
```

**Expected Result:**
```
[test-migration] Running pre-migration checks...
✓ All prerequisites met

[test-migration] Attempting to trigger migration via REST API...
[test-migration] Migration token generated successfully
[test-migration] Migration triggered successfully!

[test-migration] Monitoring migration progress
[test-migration] Status: in_progress (0s elapsed)
[test-migration] Status: in_progress (5s elapsed)
[test-migration] Status: completed (15s elapsed)
✓ Migration completed successfully

[test-migration] Record counts after migration:
  Bricks Posts : 10
  Etch Posts   : 10
  Bricks Pages : 5
  Etch Pages   : 5

[test-migration] Migration completed in 15s
```

**Validation:**
```bash
# Compare post counts
BRICKS_POSTS=$(make wp-bricks ARGS='post list --post_type=post --format=count')
ETCH_POSTS=$(make wp-etch ARGS='post list --post_type=post --format=count')
echo "Bricks: $BRICKS_POSTS, Etch: $ETCH_POSTS"
# Expected: Same count

# Verify content integrity
make wp-etch ARGS='post list --post_type=post --format=table'
# Expected: All posts from Bricks site
```

### 5.2 Content Integrity

**Objective:** Verify migrated content matches source content.

**Steps:**
```bash
# Get post from Bricks
make wp-bricks ARGS='post get 1 --field=post_title'
# Note the title

# Get same post from Etch
make wp-etch ARGS='post get 1 --field=post_title'
# Should match Bricks title
```

**Validation:**
- Post titles match
- Post content is preserved
- Post meta is migrated
- Featured images are transferred

### 5.3 Media Migration

**Objective:** Verify media files are migrated correctly.

**Steps:**
```bash
# Check media counts
BRICKS_MEDIA=$(make wp-bricks ARGS='media list --format=count')
ETCH_MEDIA=$(make wp-etch ARGS='media list --format=count')
echo "Bricks Media: $BRICKS_MEDIA, Etch Media: $ETCH_MEDIA"
```

**Expected Result:** Media counts should match

**Validation:**
```bash
# List media files
make wp-etch ARGS='media list --format=table'
```

### 5.4 CSS Conversion

**Objective:** Verify Bricks Global Classes are converted to Etch CSS.

**Steps:**
```bash
# Check Bricks Global Classes
make wp-bricks ARGS='option get bricks_global_classes --format=json'

# Check Etch Custom CSS
make wp-etch ARGS='option get etch_custom_css'
```

**Expected Result:**
- Bricks classes are converted to CSS
- CSS is stored in Etch custom CSS option

---

## 6. Performance Tests

### 6.1 Migration Duration

**Objective:** Measure migration performance with different content volumes.

**Test Cases:**

| Content Volume | Expected Duration |
|----------------|-------------------|
| 10 posts       | < 30 seconds      |
| 50 posts       | < 2 minutes       |
| 100 posts      | < 5 minutes       |

**Steps:**
```bash
# Create large test dataset
for i in {1..100}; do
  make wp-bricks ARGS="post create --post_title='Test Post $i' --post_status=publish"
done

# Run migration with timing
time make test-migration
```

**Validation:**
- Migration completes within expected timeframe
- No memory errors
- No timeout errors

### 6.2 Memory Usage

**Objective:** Monitor memory consumption during migration.

**Steps:**
```bash
# Monitor container memory
docker stats b2e-bricks-wp --no-stream

# Run migration
make test-migration

# Check for memory errors
make logs-bricks | grep -i "memory"
```

**Expected Result:**
- Memory usage stays below 512MB
- No "Allowed memory size exhausted" errors

### 6.3 Batch Processing

**Objective:** Verify batch processing handles large datasets efficiently.

**Steps:**
```bash
# Test with different batch sizes
make wp-bricks ARGS='option update b2e_settings "{\"batch_size\":10}" --format=json'
make test-migration

make wp-bricks ARGS='option update b2e_settings "{\"batch_size\":50}" --format=json'
make test-migration
```

**Validation:**
- Smaller batches: More iterations, lower memory
- Larger batches: Fewer iterations, higher memory
- Both complete successfully

---

## 7. Error Handling Tests

### 7.1 Invalid API Key

**Objective:** Verify graceful handling of invalid API credentials.

**Steps:**
```bash
# Set invalid API key
make wp-bricks ARGS='option update b2e_settings "{\"target_url\":\"http://etch-wp\",\"api_key\":\"invalid\",\"api_username\":\"admin\"}" --format=json'

# Attempt migration
make test-migration
```

**Expected Result:**
- Migration fails with clear error message
- Error is logged in b2e_error_log
- No fatal PHP errors

**Validation:**
```bash
make wp-bricks ARGS='option get b2e_error_log'
# Expected: Error message about invalid credentials
```

### 7.2 Network Timeout

**Objective:** Verify handling of network connectivity issues.

**Steps:**
```bash
# Stop Etch container
docker-compose stop etch-wp

# Attempt migration
make test-migration
```

**Expected Result:**
- Migration fails gracefully
- Timeout error is logged
- Bricks site remains functional

**Validation:**
```bash
# Restart Etch
docker-compose start etch-wp

# Verify Bricks site is still accessible
curl -I http://localhost:8080
```

### 7.3 Database Error

**Objective:** Verify handling of database errors during migration.

**Steps:**
```bash
# Simulate database error by filling disk
# (Not recommended for production testing)

# Or test with invalid post data
make wp-bricks ARGS='eval "
  update_post_meta(999999, \"_bricks_page_content_2\", \"invalid json\");
"'
```

**Expected Result:**
- Error is caught and logged
- Migration continues with other posts
- Error count is tracked

---

## 8. Rollback Tests

### 8.1 Cleanup

**Objective:** Verify migration can be cleaned up/reset.

**Steps:**
```bash
# Run migration
make test-migration

# Check Etch post count
BEFORE=$(make wp-etch ARGS='post list --post_type=post --format=count')

# Reset Etch database
make wp-etch ARGS='db reset --yes'

# Reinstall WordPress
make wp-etch ARGS='core install --url=http://localhost:8081 --title="Etch Target" --admin_user=admin --admin_password=admin --admin_email=admin@local.dev'

# Check post count
AFTER=$(make wp-etch ARGS='post list --post_type=post --format=count')

echo "Before: $BEFORE, After: $AFTER"
```

**Expected Result:**
- Etch database is reset successfully
- Post count returns to 0 (or default)

### 8.2 Full Environment Reset

**Objective:** Verify complete environment can be reset.

**Steps:**
```bash
# Full cleanup
make clean
# Confirm with 'y'

# Rebuild
make setup
```

**Expected Result:**
- All containers and volumes removed
- Fresh setup completes successfully
- Both WordPress instances are clean

---

## 📊 Test Summary Template

Use this template to document test results:

```markdown
## Test Run: [Date]

**Environment:**
- Docker Version: [version]
- WordPress Version: [version]
- Plugin Version: [version]

**Pre-Flight Checks:**
- [ ] Docker installed
- [ ] Ports available
- [ ] Disk space sufficient

**Setup Tests:**
- [ ] Containers started
- [ ] WordPress installed
- [ ] Plugin activated
- [ ] Composer dependencies installed

**Integration Tests:**
- [ ] API connection successful
- [ ] Token generation working
- [ ] Migration starts

**End-to-End Tests:**
- [ ] Full migration completed
- [ ] Content integrity verified
- [ ] Media migrated
- [ ] CSS converted

**Performance:**
- Migration duration: [X] seconds
- Memory usage: [X] MB
- Batch size: [X]

**Errors Encountered:**
- [List any errors]

**Notes:**
- [Additional observations]
```

---

**Created:** 2025-10-23  
**Version:** 1.0
