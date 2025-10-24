<?php
/**
 * Base AJAX Handler
 *
 * Abstract base class for all AJAX handlers
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

namespace Bricks2Etch\Ajax;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
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
	 * Rate Limiter instance
	 *
	 * @var \Bricks2Etch\Security\B2E_Rate_Limiter|null
	 */
	protected $rate_limiter;

	/**
	 * Input Validator instance
	 *
	 * @var \Bricks2Etch\Security\B2E_Input_Validator|null
	 */
	protected $input_validator;

	/**
	 * Audit Logger instance
	 *
	 * @var \Bricks2Etch\Security\B2E_Audit_Logger|null
	 */
	protected $audit_logger;

	/**
	 * Constructor
	 *
	 * @param \Bricks2Etch\Security\B2E_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
	 * @param \Bricks2Etch\Security\B2E_Input_Validator|null $input_validator Input validator instance (optional).
	 * @param \Bricks2Etch\Security\B2E_Audit_Logger|null $audit_logger Audit logger instance (optional).
	 */
	public function __construct( $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
		$this->rate_limiter    = $rate_limiter;
		$this->input_validator = $input_validator;
		$this->audit_logger    = $audit_logger;

		// Try to resolve from container if not provided
		if ( function_exists( 'b2e_container' ) ) {
			try {
				$container = b2e_container();

				if ( ! $this->rate_limiter && $container->has( 'rate_limiter' ) ) {
					$this->rate_limiter = $container->get( 'rate_limiter' );
				}

				if ( ! $this->input_validator && $container->has( 'input_validator' ) ) {
					$this->input_validator = $container->get( 'input_validator' );
				}

				if ( ! $this->audit_logger && $container->has( 'audit_logger' ) ) {
					$this->audit_logger = $container->get( 'audit_logger' );
				}
			} catch ( \Exception $e ) {
				// Silently fail if container not available
			}
		}

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
		return check_ajax_referer( $this->nonce_action, $this->nonce_field, false );
	}

	/**
	 * Check user capabilities
	 *
	 * @param string $capability Default: 'manage_options'
	 * @return bool
	 */
	protected function check_capability( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}

	/**
	 * Verify request (nonce + capability)
	 *
	 * @param string $capability Default: 'manage_options'
	 * @return bool
	 */
	protected function verify_request( $capability = 'manage_options' ) {
		$user_id = get_current_user_id();

		if ( ! $this->verify_nonce() ) {
			// Log authentication failure
			if ( $this->audit_logger ) {
				$this->audit_logger->log_authentication_attempt( false, 'user_' . $user_id, 'nonce' );
			}
			wp_send_json_error( 'Invalid nonce' );
			return false;
		}

		if ( ! $this->check_capability( $capability ) ) {
			// Log authorization failure
			if ( $this->audit_logger ) {
				$this->audit_logger->log_authorization_failure( $user_id, 'ajax_request', $_REQUEST['action'] ?? 'unknown' );
			}
			wp_send_json_error( 'Insufficient permissions' );
			return false;
		}

		// Log successful authentication
		if ( $this->audit_logger ) {
			$this->audit_logger->log_authentication_attempt( true, 'user_' . $user_id, 'nonce' );
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
	protected function get_post( $key, $default = null ) {
		return isset( $_POST[ $key ] ) ? $_POST[ $key ] : $default;
	}

	/**
	 * Check rate limit for action
	 *
	 * @param string $action Action name.
	 * @param int $limit Request limit (default: 60).
	 * @param int $window Time window in seconds (default: 60).
	 * @return bool True if within limit, false if exceeded.
	 */
	protected function check_rate_limit( $action, $limit = 60, $window = 60 ) {
		if ( ! $this->rate_limiter ) {
			return true; // No rate limiting if service not available
		}

		$identifier = $this->rate_limiter->get_identifier();

		if ( $this->rate_limiter->check_rate_limit( $identifier, $action, $limit, $window ) ) {
			// Log rate limit exceeded
			if ( $this->audit_logger ) {
				$this->audit_logger->log_rate_limit_exceeded( $identifier, $action );
			}

			wp_send_json_error(
				array(
					'message' => 'Rate limit exceeded. Please try again later.',
					'code'    => 'rate_limit_exceeded',
				)
			);
			return false;
		}

		// Record this request
		$this->rate_limiter->record_request( $identifier, $action, $window );

		return true;
	}

	/**
	 * Validate input data
	 *
	 * @param array $data Data to validate.
	 * @param array $rules Validation rules.
	 * @return array Validated data.
	 */
	protected function validate_input( $data, $rules ) {
		if ( ! $this->input_validator ) {
			return $data; // No validation if service not available
		}

		try {
			return \Bricks2Etch\Security\B2E_Input_Validator::validate_request_data( $data, $rules );
		} catch ( \InvalidArgumentException $e ) {
			// Log invalid input
			if ( $this->audit_logger ) {
				$this->audit_logger->log_security_event(
					'invalid_input',
					'medium',
					$e->getMessage(),
					array(
						'data'  => $data,
						'rules' => $rules,
					)
				);
			}

			wp_send_json_error(
				array(
					'message' => 'Invalid input: ' . $e->getMessage(),
					'code'    => 'invalid_input',
				)
			);
			return array();
		}
	}

	/**
	 * Log security event
	 *
	 * @param string $type Event type.
	 * @param string $message Event message.
	 * @param array $context Additional context (optional).
	 * @param string|null $severity_override Optional severity override (low, medium, high, critical).
	 */
	protected function log_security_event( $type, $message, $context = array(), $severity_override = null ) {
		if ( $this->audit_logger ) {
			// Use override if provided, otherwise determine based on event type
			if ( $severity_override !== null ) {
				$severity = $severity_override;
			} else {
				$severity = 'low';

				// Determine severity based on event type
				if ( in_array( $type, array( 'auth_failure', 'rate_limit_exceeded', 'invalid_input' ), true ) ) {
					$severity = 'medium';
				}
			}

			$this->audit_logger->log_security_event( $type, $severity, $message, $context );
		}
	}

	/**
	 * Sanitize array recursively
	 *
	 * @param array $array Array to sanitize.
	 * @return array Sanitized array.
	 */
	protected function sanitize_array( $array ) {
		if ( $this->input_validator ) {
			return $this->input_validator->sanitize_array_recursive( $array );
		}

		// Fallback sanitization
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = $this->sanitize_array( $value );
			} elseif ( is_string( $value ) ) {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		return $array;
	}

	/**
	 * Validate integer
	 *
	 * @param mixed $value Value to validate.
	 * @param int|null $min Minimum value (optional).
	 * @param int|null $max Maximum value (optional).
	 * @return int|null Validated integer or null.
	 */
	protected function validate_integer( $value, $min = null, $max = null ) {
		if ( $this->input_validator ) {
			try {
				return $this->input_validator->validate_integer( $value, $min, $max, true );
			} catch ( \InvalidArgumentException $e ) {
				return null;
			}
		}

		// Fallback validation
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$value = intval( $value );

		if ( $min !== null && $value < $min ) {
			return null;
		}

		if ( $max !== null && $value > $max ) {
			return null;
		}

		return $value;
	}

	/**
	 * Validate JSON
	 *
	 * @param string $json JSON string to validate.
	 * @return array|null Decoded array or null on failure.
	 */
	protected function validate_json( $json ) {
		if ( $this->input_validator ) {
			try {
				return $this->input_validator->validate_json( $json, true );
			} catch ( \InvalidArgumentException $e ) {
				return null;
			}
		}

		// Fallback validation
		$decoded = json_decode( $json, true );
		return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : null;
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address.
	 */
	protected function get_client_ip() {
		$ip = '';

		// Check for proxy headers
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = $_SERVER[ $header ];

				// X-Forwarded-For can contain multiple IPs
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				// Validate IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					break;
				}
			}
		}

		return ! empty( $ip ) ? $ip : 'unknown';
	}

	/**
	 * Sanitize URL
	 *
	 * @param string $url
	 * @return string
	 */
	protected function sanitize_url( $url ) {
		if ( $this->input_validator ) {
			try {
				return $this->input_validator->validate_url( $url, true );
			} catch ( \InvalidArgumentException $e ) {
				return '';
			}
		}
		return esc_url_raw( $url );
	}

	/**
	 * Sanitize text
	 *
	 * @param string $text
	 * @return string
	 */
	protected function sanitize_text( $text ) {
		if ( $this->input_validator ) {
			try {
				return $this->input_validator->validate_text( $text, 255, true );
			} catch ( \InvalidArgumentException $e ) {
				return '';
			}
		}
		return sanitize_text_field( $text );
	}

	/**
	 * Log message
	 *
	 * @param string $message
	 */
	protected function log( $message ) {
		error_log( 'B2E AJAX: ' . $message );
	}
}

\class_alias( __NAMESPACE__ . '\\B2E_Base_Ajax_Handler', 'B2E_Base_Ajax_Handler' );
