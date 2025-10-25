<?php
/**
 * Test AJAX Handlers (LocalWP Version)
 * 
 * Comprehensive tests for the new modular AJAX handler structure
 */

echo "=== Testing AJAX Handlers (EFS) ===\n\n";

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

// Test 1: Check if classes are loaded
echo "--- Test 1: Class Loading ---\n";

test_result('EFS_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\EFS_Ajax_Handler'));
test_result('EFS_Base_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\EFS_Base_Ajax_Handler'));
test_result('EFS_CSS_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\Handlers\EFS_CSS_Ajax_Handler'));
test_result('EFS_Content_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\Handlers\EFS_Content_Ajax_Handler'));
test_result('EFS_Media_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\Handlers\EFS_Media_Ajax_Handler'));
test_result('EFS_Validation_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\Handlers\EFS_Validation_Ajax_Handler'));
test_result('EFS_Logs_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\Handlers\EFS_Logs_Ajax_Handler'));
test_result('EFS_Connection_Ajax_Handler class loaded', class_exists('Bricks2Etch\Ajax\Handlers\EFS_Connection_Ajax_Handler'));

echo "\n";

// Test 2: Check if AJAX actions are registered
echo "--- Test 2: AJAX Action Registration ---\n";

global $wp_filter;

// Ensure admin hooks fired so AJAX handlers register their actions.
if ( ! defined('WP_ADMIN') ) {
    define('WP_ADMIN', true);
}

if ( function_exists('efs_container') ) {
    $container = efs_container();
    if ( $container && method_exists( $container, 'get' ) && $container->has( 'admin_interface' ) ) {
        // Instantiating the admin interface registers AJAX actions.
        $container->get( 'admin_interface' );
    }
}

$actions_to_check = array(
    'wp_ajax_efs_migrate_css',
    'wp_ajax_efs_migrate_batch',
    'wp_ajax_efs_get_bricks_posts',
    'wp_ajax_efs_migrate_media',
    'wp_ajax_efs_validate_api_key',
    'wp_ajax_efs_validate_migration_token',
    'wp_ajax_efs_get_logs',
    'wp_ajax_efs_clear_logs',
    'wp_ajax_efs_test_connection',
    'wp_ajax_efs_generate_migration_key',
    'wp_ajax_efs_cancel_migration',
);

foreach ($actions_to_check as $action) {
    $registered = isset($wp_filter[$action]) && !empty($wp_filter[$action]);
    test_result("{$action} registered", $registered, $registered ? '' : "Action not registered");
}

echo "\n";

// Test 3: Check converter classes
echo "--- Test 3: Converter Classes ---\n";

test_result('EFS_Element_Factory class loaded', class_exists('Bricks2Etch\Converters\EFS_Element_Factory'));
test_result('EFS_Gutenberg_Generator class loaded', class_exists('Bricks2Etch\Parsers\EFS_Gutenberg_Generator'));

echo "\n";

// Test 4: Check service container
echo "--- Test 4: Service Container ---\n";

if (function_exists('efs_container')) {
    $container = efs_container();
    test_result('Service container accessible', $container !== null);
    
    // Test some core services
    $services_to_check = ['error_handler', 'api_client', 'cors_manager'];
    foreach ($services_to_check as $service) {
        try {
            $instance = $container->get($service);
            test_result("Service '{$service}' available", $instance !== null);
        } catch (Exception $e) {
            test_result("Service '{$service}' available", false, $e->getMessage());
        }
    }
} else {
    test_result('Service container accessible', false, 'efs_container() function not found');
}

echo "\n";

// Summary
echo "===========================================\n";
echo "Test Summary\n";
echo "===========================================\n";
echo "Total Tests:  {$total_tests}\n";
echo "Passed:       {$passed_tests}\n";
echo "Failed:       {$failed_tests}\n";
echo "Success Rate: " . ($total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0) . "%\n";
echo "===========================================\n";

if ($failed_tests > 0) {
    exit(1);
}
