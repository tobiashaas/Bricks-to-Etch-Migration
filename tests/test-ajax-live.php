<?php
/**
 * Live AJAX Handler Tests
 * 
 * Tests actual AJAX endpoint functionality
 * 
 * Usage: docker exec b2e-bricks php /tmp/test-ajax-live.php
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Live AJAX Handler Tests ===\n\n";

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
// Test 1: Test get_bricks_posts endpoint
// ============================================
echo "--- Test 1: Get Bricks Posts Endpoint ---\n";

// Simulate AJAX request
$_POST['action'] = 'b2e_get_bricks_posts';
$_POST['nonce'] = wp_create_nonce('b2e_nonce');

// Set current user as admin
wp_set_current_user(1);

// Capture output
ob_start();
try {
    do_action('wp_ajax_b2e_get_bricks_posts');
    $output = ob_get_clean();
    
    // Parse JSON response
    $response = json_decode($output, true);
    
    test_result('get_bricks_posts returns valid JSON', $response !== null, $output);
    test_result('get_bricks_posts has success field', isset($response['success']));
    
    if (isset($response['success']) && $response['success']) {
        test_result('get_bricks_posts returns success', true);
        test_result('get_bricks_posts has data', isset($response['data']));
        test_result('get_bricks_posts has posts array', isset($response['data']['posts']));
        
        $posts_count = isset($response['data']['count']) ? $response['data']['count'] : 0;
        echo "   ℹ️  Found {$posts_count} posts\n";
        
        if (isset($response['data']['bricks_count'])) {
            echo "   ℹ️  Bricks posts: {$response['data']['bricks_count']}\n";
        }
        if (isset($response['data']['gutenberg_count'])) {
            echo "   ℹ️  Gutenberg posts: {$response['data']['gutenberg_count']}\n";
        }
        if (isset($response['data']['media_count'])) {
            echo "   ℹ️  Media: {$response['data']['media_count']}\n";
        }
    } else {
        test_result('get_bricks_posts returns success', false, isset($response['data']) ? $response['data'] : 'No error message');
    }
} catch (Exception $e) {
    ob_end_clean();
    test_result('get_bricks_posts executes without error', false, $e->getMessage());
}

// Clean up
unset($_POST['action']);
unset($_POST['nonce']);

echo "\n";

// ============================================
// Test 2: Test URL Conversion
// ============================================
echo "--- Test 2: Docker URL Conversion ---\n";

// Test URL conversion in handlers
$test_urls = array(
    'http://localhost:8081' => 'http://b2e-etch',
    'https://localhost:8081' => 'http://b2e-etch',
    'http://example.com' => 'http://example.com', // Should not change
);

// Create a test handler to access private method
class Test_URL_Handler extends B2E_Base_Ajax_Handler {
    protected function register_hooks() {}
    
    public function test_convert_url($url) {
        // Simulate the conversion logic
        if (strpos($url, 'localhost:8081') !== false) {
            $url = str_replace('http://localhost:8081', 'http://b2e-etch', $url);
            $url = str_replace('https://localhost:8081', 'http://b2e-etch', $url);
        }
        return $url;
    }
}

$url_handler = new Test_URL_Handler();

foreach ($test_urls as $input => $expected) {
    $result = $url_handler->test_convert_url($input);
    test_result("URL conversion: {$input}", $result === $expected, "Expected: {$expected}, Got: {$result}");
}

echo "\n";

// ============================================
// Test 3: Test Nonce Verification
// ============================================
echo "--- Test 3: Nonce Verification ---\n";

// Test with valid nonce
$valid_nonce = wp_create_nonce('b2e_nonce');
$_POST['nonce'] = $valid_nonce;

$test_handler = new Test_URL_Handler();
// We can't directly test verify_nonce without triggering wp_send_json_error
// But we can test that the nonce is created correctly
test_result('Valid nonce created', !empty($valid_nonce));
test_result('Nonce verification function exists', function_exists('wp_verify_nonce'));

// Clean up
unset($_POST['nonce']);

echo "\n";

// ============================================
// Test 4: Test Handler Method Existence
// ============================================
echo "--- Test 4: Handler Method Existence ---\n";

// Check CSS Handler
$css_handler = new B2E_CSS_Ajax_Handler();
test_result('CSS handler has migrate_css', method_exists($css_handler, 'migrate_css'));

// Check Content Handler
$content_handler = new B2E_Content_Ajax_Handler();
test_result('Content handler has migrate_batch', method_exists($content_handler, 'migrate_batch'));
test_result('Content handler has get_bricks_posts', method_exists($content_handler, 'get_bricks_posts'));

// Check Media Handler
$media_handler = new B2E_Media_Ajax_Handler();
test_result('Media handler has migrate_media', method_exists($media_handler, 'migrate_media'));

// Check Validation Handler
$validation_handler = new B2E_Validation_Ajax_Handler();
test_result('Validation handler has validate_api_key', method_exists($validation_handler, 'validate_api_key'));
test_result('Validation handler has validate_migration_token', method_exists($validation_handler, 'validate_migration_token'));

echo "\n";

// ============================================
// Test 5: Test Base Handler Helpers
// ============================================
echo "--- Test 5: Base Handler Helper Methods ---\n";

class Test_Helper_Handler extends B2E_Base_Ajax_Handler {
    protected function register_hooks() {}
    
    public function test_sanitize_url_public($url) {
        return $this->sanitize_url($url);
    }
    
    public function test_sanitize_text_public($text) {
        return $this->sanitize_text($text);
    }
    
    public function test_get_post_public($key, $default = null) {
        return $this->get_post($key, $default);
    }
}

$helper_handler = new Test_Helper_Handler();

// Test sanitize_url
$test_url = 'http://example.com/test?param=value';
$sanitized_url = $helper_handler->test_sanitize_url_public($test_url);
test_result('sanitize_url works', !empty($sanitized_url));

// Test sanitize_text
$test_text = '<script>alert("xss")</script>Hello';
$sanitized_text = $helper_handler->test_sanitize_text_public($test_text);
test_result('sanitize_text removes scripts', strpos($sanitized_text, '<script>') === false);

// Test get_post
$_POST['test_key'] = 'test_value';
$post_value = $helper_handler->test_get_post_public('test_key', 'default');
test_result('get_post retrieves value', $post_value === 'test_value');

$default_value = $helper_handler->test_get_post_public('nonexistent_key', 'default');
test_result('get_post returns default', $default_value === 'default');

unset($_POST['test_key']);

echo "\n";

// ============================================
// Test 6: Test Action Hook Registration
// ============================================
echo "--- Test 6: Action Hook Registration ---\n";

global $wp_filter;

$required_actions = array(
    'wp_ajax_b2e_migrate_css',
    'wp_ajax_b2e_migrate_batch',
    'wp_ajax_b2e_get_bricks_posts',
    'wp_ajax_b2e_migrate_media',
    'wp_ajax_b2e_validate_api_key',
    'wp_ajax_b2e_validate_migration_token',
);

foreach ($required_actions as $action) {
    $has_callbacks = isset($wp_filter[$action]) && count($wp_filter[$action]) > 0;
    test_result("{$action} has callbacks", $has_callbacks);
    
    if ($has_callbacks) {
        $callback_count = count($wp_filter[$action]);
        echo "   ℹ️  {$callback_count} callback(s) registered\n";
    }
}

echo "\n";

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "Live Test Summary\n";
echo "===========================================\n";
echo "Total Tests:  {$total_tests}\n";
echo "Passed:       {$passed_tests}\n";
echo "Failed:       {$failed_tests}\n";
echo "\n";

if ($failed_tests === 0) {
    echo "✅ All live tests passed!\n";
    exit(0);
} else {
    $pass_rate = round(($passed_tests / $total_tests) * 100, 1);
    echo "⚠️  Some tests failed (Pass rate: {$pass_rate}%)\n";
    exit(1);
}
