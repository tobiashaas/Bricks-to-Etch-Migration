<?php
namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\B2E_Base_Ajax_Handler;
use Bricks2Etch\Api\B2E_API_Client;

if (!defined('ABSPATH')) {
    exit;
}

class B2E_Connection_Ajax_Handler extends B2E_Base_Ajax_Handler {
    
    /**
     * API client instance
     * 
     * @var mixed
     */
    private $api_client;
    
    /**
     * Constructor
     * 
     * @param mixed $api_client API client instance.
     * @param \Bricks2Etch\Security\B2E_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
     * @param \Bricks2Etch\Security\B2E_Input_Validator|null $input_validator Input validator instance (optional).
     * @param \Bricks2Etch\Security\B2E_Audit_Logger|null $audit_logger Audit logger instance (optional).
     */
    public function __construct( $api_client = null, $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
        $this->api_client = $api_client;
        parent::__construct( $rate_limiter, $input_validator, $audit_logger );
    }
    
    protected function register_hooks() {
        add_action('wp_ajax_b2e_test_export_connection', array($this, 'test_export_connection'));
        add_action('wp_ajax_b2e_test_import_connection', array($this, 'test_import_connection'));
    }

    public function test_export_connection() {
        // Check rate limit (10 requests per minute)
        if ( ! $this->check_rate_limit( 'test_export_connection', 10, 60 ) ) {
            return;
        }
        
        if (!$this->verify_request()) {
            return;
        }

        // Get and validate parameters
        try {
            $validated = $this->validate_input(
                array(
                    'target_url' => $this->get_post('target_url', ''),
                    'api_key'    => $this->get_post('api_key', ''),
                ),
                array(
                    'target_url' => array( 'type' => 'url', 'required' => true ),
                    'api_key'    => array( 'type' => 'api_key', 'required' => true ),
                )
            );
        } catch ( \Exception $e ) {
            return; // Error already sent by validate_input
        }
        
        $target_url = $validated['target_url'];
        $api_key = $validated['api_key'];

        $client = new B2E_API_Client();
        $result = $client->test_connection($this->convert_to_internal_url($target_url), $api_key);

        if (isset($result['valid']) && $result['valid']) {
            // Log successful connection test
            $this->log_security_event( 'ajax_action', 'Export connection test successful', array(
                'target_url' => $target_url,
            ) );
            wp_send_json_success(array(
                'message' => __('Connection successful.', 'bricks-etch-migration'),
                'plugins' => $result['plugins'] ?? array(),
            ));
        }

        // Log failed connection test
        $errors = isset($result['errors']) && is_array($result['errors']) ? implode(', ', $result['errors']) : __('Connection failed.', 'bricks-etch-migration');
        $this->log_security_event( 'ajax_action', 'Export connection test failed: ' . $errors, array(
            'target_url' => $target_url,
        ) );
        wp_send_json_error($errors);
    }

    public function test_import_connection() {
        // Check rate limit (10 requests per minute)
        if ( ! $this->check_rate_limit( 'test_import_connection', 10, 60 ) ) {
            return;
        }
        
        if (!$this->verify_request()) {
            return;
        }

        // Get and validate parameters
        try {
            $validated = $this->validate_input(
                array(
                    'source_url' => $this->get_post('source_url', ''),
                    'api_key'    => $this->get_post('api_key', ''),
                ),
                array(
                    'source_url' => array( 'type' => 'url', 'required' => true ),
                    'api_key'    => array( 'type' => 'api_key', 'required' => true ),
                )
            );
        } catch ( \Exception $e ) {
            return; // Error already sent by validate_input
        }
        
        $source_url = $validated['source_url'];
        $api_key = $validated['api_key'];

        $client = new B2E_API_Client();
        $result = $client->test_connection($this->convert_to_internal_url($source_url), $api_key);

        if (isset($result['valid']) && $result['valid']) {
            // Log successful connection test
            $this->log_security_event( 'ajax_action', 'Import connection test successful', array(
                'source_url' => $source_url,
            ) );
            wp_send_json_success(array(
                'message' => __('Connection successful.', 'bricks-etch-migration'),
                'plugins' => $result['plugins'] ?? array(),
            ));
        }

        // Log failed connection test
        $errors = isset($result['errors']) && is_array($result['errors']) ? implode(', ', $result['errors']) : __('Connection failed.', 'bricks-etch-migration');
        $this->log_security_event( 'ajax_action', 'Import connection test failed: ' . $errors, array(
            'source_url' => $source_url,
        ) );
        wp_send_json_error($errors);
    }

    private function convert_to_internal_url($url) {
        if (strpos($url, 'localhost:8081') !== false) {
            $url = str_replace(array('http://localhost:8081', 'https://localhost:8081'), 'http://b2e-etch', $url);
        }
        return $url;
    }
}

\class_alias(__NAMESPACE__ . '\\B2E_Connection_Ajax_Handler', 'B2E_Connection_Ajax_Handler');
