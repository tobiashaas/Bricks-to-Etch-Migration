<?php
/**
 * Centralized API Service
 * Single source of truth for all API communication
 */

class B2E_API_Service {
    private static $instance = null;
    private $target_url = '';
    private $api_key = '';
    private $api_client = null;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->api_client = new B2E_API_Client();
    }
    
    /**
     * Initialize with target URL and API key
     */
    public function init($target_url, $api_key) {
        $this->target_url = $this->convert_url_for_docker($target_url);
        $this->api_key = $api_key;
        
        // Save to options for other modules
        update_option('b2e_settings', array(
            'target_url' => $this->target_url,
            'api_key' => $this->api_key
        ), false);
        
        return $this;
    }
    
    /**
     * Get current target URL
     */
    public function get_target_url() {
        if (empty($this->target_url)) {
            $settings = get_option('b2e_settings', array());
            $this->target_url = $settings['target_url'] ?? '';
        }
        return $this->target_url;
    }
    
    /**
     * Get current API key
     */
    public function get_api_key() {
        if (empty($this->api_key)) {
            $settings = get_option('b2e_settings', array());
            $this->api_key = $settings['api_key'] ?? '';
        }
        return $this->api_key;
    }
    
    /**
     * Send post to Etch
     */
    public function send_post($post_data) {
        return $this->api_client->send_post(
            $this->get_target_url(),
            $this->get_api_key(),
            $post_data
        );
    }
    
    /**
     * Send media file to Etch
     */
    public function send_media($media_data) {
        return $this->api_client->send_media_file(
            $this->get_target_url(),
            $this->get_api_key(),
            $media_data
        );
    }
    
    /**
     * Send CSS styles to Etch
     */
    public function send_css($styles) {
        return $this->api_client->send_css_styles(
            $this->get_target_url(),
            $this->get_api_key(),
            $styles
        );
    }
    
    /**
     * Send custom post types to Etch
     */
    public function send_cpts($cpts_data) {
        return $this->api_client->send_custom_post_types(
            $this->get_target_url(),
            $this->get_api_key(),
            $cpts_data
        );
    }
    
    /**
     * Get custom post types from Etch
     */
    public function get_cpts() {
        return $this->api_client->get_custom_post_types(
            $this->get_target_url(),
            $this->get_api_key()
        );
    }
    
    /**
     * Validate API connection
     */
    public function validate_connection() {
        return $this->api_client->validate_connection(
            $this->get_target_url(),
            $this->get_api_key()
        );
    }
    
    /**
     * Convert URL for Docker internal communication
     */
    private function convert_url_for_docker($url) {
        // Check if we're in a Docker/localhost environment
        $current_host = $_SERVER['HTTP_HOST'] ?? '';
        $is_source_localhost = (strpos($current_host, 'localhost') !== false || strpos($current_host, '127.0.0.1') !== false);
        $is_target_localhost = (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);
        
        // Also check site URL as fallback (for CLI context)
        if (!$is_source_localhost) {
            $site_url = get_site_url();
            $is_source_localhost = (strpos($site_url, 'localhost') !== false || strpos($site_url, '127.0.0.1') !== false);
        }
        
        // Only convert if BOTH are localhost (Docker environment)
        if ($is_source_localhost && $is_target_localhost) {
            // Convert localhost:8081 to b2e-etch for Docker internal communication
            if (strpos($url, 'localhost:8081') !== false) {
                $url = str_replace('localhost:8081', 'b2e-etch', $url);
            }
        }
        
        return $url;
    }
    
    /**
     * Convert media URL for Docker internal access
     */
    public function convert_media_url_for_docker($url) {
        // Check if URL contains localhost (Docker environment)
        $is_localhost_url = (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false);
        
        // Also check current site URL as fallback
        $site_url = get_site_url();
        $is_localhost_site = (strpos($site_url, 'localhost') !== false || strpos($site_url, '127.0.0.1') !== false);
        
        // Only convert if we're in localhost (Docker environment)
        if (($is_localhost_url || $is_localhost_site) && strpos($url, 'localhost:8080') !== false) {
            // Replace localhost:8080 with b2e-bricks (Docker container name)
            $url = str_replace('localhost:8080', 'b2e-bricks', $url);
        }
        
        return $url;
    }
}
