<?php
/**
 * CORS Manager
 *
 * Manages Cross-Origin Resource Sharing (CORS) with whitelist-based origin validation.
 * Replaces wildcard CORS with secure, configurable origin checking.
 *
 * @package    Bricks2Etch
 * @subpackage Security
 * @since      0.5.0
 */

namespace Bricks2Etch\Security;

use Bricks2Etch\Repositories\Interfaces\Settings_Repository_Interface;

/**
 * CORS Manager Class
 *
 * Provides whitelist-based CORS policy management with configurable allowed origins.
 */
class B2E_CORS_Manager {

	/**
	 * Settings Repository instance
	 *
	 * @var Settings_Repository_Interface
	 */
	private $settings_repository;

	/**
	 * Constructor
	 *
	 * @param Settings_Repository_Interface $settings_repository Settings repository instance.
	 */
	public function __construct( Settings_Repository_Interface $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	/**
	 * Get allowed CORS origins
	 *
	 * Returns array of allowed origins from settings with fallback to development defaults.
	 *
	 * @return array Array of allowed origin URLs.
	 */
	public function get_allowed_origins() {
		$origins = $this->settings_repository->get_cors_allowed_origins();
		
		// Fallback to development defaults if no origins configured
		if ( empty( $origins ) ) {
			$origins = $this->get_default_origins();
		}
		
		return $origins;
	}

	/**
	 * Get default CORS origins for development
	 *
	 * @return array Array of default development origin URLs.
	 */
	public function get_default_origins() {
		return array(
			'http://localhost:8888',
			'http://localhost:8889',
			'http://127.0.0.1:8888',
			'http://127.0.0.1:8889',
		);
	}

	/**
	 * Check if origin is allowed
	 *
	 * @param string $origin Origin URL to check.
	 * @return bool True if origin is allowed, false otherwise.
	 */
	public function is_origin_allowed( $origin ) {
		if ( empty( $origin ) ) {
			return false;
		}

		$allowed_origins = $this->get_allowed_origins();
		
		// Normalize origin (remove trailing slash)
		$origin = rtrim( $origin, '/' );
		
		// Check if origin is in whitelist
		foreach ( $allowed_origins as $allowed ) {
			$allowed = rtrim( $allowed, '/' );
			if ( $origin === $allowed ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Add CORS headers to response
	 *
	 * Sets CORS headers only if the request origin is in the whitelist.
	 * Called via WordPress rest_pre_serve_request filter.
	 *
	 * @return void
	 */
	public function add_cors_headers() {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
		
		// Check if origin is allowed
		if ( ! $this->is_origin_allowed( $origin ) ) {
			// Log CORS violation if audit logger is available
			if ( function_exists( 'b2e_container' ) ) {
				try {
					$container = b2e_container();
					if ( $container->has( 'audit_logger' ) ) {
						$audit_logger = $container->get( 'audit_logger' );
						$audit_logger->log_security_event(
							'cors_violation',
							'medium',
							'CORS request from unauthorized origin',
							array( 'origin' => $origin )
						);
					}
				} catch ( \Exception $e ) {
					// Silently fail if container not available
				}
			}
			
			// Don't set CORS headers for unauthorized origins
			return;
		}
		
		// Set CORS headers for allowed origin
		header( 'Access-Control-Allow-Origin: ' . $origin );
		header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
		header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Vary: Origin' );
	}

	/**
	 * Handle OPTIONS preflight requests
	 *
	 * Responds to CORS preflight requests with appropriate headers.
	 *
	 * @return void
	 */
	public function handle_preflight_request() {
		if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
			$this->add_cors_headers();
			exit;
		}
	}
}

// Backward compatibility alias
class_alias( 'Bricks2Etch\Security\B2E_CORS_Manager', 'B2E_CORS_Manager' );
