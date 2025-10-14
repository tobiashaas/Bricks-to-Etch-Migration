<?php
/**
 * Migration Manager for Bricks to Etch Migration Plugin
 * 
 * Main controller for the migration process
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Migration_Manager {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Plugin detector instance
     */
    private $plugin_detector;
    
    /**
     * Content parser instance
     */
    private $content_parser;
    
    /**
     * CSS converter instance
     */
    private $css_converter;
    
    /**
     * Gutenberg generator instance
     */
    private $gutenberg_generator;
    
    /**
     * API client instance
     */
    private $api_client;
    
    /**
     * Transfer manager instance
     */
    private $transfer_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new B2E_Error_Handler();
        $this->plugin_detector = new B2E_Plugin_Detector();
        $this->content_parser = new B2E_Content_Parser();
        $this->css_converter = new B2E_CSS_Converter();
        $this->gutenberg_generator = new B2E_Gutenberg_Generator();
        $this->api_client = new B2E_API_Client();
        $this->transfer_manager = new B2E_Transfer_Manager();
    }
    
    /**
     * Start migration process
     */
    public function start_migration($target_url, $api_key) {
        try {
            // Initialize progress
            $this->init_progress();
            
            // Step 1: Validation
            $this->update_progress('validation', 10, __('Validating migration requirements...', 'bricks-etch-migration'));
            $validation_result = $this->validate_migration_requirements();
            
            if (!$validation_result['valid']) {
                $error_message = 'Migration validation failed: ' . implode(', ', $validation_result['errors']);
                
                $this->error_handler->log_error('E103', array(
                    'validation_errors' => $validation_result['errors'],
                    'action' => 'Migration validation failed'
                ));
                
                $this->update_progress('error', 0, $error_message);
                
                return new WP_Error('validation_failed', $error_message);
            }
            
            // Step 2: Custom Post Types
            $this->update_progress('cpts', 20, __('Migrating custom post types...', 'bricks-etch-migration'));
            $cpt_result = $this->migrate_custom_post_types($target_url, $api_key);
            
            if (is_wp_error($cpt_result)) {
                return $cpt_result;
            }
            
            // Step 3: ACF Field Groups
            $this->update_progress('acf_field_groups', 30, __('Migrating ACF field groups...', 'bricks-etch-migration'));
            $acf_result = $this->migrate_acf_field_groups($target_url, $api_key);
            
            if (is_wp_error($acf_result)) {
                return $acf_result;
            }
            
            // Step 4: MetaBox Configurations
            $this->update_progress('metabox_configs', 40, __('Migrating MetaBox configurations...', 'bricks-etch-migration'));
            $metabox_result = $this->migrate_metabox_configs($target_url, $api_key);
            
            if (is_wp_error($metabox_result)) {
                return $metabox_result;
            }
            
            // Step 5: CSS Classes
            $this->update_progress('css_classes', 50, __('Converting CSS classes...', 'bricks-etch-migration'));
            $css_result = $this->migrate_css_classes($target_url, $api_key);
            
            if (is_wp_error($css_result)) {
                return $css_result;
            }
            
            // Step 6: Posts & Content
            $this->update_progress('posts', 70, __('Migrating posts and content...', 'bricks-etch-migration'));
            $posts_result = $this->migrate_posts($target_url, $api_key);
            
            if (is_wp_error($posts_result)) {
                return $posts_result;
            }
            
            // Step 7: Finalization
            $this->update_progress('finalization', 90, __('Finalizing migration...', 'bricks-etch-migration'));
            $finalization_result = $this->finalize_migration();
            
            if (is_wp_error($finalization_result)) {
                return $finalization_result;
            }
            
            // Complete
            $this->update_progress('completed', 100, __('Migration completed successfully!', 'bricks-etch-migration'));
            
            return true;
            
        } catch (Exception $e) {
            $error_message = 'Migration process failed: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ', Line: ' . $e->getLine() . ')';
            
            $this->error_handler->log_error('E201', array(
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'action' => 'Migration process failed'
            ));
            
            $this->update_progress('error', 0, $error_message);
            
            return new WP_Error('migration_failed', $error_message);
        }
    }
    
    /**
     * Initialize progress tracking
     */
    private function init_progress() {
        $progress = array(
            'status' => 'running',
            'current_step' => 'validation',
            'percentage' => 0,
            'started_at' => current_time('mysql'),
            'completed_at' => null,
        );
        
        update_option('b2e_migration_progress', $progress);
    }
    
    /**
     * Update progress
     */
    private function update_progress($step, $percentage, $message) {
        $progress = get_option('b2e_migration_progress', array());
        $progress['current_step'] = $step;
        $progress['percentage'] = $percentage;
        $progress['message'] = $message;
        
        if ($step === 'completed') {
            $progress['status'] = 'completed';
            $progress['completed_at'] = current_time('mysql');
        } elseif ($step === 'error') {
            $progress['status'] = 'error';
            $progress['completed_at'] = current_time('mysql');
        }
        
        update_option('b2e_migration_progress', $progress);
    }
    
    /**
     * Validate migration requirements
     */
    private function validate_migration_requirements() {
        return $this->plugin_detector->validate_migration_requirements();
    }
    
    /**
     * Migrate custom post types
     */
    private function migrate_custom_post_types($target_url, $api_key) {
        $cpt_migrator = new B2E_CPT_Migrator();
        $cpts = $cpt_migrator->export_custom_post_types();
        
        if (empty($cpts)) {
            return true; // No CPTs to migrate
        }
        
        $result = $this->api_client->send_custom_post_types($target_url, $api_key, $cpts);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Migrate ACF field groups
     */
    private function migrate_acf_field_groups($target_url, $api_key) {
        $acf_migrator = new B2E_ACF_Field_Groups_Migrator();
        $field_groups = $acf_migrator->export_field_groups();
        
        if (empty($field_groups)) {
            return true; // No field groups to migrate
        }
        
        $result = $this->api_client->send_acf_field_groups($target_url, $api_key, $field_groups);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Migrate MetaBox configurations
     */
    private function migrate_metabox_configs($target_url, $api_key) {
        $metabox_migrator = new B2E_MetaBox_Migrator();
        $configs = $metabox_migrator->export_metabox_configs();
        
        if (empty($configs)) {
            return true; // No configs to migrate
        }
        
        $result = $this->api_client->send_metabox_configs($target_url, $api_key, $configs);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Migrate CSS classes
     */
    private function migrate_css_classes($target_url, $api_key) {
        $etch_styles = $this->css_converter->convert_bricks_classes_to_etch();
        
        if (empty($etch_styles)) {
            return true; // No styles to migrate
        }
        
        $result = $this->api_client->send_css_classes($target_url, $api_key, $etch_styles);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
    
    /**
     * Migrate posts
     */
    private function migrate_posts($target_url, $api_key) {
        $bricks_posts = $this->content_parser->get_bricks_posts();
        
        if (empty($bricks_posts)) {
            return true; // No posts to migrate
        }
        
        $total_posts = count($bricks_posts);
        $migrated_posts = 0;
        
        foreach ($bricks_posts as $post) {
            // Parse Bricks content
            $bricks_content = $this->content_parser->parse_bricks_content($post->ID);
            
            if (!$bricks_content) {
                continue; // Skip posts without Bricks content
            }
            
            // Generate Etch Gutenberg blocks
            $etch_content = $this->gutenberg_generator->generate_gutenberg_blocks($bricks_content);
            
            if (empty($etch_content)) {
                continue; // Skip posts without content
            }
            
            // Send to target site
            $result = $this->api_client->send_post($target_url, $api_key, $post, $etch_content);
            
            if (is_wp_error($result)) {
                $this->error_handler->log_error('E201', array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'error' => $result->get_error_message(),
                    'action' => 'Failed to migrate post'
                ));
                continue;
            }
            
            // Migrate post meta
            $meta_migrator = new B2E_Custom_Fields_Migrator();
            $meta_result = $meta_migrator->migrate_post_meta($post->ID, $result['post_id'], $target_url, $api_key);
            
            if (is_wp_error($meta_result)) {
                $this->error_handler->log_error('E301', array(
                    'post_id' => $post->ID,
                    'target_post_id' => $result['post_id'],
                    'error' => $meta_result->get_error_message(),
                    'action' => 'Failed to migrate post meta'
                ));
            }
            
            $migrated_posts++;
            
            // Update progress
            $progress_percentage = 70 + (($migrated_posts / $total_posts) * 20);
            $this->update_progress('posts', $progress_percentage, 
                sprintf(__('Migrating posts... %d/%d', 'bricks-etch-migration'), $migrated_posts, $total_posts));
        }
        
        return true;
    }
    
    /**
     * Finalize migration
     */
    private function finalize_migration() {
        $settings = get_option('b2e_settings', array());
        
        // Cleanup Bricks meta if requested
        if (!empty($settings['cleanup_bricks_meta'])) {
            $this->cleanup_bricks_meta();
        }
        
        // Update migration log
        $this->error_handler->log_error('W001', array(
            'completed_at' => current_time('mysql'),
            'total_posts' => count($this->content_parser->get_bricks_posts()),
        ));
        
        return true;
    }
    
    /**
     * Cleanup Bricks meta data
     */
    private function cleanup_bricks_meta() {
        global $wpdb;
        
        // Remove Bricks meta keys
        $bricks_meta_keys = array(
            '_bricks_template_type',
            '_bricks_editor_mode',
            '_bricks_page_content_2',
            '_bricks_page_content',
            '_bricks_page_content_1',
        );
        
        foreach ($bricks_meta_keys as $meta_key) {
            $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
        
        // Remove Bricks global classes
        delete_option('bricks_global_classes');
        
        $this->error_handler->log_error('W002', array(
            'meta_keys_removed' => $bricks_meta_keys,
            'action' => 'Cleanup completed'
        ));
    }
    
    /**
     * Get migration status
     */
    public function get_migration_status() {
        return get_option('b2e_migration_progress', array(
            'status' => 'idle',
            'current_step' => '',
            'percentage' => 0,
            'started_at' => null,
            'completed_at' => null,
        ));
    }
    
    /**
     * Resume migration
     */
    public function resume_migration($target_url, $api_key) {
        $progress = $this->get_migration_status();
        
        if ($progress['status'] !== 'running') {
            return new WP_Error('not_running', 'No migration in progress to resume');
        }
        
        // Continue from current step
        $current_step = $progress['current_step'];
        
        switch ($current_step) {
            case 'cpts':
                return $this->migrate_custom_post_types($target_url, $api_key);
            case 'acf_field_groups':
                return $this->migrate_acf_field_groups($target_url, $api_key);
            case 'metabox_configs':
                return $this->migrate_metabox_configs($target_url, $api_key);
            case 'css_classes':
                return $this->migrate_css_classes($target_url, $api_key);
            case 'posts':
                return $this->migrate_posts($target_url, $api_key);
            case 'finalization':
                return $this->finalize_migration();
            default:
                return new WP_Error('invalid_step', 'Invalid migration step');
        }
    }
}
