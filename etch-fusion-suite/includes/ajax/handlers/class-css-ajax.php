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
use Bricks2Etch\Parsers\EFS_CSS_Converter;
use Bricks2Etch\Api\EFS_API_Client;

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
		$this->css_service = $css_service;
		parent::__construct( $rate_limiter, $input_validator, $audit_logger );
	}

	/**
	 * Register WordPress hooks
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_b2e_convert_css', array( $this, 'convert_css' ) );
		add_action( 'wp_ajax_b2e_get_global_styles', array( $this, 'get_global_styles' ) );
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

		// Convert localhost:8081 to b2e-etch for Docker internal communication
		$internal_url = $this->convert_to_internal_url( $target_url );

		// Save settings temporarily with internal URL
		update_option(
			'b2e_settings',
			array(
				'target_url' => $internal_url,
				'api_key'    => $api_key,
			),
			false
		);

		// Migrate CSS
		try {
			// Step 1: Convert Bricks classes to Etch styles
			$this->log( '🎨 CSS Migration: Step 1 - Converting Bricks classes to Etch styles...' );
			$css_converter = new EFS_CSS_Converter();
			$result        = $css_converter->convert_bricks_classes_to_etch();

			if ( is_wp_error( $result ) ) {
				$this->log( '❌ CSS Migration: Converter returned error: ' . $result->get_error_message() );
				wp_send_json_error( $result->get_error_message() );
				return;
			}

			// Extract styles and style_map from result
			$etch_styles = $result['styles'] ?? array();
			$style_map   = $result['style_map'] ?? array();

			$styles_count = count( $etch_styles );
			$this->log( '✅ CSS Migration: Converted ' . $styles_count . ' styles' );
			$this->log( '✅ CSS Migration: Created style map with ' . count( $style_map ) . ' entries' );

			if ( $styles_count === 0 ) {
				$this->log( '⚠️ CSS Migration: No styles to migrate (empty array)' );
				wp_send_json_success(
					array(
						'message'      => 'No CSS styles found to migrate',
						'styles_count' => 0,
					)
				);
				return;
			}

			// Step 2: Send styles AND style_map to Etch via API
			$this->log( '🎨 CSS Migration: Step 2 - Sending ' . $styles_count . ' styles to Etch API...' );
			$api_client = new EFS_API_Client();
			$api_result = $api_client->send_css_styles( $internal_url, $api_key, $result );

			if ( is_wp_error( $api_result ) ) {
				$this->log( '❌ CSS Migration: API error: ' . $api_result->get_error_message() );
				wp_send_json_error( 'Failed to send styles to Etch: ' . $api_result->get_error_message() );
				return;
			}

			// Step 3: Save style map from API response
			if ( isset( $api_result['style_map'] ) && is_array( $api_result['style_map'] ) ) {
				update_option( 'efs_style_map', $api_result['style_map'] );
				$this->log( '✅ CSS Migration: Saved style map with ' . count( $api_result['style_map'] ) . ' entries' );
			} else {
				$this->log( '⚠️ CSS Migration: No style map in API response!' );
			}

			$this->log( '✅ CSS Migration: SUCCESS - ' . $styles_count . ' styles migrated' );

			// Log successful CSS migration
			$this->log_security_event(
				'ajax_action',
				'CSS migrated successfully',
				array(
					'styles_count' => $styles_count,
				)
			);

			wp_send_json_success(
				array(
					'message'      => 'CSS migrated successfully',
					'styles_count' => $styles_count,
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
