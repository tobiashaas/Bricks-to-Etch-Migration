<?php
/**
 * Content AJAX Handler
 * 
 * Handles content migration AJAX requests
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-ajax-handler.php';

class B2E_Content_Ajax_Handler extends B2E_Base_Ajax_Handler {
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action('wp_ajax_b2e_migrate_batch', array($this, 'migrate_batch'));
        add_action('wp_ajax_b2e_get_bricks_posts', array($this, 'get_bricks_posts'));
    }
    
    /**
     * AJAX handler for batch migration (one post at a time)
     */
    public function migrate_batch() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $post_id = intval($this->get_post('post_id', 0));
        $target_url = $this->sanitize_url($this->get_post('target_url', ''));
        $api_key = $this->sanitize_text($this->get_post('api_key', ''));
        
        if (empty($post_id) || empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
            return;
        }
        
        // Check if it's media/attachment
        if ($post->post_type === 'attachment') {
            // Media migration handled separately
            wp_send_json_success(array(
                'message' => 'Media migration handled separately',
                'skipped' => true
            ));
            return;
        }
        
        // Migrate this single post
        try {
            // Convert to internal URL
            $internal_url = $this->convert_to_internal_url($target_url);
            
            // Save settings temporarily
            update_option('b2e_settings', array(
                'target_url' => $internal_url,
                'api_key' => $api_key
            ), false);
            
            $migration_manager = new B2E_Migration_Manager();
            $result = $migration_manager->migrate_single_post($post);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(array(
                    'message' => 'Post migrated successfully',
                    'post_title' => $post->post_title
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to get list of ALL content (Bricks, Gutenberg, Media)
     */
    public function get_bricks_posts() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Use content_parser to get all content types
        $content_parser = new B2E_Content_Parser();
        
        $bricks_posts = $content_parser->get_bricks_posts();
        $gutenberg_posts = $content_parser->get_gutenberg_posts();
        $media = $content_parser->get_media();
        
        $posts_data = array();
        
        // Add Bricks posts
        foreach ($bricks_posts as $post) {
            $posts_data[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'has_bricks' => true
            );
        }
        
        // Add Gutenberg posts
        foreach ($gutenberg_posts as $post) {
            $posts_data[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'has_bricks' => false
            );
        }
        
        // Add Media
        foreach ($media as $attachment) {
            $posts_data[] = array(
                'id' => $attachment->ID,
                'title' => $attachment->post_title ?: basename($attachment->guid),
                'type' => 'attachment',
                'has_bricks' => false
            );
        }
        
        wp_send_json_success(array(
            'posts' => $posts_data,
            'count' => count($posts_data),
            'bricks_count' => count($bricks_posts),
            'gutenberg_count' => count($gutenberg_posts),
            'media_count' => count($media)
        ));
    }
    
    /**
     * Convert localhost URL to internal Docker URL
     * 
     * @param string $url
     * @return string
     */
    private function convert_to_internal_url($url) {
        if (strpos($url, 'localhost:8081') !== false) {
            return str_replace('localhost:8081', 'b2e-etch', $url);
        }
        return $url;
    }
}
