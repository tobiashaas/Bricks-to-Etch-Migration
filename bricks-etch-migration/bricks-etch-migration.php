<?php
/**
 * Plugin Name: Bricks to Etch Migration
 * Plugin URI: https://github.com/tobiashaas/Bricks-to-Etch-Migration
 * Description: One-time migration tool for converting Bricks Builder websites to Etch PageBuilder with complete automation.
 * Version: 0.5.1
 * Author: Tobias Haas
 * License: GPL v2 or later
 * Text Domain: bricks-etch-migration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('B2E_VERSION', '0.3.7');
define('B2E_PLUGIN_FILE', __FILE__);
define('B2E_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('B2E_PLUGIN_URL', plugin_dir_url(__FILE__));
define('B2E_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load required classes (in correct order)
require_once B2E_PLUGIN_DIR . 'includes/error_handler.php';
require_once B2E_PLUGIN_DIR . 'includes/plugin_detector.php';
require_once B2E_PLUGIN_DIR . 'includes/content_parser.php';
require_once B2E_PLUGIN_DIR . 'includes/css_converter.php';
require_once B2E_PLUGIN_DIR . 'includes/gutenberg_generator.php';
require_once B2E_PLUGIN_DIR . 'includes/dynamic_data_converter.php';
require_once B2E_PLUGIN_DIR . 'includes/api_client.php';
require_once B2E_PLUGIN_DIR . 'includes/api_endpoints.php';
require_once B2E_PLUGIN_DIR . 'includes/custom_fields_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/acf_field_groups_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/metabox_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/cpt_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/media_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/migration_token_manager.php';
require_once B2E_PLUGIN_DIR . 'includes/migration_manager.php';
require_once B2E_PLUGIN_DIR . 'includes/admin_interface.php';

// Load AJAX handlers (NEW - v0.5.1)
require_once B2E_PLUGIN_DIR . 'includes/ajax/class-ajax-handler.php';

// Main plugin class
class Bricks_Etch_Migration {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Admin interface instance
     */
    private $admin_interface = null;
    
    /**
     * AJAX handler instance (NEW - v0.5.1)
     */
    private $ajax_handler = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        // Admin menu is now handled by B2E_Admin_Interface class only
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize REST API endpoints immediately
        add_action('plugins_loaded', array($this, 'init_rest_api'));
        
        // Enable Application Passwords for local development (disable HTTPS requirement)
        add_filter('wp_is_application_passwords_available', '__return_true');
        
        // Activation/Deactivation hooks are registered at the end of the file
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('bricks-etch-migration', false, dirname(B2E_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize admin interface with menu registration
        if (is_admin()) {
            $this->admin_interface = new B2E_Admin_Interface(true);
        }
        
        // Always initialize admin interface for AJAX handlers
        if (!isset($this->admin_interface)) {
            $this->admin_interface = new B2E_Admin_Interface(false);
        }
        
        // Initialize AJAX handlers (NEW - v0.5.1)
        $this->ajax_handler = new B2E_Ajax_Handler();
        
        // Initialize error handler
        new B2E_Error_Handler();
    }
    
    /**
     * Initialize REST API endpoints
     */
    public function init_rest_api() {
        // Add CORS headers for API requests first
        $this->add_cors_headers();
        
        // Initialize API endpoints when REST API is ready
        B2E_API_Endpoints::init();
    }
    
    /**
     * Add CORS headers for API requests
     */
    private function add_cors_headers() {
        // Add CORS headers for REST API requests
        add_action('rest_api_init', function() {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', function($value) {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
                header('Access-Control-Allow-Credentials: true');
                return $value;
            });
        });
        
        // Handle preflight OPTIONS requests
        add_action('rest_api_init', function() {
            add_filter('rest_pre_dispatch', function($result, $server, $request) {
                if ($request->get_method() === 'OPTIONS') {
                    return new WP_REST_Response(null, 200);
                }
                return $result;
            }, 10, 3);
        });
    }
    
    /**
     * Add admin menu - REMOVED: This was causing duplicate menus
     * Admin menu is now handled by B2E_Admin_Interface class only
     */
    
    /**
     * Admin page callback - REMOVED: This was causing duplicate dashboards
     * Dashboard rendering is now handled by B2E_Admin_Interface class only
     */
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Plugin activation tasks
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation with complete cleanup
     */
    public static function deactivate() {
        // Clear ALL plugin data on deactivation
        global $wpdb;
        
        // Clear transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_b2e_%' 
             OR option_name LIKE '_transient_timeout_b2e_%'"
        );
        
        // Clear all B2E options
        $b2e_options = [
            'b2e_settings',
            'b2e_migration_progress', 
            'b2e_migration_token',
            'b2e_migration_token_value',
            'b2e_private_key',
            'b2e_error_log',
            'b2e_api_key',
            'b2e_import_api_key',
            'b2e_export_api_key',
            'b2e_migration_settings'
        ];
        
        foreach ($b2e_options as $option) {
            delete_option($option);
        }
        
        // Clear user meta
        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '%b2e%'"
        );
        
        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_bricks-etch-migration' !== $hook) {
            return;
        }
        
        // Note: JavaScript is now inline in admin_interface.php for better integration
        // Disabling external admin.js to prevent conflicts
        /*
        wp_enqueue_script(
            'b2e-admin',
            B2E_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            B2E_VERSION,
            true
        );
        */
        
        wp_enqueue_style(
            'b2e-admin',
            B2E_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            B2E_VERSION
        );
        
        // Note: wp_localize_script disabled since we're using inline JavaScript
        /*
        wp_localize_script('b2e-admin', 'b2eData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2e_nonce'),
            'strings' => array(
                'confirmMigration' => __('Are you sure you want to start the migration?', 'bricks-etch-migration'),
                'migrationStarted' => __('Migration started successfully!', 'bricks-etch-migration'),
                'errorOccurred' => __('An error occurred. Please check the logs.', 'bricks-etch-migration'),
            )
        ));
        */
    }
    
    /**
     * Plugin activation - DUPLICATE REMOVED
     * Using static version instead
     */
    
    /**
     * Plugin deactivation - DUPLICATE REMOVED  
     * Using static version instead
     */
    
    /**
     * Create migration tables/options - REMOVED
     * No longer needed with static activation
     */
    
    /**
     * Clean up transients - REMOVED
     * No longer needed with static deactivation
     */
}

/**
 * Global debug helper function for development
 * 
 * @param string $message Debug message
 * @param mixed $data Optional data to log
 * @param string $context Context identifier (default: B2E_DEBUG)
 */
function b2e_debug_log($message, $data = null, $context = 'B2E_DEBUG') {
    if (!WP_DEBUG || !WP_DEBUG_LOG) {
        return;
    }
    
    $log_message = sprintf(
        '[%s] %s: %s',
        $context,
        current_time('Y-m-d H:i:s'),
        $message
    );
    
    if ($data !== null) {
        $log_message .= ' | Data: ' . print_r($data, true);
    }
    
    error_log($log_message);
}

// Initialize the plugin
function bricks_etch_migration() {
    return Bricks_Etch_Migration::get_instance();
}

// Start the plugin
bricks_etch_migration();

// Plugin activation/deactivation hooks
register_activation_hook(__FILE__, array('Bricks_Etch_Migration', 'activate'));
register_deactivation_hook(__FILE__, array('Bricks_Etch_Migration', 'deactivate'));
