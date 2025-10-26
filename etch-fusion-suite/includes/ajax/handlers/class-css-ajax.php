<?php
/**
 * CSS AJAX Handler
 *
 * Handles CSS migration AJAX requests
 *
 * @package Etch_Fusion_Suite
 * @since 0.5.1
 */

namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\EFS_Base_Ajax_Handler;
use Bricks2Etch\Services\EFS_CSS_Service;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_CSS_Ajax_Handler extends EFS_Base_Ajax_Handler {

	/**
	 * CSS service instance
	 *
	 * @var mixed
	 */
	private $css_service;

	/**
	 * Constructor
	 *
	 * @param mixed $css_service CSS service instance.
	 * @param \Bricks2Etch\Security\B2E_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
	 * @param \Bricks2Etch\Security\B2E_Input_Validator|null $input_validator Input validator instance (optional).
	 * @param \Bricks2Etch\Security\B2E_Audit_Logger|null $audit_logger Audit logger instance (optional).
	 */
	public function __construct( $css_service = null, $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
		if ( $css_service ) {
			$this->css_service = $css_service;
		} elseif ( function_exists( 'efs_container' ) ) {
			try {
				$this->css_service = efs_container()->get( 'css_service' );
			} catch ( \Exception $exception ) {
				$this->css_service = null;
			}
		}

		parent::__construct( $rate_limiter, $input_validator, $audit_logger );
	}

	/**
	 * Register WordPress hooks
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_efs_migrate_css', array( $this, 'convert_css' ) );
		add_action( 'wp_ajax_efs_convert_css', array( $this, 'convert_css' ) );
		add_action( 'wp_ajax_efs_get_global_styles', array( $this, 'get_global_styles' ) );
	}

	/**
	 * AJAX handler to migrate CSS
	 */
	public function convert_css() {
		$this->log( '========================================' );
		$this->log( '🎨 CSS Migration: AJAX handler called - START' );
		$this->log( '🎨 CSS Migration: POST data: ' . wp_json_encode( $_POST ) );
		$this->log( '========================================' );

		// Check rate limit (30 requests per minute)
		if ( ! $this->check_rate_limit( 'convert_css', 30, 60 ) ) {
			return;
		}

		// Verify nonce
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( __( 'Invalid request.', 'etch-fusion-suite' ) );
			return;
		}

		// Get and validate parameters
		try {
			$validated = $this->validate_input(
				array(
					'target_url' => $this->get_post( 'target_url', '' ),
					'api_key'    => $this->get_post( 'api_key', '' ),
				),
				array(
					'target_url' => array(
						'type'     => 'url',
						'required' => true,
					),
					'api_key'    => array(
						'type'     => 'api_key',
						'required' => true,
					),
				)
			);
		} catch ( \Exception $e ) {
			$this->log( '❌ CSS Migration: Validation failed: ' . $e->getMessage() );
			return; // Error already sent by validate_input
		}

		$target_url = $validated['target_url'];
		$api_key    = $validated['api_key'];

		$this->log( '🎨 CSS Migration: target_url=' . $target_url . ', api_key=' . substr( $api_key, 0, 20 ) . '...' );

		// Convert localhost:8081 to efs-etch for Docker internal communication
		$internal_url = $this->convert_to_internal_url( $target_url );

		// Save settings temporarily with internal URL
		update_option(
			'efs_settings',
			array(
				'target_url' => $internal_url,
				'api_key'    => $api_key,
			),
			false
		);

		// Migrate CSS
		try {
			if ( ! $this->css_service || ! $this->css_service instanceof EFS_CSS_Service ) {
				wp_send_json_error( __( 'CSS service unavailable. Please ensure the service container is initialised.', 'etch-fusion-suite' ) );
				return;
			}

			$this->log( '🎨 CSS Migration: Starting service-driven conversion...' );
			$result = $this->css_service->migrate_css_classes( $internal_url, $api_key );

			if ( is_wp_error( $result ) ) {
				$this->log( '❌ CSS Migration: Service returned error: ' . $result->get_error_message() );
				wp_send_json_error( $result->get_error_message() );
				return;
			}

			$api_response = isset( $result['response'] ) && is_array( $result['response'] ) ? $result['response'] : array();
			$styles_count = $result['migrated'] ?? ( isset( $api_response['style_map'] ) ? count( (array) $api_response['style_map'] ) : 0 );

			if ( isset( $api_response['style_map'] ) && is_array( $api_response['style_map'] ) ) {
				update_option( 'efs_style_map', $api_response['style_map'] );
				$this->log( '✅ CSS Migration: Saved style map with ' . count( $api_response['style_map'] ) . ' entries' );
			}

			$this->log_security_event(
				'ajax_action',
				'CSS migrated successfully',
				array(
					'styles_count' => $styles_count,
				)
			);

			$message = $result['message'] ?? __( 'CSS migrated successfully.', 'etch-fusion-suite' );
			wp_send_json_success(
				array(
					'message'      => $message,
					'styles_count' => $styles_count,
					'api_response' => $api_response,
				)
			);
		} catch ( \Exception $e ) {
			$this->log( '❌ CSS Migration: Exception: ' . $e->getMessage() );

			// Log CSS migration failure
			$this->log_security_event( 'ajax_action', 'CSS migration exception: ' . $e->getMessage() );

			wp_send_json_error( 'Exception: ' . $e->getMessage() );
		}
	}

	public function get_global_styles() {
		$styles = $this->css_service->get_global_styles();

		if ( is_wp_error( $styles ) ) {
			wp_send_json_error( $styles->get_error_message() );
		} else {
			wp_send_json_success( $styles );
		}
	}

	/**
	 * Convert localhost URL to internal Docker URL
	 *
	 * @param string $url
	 * @return string
	 */
	private function convert_to_internal_url( $url ) {
		if ( strpos( $url, 'localhost:8081' ) !== false ) {
			return str_replace( 'localhost:8081', 'efs-etch', $url );
		}
		return $url;
	}
}
