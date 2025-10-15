<?php
/**
 * Migration Token Manager for Bricks to Etch Migration Plugin
 * 
 * Inspired by WPvivid's elegant migration system with domain-embedded tokens
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
     * @return string Migration URL
     */
    public function generate_migration_url($target_domain = null) {
        if (empty($target_domain)) {
            $target_domain = home_url();
        }
        
        // Generate secure token
        $token = $this->generate_secure_token();
        
        // Store token with expiration
        $this->store_token($token);
        
        // Build migration URL
        $migration_url = add_query_arg(array(
            'domain' => $target_domain,
            'token' => $token,
            'expires' => time() + self::TOKEN_EXPIRATION,
        ), $target_domain);
        
        return $migration_url;
    }
    
    /**
     * Generate secure migration token
     */
    private function generate_secure_token() {
        // Generate RSA key pair for this migration session
        $key_pair = $this->generate_rsa_key_pair();
        
        // Store private key for this site
        update_option('b2e_private_key', $key_pair['private']);
        
        // Return public key as token (base64 encoded)
        return base64_encode($key_pair['public']);
    }
    
    /**
     * Generate RSA key pair
     */
    private function generate_rsa_key_pair() {
        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        
        $res = openssl_pkey_new($config);
        if (!$res) {
            throw new Exception('Failed to generate RSA key pair');
        }
        
        // Extract private key
        openssl_pkey_export($res, $private_key);
        
        // Extract public key
        $key_details = openssl_pkey_get_details($res);
        $public_key = $key_details['key'];
        
        return array(
            'private' => $private_key,
            'public' => $public_key,
        );
    }
    
    /**
     * Store token with expiration
     */
    private function store_token($token) {
        $token_data = array(
            'token' => $token,
            'created_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRATION),
            'domain' => home_url(),
        );
        
        update_option('b2e_migration_token', $token_data);
        
        // Also store in transients for faster access
        set_transient('b2e_token_' . substr($token, 0, 16), $token_data, self::TOKEN_EXPIRATION);
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
        // Check expiration
        if (time() > $expires) {
            return new WP_Error('token_expired', 'Migration token has expired');
        }
        
        // Get stored token data
        $token_data = get_option('b2e_migration_token', array());
        
        if (empty($token_data) || $token_data['token'] !== $token) {
            return new WP_Error('invalid_token', 'Invalid migration token');
        }
        
        // Validate domain (optional - for security)
        if (!empty($source_domain) && $token_data['domain'] !== $source_domain) {
            return new WP_Error('domain_mismatch', 'Source domain does not match token');
        }
        
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
