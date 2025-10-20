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
        // Debug: Log all headers and parameters
        error_log('B2E API Key Debug - Headers: ' . print_r($request->get_headers(), true));
        error_log('B2E API Key Debug - Params: ' . print_r($request->get_params(), true));
        
        // Try to get API key from header first, then from parameter
        $api_key = $request->get_header('X-API-Key');
        if (empty($api_key)) {
            $api_key = $request->get_header('x_api_key');
        }
        if (empty($api_key)) {
            $api_key = $request->get_param('api_key');
        }
        
        error_log('B2E API Key Debug - Extracted key: ' . $api_key);
        
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key is required', array('status' => 400));
        }
        
        $valid_key = get_option('b2e_api_key');
        error_log('B2E API Key Debug - Stored key: ' . $valid_key);
        
        if ($api_key !== $valid_key) {
            return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
        }
        
        return new WP_REST_Response(array(
            'valid' => true,
            'message' => 'API key is valid',
        ), 200);
    }
    
    /**
     * Check authentication (Application Password or API Key for backwards compatibility)
     */
    public static function check_api_key($request) {
        // Try Application Password first (WordPress standard)
        // Check if Authorization header is present
        $auth_header = $request->get_header('Authorization');
        
        error_log('B2E Auth Check - Authorization header: ' . ($auth_header ? 'present' : 'missing'));
        
        if (!empty($auth_header) && strpos($auth_header, 'Basic ') === 0) {
            // Extract credentials from Basic Auth
            $credentials = base64_decode(substr($auth_header, 6));
            list($username, $password) = explode(':', $credentials, 2);
            
            error_log('B2E Auth Check - Username: ' . $username);
            error_log('B2E Auth Check - Password length: ' . strlen($password));
            
            // Remove spaces from password (Application Passwords have spaces for readability)
            $password = str_replace(' ', '', $password);
            
            error_log('B2E Auth Check - Clean password length: ' . strlen($password));
            
            // Try to authenticate with Application Password
            $user = wp_authenticate_application_password(null, $username, $password);
            
            error_log('B2E Auth Check - Auth result: ' . (is_wp_error($user) ? $user->get_error_message() : 'Success'));
            
            if (!is_wp_error($user) && $user instanceof WP_User) {
                // Authentication successful
                wp_set_current_user($user->ID);
                error_log('B2E Auth Check - User set: ' . $user->ID);
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
            $valid_key = get_option('b2e_api_key');
            
            if ($api_key === $valid_key) {
                return true;
            }
        }
        
        return new WP_Error('unauthorized', 'Authentication required. Use Application Password or API key.', array('status' => 401));
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
     * Receive migrated media from Bricks site
     */
    public static function receive_migrated_media($request) {
        try {
            $media_data = $request->get_json_params();
            
            if (empty($media_data)) {
                return new WP_Error('no_data', 'No media data received', array('status' => 400));
            }
            
            // Decode file content
            $file_content = base64_decode($media_data['file_content']);
            
            if (!$file_content) {
                return new WP_Error('invalid_file', 'Invalid file content', array('status' => 400));
            }
            
            // Upload file to WordPress
            $upload_dir = wp_upload_dir();
            $file_name = sanitize_file_name($media_data['file_name']);
            $file_path = $upload_dir['path'] . '/' . $file_name;
            
            // Ensure unique filename
            $counter = 1;
            $original_name = pathinfo($file_name, PATHINFO_FILENAME);
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            
            while (file_exists($file_path)) {
                $file_name = $original_name . '-' . $counter . '.' . $extension;
                $file_path = $upload_dir['path'] . '/' . $file_name;
                $counter++;
            }
            
            // Write file
            if (file_put_contents($file_path, $file_content) === false) {
                return new WP_Error('write_failed', 'Failed to write file', array('status' => 500));
            }
            
            // Create attachment post
            $attachment_data = array(
                'post_title' => sanitize_text_field($media_data['post_title'] ?? ''),
                'post_content' => wp_kses_post($media_data['post_content'] ?? ''),
                'post_excerpt' => wp_kses_post($media_data['post_excerpt'] ?? ''),
                'post_mime_type' => sanitize_text_field($media_data['post_mime_type']),
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'meta_input' => array(
                    '_b2e_migrated_from_bricks' => true,
                    '_b2e_original_media_id' => intval($media_data['meta_input']['_b2e_original_media_id'] ?? 0),
                    '_b2e_migration_date' => sanitize_text_field($media_data['meta_input']['_b2e_migration_date'] ?? current_time('mysql'))
                )
            );
            
            $attachment_id = wp_insert_post($attachment_data);
            
            if (is_wp_error($attachment_id)) {
                unlink($file_path); // Clean up file
                return new WP_Error('insert_failed', 'Failed to insert attachment: ' . $attachment_id->get_error_message(), array('status' => 500));
            }
            
            // Update attachment metadata
            $file_url = $upload_dir['url'] . '/' . $file_name;
            update_post_meta($attachment_id, '_wp_attached_file', $upload_dir['subdir'] . '/' . $file_name);
            
            // Generate attachment metadata (require image.php for image processing)
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);
            
            // Log successful migration
            error_log("B2E: Successfully migrated media '{$media_data['file_name']}' (ID: {$attachment_id}) from Bricks");
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Media migrated successfully',
                'attachment_id' => $attachment_id,
                'file_name' => $media_data['file_name'],
                'file_url' => wp_get_attachment_url($attachment_id)
            ), 200);
            
        } catch (Exception $e) {
            error_log("B2E: Error receiving migrated media: " . $e->getMessage());
            return new WP_Error('receive_failed', 'Failed to receive migrated media: ' . $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Get migrated content count
     */
    public static function get_migrated_content_count($request) {
        try {
            $migrated_posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_b2e_migrated_from_bricks',
                        'value' => true,
                        'compare' => '='
                    )
                )
            ));
            
            $migrated_pages = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_b2e_migrated_from_bricks',
                        'value' => true,
                        'compare' => '='
                    )
                )
            ));
            
            $migrated_media = get_posts(array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_b2e_migrated_from_bricks',
                        'value' => true,
                        'compare' => '='
                    )
                )
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'posts' => count($migrated_posts),
                'pages' => count($migrated_pages),
                'media' => count($migrated_media),
                'total' => count($migrated_posts) + count($migrated_pages) + count($migrated_media)
            ), 200);
            
        } catch (Exception $e) {
            return new WP_Error('count_failed', 'Failed to get migrated content count: ' . $e->getMessage(), array('status' => 500));
        }
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
            return new WP_Error('post_save_failed', 'Failed to save post', array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'post_id' => $post_id,
            'action' => $action,
            'message' => 'Post ' . $action . ' successfully',
        ), 200);
    }
    
    /**
     * Import CSS classes
     */
    public static function import_css_classes($request) {
        error_log('🎯 API Endpoint: import_css_classes called');
        
        $classes_data = $request->get_json_params();
        $styles_count = is_array($classes_data) ? count($classes_data) : 0;
        
        error_log('🎯 API Endpoint: Received ' . $styles_count . ' CSS classes');
        
        if (empty($classes_data)) {
            error_log('❌ API Endpoint: No CSS classes data received');
            return new WP_Error('missing_data', 'CSS classes data is required', array('status' => 400));
        }
        
        error_log('🎯 API Endpoint: Calling CSS Converter to import styles...');
        $css_converter = new B2E_CSS_Converter();
        $result = $css_converter->import_etch_styles($classes_data);
        
        if (is_wp_error($result)) {
            error_log('❌ API Endpoint: CSS Converter returned error: ' . $result->get_error_message());
            return $result;
        }
        
        // Get the style map from Etch side
        $style_map = get_option('b2e_style_map', array());
        
        error_log('✅ API Endpoint: CSS classes imported successfully (' . $styles_count . ' styles)');
        error_log('📋 API Endpoint: Returning style map with ' . count($style_map) . ' entries');
        
        return new WP_REST_Response(array(
            'message' => 'CSS classes imported successfully',
            'imported_count' => $styles_count,
            'style_map' => $style_map, // Return style map to Bricks side!
        ), 200);
    }
    
    /**
     * Import custom post types
     */
    public static function import_custom_post_types($request) {
        try {
            $cpts_data = $request->get_json_params();
            
            if (empty($cpts_data)) {
                return new WP_Error('missing_data', 'CPTs data is required', array('status' => 400));
            }
            
            $cpt_migrator = new B2E_CPT_Migrator();
            $result = $cpt_migrator->import_custom_post_types($cpts_data);
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            return new WP_REST_Response(array(
                'message' => 'Custom post types registered successfully',
                'registered_count' => count($cpts_data),
            ), 200);
        } catch (Exception $e) {
            error_log('B2E Import CPTs Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('import_error', 'CPT import failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
        }
    }
    
    /**
     * Import ACF field groups
     */
    public static function import_acf_field_groups($request) {
        try {
            $field_groups_data = $request->get_json_params();
            
            if (empty($field_groups_data)) {
                return new WP_Error('missing_data', 'Field groups data is required', array('status' => 400));
            }
            
            // Check if ACF is active
            if (!function_exists('acf_add_local_field_group')) {
                // ACF not active - skip import but don't fail
                return new WP_REST_Response(array(
                    'message' => 'ACF plugin is not active - skipping field groups import',
                    'imported_count' => 0,
                    'skipped' => true,
                ), 200);
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
        } catch (Exception $e) {
            error_log('B2E Import ACF Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('import_error', 'ACF import failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
        }
    }
    
    /**
     * Import MetaBox configs
     */
    public static function import_metabox_configs($request) {
        try {
            $configs_data = $request->get_json_params();
            
            if (empty($configs_data)) {
                return new WP_Error('missing_data', 'MetaBox configs data is required', array('status' => 400));
            }
            
            // Check if Meta Box is active
            if (!function_exists('rwmb_meta')) {
                // Meta Box not active - skip import but don't fail
                return new WP_REST_Response(array(
                    'message' => 'Meta Box plugin is not active - skipping configs import',
                    'imported_count' => 0,
                    'skipped' => true,
                ), 200);
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
        } catch (Exception $e) {
            error_log('B2E Import MetaBox Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('import_error', 'MetaBox import failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
        }
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
    
    /**
     * Import media file
     */
    public static function import_media_file($request) {
        try {
            $media_data = $request->get_json_params();
            
            if (empty($media_data)) {
                return new WP_Error('missing_data', 'Media data is required', array('status' => 400));
            }
            
            // Validate required fields
            $required_fields = array('filename', 'mime_type', 'file_content');
            foreach ($required_fields as $field) {
                if (!isset($media_data[$field])) {
                    return new WP_Error('missing_field', "Required field '{$field}' is missing", array('status' => 400));
                }
            }
            
            // Decode file content
            $file_content = base64_decode($media_data['file_content']);
            if ($file_content === false) {
                return new WP_Error('invalid_file_content', 'Invalid base64 file content', array('status' => 400));
            }
            
            // Create temporary file
            $temp_file = wp_tempnam($media_data['filename']);
            file_put_contents($temp_file, $file_content);
            
            // Prepare file array for wp_handle_sideload
            $file_array = array(
                'name' => $media_data['filename'],
                'type' => $media_data['mime_type'],
                'tmp_name' => $temp_file,
                'error' => 0,
                'size' => strlen($file_content),
            );
            
            // Upload file
            $upload = wp_handle_sideload($file_array, array('test_form' => false));
            
            if (isset($upload['error'])) {
                return new WP_Error('upload_failed', 'File upload failed: ' . $upload['error'], array('status' => 500));
            }
            
            // Create attachment post
            $attachment = array(
                'post_mime_type' => $media_data['mime_type'],
                'post_title' => $media_data['title'] ?? sanitize_file_name($media_data['filename']),
                'post_content' => $media_data['description'] ?? '',
                'post_excerpt' => $media_data['caption'] ?? '',
                'post_status' => 'inherit',
                'post_parent' => $media_data['post_parent'] ?? 0,
            );
            
            $attachment_id = wp_insert_attachment($attachment, $upload['file']);
            
            if (is_wp_error($attachment_id)) {
                return $attachment_id;
            }
            
            // Generate attachment metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);
            
            // Set alt text
            if (!empty($media_data['alt_text'])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $media_data['alt_text']);
            }
            
            // Set upload date if provided
            if (!empty($media_data['upload_date'])) {
                wp_update_post(array(
                    'ID' => $attachment_id,
                    'post_date' => $media_data['upload_date'],
                    'post_date_gmt' => get_gmt_from_date($media_data['upload_date']),
                ));
            }
            
            // Store original metadata if provided
            if (!empty($media_data['metadata'])) {
                update_post_meta($attachment_id, '_wp_attachment_metadata', $media_data['metadata']);
            }
            
            return new WP_REST_Response(array(
                'message' => 'Media file imported successfully',
                'media_id' => $attachment_id,
                'file_url' => wp_get_attachment_url($attachment_id),
            ), 200);
            
        } catch (Exception $e) {
            error_log('B2E Import Media Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('import_error', 'Media import failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
        }
    }
    
    /**
     * Handle key-based migration request
     */
    public static function handle_key_migration($request) {
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
                return new WP_Error('missing_params', 'Missing required migration parameters', array('status' => 400));
            }
            
            // Validate migration token
            $token_manager = new B2E_Migration_Token_Manager();
            $validation = $token_manager->validate_migration_token($token, $domain, $expires);
            
            if (is_wp_error($validation)) {
                error_log('B2E Debug - Token validation failed: ' . $validation->get_error_message());
                error_log('B2E Debug - Validation error code: ' . $validation->get_error_code());
                error_log('B2E Debug - Validation error data: ' . print_r($validation->get_error_data(), true));
                return new WP_Error('invalid_token', $validation->get_error_message(), array('status' => 401));
            }
            
            error_log('B2E Debug - Token validation successful');
            
            // Start import process (this runs on TARGET site)
            $migration_manager = new B2E_Migration_Manager();
            $result = $migration_manager->start_import_process($domain, $token);
            
            if (is_wp_error($result)) {
                return new WP_Error('migration_failed', $result->get_error_message(), array('status' => 500));
            }
            
            // Return success response with migration details
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Key-based migration started successfully!',
                    'migration_url' => $request->get_route(),
                    'source_domain' => $domain,
                    'target_domain' => home_url(),
                    'started_at' => current_time('mysql'),
                ), 200);
            
        } catch (Exception $e) {
            error_log('B2E Key Migration Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('migration_error', 'Migration failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
        }
    }
    
    /**
     * Generate migration key endpoint
     * Creates a new migration token and returns the migration key URL
     */
    public static function generate_migration_key($request) {
        try {
            // Create token manager
            $token_manager = new B2E_Migration_Token_Manager();
            
            // Generate migration token
            $token_data = $token_manager->generate_migration_token();
            
            if (is_wp_error($token_data)) {
                return new WP_Error('token_generation_failed', $token_data->get_error_message(), array('status' => 500));
            }
            
            // Build migration key URL
            $migration_key = add_query_arg(array(
                'domain' => home_url(),
                'token' => $token_data['token'],
                'expires' => $token_data['expires']
            ), home_url());
            
            // Return response
            return new WP_REST_Response(array(
                'success' => true,
                'migration_key' => $migration_key,
                'token' => $token_data['token'],
                'domain' => home_url(),
                'expires' => $token_data['expires'],
                'expires_at' => date('Y-m-d H:i:s', $token_data['expires']),
                'valid_for' => '24 hours',
                'generated_at' => current_time('mysql'),
            ), 200);
            
        } catch (Exception $e) {
            error_log('B2E Generate Key Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('generation_error', 'Failed to generate migration key: ' . $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Validate migration token endpoint
     * Used by the "Validate Key" button to test API connection
     */
    public static function validate_migration_token($request) {
        try {
            // Get request body
            $body = $request->get_json_params();
            
            if (empty($body['token']) || empty($body['expires'])) {
                return new WP_Error('missing_parameters', 'Token and expires parameters are required', array('status' => 400));
            }
            
            $token = sanitize_text_field($body['token']);
            $expires = intval($body['expires']);
            
            // Validate token using token manager
            $token_manager = new B2E_Migration_Token_Manager();
            $validation_result = $token_manager->validate_migration_token($token, '', $expires);
            
            if (is_wp_error($validation_result)) {
                return new WP_Error('token_validation_failed', $validation_result->get_error_message(), array('status' => 401));
            }
            
            // Token is valid - generate or retrieve API key
            $api_key_data = get_option('b2e_api_key');
            
            // If no API key exists, generate one
            if (empty($api_key_data)) {
                $api_client = new B2E_API_Client();
                $api_key = $api_client->create_api_key();
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
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Token validation successful',
                'api_key' => $api_key,
                'target_domain' => home_url(),
                'site_name' => get_bloginfo('name'),
                'wordpress_version' => get_bloginfo('version'),
                'etch_active' => class_exists('Etch\Plugin') || function_exists('etch_run_plugin'),
                'validated_at' => current_time('mysql'),
            ), 200);
            
        } catch (Exception $e) {
            error_log('B2E Token Validation Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('validation_error', 'Token validation failed: ' . $e->getMessage(), array('status' => 500));
        }
    }
}
