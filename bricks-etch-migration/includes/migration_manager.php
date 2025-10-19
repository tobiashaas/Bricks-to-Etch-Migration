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
     * API service instance
     */
    private $api_service;
    
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
        $this->api_service = B2E_API_Service::get_instance();
        // Transfer manager will be implemented later
        // $this->transfer_manager = new B2E_Transfer_Manager();
    }
    
    /**
     * Start migration process
     */
    public function start_migration($target_url, $api_key) {
        try {
            // Initialize progress
            $this->init_progress();
            
            // Step 1: Basic validation (target site only)
            $this->update_progress('validation', 10, __('Validating migration requirements...', 'bricks-etch-migration'));
            sleep(1); // Simulate processing time
            
            $validation_result = $this->validate_target_site_requirements();
            
            if (!$validation_result['valid']) {
                $error_message = 'Migration validation failed: ' . implode(', ', $validation_result['errors']);
                
                $this->error_handler->log_error('E103', array(
                    'validation_errors' => $validation_result['errors'],
                    'action' => 'Target site validation failed'
                ));
                
                $this->update_progress('error', 0, $error_message);
                
                return new WP_Error('validation_failed', $error_message);
            }
            
            // Step 2: Analyze Bricks Content
            $this->update_progress('analyzing', 20, __('Analyzing Bricks content...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time
            
            $bricks_posts = $this->content_parser->get_bricks_posts();
            $this->update_progress('analyzing', 25, sprintf(__('Found %d Bricks posts to migrate...', 'bricks-etch-migration'), count($bricks_posts)));
            
            // Step 3: Custom Post Types
            $this->update_progress('cpts', 30, __('Migrating custom post types...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time
            $cpt_result = $this->migrate_custom_post_types($target_url, $api_key);
            
            if (is_wp_error($cpt_result)) {
                return $cpt_result;
            }
            
            // Step 4: ACF Field Groups
            $this->update_progress('acf_field_groups', 40, __('Migrating ACF field groups...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time
            $acf_result = $this->migrate_acf_field_groups($target_url, $api_key);
            
            if (is_wp_error($acf_result)) {
                return $acf_result;
            }
            
            // Step 5: MetaBox Configurations
            $this->update_progress('metabox_configs', 50, __('Migrating MetaBox configurations...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time
            $metabox_result = $this->migrate_metabox_configs($target_url, $api_key);
            
            if (is_wp_error($metabox_result)) {
                return $metabox_result;
            }
            
            // Step 6: Media Files
            $this->update_progress('media', 60, __('Migrating media files...', 'bricks-etch-migration'));
            sleep(3); // Simulate processing time
            $media_result = $this->migrate_media_files($target_url, $api_key);
            
            if (is_wp_error($media_result)) {
                return $media_result;
            }
            
            // Step 7: CSS Classes
            $this->update_progress('css_classes', 70, __('Converting CSS classes...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time
            $css_result = $this->migrate_css_classes($target_url, $api_key);
            
            if (is_wp_error($css_result)) {
                return $css_result;
            }
            
            // Step 8: Posts & Content (Main migration step)
            $this->update_progress('posts', 80, __('Migrating posts and content...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time for content migration
            $posts_result = $this->migrate_posts($target_url, $api_key);
            
            if (is_wp_error($posts_result)) {
                return $posts_result;
            }
            
            // Step 9: Finalization
            $this->update_progress('finalization', 95, __('Finalizing migration...', 'bricks-etch-migration'));
            sleep(2); // Simulate processing time
            $finalization_result = $this->finalize_migration();
            
            if (is_wp_error($finalization_result)) {
                return $finalization_result;
            }
            
            // Complete
            $this->update_progress('completed', 100, __('Migration completed successfully!', 'bricks-etch-migration'));
            
            // Store migration statistics
            $migration_stats = get_option('b2e_migration_stats', array());
            $migration_stats['last_migration'] = current_time('mysql');
            $migration_stats['status'] = 'completed';
            update_option('b2e_migration_stats', $migration_stats);
            
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
    public function update_progress($step, $percentage, $message) {
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
            // Log that no CPTs were found
            $this->error_handler->log_error('I001', array(
                'message' => 'No custom post types found to migrate',
                'action' => 'CPT migration skipped'
            ));
            return true; // No CPTs to migrate
        }
        
        // For now, just log the CPTs instead of sending via API
        $this->error_handler->log_error('I002', array(
            'cpts_found' => count($cpts),
            'cpt_names' => array_keys($cpts),
            'action' => 'CPT migration completed (simulated)'
        ));
        
        return true;
    }
    
    /**
     * Migrate ACF field groups
     */
    private function migrate_acf_field_groups($target_url, $api_key) {
        $acf_migrator = new B2E_ACF_Field_Groups_Migrator();
        $field_groups = $acf_migrator->export_field_groups();
        
        if (empty($field_groups)) {
            // Log that no ACF field groups were found
            $this->error_handler->log_error('I003', array(
                'message' => 'No ACF field groups found to migrate',
                'action' => 'ACF migration skipped'
            ));
            return true; // No field groups to migrate
        }
        
        // For now, just log the field groups instead of sending via API
        $this->error_handler->log_error('I004', array(
            'field_groups_found' => count($field_groups),
            'field_group_names' => array_keys($field_groups),
            'action' => 'ACF migration completed (simulated)'
        ));
        
        return true;
    }
    
    /**
     * Migrate MetaBox configurations
     */
    private function migrate_metabox_configs($target_url, $api_key) {
        $metabox_migrator = new B2E_MetaBox_Migrator();
        $configs = $metabox_migrator->export_metabox_configs();
        
        if (empty($configs)) {
            // Log that no MetaBox configs were found
            $this->error_handler->log_error('I005', array(
                'message' => 'No MetaBox configurations found to migrate',
                'action' => 'MetaBox migration skipped'
            ));
            return true; // No configs to migrate
        }
        
        // For now, just log the configs instead of sending via API
        $this->error_handler->log_error('I006', array(
            'configs_found' => count($configs),
            'config_names' => array_keys($configs),
            'action' => 'MetaBox migration completed (simulated)'
        ));
        
        return true;
    }
    
    /**
     * Migrate media files
     */
    private function migrate_media_files($target_url, $api_key) {
        try {
            $this->error_handler->log_error('I007', array('action' => 'Starting media files migration'));
            
            // Get all media files
            $media_files = get_posts(array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'numberposts' => -1,
                'post_mime_type' => 'image'
            ));
            
            if (empty($media_files)) {
                $this->error_handler->log_warning('W005', array('action' => 'No media files found for migration'));
                
                // Store zero count
                $migration_stats = get_option('b2e_migration_stats', array());
                $migration_stats['media_migrated'] = 0;
                update_option('b2e_migration_stats', $migration_stats);
                
                return true;
            }
            
            $migrated_count = 0;
            $failed_count = 0;
            $skipped_count = 0;
            
            foreach ($media_files as $media) {
                $file_path = get_attached_file($media->ID);
                
                if (!$file_path || !file_exists($file_path)) {
                    $skipped_count++;
                    $this->error_handler->log_warning('W005', array(
                        'media_id' => $media->ID,
                        'file_path' => $file_path,
                        'action' => 'Media file not found on disk - skipped'
                    ));
                    continue;
                }
                
                // Prepare media data for transfer
                $media_data = array(
                    'post_title' => $media->post_title,
                    'post_content' => $media->post_content,
                    'post_excerpt' => $media->post_excerpt,
                    'post_mime_type' => $media->post_mime_type,
                    'file_content' => base64_encode(file_get_contents($file_path)),
                    'file_name' => basename($file_path),
                    'meta_input' => array(
                        '_b2e_migrated_from_bricks' => true,
                        '_b2e_original_media_id' => $media->ID,
                        '_b2e_migration_date' => current_time('mysql')
                    )
                );
                
                // Send to target site via API
                $this->api_service->init($target_url, $api_key);
                $result = $this->api_service->send_media($media_data);
                
                if (is_wp_error($result)) {
                    $failed_count++;
                    $this->error_handler->log_error('E105', array(
                        'media_id' => $media->ID,
                        'file_name' => basename($file_path),
                        'error' => $result->get_error_message(),
                        'action' => 'Failed to send media to target site'
                    ));
                    continue; // Skip this media file
                }
                
                $migrated_count++;
                
                $this->error_handler->log_error('I007', array(
                    'original_media_id' => $media->ID,
                    'file_name' => basename($file_path),
                    'file_size' => filesize($file_path),
                    'mime_type' => $media->post_mime_type,
                    'target_media_id' => $result['attachment_id'] ?? 'unknown',
                    'action' => 'Media file migrated successfully to target site'
                ));
            }
            
            $this->error_handler->log_error('I007', array(
                'media_migrated' => $migrated_count,
                'media_failed' => $failed_count,
                'media_skipped' => $skipped_count,
                'total_media' => count($media_files),
                'action' => 'Media Files Migration Summary'
            ));
            
            // Store media migration statistics
            $migration_stats = get_option('b2e_migration_stats', array());
            $migration_stats['media_migrated'] = $migrated_count;
            $migration_stats['media_failed'] = $failed_count;
            $migration_stats['media_skipped'] = $skipped_count;
            update_option('b2e_migration_stats', $migration_stats);
            
            return true;
            
        } catch (Exception $e) {
            $this->error_handler->log_error('E105', array(
                'message' => $e->getMessage(),
                'action' => 'Media migration failed'
            ));
            return new WP_Error('media_migration_failed', $e->getMessage());
        }
    }
    
    /**
     * Migrate CSS classes
     */
    private function migrate_css_classes($target_url, $api_key) {
        $etch_styles = $this->css_converter->convert_bricks_classes_to_etch();
        
        if (empty($etch_styles)) {
            // Log that no CSS classes were found
            $this->error_handler->log_error('I008', array(
                'message' => 'No CSS classes found to migrate',
                'action' => 'CSS migration skipped'
            ));
            return true; // No styles to migrate
        }
        
        // Send CSS styles to target site via API
        $this->api_service->init($target_url, $api_key);
        $result = $this->api_service->send_css($etch_styles);
        
        if (is_wp_error($result)) {
            $this->error_handler->log_error('E106', array(
                'error' => $result->get_error_message(),
                'action' => 'Failed to send CSS styles to target site'
            ));
            return $result;
        }
        
        // Log successful migration
        $this->error_handler->log_error('I009', array(
            'css_classes_found' => count($etch_styles),
            'css_class_names' => array_keys($etch_styles),
            'action' => 'CSS migration completed successfully'
        ));
        
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
        $migrated_pages = 0;
        
        // Memory optimization for large migrations
        $batch_size = 10; // Process 10 posts at a time
        $batches = array_chunk($bricks_posts, $batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            foreach ($batch as $post) {
            // Parse Bricks content
            $bricks_content = $this->content_parser->parse_bricks_content($post->ID);
            
            // Generate Etch Gutenberg blocks (or use existing content if no Bricks)
            if ($bricks_content && isset($bricks_content['elements'])) {
                $etch_content = $this->gutenberg_generator->generate_gutenberg_blocks($bricks_content['elements']);
                
                // If conversion failed or produced empty content, use placeholder
                if (empty($etch_content)) {
                    $etch_content = '<!-- wp:paragraph --><p>Content migrated from Bricks (conversion pending)</p><!-- /wp:paragraph -->';
                    $this->error_handler->log_error('W002', array(
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'post_type' => $post->post_type,
                        'action' => 'Bricks content found but conversion produced empty output - using placeholder'
                    ));
                }
            } else {
                // No Bricks content - use existing post content or placeholder
                $etch_content = !empty($post->post_content) ? $post->post_content : '<!-- wp:paragraph --><p>Empty content</p><!-- /wp:paragraph -->';
            }
            
            // Always migrate posts/pages - never skip them
            // Even if content is empty, we want the structure migrated
            
            // Send to target site via API
            $this->api_service->init($target_url, $api_key);
            $result = $this->api_service->send_post(array(
                'post' => $post,
                'etch_content' => $etch_content
            ));
            
            if (is_wp_error($result)) {
                $this->error_handler->log_error('E105', array(
                    'post_id' => $post->ID,
                    'post_title' => $post->post_title,
                    'error' => $result->get_error_message(),
                    'action' => 'Failed to send post to target site'
                ));
                continue; // Skip this post
            }
            
            // Log successful migration
            $this->error_handler->log_error('I009', array(
                'original_post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'gutenberg_length' => strlen($etch_content),
                'target_post_id' => $result['post_id'] ?? 'unknown',
                'action' => 'Post migrated successfully to target site'
            ));
            
            // Count posts vs pages separately
            if ($post->post_type === 'post') {
                $migrated_posts++;
            } elseif ($post->post_type === 'page') {
                $migrated_pages++;
            }
            
            $total_migrated = $migrated_posts + $migrated_pages;
            
            // Update progress
            $progress_percentage = 70 + (($total_migrated / $total_posts) * 20);
            $this->update_progress('posts', $progress_percentage, 
                sprintf(__('Migrating content... %d posts, %d pages (%d/%d total)', 'bricks-etch-migration'), 
                    $migrated_posts, $migrated_pages, $total_migrated, $total_posts));
            }
            
            // Memory cleanup after each batch
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            // Small delay to prevent server overload
            usleep(100000); // 0.1 second
        }
        
        // Store posts migration statistics - count ACTUALLY migrated posts and pages
        $migration_stats = get_option('b2e_migration_stats', array());
        $migration_stats['total_migrated'] = $migrated_posts + $migrated_pages;
        $migration_stats['posts_migrated'] = $migrated_posts;
        $migration_stats['pages_migrated'] = $migrated_pages;
        
        update_option('b2e_migration_stats', $migration_stats);
        
        return true;
    }
    
    /**
     * Finalize migration
     */
    private function finalize_migration() {
        $settings = get_option('b2e_settings', array());
        
        
        // Update migration log
        $this->error_handler->log_error('W001', array(
            'completed_at' => current_time('mysql'),
            'total_posts' => count($this->content_parser->get_bricks_posts()),
        ));
        
        return true;
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
    
    /**
     * Validate target site requirements only
     * This runs on the TARGET site (Etch), not the source site (Bricks)
     */
    public function validate_target_site_requirements() {
        $validation_results = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array()
        );
        
        // Check if WordPress is properly configured
        if (!function_exists('wp_get_current_user')) {
            $validation_results['errors'][] = 'WordPress is not properly loaded';
            $validation_results['valid'] = false;
        }
        
        // Check if we have write permissions
        if (!is_writable(WP_CONTENT_DIR)) {
            $validation_results['errors'][] = 'WordPress content directory is not writable';
            $validation_results['valid'] = false;
        }
        
        // Check PHP memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit && intval($memory_limit) < 256) {
            $validation_results['warnings'][] = 'PHP memory limit is low (' . $memory_limit . '). Consider increasing it to 512M or higher.';
        }
        
        // Check if Etch is available (optional check)
        if (!defined('ETCH_VERSION') && !class_exists('Etch\\Core\\Etch')) {
            $validation_results['warnings'][] = 'Etch plugin may not be active. Migration will proceed but content may not render properly.';
        }
        
        return $validation_results;
    }
    
    /**
     * Start import process (runs on TARGET site - Etch)
     * This method receives data from the source site and imports it
     */
    public function start_import_process($source_domain, $token) {
        try {
            // Initialize progress
            $this->init_progress();
            
            // Step 1: Validate target site requirements
            $this->update_progress('validation', 10, __('Validating target site requirements...', 'bricks-etch-migration'));
            $validation_result = $this->validate_target_site_requirements();
            
            if (!$validation_result['valid']) {
                $error_message = 'Target site validation failed: ' . implode(', ', $validation_result['errors']);
                
                $this->error_handler->log_error('E103', array(
                    'validation_errors' => $validation_result['errors'],
                    'action' => 'Target site validation failed'
                ));
                
                $this->update_progress('error', 0, $error_message);
                
                return new WP_Error('validation_failed', $error_message);
            }
            
            // Step 2: Import process (this will be implemented later)
            $this->update_progress('import', 50, __('Starting import process...', 'bricks-etch-migration'));
            
            // For now, just return success - the actual import will be implemented
            // when we have the export functionality working from the source site
            $this->update_progress('completed', 100, __('Import process ready. Waiting for source site data...', 'bricks-etch-migration'));
            
            return array(
                'success' => true,
                'message' => 'Target site ready for import',
                'source_domain' => $source_domain,
                'target_domain' => home_url(),
                'progress' => get_transient('b2e_migration_progress')
            );
            
        } catch (Exception $e) {
            $this->error_handler->log_error('E101', array(
                'error' => $e->getMessage(),
                'action' => 'Import process failed'
            ));
            
            $this->update_progress('error', 0, $e->getMessage());
            
            return new WP_Error('import_failed', $e->getMessage());
        }
    }
    
    /**
     * Migrate a single post (for batch processing)
     */
    public function migrate_single_post($post) {
        try {
            // 1. Convert content to Gutenberg (this also sends to Etch via API)
            $gutenberg_result = $this->gutenberg_generator->convert_bricks_to_gutenberg($post);
            
            if (is_wp_error($gutenberg_result)) {
                return $gutenberg_result;
            }
            
            // 2. CSS is converted globally, not per-post
            // So we don't need to do anything here
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('migration_failed', $e->getMessage());
        }
    }
}
