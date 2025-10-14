<?php
/**
 * Autoloader for Bricks to Etch Migration Plugin
 * 
 * PSR-4 compatible autoloader for the plugin classes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Autoloader {
    
    /**
     * Namespace prefix
     */
    private static $prefix = 'B2E_';
    
    /**
     * Base directory for the namespace prefix
     */
    private static $base_dir = '';
    
    /**
     * Initialize the autoloader
     */
    public static function init() {
        self::$base_dir = B2E_PLUGIN_DIR . 'includes/';
        
        spl_autoload_register(array(__CLASS__, 'load_class'));
    }
    
    /**
     * Load the class file for a given class name
     * 
     * @param string $class_name The fully-qualified class name
     */
    public static function load_class($class_name) {
        // Does the class use the namespace prefix?
        $prefix_length = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class_name, $prefix_length) !== 0) {
            // No, move to the next registered autoloader
            return;
        }
        
        // Get the relative class name
        $relative_class = substr($class_name, $prefix_length);
        
        // Convert class name to file name
        // B2E_Admin_Interface -> admin_interface.php
        $file_name = strtolower($relative_class) . '.php';
        $file = self::$base_dir . $file_name;
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}
