<?php
/**
 * Test AJAX Handlers
 * 
 * Comprehensive tests for the new modular AJAX handler structure
 * 
 * Usage: docker exec b2e-bricks php /tmp/test-ajax-handlers.php
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Testing AJAX Handlers (Phase 2) ===\n\n";

// Test counters
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;

function test_result($test_name, $passed, $message = '') {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    
    if ($passed) {
        $passed_tests++;
        echo "✅ PASS: {$test_name}\n";
    } else {
        $failed_tests++;
        echo "❌ FAIL: {$test_name}\n";
        if ($message) {
            echo "   → {$message}\n";
        }
    }
}

// ============================================
// Test 1: Check if AJAX handler files exist
// ============================================
echo "--- Test 1: File Structure ---\n";

$files_to_check = array(
    '/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/class-base-ajax-handler.php',
    '/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/class-ajax-handler.php',
    '/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/handlers/class-css-ajax.php',
    '/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/handlers/class-content-ajax.php',
    '/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/handlers/class-media-ajax.php',
    '/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/handlers/class-validation-ajax.php',
);

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    test_result(basename($file) . ' exists', $exists, $exists ? '' : "File not found: {$file}");
}

echo "\n";

// ============================================
// Test 2: Check if classes are loadable
// ============================================
echo "--- Test 2: Class Loading ---\n";

try {
    require_once('/var/www/html/wp-content/plugins/etch-fusion-suite/includes/ajax/class-ajax-handler.php');
    test_result('B2E_Ajax_Handler class loaded', class_exists('B2E_Ajax_Handler'));
} catch (Exception $e) {
    test_result('B2E_Ajax_Handler class loaded', false, $e->getMessage());
}

try {
    test_result('B2E_Base_Ajax_Handler class loaded', class_exists('B2E_Base_Ajax_Handler'));
} catch (Exception $e) {
    test_result('B2E_Base_Ajax_Handler class loaded', false, $e->getMessage());
}

try {
    test_result('B2E_CSS_Ajax_Handler class loaded', class_exists('B2E_CSS_Ajax_Handler'));
} catch (Exception $e) {
    test_result('B2E_CSS_Ajax_Handler class loaded', false, $e->getMessage());
}

try {
    test_result('B2E_Content_Ajax_Handler class loaded', class_exists('B2E_Content_Ajax_Handler'));
} catch (Exception $e) {
    test_result('B2E_Content_Ajax_Handler class loaded', false, $e->getMessage());
}

try {
    test_result('B2E_Media_Ajax_Handler class loaded', class_exists('B2E_Media_Ajax_Handler'));
} catch (Exception $e) {
    test_result('B2E_Media_Ajax_Handler class loaded', false, $e->getMessage());
}

try {
    test_result('B2E_Validation_Ajax_Handler class loaded', class_exists('B2E_Validation_Ajax_Handler'));
} catch (Exception $e) {
    test_result('B2E_Validation_Ajax_Handler class loaded', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 3: Check if AJAX actions are registered
// ============================================
echo "--- Test 3: AJAX Action Registration ---\n";

global $wp_filter;

$actions_to_check = array(
    'wp_ajax_b2e_migrate_css',
    'wp_ajax_b2e_migrate_batch',
    'wp_ajax_b2e_get_bricks_posts',
    'wp_ajax_b2e_migrate_media',
    'wp_ajax_b2e_validate_api_key',
    'wp_ajax_b2e_validate_migration_token',
);

foreach ($actions_to_check as $action) {
    $registered = isset($wp_filter[$action]) && !empty($wp_filter[$action]);
    test_result("{$action} registered", $registered, $registered ? '' : "Action not registered");
}

echo "\n";

// ============================================
// Test 4: Test Base Handler Methods
// ============================================
echo "--- Test 4: Base Handler Methods ---\n";

// Create a test handler that extends base
class Test_Ajax_Handler extends B2E_Base_Ajax_Handler {
    protected function register_hooks() {
        // No hooks needed for testing
    }
    
    public function test_verify_nonce() {
        return method_exists($this, 'verify_nonce');
    }
    
    public function test_check_capability() {
        return method_exists($this, 'check_capability');
    }
    
    public function test_verify_request() {
        return method_exists($this, 'verify_request');
    }
    
    public function test_get_post() {
        return method_exists($this, 'get_post');
    }
    
    public function test_sanitize_url() {
        return method_exists($this, 'sanitize_url');
    }
    
    public function test_sanitize_text() {
        return method_exists($this, 'sanitize_text');
    }
    
    public function test_log() {
        return method_exists($this, 'log');
    }
}

try {
    $test_handler = new Test_Ajax_Handler();
    test_result('Base handler instantiates', true);
    test_result('verify_nonce() method exists', $test_handler->test_verify_nonce());
    test_result('check_capability() method exists', $test_handler->test_check_capability());
    test_result('verify_request() method exists', $test_handler->test_verify_request());
    test_result('get_post() method exists', $test_handler->test_get_post());
    test_result('sanitize_url() method exists', $test_handler->test_sanitize_url());
    test_result('sanitize_text() method exists', $test_handler->test_sanitize_text());
    test_result('log() method exists', $test_handler->test_log());
} catch (Exception $e) {
    test_result('Base handler instantiates', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 5: Test Handler Instantiation
// ============================================
echo "--- Test 5: Handler Instantiation ---\n";

try {
    $ajax_handler = new B2E_Ajax_Handler();
    test_result('Main AJAX handler instantiates', true);
    
    // Check if handlers are initialized
    $css_handler = $ajax_handler->get_handler('css');
    test_result('CSS handler accessible', $css_handler !== null);
    
    $content_handler = $ajax_handler->get_handler('content');
    test_result('Content handler accessible', $content_handler !== null);
    
    $media_handler = $ajax_handler->get_handler('media');
    test_result('Media handler accessible', $media_handler !== null);
    
    $validation_handler = $ajax_handler->get_handler('validation');
    test_result('Validation handler accessible', $validation_handler !== null);
    
} catch (Exception $e) {
    test_result('Main AJAX handler instantiates', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 6: Test CSS Handler Methods
// ============================================
echo "--- Test 6: CSS Handler Methods ---\n";

try {
    $css_handler = new B2E_CSS_Ajax_Handler();
    test_result('CSS handler instantiates', true);
    test_result('CSS handler has migrate_css method', method_exists($css_handler, 'migrate_css'));
} catch (Exception $e) {
    test_result('CSS handler instantiates', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 7: Test Content Handler Methods
// ============================================
echo "--- Test 7: Content Handler Methods ---\n";

try {
    $content_handler = new B2E_Content_Ajax_Handler();
    test_result('Content handler instantiates', true);
    test_result('Content handler has migrate_batch method', method_exists($content_handler, 'migrate_batch'));
    test_result('Content handler has get_bricks_posts method', method_exists($content_handler, 'get_bricks_posts'));
} catch (Exception $e) {
    test_result('Content handler instantiates', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 8: Test Media Handler Methods
// ============================================
echo "--- Test 8: Media Handler Methods ---\n";

try {
    $media_handler = new B2E_Media_Ajax_Handler();
    test_result('Media handler instantiates', true);
    test_result('Media handler has migrate_media method', method_exists($media_handler, 'migrate_media'));
} catch (Exception $e) {
    test_result('Media handler instantiates', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 9: Test Validation Handler Methods
// ============================================
echo "--- Test 9: Validation Handler Methods ---\n";

try {
    $validation_handler = new B2E_Validation_Ajax_Handler();
    test_result('Validation handler instantiates', true);
    test_result('Validation handler has validate_api_key method', method_exists($validation_handler, 'validate_api_key'));
    test_result('Validation handler has validate_migration_token method', method_exists($validation_handler, 'validate_migration_token'));
} catch (Exception $e) {
    test_result('Validation handler instantiates', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 10: Test Plugin Integration
// ============================================
echo "--- Test 10: Plugin Integration ---\n";

// Check if plugin has ajax_handler property
$plugin = etch_fusion_suite::get_instance();
test_result('Plugin instance exists', $plugin !== null);

// Use reflection to check private property
try {
    $reflection = new ReflectionClass($plugin);
    $property = $reflection->getProperty('ajax_handler');
    $property->setAccessible(true);
    $ajax_handler_instance = $property->getValue($plugin);
    test_result('Plugin has ajax_handler property', $ajax_handler_instance !== null);
    test_result('ajax_handler is B2E_Ajax_Handler instance', $ajax_handler_instance instanceof B2E_Ajax_Handler);
} catch (Exception $e) {
    test_result('Plugin has ajax_handler property', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 11: Test Backwards Compatibility
// ============================================
echo "--- Test 11: Backwards Compatibility ---\n";

// Check if old AJAX handlers still exist in admin_interface
test_result('Old ajax_migrate_css still exists', method_exists('B2E_Admin_Interface', 'ajax_migrate_css'));
test_result('Old ajax_migrate_batch still exists', method_exists('B2E_Admin_Interface', 'ajax_migrate_batch'));
test_result('Old ajax_get_bricks_posts still exists', method_exists('B2E_Admin_Interface', 'ajax_get_bricks_posts'));
test_result('Old ajax_migrate_media still exists', method_exists('B2E_Admin_Interface', 'ajax_migrate_media'));

echo "\n";

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "Test Summary\n";
echo "===========================================\n";
echo "Total Tests:  {$total_tests}\n";
echo "Passed:       {$passed_tests}\n";
echo "Failed:       {$failed_tests}\n";
echo "\n";

if ($failed_tests === 0) {
    echo "✅ All tests passed!\n";
    exit(0);
} else {
    $pass_rate = round(($passed_tests / $total_tests) * 100, 1);
    echo "⚠️  Some tests failed (Pass rate: {$pass_rate}%)\n";
    exit(1);
}
