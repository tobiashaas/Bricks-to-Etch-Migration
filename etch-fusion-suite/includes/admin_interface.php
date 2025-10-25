<?php
/**
 * Admin Interface for Etch Fusion Suite
 *
 * Handles the WordPress admin menu and dashboard
 */

namespace Bricks2Etch\Admin;

use Bricks2Etch\Controllers\EFS_Dashboard_Controller;
use Bricks2Etch\Controllers\EFS_Settings_Controller;
use Bricks2Etch\Controllers\EFS_Migration_Controller;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Admin_Interface {
	private $dashboard_controller;
	private $settings_controller;
	private $migration_controller;

	public function __construct(
		EFS_Dashboard_Controller $dashboard_controller,
		EFS_Settings_Controller $settings_controller,
		EFS_Migration_Controller $migration_controller,
		$register_menu = true
	) {
		$this->dashboard_controller = $dashboard_controller;
		$this->settings_controller  = $settings_controller;
		$this->migration_controller = $migration_controller;

		if ( $register_menu ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		add_action( 'wp_ajax_b2e_start_migration', array( $this, 'start_migration' ) );
		add_action( 'wp_ajax_b2e_get_migration_progress', array( $this, 'get_progress' ) );
		add_action( 'wp_ajax_b2e_migrate_batch', array( $this, 'process_batch' ) );
		add_action( 'wp_ajax_b2e_cancel_migration', array( $this, 'cancel_migration' ) );
		add_action( 'wp_ajax_b2e_generate_report', array( $this, 'generate_report' ) );
		add_action( 'wp_ajax_b2e_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_b2e_test_connection', array( $this, 'test_connection' ) );
		add_action( 'wp_ajax_b2e_generate_migration_key', array( $this, 'generate_migration_key' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Etch Fusion', 'etch-fusion-suite' ),
			__( 'Etch Fusion', 'etch-fusion-suite' ),
			'manage_options',
			'etch-fusion-suite',
			array( $this, 'render_dashboard' ),
			'dashicons-migrate',
			30
		);
	}

	public function render_dashboard() {
		$this->dashboard_controller->render();
	}

	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'etch-fusion-suite' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'b2e-admin-css',
			EFS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			EFS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'b2e-admin-main',
			EFS_PLUGIN_URL . 'assets/js/admin/main.js',
			array(),
			EFS_PLUGIN_VERSION,
			true
		);

		wp_script_add_data( 'b2e-admin-main', 'type', 'module' );

		$context            = $this->dashboard_controller->get_dashboard_context();
		$context['ajaxUrl'] = admin_url( 'admin-ajax.php' );

		wp_localize_script( 'b2e-admin-main', 'efsData', $context );
	}

	public function start_migration() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->migration_controller->start_migration( $_POST );
		$this->send_response( $result );
	}

	public function get_progress() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->migration_controller->get_progress( $_POST );
		$this->send_response( $result );
	}

	public function process_batch() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->migration_controller->process_batch( $_POST );
		$this->send_response( $result );
	}

	public function cancel_migration() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->migration_controller->cancel_migration( $_POST );
		$this->send_response( $result );
	}

	public function generate_report() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->migration_controller->generate_report();
		$this->send_response( $result );
	}

	public function save_settings() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->settings_controller->save_settings( $_POST );
		$this->send_response( $result );
	}

	public function test_connection() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->settings_controller->test_connection( $_POST );
		$this->send_response( $result );
	}

	public function generate_migration_key() {
		if ( ! $this->verify_request() ) {
			return;
		}

		$result = $this->settings_controller->generate_migration_key( $_POST );
		$this->send_response( $result );
	}

	private function verify_request() {
		if ( ! check_ajax_referer( 'b2e_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Invalid request.', 'etch-fusion-suite' ) );
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'etch-fusion-suite' ) );
			return false;
		}

		return true;
	}

	private function send_response( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
			return;
		}

		wp_send_json_success( $result );
	}
}
