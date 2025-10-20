<?php
/**
 * Bootstrap File
 * 
 * Initializes the new modular architecture
 * Can be loaded alongside legacy code during migration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Autoloader for new architecture
spl_autoload_register(function ($class) {
    // Only autoload our namespace
    if (strpos($class, 'BricksEtchMigration\\') !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $class = str_replace('BricksEtchMigration\\', '', $class);
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/src/' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize plugin
function b2e_get_plugin() {
    return \BricksEtchMigration\Core\Plugin::getInstance();
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    b2e_get_plugin()->init();
}, 5); // Priority 5 to run before other initializations

// Helper function to get services
function b2e_service($id) {
    return b2e_get_plugin()->get($id);
}
