<?php
namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\EFS_Base_Ajax_Handler;
use Bricks2Etch\Controllers\EFS_Template_Controller;
use Bricks2Etch\Security\EFS_Audit_Logger;
use Bricks2Etch\Security\EFS_Input_Validator;
use Bricks2Etch\Security\EFS_Rate_Limiter;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler for template extraction flow.
 */
class EFS_Template_Ajax_Handler extends EFS_Base_Ajax_Handler {
	/**
	 * @var EFS_Template_Controller
	 */
	protected $template_controller;

	/**
	 * Constructor.
	 */
	public function __construct( EFS_Template_Controller $template_controller, EFS_Rate_Limiter $rate_limiter, EFS_Input_Validator $input_validator, EFS_Audit_Logger $audit_logger ) {
		parent::__construct( $rate_limiter, $input_validator, $audit_logger );
		$this->template_controller = $template_controller;
	}

	/**
	 * Registers AJAX hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_b2e_extract_template', array( $this, 'extract_template' ) );
		add_action( 'wp_ajax_b2e_get_extraction_progress', array( $this, 'get_extraction_progress' ) );
		add_action( 'wp_ajax_b2e_save_template', array( $this, 'save_template' ) );
		add_action( 'wp_ajax_b2e_get_saved_templates', array( $this, 'get_saved_templates' ) );
		add_action( 'wp_ajax_b2e_delete_template', array( $this, 'delete_template' ) );
	}

	/**
	 * Initiates template extraction.
	 */
	public function extract_template() {
		$this->verify_nonce();

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You are not authorized to perform template extraction.', 'etch-fusion-suite' ), 403 );
		}

		$this->limit_rate( 'template_extract', 10, MINUTE_IN_SECONDS );

		$source      = isset( $_POST['source'] ) ? wp_unslash( $_POST['source'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$source_type = isset( $_POST['source_type'] ) ? sanitize_key( wp_unslash( $_POST['source_type'] ) ) : 'url'; // phpcs:ignore WordPress.Security.NonceVerification

		$result = $this->template_controller->extract_template( $source, $source_type );

		if ( is_wp_error( $result ) ) {
			$this->audit_logger->log_event( 'template_extract_failed', array( 'message' => $result->get_error_message() ) );
			wp_send_json_error( $result->get_error_message(), 400 );
		}

		$this->audit_logger->log_event( 'template_extract_success', array( 'source_type' => $source_type ) );

		wp_send_json_success( $result );
	}

	/**
	 * Returns extraction progress.
	 */
	public function get_extraction_progress() {
		$this->verify_nonce();
		$this->limit_rate( 'template_progress', 60, MINUTE_IN_SECONDS );

		$result = $this->template_controller->get_extraction_progress();
		wp_send_json_success( $result );
	}

	/**
	 * Saves template.
	 */
	public function save_template() {
		$this->verify_nonce();

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You are not authorized to save templates.', 'etch-fusion-suite' ), 403 );
		}
		$this->limit_rate( 'template_save', 20, MINUTE_IN_SECONDS );

		$template_data = isset( $_POST['template_data'] ) ? json_decode( wp_unslash( $_POST['template_data'] ), true ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
		$template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		$result = $this->template_controller->save_template( $template_data, $template_name );

		if ( is_wp_error( $result ) ) {
			$this->audit_logger->log_event( 'template_save_failed', array( 'message' => $result->get_error_message() ) );
			wp_send_json_error( $result->get_error_message(), 400 );
		}

		$this->audit_logger->log_event( 'template_save_success', array( 'template_id' => $result ) );

		wp_send_json_success( array( 'template_id' => $result ) );
	}

	/**
	 * Retrieves saved templates.
	 */
	public function get_saved_templates() {
		$this->verify_nonce();

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You are not authorized to view templates.', 'etch-fusion-suite' ), 403 );
		}
		$this->limit_rate( 'template_saved_list', 60, MINUTE_IN_SECONDS );

		$result = $this->template_controller->get_saved_templates();

		wp_send_json_success( $result );
	}

	/**
	 * Deletes template.
	 */
	public function delete_template() {
		$this->verify_nonce();

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You are not authorized to delete templates.', 'etch-fusion-suite' ), 403 );
		}

		$this->limit_rate( 'template_delete', 10, MINUTE_IN_SECONDS );

		$template_id = isset( $_POST['template_id'] ) ? (int) $_POST['template_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification

		$result = $this->template_controller->delete_template( $template_id );

		if ( is_wp_error( $result ) ) {
			$this->audit_logger->log_event( 'template_delete_failed', array( 'template_id' => $template_id, 'message' => $result->get_error_message() ) );
			wp_send_json_error( $result->get_error_message(), 400 );
		}

		$this->audit_logger->log_event( 'template_delete_success', array( 'template_id' => $template_id ) );

		wp_send_json_success( array( 'deleted' => true ) );
	}
}

class_alias( __NAMESPACE__ . '\\EFS_Template_Ajax_Handler', __NAMESPACE__ . '\\B2E_Template_Ajax_Handler' );
