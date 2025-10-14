<?php
/**
 * API Endpoints for Bricks to Etch Migration Plugin
 * 
 * Handles REST API endpoints for communication between source and target sites
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_API_Endpoints {
    
    /**
     * Initialize the API endpoints
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
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
    }
    
    /**
     * Test authentication endpoint
     */
    public static function test_auth($request) {
        return new WP_REST_Response(array(
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
        $api_key = $request->get_param('api_key');
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key is required', array('status' => 400));
        }
        
        $valid_key = get_option('b2e_api_key');
        
        if ($api_key !== $valid_key) {
            return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
        }
        
        return new WP_REST_Response(array(
            'valid' => true,
            'message' => 'API key is valid',
        ), 200);
    }
    
    /**
     * Check API key for authentication
     */
    public static function check_api_key($request) {
        $api_key = $request->get_header('X-API-Key');
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key is required', array('status' => 401));
        }
        
        $valid_key = get_option('b2e_api_key');
        
        if ($api_key !== $valid_key) {
            return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
        }
        
        return true;
    }
    
    /**
     * Get plugin status
     */
    public static function get_plugin_status($request) {
        $plugin_detector = new B2E_Plugin_Detector();
        
        return new WP_REST_Response(array(
            'plugins' => $plugin_detector->get_installed_plugins(),
            'bricks_detected' => $plugin_detector->is_bricks_active(),
            'etch_detected' => $plugin_detector->is_etch_active(),
        ), 200);
    }
    
    /**
     * Export posts list
     */
    public static function export_posts_list($request) {
        $posts = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_bricks_template_type',
                    'value' => 'content',
                    'compare' => '='
                ),
                array(
                    'key' => '_bricks_editor_mode',
                    'value' => 'bricks',
                    'compare' => '='
                )
            )
        ));
        
        $posts_data = array();
        foreach ($posts as $post) {
            $posts_data[] = array(
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'post_date' => $post->post_date,
                'post_status' => $post->post_status,
            );
        }
        
        return new WP_REST_Response($posts_data, 200);
    }
    
    /**
     * Export post content
     */
    public static function export_post_content($request) {
        $post_id = $request->get_param('id');
        
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
        }
        
        // Parse Bricks content
        $content_parser = new B2E_Content_Parser();
        $bricks_content = $content_parser->parse_bricks_content($post_id);
        
        if (!$bricks_content) {
            return new WP_Error('no_bricks_content', 'No Bricks content found', array('status' => 404));
        }
        
        return new WP_REST_Response(array(
            'post' => array(
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'post_date' => $post->post_date,
                'post_status' => $post->post_status,
            ),
            'bricks_content' => $bricks_content,
        ), 200);
    }
    
    /**
     * Export CSS classes
     */
    public static function export_css_classes($request) {
        $css_converter = new B2E_CSS_Converter();
        $bricks_classes = get_option('bricks_global_classes', array());
        
        $converted_classes = array();
        foreach ($bricks_classes as $class) {
            $converted_classes[] = $css_converter->convert_bricks_class_to_etch($class);
        }
        
        return new WP_REST_Response($converted_classes, 200);
    }
    
    /**
     * Export custom post types
     */
    public static function export_custom_post_types($request) {
        $cpt_migrator = new B2E_CPT_Migrator();
        $cpts = $cpt_migrator->export_custom_post_types();
        
        return new WP_REST_Response($cpts, 200);
    }
    
    /**
     * Export ACF field groups
     */
    public static function export_acf_field_groups($request) {
        $acf_migrator = new B2E_ACF_Field_Groups_Migrator();
        $field_groups = $acf_migrator->export_field_groups();
        
        return new WP_REST_Response($field_groups, 200);
    }
    
    /**
     * Export MetaBox configs
     */
    public static function export_metabox_configs($request) {
        $metabox_migrator = new B2E_MetaBox_Migrator();
        $configs = $metabox_migrator->export_metabox_configs();
        
        return new WP_REST_Response($configs, 200);
    }
    
    /**
     * Import post
     */
    public static function import_post($request) {
        $post_data = $request->get_json_params();
        
        if (empty($post_data['post']) || empty($post_data['etch_content'])) {
            return new WP_Error('missing_data', 'Post data and Etch content are required', array('status' => 400));
        }
        
        $post = $post_data['post'];
        $etch_content = $post_data['etch_content'];
        
        // Create or update post
        $post_id = wp_insert_post(array(
            'post_title' => $post['post_title'],
            'post_type' => $post['post_type'],
            'post_status' => $post['post_status'],
            'post_content' => $etch_content,
            'post_date' => $post['post_date'],
        ));
        
        if (is_wp_error($post_id)) {
            return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'post_id' => $post_id,
            'message' => 'Post imported successfully',
        ), 200);
    }
    
    /**
     * Import CSS classes
     */
    public static function import_css_classes($request) {
        $classes_data = $request->get_json_params();
        
        if (empty($classes_data)) {
            return new WP_Error('missing_data', 'CSS classes data is required', array('status' => 400));
        }
        
        $css_converter = new B2E_CSS_Converter();
        $result = $css_converter->import_etch_styles($classes_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response(array(
            'message' => 'CSS classes imported successfully',
            'imported_count' => count($classes_data),
        ), 200);
    }
    
    /**
     * Import custom post types
     */
    public static function import_custom_post_types($request) {
        $cpts_data = $request->get_json_params();
        
        if (empty($cpts_data)) {
            return new WP_Error('missing_data', 'CPTs data is required', array('status' => 400));
        }
        
        $cpt_migrator = new B2E_CPT_Migrator();
        $result = $cpt_migrator->register_custom_post_types($cpts_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response(array(
            'message' => 'Custom post types registered successfully',
            'registered_count' => count($cpts_data),
        ), 200);
    }
    
    /**
     * Import ACF field groups
     */
    public static function import_acf_field_groups($request) {
        $field_groups_data = $request->get_json_params();
        
        if (empty($field_groups_data)) {
            return new WP_Error('missing_data', 'Field groups data is required', array('status' => 400));
        }
        
        $acf_migrator = new B2E_ACF_Field_Groups_Migrator();
        $result = $acf_migrator->import_field_groups($field_groups_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response(array(
            'message' => 'ACF field groups imported successfully',
            'imported_count' => count($field_groups_data),
        ), 200);
    }
    
    /**
     * Import MetaBox configs
     */
    public static function import_metabox_configs($request) {
        $configs_data = $request->get_json_params();
        
        if (empty($configs_data)) {
            return new WP_Error('missing_data', 'MetaBox configs data is required', array('status' => 400));
        }
        
        $metabox_migrator = new B2E_MetaBox_Migrator();
        $result = $metabox_migrator->import_metabox_configs($configs_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response(array(
            'message' => 'MetaBox configs imported successfully',
            'imported_count' => count($configs_data),
        ), 200);
    }
    
    /**
     * Import post meta
     */
    public static function import_post_meta($request) {
        $meta_data = $request->get_json_params();
        
        if (empty($meta_data['post_id']) || empty($meta_data['meta'])) {
            return new WP_Error('missing_data', 'Post ID and meta data are required', array('status' => 400));
        }
        
        $post_id = intval($meta_data['post_id']);
        $meta = $meta_data['meta'];
        
        // Import meta data
        foreach ($meta as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        return new WP_REST_Response(array(
            'message' => 'Post meta imported successfully',
            'imported_count' => count($meta),
        ), 200);
    }
}
