<?php
/**
 * Test Runner for LocalWP Environment
 * 
 * Run this from your LocalWP site directory:
 * php C:\Github\Bricks2Etch\tests\run-local-tests.php
 * 
 * Or set the WP_PATH environment variable:
 * set WP_PATH=C:\Users\YourName\Local Sites\bricks\app\public
 * php run-local-tests.php
 */

// Determine WordPress path
$wp_path = getenv('WP_PATH');

if (!$wp_path) {
    // Try common LocalWP locations
    $username = getenv('USERNAME') ?: getenv('USER');
    $possible_paths = [
        "C:\\Users\\{$username}\\Local Sites\\bricks\\app\\public",
        "C:\\Users\\{$username}\\Local Sites\\etch\\app\\public",
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path . '/wp-load.php')) {
            $wp_path = $path;
            break;
        }
    }
}

if (!$wp_path || !file_exists($wp_path . '/wp-load.php')) {
    echo "❌ WordPress not found!\n\n";
    echo "Please set WP_PATH environment variable:\n";
    echo "  Windows: set WP_PATH=C:\\Users\\YourName\\Local Sites\\bricks\\app\\public\n";
    echo "  Or run from LocalWP site directory\n\n";
    exit(1);
}

echo "✅ Found WordPress at: {$wp_path}\n\n";

// Load WordPress
require_once($wp_path . '/wp-load.php');

// Check if plugin is active
if (!defined('EFS_PLUGIN_VERSION')) {
    $plugin_candidates = [
        $wp_path . '/wp-content/plugins/etch-fusion-suite/etch-fusion-suite.php',
        dirname(__DIR__) . '/etch-fusion-suite/etch-fusion-suite.php',
    ];

    $plugin_loaded = false;
    foreach ($plugin_candidates as $plugin_file) {
        if (file_exists($plugin_file)) {
            require_once $plugin_file;
            $plugin_loaded = true;
            break;
        }
    }

    if (!defined('EFS_PLUGIN_VERSION')) {
        echo "❌ Etch Fusion Suite plugin not found or not active!\n";
        if ($plugin_loaded) {
            echo "Plugin file was loaded but constants are missing—verify plugin bootstrap.\n\n";
        } else {
            echo "Please activate the plugin in WordPress admin or ensure it exists at wp-content/plugins/etch-fusion-suite.\n\n";
        }
        exit(1);
    }
}

echo "✅ Etch Fusion Suite v" . EFS_PLUGIN_VERSION . " loaded\n\n";

// Run tests
$test_files = [
    __DIR__ . '/test-element-converters-local.php',
    __DIR__ . '/test-ajax-handlers-local.php',
];

foreach ($test_files as $test_file) {
    if (file_exists($test_file)) {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Running: " . basename($test_file) . "\n";
        echo str_repeat('=', 60) . "\n\n";
        include $test_file;
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "✅ All tests completed!\n";
echo str_repeat('=', 60) . "\n";
