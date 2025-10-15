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
        
        // WPvivid-style migration endpoint
        register_rest_route($namespace, '/migrate', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'handle_wpvivid_migration'),
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
     * Handle WPvivid-style migration request
     */
    public static function handle_wpvivid_migration($request) {
        try {
            $params = $request->get_params();
            
            // Extract migration parameters
            $domain = $params['domain'] ?? null;
            $token = $params['token'] ?? null;
            $expires = isset($params['expires']) ? (int) $params['expires'] : null;
            
            if (empty($domain) || empty($token) || empty($expires)) {
                return new WP_Error('missing_params', 'Missing required migration parameters', array('status' => 400));
            }
            
            // Validate migration token
            $token_manager = new B2E_Migration_Token_Manager();
            $validation = $token_manager->validate_migration_token($token, $domain, $expires);
            
            if (is_wp_error($validation)) {
                return new WP_Error('invalid_token', $validation->get_error_message(), array('status' => 401));
            }
            
            // Start migration process
            $migration_manager = new B2E_Migration_Manager();
            $result = $migration_manager->start_migration($domain, $token);
            
            if (is_wp_error($result)) {
                return new WP_Error('migration_failed', $result->get_error_message(), array('status' => 500));
            }
            
            // Return success response with migration details
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'WPvivid-style migration started successfully!',
                'migration_url' => $request->get_link(),
                'source_domain' => $domain,
                'target_domain' => home_url(),
                'started_at' => current_time('mysql'),
            ), 200);
            
        } catch (Exception $e) {
            error_log('B2E WPvivid Migration Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('migration_error', 'Migration failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array('status' => 500));
        }
    }
}
