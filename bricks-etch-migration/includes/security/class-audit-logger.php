<?php
/**
 * Audit Logger
 *
 * Structured logging for security events including authentication, authorization,
 * rate limiting, and suspicious activity.
 *
 * @package    Bricks2Etch
 * @subpackage Security
 * @since      0.5.0
 */

namespace Bricks2Etch\Security;

/**
 * Audit Logger Class
 *
 * Provides structured security event logging with severity levels and context.
 */
class B2E_Audit_Logger {

	/**
	 * Error Handler instance
	 *
	 * @var \B2E_Error_Handler|null
	 */
	private $error_handler;

	/**
	 * Maximum number of security events to keep
	 *
	 * @var int
	 */
	private $max_events = 1000;

	/**
	 * Security log option name
	 *
	 * @var string
	 */
	private $log_option = 'b2e_security_log';

	/**
	 * Constructor
	 *
	 * @param \B2E_Error_Handler|null $error_handler Error handler instance (optional).
	 */
	public function __construct( $error_handler = null ) {
		$this->error_handler = $error_handler;
	}

	/**
	 * Log security event
	 *
	 * @param string $event_type Event type (auth_success, auth_failure, etc.).
	 * @param string $severity   Severity level (low, medium, high, critical).
	 * @param string $message    Human-readable message.
	 * @param array  $context    Additional context data (optional).
	 * @return bool True on success, false on failure.
	 */
	public function log_security_event( $event_type, $severity, $message, $context = array() ) {
		// Build log entry
		$entry = array(
			'timestamp'  => current_time( 'mysql' ),
			'event_type' => sanitize_text_field( $event_type ),
			'severity'   => sanitize_text_field( $severity ),
			'message'    => sanitize_text_field( $message ),
			'context'    => $this->build_context( $context ),
		);

		// Get existing logs
		$logs = $this->get_security_logs( $this->max_events );

		// Add new entry at the beginning
		array_unshift( $logs, $entry );

		// Trim to max events
		$logs = array_slice( $logs, 0, $this->max_events );

		// Save logs
		$saved = update_option( $this->log_option, $logs, false );

		// Also log to error_log for critical events
		if ( in_array( $severity, array( 'high', 'critical' ), true ) ) {
			error_log(
				sprintf(
					'[B2E Security] %s - %s: %s',
					strtoupper( $severity ),
					$event_type,
					$message
				)
			);

			// Use error handler if available
			if ( $this->error_handler ) {
				$this->error_handler->log_warning(
					$message,
					array(
						'event_type' => $event_type,
						'severity'   => $severity,
						'context'    => $context,
					)
				);
			}
		}

		return $saved;
	}

	/**
	 * Log authentication attempt
	 *
	 * @param bool   $success  Whether authentication was successful.
	 * @param string $username Username or identifier.
	 * @param string $method   Authentication method (api_key, token, etc.).
	 * @return bool True on success, false on failure.
	 */
	public function log_authentication_attempt( $success, $username, $method ) {
		$event_type = $success ? 'auth_success' : 'auth_failure';
		$severity   = $success ? 'low' : 'medium';
		$message    = sprintf(
			'Authentication %s for %s via %s',
			$success ? 'succeeded' : 'failed',
			$username,
			$method
		);

		return $this->log_security_event(
			$event_type,
			$severity,
			$message,
			array(
				'username' => $username,
				'method'   => $method,
			)
		);
	}

	/**
	 * Log authorization failure
	 *
	 * @param int    $user_id  User ID.
	 * @param string $action   Action attempted.
	 * @param string $resource Resource accessed.
	 * @return bool True on success, false on failure.
	 */
	public function log_authorization_failure( $user_id, $action, $resource ) {
		$message = sprintf(
			'Authorization failed for user %d attempting %s on %s',
			$user_id,
			$action,
			$resource
		);

		return $this->log_security_event(
			'authorization_failure',
			'medium',
			$message,
			array(
				'user_id'  => $user_id,
				'action'   => $action,
				'resource' => $resource,
			)
		);
	}

	/**
	 * Log rate limit exceeded
	 *
	 * @param string $identifier Identifier (IP, user ID).
	 * @param string $action     Action that was rate limited.
	 * @return bool True on success, false on failure.
	 */
	public function log_rate_limit_exceeded( $identifier, $action ) {
		$message = sprintf(
			'Rate limit exceeded for %s on action %s',
			$identifier,
			$action
		);

		return $this->log_security_event(
			'rate_limit_exceeded',
			'medium',
			$message,
			array(
				'identifier' => $identifier,
				'action'     => $action,
			)
		);
	}

	/**
	 * Log suspicious activity
	 *
	 * @param string $type    Activity type.
	 * @param array  $details Activity details.
	 * @return bool True on success, false on failure.
	 */
	public function log_suspicious_activity( $type, $details ) {
		$message = sprintf( 'Suspicious activity detected: %s', $type );

		return $this->log_security_event( 'suspicious_activity', 'high', $message, $details );
	}

	/**
	 * Get security logs
	 *
	 * @param int         $limit    Maximum number of logs to retrieve (default: 100).
	 * @param string|null $severity Filter by severity level (optional).
	 * @return array Array of log entries.
	 */
	public function get_security_logs( $limit = 100, $severity = null ) {
		$logs = get_option( $this->log_option, array() );

		// Filter by severity if specified
		if ( $severity !== null ) {
			$logs = array_filter(
				$logs,
				function ( $log ) use ( $severity ) {
					return isset( $log['severity'] ) && $log['severity'] === $severity;
				}
			);
		}

		// Limit results
		$logs = array_slice( $logs, 0, $limit );

		return $logs;
	}

	/**
	 * Clear security logs
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_security_logs() {
		return delete_option( $this->log_option );
	}

	/**
	 * Export logs as JSON
	 *
	 * @param int         $limit    Maximum number of logs to export (default: 1000).
	 * @param string|null $severity Filter by severity level (optional).
	 * @return string JSON-encoded logs.
	 */
	public function export_logs_json( $limit = 1000, $severity = null ) {
		$logs = $this->get_security_logs( $limit, $severity );
		return wp_json_encode( $logs, JSON_PRETTY_PRINT );
	}

	/**
	 * Build context array
	 *
	 * Adds standard context information to custom context.
	 *
	 * @param array $custom_context Custom context data.
	 * @return array Complete context array.
	 */
	private function build_context( $custom_context = array() ) {
		$context = array(
			'user_id'     => get_current_user_id(),
			'ip'          => $this->get_client_ip(),
			'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
			'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '',
		);

		// Merge with custom context
		return array_merge( $context, $custom_context );
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
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
}

// Backward compatibility alias
class_alias( 'Bricks2Etch\Security\B2E_Audit_Logger', 'B2E_Audit_Logger' );
