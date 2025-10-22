<?php
/**
 * Base AJAX Handler
 * 
 * Abstract base class for all AJAX handlers
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class B2E_Base_Ajax_Handler {
    
    /**
     * Nonce action
     */
    protected $nonce_action = 'b2e_nonce';
    
    /**
     * Nonce field
     */
    protected $nonce_field = 'nonce';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_hooks();
    }
    
    /**
     * Register WordPress hooks
     * Must be implemented by child classes
     */
    abstract protected function register_hooks();
    
    /**
     * Verify nonce
     * 
     * @return bool
     */
    protected function verify_nonce() {
        return check_ajax_referer($this->nonce_action, $this->nonce_field, false);
    }
    
    /**
     * Check user capabilities
     * 
     * @param string $capability Default: 'manage_options'
     * @return bool
     */
    protected function check_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    /**
     * Verify request (nonce + capability)
     * 
     * @param string $capability Default: 'manage_options'
     * @return bool
     */
    protected function verify_request($capability = 'manage_options') {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Invalid nonce');
            return false;
        }
        
        if (!$this->check_capability($capability)) {
            wp_send_json_error('Insufficient permissions');
            return false;
        }
        
        return true;
    }
    
    /**
     * Get POST parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_post($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url
     * @return string
     */
    protected function sanitize_url($url) {
        return esc_url_raw($url);
    }
    
    /**
     * Sanitize text
     * 
     * @param string $text
     * @return string
     */
    protected function sanitize_text($text) {
        return sanitize_text_field($text);
    }
    
    /**
     * Log message
     * 
     * @param string $message
     */
    protected function log($message) {
        error_log('B2E AJAX: ' . $message);
    }
}
