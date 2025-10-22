<?php
/**
 * Media AJAX Handler
 * 
 * Handles media migration AJAX requests
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-ajax-handler.php';

class B2E_Media_Ajax_Handler extends B2E_Base_Ajax_Handler {
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action('wp_ajax_b2e_migrate_media', array($this, 'migrate_media'));
    }
    
    /**
     * AJAX handler to migrate media files
     */
    public function migrate_media() {
        $this->log('🎬 Media Migration: AJAX handler called');
        
        // Verify nonce
        if (!$this->verify_nonce()) {
            $this->log('❌ Media Migration: Invalid nonce');
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = $this->sanitize_url($this->get_post('target_url', ''));
        $api_key = $this->sanitize_text($this->get_post('api_key', ''));
        
        $this->log('🎬 Media Migration: target_url=' . $target_url . ', api_key=' . substr($api_key, 0, 20) . '...');
        
        if (empty($target_url) || empty($api_key)) {
            $this->log('❌ Media Migration: Missing required parameters');
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Convert to internal URL
        $internal_url = $this->convert_to_internal_url($target_url);
        
        // Save settings temporarily
        update_option('b2e_settings', array(
            'target_url' => $internal_url,
            'api_key' => $api_key
        ), false);
        
        // Migrate media
        try {
            $this->log('🎬 Media Migration: Creating B2E_Media_Migrator');
            $media_migrator = new B2E_Media_Migrator();
            
            $this->log('🎬 Media Migration: Calling migrate_media with URL: ' . $internal_url);
            $result = $media_migrator->migrate_media($internal_url, $api_key);
            
            $this->log('🎬 Media Migration: Result: ' . print_r($result, true));
            
            if (is_wp_error($result)) {
                $this->log('❌ Media Migration: Result is WP_Error');
                wp_send_json_error($result->get_error_message());
            } else {
                $this->log('✅ Media Migration: Success');
                wp_send_json_success(array(
                    'message' => 'Media migrated successfully',
                    'migrated' => $result['migrated'] ?? 0,
                    'failed' => $result['failed'] ?? 0,
                    'skipped' => $result['skipped'] ?? 0,
                    'total' => $result['total'] ?? 0,
                    'timestamp' => current_time('mysql'),
                    'debug' => 'AJAX called at ' . current_time('mysql')
                ));
            }
        } catch (Exception $e) {
            $this->log('❌ Media Migration: Exception: ' . $e->getMessage());
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
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
