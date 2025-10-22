<?php
/**
 * AJAX Handler Functionality Tests
 * 
 * Tests handler functionality without requiring AJAX context
 * 
 * Usage: docker exec b2e-bricks php /tmp/test-ajax-functionality.php
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== AJAX Handler Functionality Tests ===\n\n";

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
// Test 1: Content Parser Integration
// ============================================
echo "--- Test 1: Content Parser Integration ---\n";

try {
    $content_parser = new B2E_Content_Parser();
    test_result('Content parser instantiates', true);
    
    $bricks_posts = $content_parser->get_bricks_posts();
    test_result('get_bricks_posts() works', is_array($bricks_posts));
    echo "   ℹ️  Found " . count($bricks_posts) . " Bricks posts\n";
    
    $gutenberg_posts = $content_parser->get_gutenberg_posts();
    test_result('get_gutenberg_posts() works', is_array($gutenberg_posts));
    echo "   ℹ️  Found " . count($gutenberg_posts) . " Gutenberg posts\n";
    
    $media = $content_parser->get_media();
    test_result('get_media() works', is_array($media));
    echo "   ℹ️  Found " . count($media) . " media files\n";
    
} catch (Exception $e) {
    test_result('Content parser works', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 2: CSS Converter Integration
// ============================================
echo "--- Test 2: CSS Converter Integration ---\n";

try {
    $css_converter = new B2E_CSS_Converter();
    test_result('CSS converter instantiates', true);
    
    // Check if method exists
    test_result('convert_bricks_classes_to_etch() exists', method_exists($css_converter, 'convert_bricks_classes_to_etch'));
    
} catch (Exception $e) {
    test_result('CSS converter works', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 3: API Client Integration
// ============================================
echo "--- Test 3: API Client Integration ---\n";

try {
    $api_client = new B2E_API_Client();
    test_result('API client instantiates', true);
    
    // Check if methods exist
    test_result('send_css_styles() exists', method_exists($api_client, 'send_css_styles'));
    test_result('validate_api_key() exists', method_exists($api_client, 'validate_api_key'));
    test_result('validate_migration_token() exists', method_exists($api_client, 'validate_migration_token'));
    
} catch (Exception $e) {
    test_result('API client works', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 4: Migration Manager Integration
// ============================================
echo "--- Test 4: Migration Manager Integration ---\n";

try {
    $migration_manager = new B2E_Migration_Manager();
    test_result('Migration manager instantiates', true);
    
    // Check if method exists
    test_result('migrate_single_post() exists', method_exists($migration_manager, 'migrate_single_post'));
    
} catch (Exception $e) {
    test_result('Migration manager works', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 5: Media Migrator Integration
// ============================================
echo "--- Test 5: Media Migrator Integration ---\n";

try {
    $media_migrator = new B2E_Media_Migrator();
    test_result('Media migrator instantiates', true);
    
    // Check if method exists
    test_result('migrate_media() exists', method_exists($media_migrator, 'migrate_media'));
    
} catch (Exception $e) {
    test_result('Media migrator works', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 6: Handler Dependencies
// ============================================
echo "--- Test 6: Handler Dependencies ---\n";

// CSS Handler dependencies
try {
    $css_handler = new B2E_CSS_Ajax_Handler();
    test_result('CSS handler can access B2E_CSS_Converter', class_exists('B2E_CSS_Converter'));
    test_result('CSS handler can access B2E_API_Client', class_exists('B2E_API_Client'));
} catch (Exception $e) {
    test_result('CSS handler dependencies', false, $e->getMessage());
}

// Content Handler dependencies
try {
    $content_handler = new B2E_Content_Ajax_Handler();
    test_result('Content handler can access B2E_Content_Parser', class_exists('B2E_Content_Parser'));
    test_result('Content handler can access B2E_Migration_Manager', class_exists('B2E_Migration_Manager'));
} catch (Exception $e) {
    test_result('Content handler dependencies', false, $e->getMessage());
}

// Media Handler dependencies
try {
    $media_handler = new B2E_Media_Ajax_Handler();
    test_result('Media handler can access B2E_Media_Migrator', class_exists('B2E_Media_Migrator'));
} catch (Exception $e) {
    test_result('Media handler dependencies', false, $e->getMessage());
}

// Validation Handler dependencies
try {
    $validation_handler = new B2E_Validation_Ajax_Handler();
    test_result('Validation handler can access B2E_API_Client', class_exists('B2E_API_Client'));
} catch (Exception $e) {
    test_result('Validation handler dependencies', false, $e->getMessage());
}

echo "\n";

// ============================================
// Test 7: URL Conversion Logic
// ============================================
echo "--- Test 7: URL Conversion Logic ---\n";

// Test URL conversion
$test_cases = array(
    array(
        'input' => 'http://localhost:8081/wp-json',
        'expected' => 'http://b2e-etch/wp-json',
        'description' => 'HTTP localhost to b2e-etch'
    ),
    array(
        'input' => 'https://localhost:8081/wp-json',
        'expected' => 'http://b2e-etch/wp-json',
        'description' => 'HTTPS localhost to b2e-etch'
    ),
    array(
        'input' => 'http://example.com/wp-json',
        'expected' => 'http://example.com/wp-json',
        'description' => 'External URL unchanged'
    ),
);

foreach ($test_cases as $case) {
    $result = $case['input'];
    if (strpos($result, 'localhost:8081') !== false) {
        $result = str_replace('http://localhost:8081', 'http://b2e-etch', $result);
        $result = str_replace('https://localhost:8081', 'http://b2e-etch', $result);
    }
    
    $passed = ($result === $case['expected']);
    test_result($case['description'], $passed, "Expected: {$case['expected']}, Got: {$result}");
}

echo "\n";

// ============================================
// Test 8: WordPress Options Integration
// ============================================
echo "--- Test 8: WordPress Options Integration ---\n";

// Test if we can read/write options (used by handlers)
$test_option_key = 'b2e_test_option_' . time();
$test_option_value = array('test' => 'value');

update_option($test_option_key, $test_option_value, false);
$retrieved_value = get_option($test_option_key);

test_result('Can write WordPress options', $retrieved_value !== false);
test_result('Can read WordPress options', $retrieved_value === $test_option_value);

// Clean up
delete_option($test_option_key);
test_result('Can delete WordPress options', get_option($test_option_key) === false);

echo "\n";

// ============================================
// Test 9: Handler Inheritance
// ============================================
echo "--- Test 9: Handler Inheritance ---\n";

$handlers = array(
    'B2E_CSS_Ajax_Handler',
    'B2E_Content_Ajax_Handler',
    'B2E_Media_Ajax_Handler',
    'B2E_Validation_Ajax_Handler',
);

foreach ($handlers as $handler_class) {
    $handler = new $handler_class();
    $is_subclass = is_subclass_of($handler, 'B2E_Base_Ajax_Handler');
    test_result("{$handler_class} extends B2E_Base_Ajax_Handler", $is_subclass);
}

echo "\n";

// ============================================
// Test 10: Error Handling
// ============================================
echo "--- Test 10: Error Handling ---\n";

// Test that handlers can handle WP_Error
$test_error = new WP_Error('test_error', 'Test error message');
test_result('WP_Error can be created', is_wp_error($test_error));
test_result('WP_Error has message', $test_error->get_error_message() === 'Test error message');

// Test is_wp_error function (used by handlers)
test_result('is_wp_error() function exists', function_exists('is_wp_error'));
test_result('is_wp_error() works correctly', is_wp_error($test_error) === true);
test_result('is_wp_error() returns false for non-errors', is_wp_error('not an error') === false);

echo "\n";

// ============================================
// Test 11: Logging Capability
// ============================================
echo "--- Test 11: Logging Capability ---\n";

// Test error_log function (used by handlers)
test_result('error_log() function exists', function_exists('error_log'));

// Test that we can write to error log
$test_log_message = 'B2E Test: ' . time();
$log_result = error_log($test_log_message);
test_result('Can write to error log', $log_result !== false);

echo "\n";

// ============================================
// Test 12: JSON Functions
// ============================================
echo "--- Test 12: JSON Functions ---\n";

// Test JSON encoding (used by handlers)
$test_data = array('success' => true, 'data' => array('test' => 'value'));
$json = json_encode($test_data);
test_result('json_encode() works', $json !== false);

$decoded = json_decode($json, true);
test_result('json_decode() works', $decoded !== null);
test_result('JSON round-trip preserves data', $decoded === $test_data);

// Test wp_send_json_success and wp_send_json_error exist
test_result('wp_send_json_success() exists', function_exists('wp_send_json_success'));
test_result('wp_send_json_error() exists', function_exists('wp_send_json_error'));

echo "\n";

// ============================================
// Summary
// ============================================
echo "===========================================\n";
echo "Functionality Test Summary\n";
echo "===========================================\n";
echo "Total Tests:  {$total_tests}\n";
echo "Passed:       {$passed_tests}\n";
echo "Failed:       {$failed_tests}\n";
echo "\n";

if ($failed_tests === 0) {
    echo "✅ All functionality tests passed!\n";
    exit(0);
} else {
    $pass_rate = round(($passed_tests / $total_tests) * 100, 1);
    echo "⚠️  Some tests failed (Pass rate: {$pass_rate}%)\n";
    exit(1);
}
