<?php
/**
 * PHPUnit bootstrap file for Bricks to Etch Migration Plugin
 *
 * @package Bricks2Etch\Tests
 */

// Define test environment
define('B2E_TESTS_DIR', __DIR__);
define('B2E_PLUGIN_DIR', dirname(__DIR__));

// WordPress tests directory
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// WordPress core directory
$_core_dir = getenv('WP_CORE_DIR');
if (!$_core_dir) {
    $_core_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress';
}

// Check if WordPress test suite is available
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress test suite at: $_tests_dir\n";
    echo "Please install WordPress test suite:\n";
    echo "  bash bin/install-wp-tests.sh wordpress_test root '' localhost latest\n";
    exit(1);
}

// Load Composer autoloader
if (file_exists(B2E_PLUGIN_DIR . '/vendor/autoload.php')) {
    require_once B2E_PLUGIN_DIR . '/vendor/autoload.php';
}

// Load WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin for testing
 */
function _manually_load_plugin() {
    require B2E_PLUGIN_DIR . '/bricks-etch-migration.php';
}

// Register plugin activation
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WordPress test suite
require $_tests_dir . '/includes/bootstrap.php';
