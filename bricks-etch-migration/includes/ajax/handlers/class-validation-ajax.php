<?php
/**
 * Validation AJAX Handler
 * 
 * Handles API key and token validation AJAX requests
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-ajax-handler.php';

class B2E_Validation_Ajax_Handler extends B2E_Base_Ajax_Handler {
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action('wp_ajax_b2e_validate_api_key', array($this, 'validate_api_key'));
        add_action('wp_ajax_b2e_validate_migration_token', array($this, 'validate_migration_token'));
    }
    
    /**
     * AJAX handler for validating API key
     */
    public function validate_api_key() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = $this->sanitize_url($this->get_post('target_url', ''));
        $api_key = $this->sanitize_text($this->get_post('api_key', ''));
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Target URL and API key are required');
            return;
        }
        
        // Convert to internal URL
        $internal_url = $this->convert_to_internal_url($target_url);
        
        // Validate API key via API client
        $api_client = new B2E_API_Client();
        $result = $api_client->validate_api_key($internal_url, $api_key);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX handler for validating migration token
     */
    public function validate_migration_token() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = $this->sanitize_url($this->get_post('target_url', ''));
        $token = $this->sanitize_text($this->get_post('token', ''));
        $expires = intval($this->get_post('expires', 0));
        
        if (empty($target_url) || empty($token) || empty($expires)) {
            wp_send_json_error('Target URL, token, and expiration are required');
            return;
        }
        
        // Convert to internal URL
        $internal_url = $this->convert_to_internal_url($target_url);
        
        // Validate migration token on target site
        $api_client = new B2E_API_Client();
        $result = $api_client->validate_migration_token($internal_url, $token, $expires);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Token is valid, return success with API key
            wp_send_json_success($result);
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
            $url = str_replace('http://localhost:8081', 'http://b2e-etch', $url);
            $url = str_replace('https://localhost:8081', 'http://b2e-etch', $url);
        }
        return $url;
    }
}
