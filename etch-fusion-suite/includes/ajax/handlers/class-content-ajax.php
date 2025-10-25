<?php
/**
 * Content AJAX Handler
 *
 * Handles content migration AJAX requests
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

namespace Bricks2Etch\Ajax\Handlers;

use Bricks2Etch\Ajax\EFS_Base_Ajax_Handler;
use Bricks2Etch\Core\EFS_Migration_Manager;
use Bricks2Etch\Parsers\EFS_Content_Parser;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Content_Ajax_Handler extends EFS_Base_Ajax_Handler {

	/**
	 * Migration service instance
	 *
	 * @var mixed
	 */
	private $migration_service;

	/**
	 * Constructor
	 *
	 * @param mixed $migration_service Migration service instance.
	 * @param \Bricks2Etch\Security\EFS_Rate_Limiter|null $rate_limiter Rate limiter instance (optional).
	 * @param \Bricks2Etch\Security\EFS_Input_Validator|null $input_validator Input validator instance (optional).
	 * @param \Bricks2Etch\Security\EFS_Audit_Logger|null $audit_logger Audit logger instance (optional).
	 */
	public function __construct( $migration_service = null, $rate_limiter = null, $input_validator = null, $audit_logger = null ) {
		$this->migration_service = $migration_service;
		parent::__construct( $rate_limiter, $input_validator, $audit_logger );
	}

	/**
	 * Register WordPress hooks
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_efs_migrate_batch', array( $this, 'migrate_batch' ) );
		add_action( 'wp_ajax_efs_get_bricks_posts', array( $this, 'get_bricks_posts' ) );
	}

	/**
	 * AJAX handler for batch migration (one post at a time)
	 */
	public function migrate_batch() {
		// Check rate limit (30 requests per minute)
		if ( ! $this->check_rate_limit( 'migrate_batch', 30, 60 ) ) {
			return;
		}

		// Verify nonce
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		// Get and validate parameters
		try {
			$validated = $this->validate_input(
				array(
					'post_id'    => $this->get_post( 'post_id', 0 ),
					'target_url' => $this->get_post( 'target_url', '' ),
					'api_key'    => $this->get_post( 'api_key', '' ),
				),
				array(
					'post_id'    => array(
						'type'     => 'integer',
						'required' => true,
						'min'      => 1,
					),
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
			return; // Error already sent by validate_input
		}

		$post_id    = $validated['post_id'];
		$target_url = $validated['target_url'];
		$api_key    = $validated['api_key'];

		// Get the post
		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( 'Post not found' );
			return;
		}

		// Check if it's media/attachment
		if ( $post->post_type === 'attachment' ) {
			// Media migration handled separately
			wp_send_json_success(
				array(
					'message' => 'Media migration handled separately',
					'skipped' => true,
				)
			);
			return;
		}

		// Migrate this single post
		try {
			// Convert to internal URL
			$internal_url = $this->convert_to_internal_url( $target_url );

			// Save settings temporarily
			update_option(
				'b2e_settings',
				array(
					'target_url' => $internal_url,
					'api_key'    => $api_key,
				),
				false
			);

			$migration_manager = new EFS_Migration_Manager();
			$result            = $migration_manager->migrate_single_post( $post );

			if ( is_wp_error( $result ) ) {
				// Log migration failure
				$this->log_security_event(
					'ajax_action',
					'Batch migration failed: ' . $result->get_error_message(),
					array(
						'post_id' => $post_id,
					)
				);
				wp_send_json_error( $result->get_error_message() );
			} else {
				// Log successful migration
				$this->log_security_event(
					'ajax_action',
					'Post migrated successfully',
					array(
						'post_id'    => $post_id,
						'post_title' => $post->post_title,
					)
				);
				wp_send_json_success(
					array(
						'message'    => 'Post migrated successfully',
						'post_title' => $post->post_title,
					)
				);
			}
		} catch ( \Exception $e ) {
			$this->log_security_event(
				'ajax_action',
				'Batch migration exception: ' . $e->getMessage(),
				array(
					'post_id' => $post_id,
				)
			);
			wp_send_json_error( 'Exception: ' . $e->getMessage() );
		}
	}

	/**
	 * AJAX handler to get list of ALL content (Bricks, Gutenberg, Media)
	 */
	public function get_bricks_posts() {
		// Check rate limit (60 requests per minute)
		if ( ! $this->check_rate_limit( 'get_bricks_posts', 60, 60 ) ) {
			return;
		}

		// Verify nonce
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		// Use content_parser to get all content types
		$content_parser = new EFS_Content_Parser();

		$bricks_posts    = $content_parser->get_bricks_posts();
		$gutenberg_posts = $content_parser->get_gutenberg_posts();
		$media           = $content_parser->get_media();

		$posts_data = array();

		// Add Bricks posts
		foreach ( $bricks_posts as $post ) {
			$posts_data[] = array(
				'id'         => $post->ID,
				'title'      => $post->post_title,
				'type'       => $post->post_type,
				'has_bricks' => true,
			);
		}

		// Add Gutenberg posts
		foreach ( $gutenberg_posts as $post ) {
			$posts_data[] = array(
				'id'         => $post->ID,
				'title'      => $post->post_title,
				'type'       => $post->post_type,
				'has_bricks' => false,
			);
		}

		// Add Media
		foreach ( $media as $attachment ) {
			$posts_data[] = array(
				'id'         => $attachment->ID,
				'title'      => $attachment->post_title ?: basename( $attachment->guid ),
				'type'       => 'attachment',
				'has_bricks' => false,
			);
		}

		wp_send_json_success(
			array(
				'posts'           => $posts_data,
				'count'           => count( $posts_data ),
				'bricks_count'    => count( $bricks_posts ),
				'gutenberg_count' => count( $gutenberg_posts ),
				'media_count'     => count( $media ),
			)
		);
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
