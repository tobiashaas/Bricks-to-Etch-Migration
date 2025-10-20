<?php
/**
 * Plugin Name: Bricks to Etch Migration
 * Plugin URI: https://github.com/tobiashaas/Bricks-to-Etch-Migration
 * Description: Modern, lightweight migration tool for converting Bricks Builder to Etch PageBuilder.
 * Version: 2.0.0
 * Author: Tobias Haas
 * License: GPL v2 or later
 * Text Domain: bricks-etch-migration
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('B2E_VERSION', '2.0.0');
define('B2E_PLUGIN_FILE', __FILE__);
define('B2E_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('B2E_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load modern architecture
require_once B2E_PLUGIN_DIR . 'bootstrap.php';

// Initialize plugin
add_action('plugins_loaded', function() {
    b2e_get_plugin()->init();
}, 5);

// Activation hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up options
    delete_option('b2e_style_map');
    delete_option('etch_styles');
    delete_option('b2e_migration_token');
    flush_rewrite_rules();
});
