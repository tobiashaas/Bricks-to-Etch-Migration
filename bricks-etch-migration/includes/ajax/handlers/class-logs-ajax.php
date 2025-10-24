<?php
namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\B2E_Base_Ajax_Handler;
use Bricks2Etch\Core\B2E_Error_Handler;

if (!defined('ABSPATH')) {
    exit;
}

class B2E_Logs_Ajax_Handler extends B2E_Base_Ajax_Handler {
    
    /**
     * Error handler instance
     * 
     * @var mixed
     */
    private $error_handler;
    
    /**
     * Constructor
     * 
     * @param mixed $error_handler Error handler instance.
     * @param \Bricks2Etch\Security\B2E_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
     * @param \Bricks2Etch\Security\B2E_Input_Validator|null $input_validator Input validator instance (optional).
     * @param \Bricks2Etch\Security\B2E_Audit_Logger|null $audit_logger Audit logger instance (optional).
     */
    public function __construct( $error_handler = null, $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
        $this->error_handler = $error_handler;
        parent::__construct( $rate_limiter, $input_validator, $audit_logger );
    }
    
    protected function register_hooks() {
        add_action('wp_ajax_b2e_clear_logs', array($this, 'clear_logs'));
        add_action('wp_ajax_b2e_get_logs', array($this, 'get_logs'));
    }

    public function clear_logs() {
        // Check rate limit (10 requests per minute - sensitive operation)
        if ( ! $this->check_rate_limit( 'clear_logs', 10, 60 ) ) {
            return;
        }
        
        if (!$this->verify_request()) {
            return;
        }

        // Log critical security event
        $this->log_security_event( 'logs_cleared', 'Migration logs cleared', array(), 'critical' );

        $error_handler = new B2E_Error_Handler();
        $result = $error_handler->clear_log();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Migration logs cleared successfully.', 'bricks-etch-migration'),
        ));
    }

    public function get_logs() {
        // Check rate limit (60 requests per minute)
        if ( ! $this->check_rate_limit( 'get_logs', 60, 60 ) ) {
            return;
        }
        
        if (!$this->verify_request()) {
            return;
        }

        // Log log access
        $this->log_security_event( 'ajax_action', 'Migration logs accessed' );

        $error_handler = new B2E_Error_Handler();
        $logs = $error_handler->get_recent_logs();

        wp_send_json_success(array(
            'logs' => $logs,
        ));
    }
}

\class_alias(__NAMESPACE__ . '\\B2E_Logs_Ajax_Handler', 'B2E_Logs_Ajax_Handler');
