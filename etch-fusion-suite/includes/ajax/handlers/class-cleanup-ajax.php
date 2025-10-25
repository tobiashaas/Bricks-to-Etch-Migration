<?php
namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\EFS_Base_Ajax_Handler;
use Bricks2Etch\Api\EFS_API_Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Cleanup_Ajax_Handler extends EFS_Base_Ajax_Handler {

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
	 * @param \Bricks2Etch\Security\EFS_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
	 * @param \Bricks2Etch\Security\EFS_Input_Validator|null $input_validator Input validator instance (optional).
	 * @param \Bricks2Etch\Security\EFS_Audit_Logger|null $audit_logger Audit logger instance (optional).
	 */
	public function __construct( $api_client = null, $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
		$this->api_client = $api_client;
		parent::__construct( $rate_limiter, $input_validator, $audit_logger );
	}

	protected function register_hooks() {
		add_action( 'wp_ajax_efs_cleanup_etch', array( $this, 'cleanup_etch' ) );
	}

	public function cleanup_etch() {
		// Check rate limit (5 requests per minute - very sensitive operation)
		if ( ! $this->check_rate_limit( 'cleanup_etch', 5, 60 ) ) {
			return;
		}

		if ( ! $this->verify_request() ) {
			return;
		}

		// Get and validate parameters
		try {
			$validated = $this->validate_input(
				array(
					'target_url' => $this->get_post( 'target_url', '' ),
					'api_key'    => $this->get_post( 'api_key', '' ),
					'confirm'    => $this->get_post( 'confirm', false ),
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
					'confirm'    => array(
						'type'     => 'text',
						'required' => false,
					),
				)
			);
		} catch ( \Exception $e ) {
			return; // Error already sent by validate_input
		}

		$target_url = $validated['target_url'];
		$api_key    = $validated['api_key'];
		$confirm    = $validated['confirm'] ?? false;

		// Require confirmation for destructive operation
		if ( $confirm !== 'true' && $confirm !== true ) {
			wp_send_json_error( __( 'Cleanup requires confirmation. Please confirm this destructive operation.', 'etch-fusion-suite' ) );
			return;
		}

		$client   = new EFS_API_Client();
		$commands = array(
			'wp post delete $(wp post list --post_type=post,page,attachment --format=ids) --force',
			'wp option delete etch_styles',
			'wp cache flush',
			'wp transient delete --all',
		);

		// Log cleanup attempt (critical severity - destructive operation)
		$this->log_security_event(
			'cleanup_executed',
			'Cleanup operation executed',
			array(
				'target_url' => $target_url,
			),
			'critical'
		);

		wp_send_json_success(
			array(
				'message'  => __( 'Cleanup commands generated.', 'etch-fusion-suite' ),
				'commands' => $commands,
			)
		);
	}
}
