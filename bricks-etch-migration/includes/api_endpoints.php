<?php
/**
 * API Endpoints for Bricks to Etch Migration Plugin
 * 
 * Handles REST API endpoints for communication between source and target sites
 */

namespace Bricks2Etch\Api;

use Bricks2Etch\Core\B2E_Migration_Token_Manager;
use Bricks2Etch\Core\B2E_Migration_Manager;
use Bricks2Etch\Migrators\B2E_Migrator_Registry;
use Bricks2Etch\Migrators\Interfaces\Migrator_Interface;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_API_Endpoints {
    /**
     * @var \Bricks2Etch\Container\B2E_Service_Container|null
     */
    private static $container;
    
    /**
     * @var \Bricks2Etch\Repositories\Interfaces\Settings_Repository_Interface|null
     */
    private static $settings_repository;
    
    /**
     * @var \Bricks2Etch\Repositories\Interfaces\Migration_Repository_Interface|null
     */
    private static $migration_repository;
    
    /**
     * @var \Bricks2Etch\Repositories\Interfaces\Style_Repository_Interface|null
     */
    private static $style_repository;
    
    /**
     * @var \Bricks2Etch\Security\B2E_Rate_Limiter|null
     */
    private static $rate_limiter;
    
    /**
     * @var \Bricks2Etch\Security\B2E_Input_Validator|null
     */
    private static $input_validator;
    
    /**
     * @var \Bricks2Etch\Security\B2E_Audit_Logger|null
     */
    private static $audit_logger;
    
    /**
     * @var \Bricks2Etch\Security\B2E_CORS_Manager|null
     */
    private static $cors_manager;
    
    /**
     * @var B2E_Migrator_Registry|null
     */
    private static $migrator_registry;
    
    /**
     * Initialize the API endpoints
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
        
        // Add global CORS enforcement filter
        add_filter('rest_request_before_callbacks', array(__CLASS__, 'enforce_cors_globally'), 10, 3);
    }

    /**
     * Set the service container instance.
     *
     * @param \Bricks2Etch\Container\B2E_Service_Container $container
     */
    public static function set_container($container) {
        self::$container = $container;
        
        // Resolve repositories from container
        if ($container->has('settings_repository')) {
            self::$settings_repository = $container->get('settings_repository');
        }
        if ($container->has('migration_repository')) {
            self::$migration_repository = $container->get('migration_repository');
        }
        if ($container->has('style_repository')) {
            self::$style_repository = $container->get('style_repository');
        }
        
        // Resolve security services from container
        if ($container->has('rate_limiter')) {
            self::$rate_limiter = $container->get('rate_limiter');
        }
        if ($container->has('input_validator')) {
            self::$input_validator = $container->get('input_validator');
        }
        if ($container->has('audit_logger')) {
            self::$audit_logger = $container->get('audit_logger');
        }
        if ($container->has('cors_manager')) {
            self::$cors_manager = $container->get('cors_manager');
        }
        if ($container->has('migrator_registry')) {
            self::$migrator_registry = $container->get('migrator_registry');
        }
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $id
     *
     * @return mixed
     */
    private static function resolve($id) {
        if (self::$container && self::$container->has($id)) {
            return self::$container->get($id);
        }

        if (class_exists($id)) {
            return new $id();
        }

        return null;
    }
    
    /**
     * Check CORS origin for REST API endpoint
     *
     * @return \WP_Error|bool True if origin allowed, WP_Error if denied.
     */
    private static function check_cors_origin() {
        if (!self::$cors_manager) {
            return true; // No CORS check if manager not available
        }
        
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (!self::$cors_manager->is_origin_allowed($origin)) {
            // Log CORS violation
            if (self::$audit_logger) {
                self::$audit_logger->log_security_event(
                    'cors_violation',
                    'medium',
                    'REST API request from unauthorized origin',
                    array('origin' => $origin)
                );
            }
            
            return new \WP_Error(
                'cors_violation',
                'Origin not allowed',
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Enforce CORS globally for all REST API requests
     *
     * This filter runs before any REST API callback and provides a safety net
     * to ensure no endpoint can bypass CORS validation.
     *
     * @param \WP_HTTP_Response|\WP_Error $response Result to send to the client.
     * @param \WP_REST_Server             $server   Server instance.
     * @param \WP_REST_Request            $request  Request used to generate the response.
     * @return \WP_HTTP_Response|\WP_Error Modified response or error.
     */
    public static function enforce_cors_globally($response, $server, $request) {
        // Skip OPTIONS preflight requests (headers already handled by B2E_CORS_Manager::add_cors_headers())
        if ($request->get_method() === 'OPTIONS') {
            return $response;
        }
        
        // Only check our own endpoints
        $route = $request->get_route();
        if (strpos($route, '/b2e/v1/') !== 0) {
            return $response;
        }
        
        // Perform CORS check
        $cors_check = self::check_cors_origin();
        if (is_wp_error($cors_check)) {
            // Log the violation with route information
            if (self::$audit_logger) {
                $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
                self::$audit_logger->log_security_event(
                    'cors_violation',
                    'medium',
                    'Global CORS enforcement blocked request',
                    array(
                        'origin' => $origin,
                        'route' => $route,
                        'method' => $request->get_method()
                    )
                );
            }
            return $cors_check;
        }
        
        return $response;
    }
    
    /**
     * Check rate limit for REST API endpoint
     *
     * @param string $action Action name.
     * @param int $limit Request limit (default: 30).
     * @return \WP_Error|bool True if within limit, WP_Error if exceeded.
     */
    private static function check_rate_limit($action, $limit = 30) {
        if (!self::$rate_limiter) {
            return true;
        }
        
        $identifier = self::$rate_limiter->get_identifier();
        
        if (self::$rate_limiter->check_rate_limit($identifier, $action, $limit, 60)) {
            // Log rate limit exceeded
            if (self::$audit_logger) {
                self::$audit_logger->log_rate_limit_exceeded($identifier, $action);
            }
            
            return new \WP_Error(
                'rate_limit_exceeded',
                'Rate limit exceeded. Please try again later.',
                array('status' => 429)
            );
        }
        
        // Record this request
        self::$rate_limiter->record_request($identifier, $action, 60);
        
        return true;
    }
    
    /**
     * Validate request data
     *
     * @param array $data Data to validate.
     * @param array $rules Validation rules.
     * @return array|\WP_Error Validated data or WP_Error.
     */
    private static function validate_request_data($data, $rules) {
        if (!self::$input_validator) {
            return $data;
        }
        
        try {
            return \Bricks2Etch\Security\B2E_Input_Validator::validate_request_data($data, $rules);
        } catch (\InvalidArgumentException $e) {
            // Log invalid input
            if (self::$audit_logger) {
                self::$audit_logger->log_security_event('invalid_input', 'medium', $e->getMessage(), array(
                    'data' => $data,
                    'rules' => $rules
                ));
            }
            
            return new \WP_Error(
                'invalid_input',
                'Invalid input: ' . $e->getMessage(),
                array('status' => 400)
            );
        }
    }
    
    
    /**
     * Register REST API routes
     */
    public static function register_routes() {
        $namespace = 'b2e/v1';
        
        // Authentication endpoint (POST)
        register_rest_route($namespace, '/auth/validate', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'validate_api_key'),
            'permission_callback' => '__return_true',
        ));
        
        // Authentication endpoint (GET) - for easier testing
        register_rest_route($namespace, '/auth/test', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'test_auth'),
            'permission_callback' => '__return_true',
        ));
        
        // Key-based migration endpoint
        register_rest_route($namespace, '/migrate', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'handle_key_migration'),
            'permission_callback' => '__return_true',
        ));
        
        // Token validation endpoint for key validation
        register_rest_route($namespace, '/validate', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'validate_migration_token'),
            'permission_callback' => '__return_true',
        ));
        
        // Generate migration key endpoint
        register_rest_route($namespace, '/generate-key', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'generate_migration_key'),
            'permission_callback' => '__return_true',
        ));
        
        // Legacy endpoint - kept for backwards compatibility
        register_rest_route($namespace, '/receive-post', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_post'), // Use new import_post directly
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        // Receive migrated media endpoint
        register_rest_route($namespace, '/receive-media', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'receive_migrated_media'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        // API key validation endpoint
        register_rest_route($namespace, '/validate-api-key', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'validate_api_key'),
            'permission_callback' => '__return_true',
        ));
        
        // Get migrated content count endpoint
        register_rest_route($namespace, '/migrated-count', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_migrated_content_count'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        // Plugin status endpoint
        register_rest_route($namespace, '/validate/plugins', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_plugin_status'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        // Export endpoints
        register_rest_route($namespace, '/export/posts', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_posts_list'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/export/post/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_post_content'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        register_rest_route($namespace, '/export/css-classes', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_css_classes'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/export/cpts', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_custom_post_types'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/export/acf-field-groups', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_acf_field_groups'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/export/metabox-configs', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_metabox_configs'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));

        register_rest_route($namespace, '/export/migrators', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'list_migrators'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));

        register_rest_route($namespace, '/export/migrator/(?P<type>[a-z0-9_\-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'export_migrator_by_type'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        // Import endpoints
        register_rest_route($namespace, '/import/post', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_post'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/import/css-classes', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_css_classes'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/import/cpts', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_custom_post_types'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/import/acf-field-groups', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_acf_field_groups'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/import/metabox-configs', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_metabox_configs'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/import/post-meta', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_post_meta'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
        
        register_rest_route($namespace, '/import/media', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'import_media_file'),
            'permission_callback' => array(__CLASS__, 'check_api_key'),
        ));
    }
    
    /**
     * Test authentication endpoint
     */
    public static function test_auth($request) {
        // Check CORS origin
        $cors_check = self::check_cors_origin();
        if (is_wp_error($cors_check)) {
            return $cors_check;
        }
        
        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Bricks to Etch Migration API is working!',
            'timestamp' => current_time('mysql'),
            'endpoints' => array(
                'auth/validate' => 'POST - Validate API key',
                'validate/plugins' => 'GET - Check plugin status',
                'export/posts' => 'GET - Export posts list'
            )
        ), 200);
    }
    
    /**
     * Validate API key
     */
    public static function validate_api_key($request) {
        // Check CORS origin
        $cors_check = self::check_cors_origin();
        if (is_wp_error($cors_check)) {
            return $cors_check;
        }
        
        // Check rate limit (10 requests per minute for auth)
        $rate_check = self::check_rate_limit('validate_api_key', 10);
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        // Try to get API key from header first, then from parameter
        $api_key = $request->get_header('X-API-Key');
        if (empty($api_key)) {
            $api_key = $request->get_header('x_api_key');
        }
        if (empty($api_key)) {
            $api_key = $request->get_param('api_key');
        }
        
        if (empty($api_key)) {
            if (self::$audit_logger) {
                self::$audit_logger->log_authentication_attempt(false, 'unknown', 'api_key');
            }
            return new \WP_Error('missing_api_key', 'API key is required', array('status' => 400));
        }
        
        // Validate API key format
        if (self::$input_validator) {
            try {
                $api_key = self::$input_validator->validate_api_key($api_key);
            } catch (\InvalidArgumentException $e) {
                if (self::$audit_logger) {
                    self::$audit_logger->log_authentication_attempt(false, 'unknown', 'api_key');
                }
                return new \WP_Error('invalid_api_key_format', $e->getMessage(), array('status' => 400));
            }
        }
        
        $valid_key = self::$settings_repository ? self::$settings_repository->get_api_key() : get_option('b2e_api_key');
        
        if ($api_key !== $valid_key) {
            // Log failed authentication
            if (self::$audit_logger) {
                self::$audit_logger->log_authentication_attempt(false, 'api_key', 'api_key');
            }
            return new \WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
        }
        
        // Log successful authentication
        if (self::$audit_logger) {
            self::$audit_logger->log_authentication_attempt(true, 'api_key', 'api_key');
        }
        
        return new \WP_REST_Response(array(
            'valid' => true,
            'message' => 'API key is valid',
        ), 200);
    }
    
    /**
     * Check authentication (Application Password or API Key for backwards compatibility)
     */
    public static function check_api_key($request) {
        // Check CORS origin
        $cors_check = self::check_cors_origin();
        if (is_wp_error($cors_check)) {
            return $cors_check;
        }
        
        // Check rate limit (30 requests per minute)
        $rate_check = self::check_rate_limit('check_api_key', 30);
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        // Try Application Password first (WordPress standard)
        // Check if Authorization header is present
        $auth_header = $request->get_header('Authorization');
        
        if (!empty($auth_header) && strpos($auth_header, 'Basic ') === 0) {
            // Extract credentials from Basic Auth
            $credentials = base64_decode(substr($auth_header, 6));
            list($username, $password) = explode(':', $credentials, 2);
            
            // Remove spaces from password (Application Passwords have spaces for readability)
            $password = str_replace(' ', '', $password);
            
            // Try to authenticate with Application Password
            $user = wp_authenticate_application_password(null, $username, $password);
            
            if (!is_wp_error($user) && $user instanceof \WP_User) {
                // Authentication successful
                wp_set_current_user($user->ID);
                return true;
            }
        }
        
        // Also check if user is already authenticated
        $user = wp_get_current_user();
        if ($user && $user->ID > 0) {
            return true;
        }
        
        // Fallback: Check custom API key (backwards compatibility)
        $api_key = $request->get_header('X-API-Key');
        
        if (!empty($api_key)) {
            // Remove spaces from API key
            $api_key = str_replace(' ', '', $api_key);
            $valid_key = self::$settings_repository ? self::$settings_repository->get_api_key() : get_option('b2e_api_key');
            
            if ($api_key === $valid_key) {
                return true;
            }
        }
        
        return new \WP_Error('unauthorized', 'Authentication required. Use Application Password or API key.', array('status' => 401));
    }

    /**
     * Get plugin status
     */
    public static function get_plugin_status($request) {
        // Check rate limit (30 requests per minute)
        $rate_check = self::check_rate_limit('get_plugin_status', 30);
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        $plugin_detector = self::resolve('plugin_detector');
        
        return new \WP_REST_Response(array(
            'success' => true,
            'plugins' => array(
                'bricks_active' => $plugin_detector->is_bricks_active(),
                'etch_active' => $plugin_detector->is_etch_active(),
            ),
        ), 200);
    }

    /**
     * Export posts list
     */
    public static function export_posts_list($request) {
        // Check rate limit (30 requests per minute)
        $rate_check = self::check_rate_limit('export_posts_list', 30);
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
        $content_service = self::resolve('content_service');
        $content_parser = self::resolve('content_parser');

        $bricks_posts = $content_service ? $content_service->get_bricks_posts() : $content_parser->get_bricks_posts();
        $gutenberg_posts = $content_service ? $content_service->get_gutenberg_posts() : $content_parser->get_gutenberg_posts();
        $media = $content_service ? $content_service->get_all_content()['media'] : $content_parser->get_media();
        
        return new \WP_REST_Response(array(
            'bricks_posts' => $bricks_posts,
            'gutenberg_posts' => $gutenberg_posts,
            'media' => $media,
            'timestamp' => current_time('mysql'),
        ), 200);
    }

    /**
     * Export post content
     */
    public static function export_post_content($request) {
        // Check rate limit (30 requests per minute)
        $rate_check = self::check_rate_limit('export_post_content', 30);
        if (is_wp_error($rate_check)) {
            return $rate_check;
        }
        
    $post_id = $request->get_param('id');
    
    $post = get_post($post_id);
    if (!$post) {
        return new \WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
    
    // Parse Bricks content
    $content_service = self::resolve('content_service');
    $content_parser = self::resolve('content_parser');

    $bricks_posts = $content_service ? $content_service->get_bricks_posts() : $content_parser->get_bricks_posts();
    $gutenberg_posts = $content_service ? $content_service->get_gutenberg_posts() : $content_parser->get_gutenberg_posts();
    $media = $content_service ? $content_service->get_all_content()['media'] : $content_parser->get_media();
    
    if (!$bricks_posts && !$gutenberg_posts && !$media) {
        return new \WP_Error('no_bricks_content', 'No Bricks content found', array('status' => 404));
    }
    
    return new \WP_REST_Response(array(
        'post' => array(
            'ID' => $post->ID,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'post_date' => $post->post_date,
            'post_status' => $post->post_status,
        ),
        'bricks_content' => $bricks_posts,
        'gutenberg_content' => $gutenberg_posts,
        'media' => $media,
    ), 200);
}

/**
 * Export CSS classes
 */
public static function export_css_classes($request) {
    // Check rate limit (30 requests per minute)
    $rate_check = self::check_rate_limit('export_css_classes', 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $css_service = self::resolve('css_service');
    $css_classes = $css_service ? $css_service->get_bricks_global_classes() : array();
    
    return new \WP_REST_Response($css_classes, 200);
}

/**
 * Export custom post types
 */
public static function export_custom_post_types($request) {
    // Check rate limit (30 requests per minute)
    $rate_check = self::check_rate_limit('export_custom_post_types', 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $payload = array(
        'cpts' => array(),
        'timestamp' => current_time('mysql'),
    );

    if (self::$migrator_registry && self::$migrator_registry->has('cpt')) {
        $migrator = self::$migrator_registry->get('cpt');
        if ($migrator instanceof Migrator_Interface) {
            $payload['cpts'] = $migrator->export();
            return new \WP_REST_Response($payload, 200);
        }
    }

    $cpt_migrator = self::resolve('cpt_migrator');
    if ($cpt_migrator) {
        $payload['cpts'] = $cpt_migrator->export_custom_post_types();
    }

    return new \WP_REST_Response($payload, 200);
}

/**
 * Export ACF field groups
 */
public static function export_acf_field_groups($request) {
    // Check rate limit (30 requests per minute)
    $rate_check = self::check_rate_limit('export_acf_field_groups', 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $payload = array(
        'acf_field_groups' => array(),
        'timestamp' => current_time('mysql'),
    );

    if (self::$migrator_registry && self::$migrator_registry->has('acf')) {
        $migrator = self::$migrator_registry->get('acf');
        if ($migrator instanceof Migrator_Interface) {
            $payload['acf_field_groups'] = $migrator->export();
            return new \WP_REST_Response($payload, 200);
        }
    }

    $acf_migrator = self::resolve('acf_migrator');
    if ($acf_migrator) {
        $payload['acf_field_groups'] = $acf_migrator->export_field_groups();
    }

    return new \WP_REST_Response($payload, 200);
}

/**
 * Export MetaBox configs
 */
public static function export_metabox_configs($request) {
    // Check rate limit (30 requests per minute)
    $rate_check = self::check_rate_limit('export_metabox_configs', 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $payload = array(
        'metabox_configs' => array(),
        'timestamp' => current_time('mysql'),
    );

    if (self::$migrator_registry && self::$migrator_registry->has('metabox')) {
        $migrator = self::$migrator_registry->get('metabox');
        if ($migrator instanceof Migrator_Interface) {
            $payload['metabox_configs'] = $migrator->export();
            return new \WP_REST_Response($payload, 200);
        }
    }

    $metabox_migrator = self::resolve('metabox_migrator');
    if ($metabox_migrator) {
        $payload['metabox_configs'] = $metabox_migrator->export_metabox_configs();
    }

    return new \WP_REST_Response($payload, 200);
}

/**
 * List registered migrators
 */
public static function list_migrators($request) {
    $rate_check = self::check_rate_limit('list_migrators', 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }

    if (!self::$migrator_registry) {
        return new \WP_REST_Response(array(), 200);
    }

    $migrators = array();
    foreach (self::$migrator_registry->get_all() as $migrator) {
        $migrators[] = array(
            'type' => $migrator->get_type(),
            'name' => $migrator->get_name(),
            'priority' => $migrator->get_priority(),
            'supports' => $migrator->supports(),
        );
    }

    return new \WP_REST_Response(array(
        'migrators' => $migrators,
        'timestamp' => current_time('mysql'),
    ), 200);
}

/**
 * Export data for a specific migrator type
 */
public static function export_migrator_by_type($request) {
    $type = sanitize_key($request->get_param('type'));

    $rate_check = self::check_rate_limit('export_migrator_' . $type, 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }

    if (!self::$migrator_registry || !self::$migrator_registry->has($type)) {
        return new \WP_Error('migrator_not_found', 'Migrator not registered: ' . $type, array('status' => 404));
    }

    $migrator = self::$migrator_registry->get($type);

    if (!$migrator->supports()) {
        return new \WP_Error('migrator_not_supported', 'Migrator not supported in current environment: ' . $type, array('status' => 400));
    }

    $data = array(
        'type' => $migrator->get_type(),
        'name' => $migrator->get_name(),
        'priority' => $migrator->get_priority(),
        'stats' => $migrator->get_stats(),
        'export' => $migrator->export(),
    );

    return new \WP_REST_Response($data, 200);
}

/**
 * Import post
 */
public static function import_post($request) {
    // Check rate limit (20 requests per minute for write operations)
    $rate_check = self::check_rate_limit('import_post', 20);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $post_data = $request->get_json_params();
    
    if (empty($post_data['post']) || empty($post_data['etch_content'])) {
        return new \WP_Error('missing_data', 'Post data and Etch content are required', array('status' => 400));
    }
    
    $post = $post_data['post'];
    $etch_content = $post_data['etch_content'];
    
    // Check if post already exists by post_title (more reliable than slug)
    $existing_post = null;
    if (!empty($post['post_title'])) {
        $args = array(
            'post_type' => $post['post_type'],
            'post_status' => 'any',
            'title' => $post['post_title'],
            'posts_per_page' => 1,
            'fields' => 'ids'
        );
        $posts = get_posts($args);
        if (!empty($posts)) {
            $existing_post = get_post($posts[0]);
        }
    }
    
    // If exists, update it. Otherwise create new.
    $post_args = array(
        'post_title' => $post['post_title'],
        'post_name' => $post['post_name'],
        'post_type' => $post['post_type'],
        'post_status' => $post['post_status'],
        'post_content' => $etch_content,
        'post_date' => $post['post_date'],
    );
    
    if ($existing_post) {
        // Update existing post
        $post_args['ID'] = $existing_post->ID;
        $post_id = wp_update_post($post_args);
        $action = 'updated';
    } else {
        // Create new post
        $post_id = wp_insert_post($post_args);
        $action = 'created';
    }
    
    if (is_wp_error($post_id)) {
        return new \WP_Error('post_save_failed', 'Failed to save post', array('status' => 500));
    }
    
    return new \WP_REST_Response(array(
        'post_id' => $post_id,
        'action' => $action,
        'message' => 'Post ' . $action . ' successfully',
    ), 200);
}

/**
 * Import CSS classes
 */
public static function import_css_classes($request) {
    // Check rate limit (10 requests per minute for CSS import)
    $rate_check = self::check_rate_limit('import_css_classes', 10);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    error_log('🎯 API Endpoint: import_css_classes called');
    
    $classes_data = $request->get_json_params();
    $styles_count = is_array($classes_data) ? count($classes_data) : 0;
    
    error_log('🎯 API Endpoint: Received ' . $styles_count . ' CSS classes');
    
    if (empty($classes_data)) {
        error_log('❌ API Endpoint: No CSS classes data received');
        return new \WP_Error('missing_data', 'CSS classes data is required', array('status' => 400));
    }
    
    error_log('🎯 API Endpoint: Calling CSS Converter to import styles...');
    $css_service = self::resolve('css_service');
    $conversion_result = $css_service ? $css_service->import_etch_styles($classes_data) : null;
    
    if (is_wp_error($conversion_result)) {
        error_log('❌ API Endpoint: CSS Converter returned error: ' . $conversion_result->get_error_message());
        return $conversion_result;
    }
    
    // Get the style map from Etch side
    $style_map = self::$style_repository ? self::$style_repository->get_style_map() : array();
    
    error_log('✅ API Endpoint: CSS classes imported successfully (' . $styles_count . ' styles)');
    error_log('📋 API Endpoint: Returning style map with ' . count($style_map) . ' entries');
    
    return new \WP_REST_Response(array(
        'message' => 'CSS classes imported successfully',
        'imported_count' => $styles_count,
        'style_map' => $style_map, // Return style map to Bricks side!
    ), 200);
}

/**
 * Import custom post types
 */
public static function import_custom_post_types($request) {
    // Check rate limit (10 requests per minute for write operations)
    $rate_check = self::check_rate_limit('import_custom_post_types', 10);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $data = $request->get_json_params();
    
    if (empty($data)) {
        return new \WP_Error('missing_data', 'CPT data is required', array('status' => 400));
    }
    
    // Store CPT data for later registration
    if (self::$migration_repository) {
        self::$migration_repository->save_imported_data('cpts', $data);
    } else {
        update_option('b2e_imported_cpts', $data);
    }
    
    return new \WP_REST_Response(array(
        'message' => 'Custom post types imported successfully',
        'count' => count($data),
    ), 200);
}

/**
 * Import ACF field groups
 */
public static function import_acf_field_groups($request) {
    // Check rate limit (10 requests per minute for write operations)
    $rate_check = self::check_rate_limit('import_acf_field_groups', 10);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $data = $request->get_json_params();
    
    if (empty($data)) {
        return new \WP_Error('missing_data', 'ACF field groups data is required', array('status' => 400));
    }
    
    // Store ACF field groups for later import
    if (self::$migration_repository) {
        self::$migration_repository->save_imported_data('acf_field_groups', $data);
    } else {
        update_option('b2e_imported_acf_field_groups', $data);
    }
    
    return new \WP_REST_Response(array(
        'message' => 'ACF field groups imported successfully',
        'count' => count($data),
    ), 200);
}

/**
 * Import MetaBox configs
 */
public static function import_metabox_configs($request) {
    // Check rate limit (10 requests per minute for write operations)
    $rate_check = self::check_rate_limit('import_metabox_configs', 10);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $data = $request->get_json_params();
    
    if (empty($data)) {
        return new \WP_Error('missing_data', 'MetaBox configs data is required', array('status' => 400));
    }
    
    // Store MetaBox configs for later import
    if (self::$migration_repository) {
        self::$migration_repository->save_imported_data('metabox_configs', $data);
    } else {
        update_option('b2e_imported_metabox_configs', $data);
    }
    
    return new \WP_REST_Response(array(
        'message' => 'MetaBox configs imported successfully',
        'count' => count($data),
    ), 200);
}

/**
 * Import post meta
 */
public static function import_post_meta($request) {
    // Check rate limit (20 requests per minute for write operations)
    $rate_check = self::check_rate_limit('import_post_meta', 20);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $data = $request->get_json_params();
    
    if (empty($data['post_id']) || empty($data['meta'])) {
        return new \WP_Error('missing_data', 'Post ID and meta data are required', array('status' => 400));
    }
    
    $post_id = intval($data['post_id']);
    $meta_data = $data['meta'];
    
    foreach ($meta_data as $meta_key => $meta_value) {
        update_post_meta($post_id, $meta_key, $meta_value);
    }
    
    return new \WP_REST_Response(array(
        'message' => 'Post meta imported successfully',
        'post_id' => $post_id,
        'meta_count' => count($meta_data),
    ), 200);
}

/**
 * Import media file
 */
public static function import_media_file($request) {
    // Check rate limit (20 requests per minute for media uploads)
    $rate_check = self::check_rate_limit('import_media_file', 20);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $data = $request->get_json_params();
    
    if (empty($data['file_content']) || empty($data['file_name'])) {
        return new \WP_Error('missing_data', 'File content and name are required', array('status' => 400));
    }
    
    // Decode base64 file content
    $file_content = base64_decode($data['file_content']);
    
    // Upload to WordPress
    $upload = wp_upload_bits($data['file_name'], null, $file_content);
    
    if ($upload['error']) {
        return new \WP_Error('upload_failed', $upload['error'], array('status' => 500));
    }
    
    // Create attachment
    $attachment_data = array(
        'post_title' => $data['post_title'] ?? '',
        'post_content' => $data['post_content'] ?? '',
        'post_excerpt' => $data['post_excerpt'] ?? '',
        'post_mime_type' => $data['post_mime_type'] ?? '',
    );
    
    $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
    
    if (is_wp_error($attachment_id)) {
        return new \WP_Error('attachment_failed', 'Failed to create attachment', array('status' => 500));
    }
    
    // Generate attachment metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_data);
    
    return new \WP_REST_Response(array(
        'message' => 'Media file imported successfully',
        'attachment_id' => $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
    ), 200);
}

/**
 * Receive migrated media
 */
public static function receive_migrated_media($request) {
    return self::import_media_file($request);
}

/**
 * Get migrated content count
 */
public static function get_migrated_content_count($request) {
    // Check rate limit (30 requests per minute)
    $rate_check = self::check_rate_limit('get_migrated_content_count', 30);
    if (is_wp_error($rate_check)) {
        return $rate_check;
    }
    
    $posts_count = wp_count_posts('post');
    $pages_count = wp_count_posts('page');
    $media_count = wp_count_posts('attachment');
    
    return new \WP_REST_Response(array(
        'posts' => $posts_count->publish,
        'pages' => $pages_count->publish,
        'media' => $media_count->inherit,
        'total' => $posts_count->publish + $pages_count->publish + $media_count->inherit,
    ), 200);
}

/**
 * Handle key-based migration request
 */
public static function handle_key_migration($request) {
    // Check CORS origin
    $cors_check = self::check_cors_origin();
    if (is_wp_error($cors_check)) {
        return $cors_check;
    }
    
    try {
        $params = $request->get_params();
        
        // Extract migration parameters
        $domain = $params['domain'] ?? null;
        $token = $params['token'] ?? null;
        $expires = isset($params['expires']) ? (int) $params['expires'] : null;
        
        // Debug logging
        error_log('=== B2E API Debug Start ===');
        error_log('B2E Debug - Received params: ' . print_r($params, true));
        error_log('B2E Debug - Domain: ' . $domain);
        error_log('B2E Debug - Token: ' . substr($token, 0, 20) . '...');
        error_log('B2E Debug - Expires: ' . $expires);
        error_log('B2E Debug - Current time: ' . time());
        error_log('B2E Debug - Expires date: ' . date('Y-m-d H:i:s', $expires));
        
        if (empty($domain) || empty($token) || empty($expires)) {
            error_log('B2E Debug - Missing parameters');
            return new \WP_Error('missing_params', 'Missing required migration parameters', array('status' => 400));
        }
        
        // Validate migration token
        $token_manager = self::resolve('token_manager');
        $validation = $token_manager->validate_migration_token($token, $domain, $expires);
        
        if (is_wp_error($validation)) {
            error_log('B2E Debug - Token validation failed: ' . $validation->get_error_message());
            error_log('B2E Debug - Validation error code: ' . $validation->get_error_code());
            error_log('B2E Debug - Validation error data: ' . print_r($validation->get_error_data(), true));
            return new \WP_Error('invalid_token', $validation->get_error_message(), array('status' => 401));
        }
        
        error_log('B2E Debug - Token validation successful');
        
        // Start import process (this runs on TARGET site)
        $migration_manager = self::resolve('migration_manager');
        $result = $migration_manager->start_import_process($domain, $token);
        
        if (is_wp_error($result)) {
            return new \WP_Error('migration_failed', $result->get_error_message(), array('status' => 500));
        }
        
        // Return success response with migration details
        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Key-based migration started successfully!',
            'migration_url' => $request->get_route(),
            'source_domain' => $domain,
            'target_domain' => home_url(),
            'started_at' => current_time('mysql'),
        ), 200);
        
    } catch (\Exception $e) {
        error_log('B2E Key Migration Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        return new \WP_Error('migration_error', 'Migration failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
    }
}

/**
 * Generate migration key endpoint
 * Creates a new migration token and returns the migration key URL
 */
public static function generate_migration_key($request) {
    try {
        // Create token manager
        $token_manager = self::resolve('token_manager');
        
        // Generate migration token
        $token_data = $token_manager->generate_migration_token();
        
        if (is_wp_error($token_data)) {
            return new \WP_Error('token_generation_failed', $token_data->get_error_message(), array('status' => 500));
        }
        
        // Build migration key URL
        $migration_key = add_query_arg(array(
            'domain' => home_url(),
            'token' => $token_data['token'],
            'expires' => $token_data['expires']
        ), home_url());
        
        // Return response
        return new \WP_REST_Response(array(
            'success' => true,
            'migration_key' => $migration_key,
            'token' => $token_data['token'],
            'domain' => home_url(),
            'expires' => $token_data['expires'],
            'expires_at' => date('Y-m-d H:i:s', $token_data['expires']),
            'valid_for' => '24 hours',
            'generated_at' => current_time('mysql'),
        ), 200);
        
    } catch (\Exception $e) {
        error_log('B2E Generate Key Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        return new \WP_Error('generation_error', 'Failed to generate migration key: ' . $e->getMessage(), array('status' => 500));
    }
}

/**
 * Validate migration token endpoint
 * Used by the "Validate Key" button to test API connection
 */
public static function validate_migration_token($request) {
    // Check CORS origin
    $cors_check = self::check_cors_origin();
    if (is_wp_error($cors_check)) {
        return $cors_check;
    }
    
    try {
        // Get request body
        $body = $request->get_json_params();
        
        if (empty($body['token']) || empty($body['expires'])) {
            return new \WP_Error('missing_parameters', 'Token and expires parameters are required', array('status' => 400));
        }
        
        $token = sanitize_text_field($body['token']);
        $expires = intval($body['expires']);
        
        // Validate token using token manager
        $token_manager = self::resolve('token_manager');
        $validation_result = $token_manager->validate_migration_token($token, '', $expires);
        
        if (is_wp_error($validation_result)) {
            return new \WP_Error('token_validation_failed', $validation_result->get_error_message(), array('status' => 401));
        }
        
        // Token is valid - generate or retrieve API key
        $api_key_data = self::$settings_repository ? self::$settings_repository->get_api_key() : get_option('b2e_api_key');
        
        // If no API key exists, generate one
        if (empty($api_key_data)) {
            $api_client = self::resolve('api_client');
            $api_key = $api_client ? $api_client->create_api_key() : wp_generate_password(32, false);
            error_log('B2E: Generated new API key for migration: ' . substr($api_key, 0, 10) . '...');
        } else {
            // Extract key from array if it's an array
            if (is_array($api_key_data) && isset($api_key_data['key'])) {
                $api_key = $api_key_data['key'];
            } else {
                $api_key = $api_key_data; // Fallback for old format
            }
            error_log('B2E: Using existing API key for migration: ' . substr($api_key, 0, 10) . '...');
        }
        
        // Return success response with API key
        return new \WP_REST_Response(array(
            'success' => true,
            'message' => 'Token validation successful',
            'api_key' => $api_key,
            'target_domain' => home_url(),
            'site_name' => get_bloginfo('name'),
            'wordpress_version' => get_bloginfo('version'),
            'etch_active' => class_exists('Etch\Plugin') || function_exists('etch_run_plugin'),
            'validated_at' => current_time('mysql'),
        ), 200);
        
    } catch (\Exception $e) {
        error_log('B2E Token Validation Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        return new \WP_Error('validation_error', 'Token validation failed: ' . $e->getMessage(), array('status' => 500));
    }
}

}
