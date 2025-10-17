<?php
/**
 * Migration Token Manager for Bricks to Etch Migration Plugin
 * 
 * Elegant migration system with domain-embedded tokens
 * Generates secure migration URLs with embedded authentication
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Migration_Token_Manager {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Token expiration time (8 hours)
     */
    const TOKEN_EXPIRATION = 8 * HOUR_IN_SECONDS;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new B2E_Error_Handler();
    }
    
    /**
     * Generate migration URL with embedded token
     * 
     * @param string $target_domain Target domain
     * @param int $expiration_seconds Token expiration time in seconds
     * @return string Migration URL
     */
    public function generate_migration_url($target_domain = null, $expiration_seconds = null) {
        if (empty($target_domain)) {
            $target_domain = home_url();
        }
        
        if (empty($expiration_seconds)) {
            $expiration_seconds = self::TOKEN_EXPIRATION;
        }
        
        // Generate secure token
        $token = $this->generate_secure_token();
        
        // Store token with expiration
        $this->store_token($token, $expiration_seconds);
        
        // Build migration URL
        $migration_url = add_query_arg(array(
            'domain' => $target_domain,
            'token' => $token,
            'expires' => time() + $expiration_seconds,
        ), $target_domain);
        
        return $migration_url;
    }
    
    /**
     * Generate secure migration token
     */
    private function generate_secure_token() {
        // Generate a simple secure token (not RSA key pair)
        $token = wp_generate_password(64, false);
        
        // Token will be stored by store_token() method - don't store it here
        return $token;
    }
    
    
    /**
     * Store token with expiration
     */
    private function store_token($token, $expiration_seconds = null) {
        if (empty($expiration_seconds)) {
            $expiration_seconds = self::TOKEN_EXPIRATION;
        }
        
        // Store simple token data
        $token_data = array(
            'token' => $token,
            'created_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', time() + $expiration_seconds),
            'domain' => home_url(),
        );
        
        update_option('b2e_migration_token', $token_data);
        
        // Store token value for validation
        update_option('b2e_migration_token_value', $token);
        
        // Also store in transients for faster access
        set_transient('b2e_token_' . substr($token, 0, 16), $token_data, $expiration_seconds);
    }
    
    /**
     * Validate migration token
     * 
     * @param string $token Token to validate
     * @param string $source_domain Source domain
     * @param int $expires Expiration timestamp
     * @return bool|WP_Error
     */
    public function validate_migration_token($token, $source_domain, $expires) {
        // Debug logging
        error_log('B2E Token Validation Debug:');
        error_log('- Received token: ' . substr($token, 0, 20) . '...');
        error_log('- Source domain: ' . $source_domain);
        error_log('- Expires: ' . $expires . ' (' . date('Y-m-d H:i:s', $expires) . ')');
        error_log('- Current time: ' . time() . ' (' . date('Y-m-d H:i:s') . ')');
        
        // Check expiration
        if (time() > $expires) {
            error_log('- Token expired!');
            return new WP_Error('token_expired', 'Migration token has expired');
        }
        
        // Get stored token value
        $stored_token = get_option('b2e_migration_token_value', '');
        error_log('- Stored token: ' . ($stored_token ? substr($stored_token, 0, 20) . '...' : 'NOT_FOUND'));
        
        if (empty($stored_token)) {
            error_log('- No stored token found!');
            return new WP_Error('invalid_token', 'No migration token found. Please generate a new key.');
        }
        
        if ($stored_token !== $token) {
            error_log('- Token mismatch!');
            error_log('- Expected: ' . substr($stored_token, 0, 20) . '...');
            error_log('- Received: ' . substr($token, 0, 20) . '...');
            return new WP_Error('invalid_token', 'Invalid migration token. Tokens do not match.');
        }
        
        error_log('- Token validation successful!');
        return true;
    }
    
    /**
     * Get migration token data
     */
    public function get_token_data() {
        return get_option('b2e_migration_token', array());
    }
    
    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens() {
        $token_data = get_option('b2e_migration_token', array());
        
        if (!empty($token_data) && isset($token_data['expires_at'])) {
            $expires_timestamp = strtotime($token_data['expires_at']);
            
            if (time() > $expires_timestamp) {
                delete_option('b2e_migration_token');
                delete_option('b2e_private_key');
                
                // Clean up transients
                global $wpdb;
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                        '_transient_b2e_token_%'
                    )
                );
            }
        }
    }
    
    /**
     * Generate migration QR code data
     */
    public function generate_qr_data($target_domain = null) {
        $migration_url = $this->generate_migration_url($target_domain);
        
        return array(
            'url' => $migration_url,
            'qr_data' => $migration_url, // Can be used with QR code libraries
            'expires_in' => self::TOKEN_EXPIRATION,
            'expires_at' => date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRATION),
        );
    }
    
    /**
     * Parse migration URL
     */
    public function parse_migration_url($url) {
        $parsed = wp_parse_url($url);
        $query_params = array();
        
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
        }
        
        return array(
            'domain' => $query_params['domain'] ?? null,
            'token' => $query_params['token'] ?? null,
            'expires' => isset($query_params['expires']) ? (int) $query_params['expires'] : null,
        );
    }
    
    /**
     * Create migration shortcut
     */
    public function create_migration_shortcut($target_domain) {
        $migration_url = $this->generate_migration_url($target_domain);
        
        // Create a short URL (optional)
        $short_url = wp_generate_password(8, false);
        
        // Store short URL mapping
        set_transient('b2e_short_' . $short_url, $migration_url, self::TOKEN_EXPIRATION);
        
        return array(
            'full_url' => $migration_url,
            'short_url' => home_url('/migrate/' . $short_url),
            'qr_data' => $migration_url,
        );
    }
    
}
