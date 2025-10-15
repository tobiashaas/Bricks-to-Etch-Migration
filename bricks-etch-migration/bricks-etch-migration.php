<?php
/**
 * Plugin Name: Bricks to Etch Migration
 * Plugin URI: https://github.com/tobiashaas/Bricks-to-Etch-Migration
 * Description: One-time migration tool for converting Bricks Builder websites to Etch PageBuilder with complete automation.
 * Version: 0.2.0
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
define('B2E_VERSION', '0.2.0');
define('B2E_PLUGIN_FILE', __FILE__);
define('B2E_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('B2E_PLUGIN_URL', plugin_dir_url(__FILE__));
define('B2E_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load all required classes
require_once B2E_PLUGIN_DIR . 'includes/class-b2e-autoloader.php';
require_once B2E_PLUGIN_DIR . 'includes/error_handler.php';
require_once B2E_PLUGIN_DIR . 'includes/admin_interface.php';
require_once B2E_PLUGIN_DIR . 'includes/api_endpoints.php';
require_once B2E_PLUGIN_DIR . 'includes/plugin_detector.php';
require_once B2E_PLUGIN_DIR . 'includes/content_parser.php';
require_once B2E_PLUGIN_DIR . 'includes/css_converter.php';
require_once B2E_PLUGIN_DIR . 'includes/gutenberg_generator.php';
require_once B2E_PLUGIN_DIR . 'includes/dynamic_data_converter.php';
require_once B2E_PLUGIN_DIR . 'includes/migration_manager.php';
require_once B2E_PLUGIN_DIR . 'includes/api_client.php';
require_once B2E_PLUGIN_DIR . 'includes/api_endpoints.php';
require_once B2E_PLUGIN_DIR . 'includes/custom_fields_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/acf_field_groups_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/metabox_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/cpt_migrator.php';
require_once B2E_PLUGIN_DIR . 'includes/cross_plugin_converter.php';
require_once B2E_PLUGIN_DIR . 'includes/class-b2e-transfer-manager.php';
require_once B2E_PLUGIN_DIR . 'includes/class-b2e-migration-analyzer.php';
require_once B2E_PLUGIN_DIR . 'includes/class-b2e-migration-settings.php';
require_once B2E_PLUGIN_DIR . 'includes/media_migrator.php';

// Main plugin class
class Bricks_Etch_Migration {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
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
        // Initialize admin interface
        if (is_admin()) {
            new B2E_Admin_Interface();
        }
        
        // Initialize API endpoints (static registration)
        B2E_API_Endpoints::init();
        
        // Initialize error handler
        new B2E_Error_Handler();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Bricks to Etch Migration', 'bricks-etch-migration'),
            __('B2E Migration', 'bricks-etch-migration'),
            'manage_options',
            'bricks-etch-migration',
            array($this, 'admin_page'),
            'dashicons-migrate',
            30
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $admin_interface = new B2E_Admin_Interface();
        $admin_interface->render_dashboard();
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
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'api_key' => '',
            'target_url' => '',
            'cleanup_bricks_meta' => false,
            'convert_div_to_flex' => true,
            'migration_status' => 'idle',
            'last_migration_date' => null,
        );
        
        add_option('b2e_settings', $default_options);
        
        // Create necessary database tables or options
        $this->create_migration_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up transients
        $this->cleanup_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create migration tables/options
     */
    private function create_migration_tables() {
        // Create migration log option
        add_option('b2e_migration_log', array());
        
        // Create progress tracking option
        add_option('b2e_migration_progress', array(
            'status' => 'idle',
            'current_step' => '',
            'percentage' => 0,
            'started_at' => null,
            'completed_at' => null,
        ));
    }
    
    /**
     * Clean up transients
     */
    private function cleanup_transients() {
        global $wpdb;
        
        // Delete all b2e transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_b2e_%' 
             OR option_name LIKE '_transient_timeout_b2e_%'"
        );
    }
}

// Initialize the plugin
function bricks_etch_migration() {
    return Bricks_Etch_Migration::get_instance();
}

// Start the plugin
bricks_etch_migration();
