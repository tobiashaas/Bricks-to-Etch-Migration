<?php
/**
 * Media AJAX Handler
 *
 * Handles media migration AJAX requests
 *
 * @package Etch_Fusion_Suite
 * @since 0.5.1
 */

namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\EFS_Base_Ajax_Handler;
use Bricks2Etch\Services\EFS_Media_Service;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Media_Ajax_Handler extends EFS_Base_Ajax_Handler {

	/**
	 * Media service instance
	 *
	 * @var mixed
	 */
	private $media_service;

	/**
	 * Constructor
	 *
	 * @param mixed $media_service Media service instance.
	 * @param \Bricks2Etch\Security\EFS_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
	 * @param \Bricks2Etch\Security\EFS_Input_Validator|null $input_validator Input validator instance (optional).
	 * @param \Bricks2Etch\Security\EFS_Audit_Logger|null $audit_logger Audit logger instance (optional).
	 */
	public function __construct( $media_service = null, $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
		if ( $media_service ) {
			$this->media_service = $media_service;
		} elseif ( function_exists( 'efs_container' ) ) {
			try {
				$this->media_service = efs_container()->get( 'media_service' );
			} catch ( \Exception $exception ) {
				$this->media_service = null;
			}
		}

		parent::__construct( $rate_limiter, $input_validator, $audit_logger );
	}

	/**
	 * Register WordPress hooks
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_efs_migrate_media', array( $this, 'migrate_media' ) );
	}

	/**
	 * AJAX handler to migrate media files
	 */
	public function migrate_media() {
		$this->log( '🎬 Media Migration: AJAX handler called' );

		// Check rate limit (30 requests per minute)
		if ( ! $this->check_rate_limit( 'migrate_media', 30, 60 ) ) {
			return;
		}

		// Verify nonce
		if ( ! $this->verify_nonce() ) {
			$this->log( '❌ Media Migration: Invalid nonce' );
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
			$this->log( '❌ Media Migration: Validation failed: ' . $e->getMessage() );
			return; // Error already sent by validate_input
		}

		$target_url = $validated['target_url'];
		$api_key    = $validated['api_key'];

		$this->log( '🎬 Media Migration: target_url=' . $target_url . ', api_key=' . substr( $api_key, 0, 20 ) . '...' );

		// Convert to internal URL
		$internal_url = $this->convert_to_internal_url( $target_url );

		// Save settings temporarily
		update_option(
			'efs_settings',
			array(
				'target_url' => $internal_url,
				'api_key'    => $api_key,
			),
			false
		);

		// Migrate media
		try {
			if ( ! $this->media_service || ! $this->media_service instanceof EFS_Media_Service ) {
				wp_send_json_error( __( 'Media service unavailable. Please ensure the service container is initialised.', 'etch-fusion-suite' ) );
				return;
			}

			$result = $this->media_service->migrate_media( $internal_url, $api_key );

			if ( is_wp_error( $result ) ) {
				$this->log_security_event( 'ajax_action', 'Media migration failed: ' . $result->get_error_message() );
				wp_send_json_error( $result->get_error_message() );
				return;
			}

			$summary = $result['summary'] ?? array();

			$this->log_security_event(
				'ajax_action',
				'Media migrated successfully',
				array(
					'migrated' => $summary['migrated_media'] ?? 0,
					'failed'   => $summary['failed_media'] ?? 0,
				)
			);

			wp_send_json_success(
				array(
					'message'   => $result['message'] ?? __( 'Media migrated successfully.', 'etch-fusion-suite' ),
					'summary'   => $summary,
					'timestamp' => current_time( 'mysql' ),
				)
			);
		} catch ( \Exception $e ) {
			$this->log( '❌ Media Migration: Exception: ' . $e->getMessage() );
			$this->log_security_event( 'ajax_action', 'Media migration exception: ' . $e->getMessage() );
			wp_send_json_error( 'Exception: ' . $e->getMessage() );
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
