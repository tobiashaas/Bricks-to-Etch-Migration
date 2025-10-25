<?php
namespace Bricks2Etch\Services;

use Bricks2Etch\Api\EFS_API_Client;
use Bricks2Etch\Core\EFS_Error_Handler;
use Bricks2Etch\Core\EFS_Plugin_Detector;
use Bricks2Etch\Migrators\EFS_Migrator_Registry;
use Bricks2Etch\Migrators\Interfaces\Migrator_Interface;
use Bricks2Etch\Parsers\EFS_Content_Parser;
use Bricks2Etch\Repositories\Interfaces\Migration_Repository_Interface;

class EFS_Migration_Service {

	/** @var EFS_Error_Handler */
	private $error_handler;

	/** @var EFS_Plugin_Detector */
	private $plugin_detector;

	/** @var EFS_Content_Parser */
	private $content_parser;

	/** @var EFS_CSS_Service */
	private $css_service;

	/** @var EFS_Media_Service */
	private $media_service;

	/** @var EFS_Content_Service */
	private $content_service;

	/** @var EFS_API_Client */
	private $api_client;

	/** @var EFS_Migrator_Registry */
	private $migrator_registry;

	/** @var Migration_Repository_Interface */
	private $migration_repository;

	/**
	 * @param EFS_Error_Handler                $error_handler
	 * @param EFS_Plugin_Detector              $plugin_detector
	 * @param EFS_Content_Parser               $content_parser
	 * @param EFS_CSS_Service                  $css_service
	 * @param EFS_Media_Service                $media_service
	 * @param EFS_Content_Service              $content_service
	 * @param EFS_API_Client                   $api_client
	 * @param EFS_Migrator_Registry            $migrator_registry
	 * @param Migration_Repository_Interface   $migration_repository
	 */
	public function __construct(
		EFS_Error_Handler $error_handler,
		EFS_Plugin_Detector $plugin_detector,
		EFS_Content_Parser $content_parser,
		EFS_CSS_Service $css_service,
		EFS_Media_Service $media_service,
		EFS_Content_Service $content_service,
		EFS_API_Client $api_client,
		EFS_Migrator_Registry $migrator_registry,
		Migration_Repository_Interface $migration_repository
	) {
		$this->error_handler        = $error_handler;
		$this->plugin_detector      = $plugin_detector;
		$this->content_parser       = $content_parser;
		$this->css_service          = $css_service;
		$this->media_service        = $media_service;
		$this->content_service      = $content_service;
		$this->api_client           = $api_client;
		$this->migrator_registry    = $migrator_registry;
		$this->migration_repository = $migration_repository;
	}

	/**
	 * Start migration workflow.
	 *
	 * @param string $target_url
	 * @param string $api_key
	 *
	 * @return array|\WP_Error
	 */
	public function start_migration( $target_url, $api_key ) {
		try {
			$this->init_progress();

			$this->update_progress( 'validation', 10, __( 'Validating migration requirements...', 'etch-fusion-suite' ) );
			$validation_result = $this->validate_target_site_requirements();

			if ( ! $validation_result['valid'] ) {
				$error_message = 'Migration validation failed: ' . implode( ', ', $validation_result['errors'] );
				$this->error_handler->log_error(
					'E103',
					array(
						'validation_errors' => $validation_result['errors'],
						'action'            => 'Target site validation failed',
					)
				);
				$this->update_progress( 'error', 0, $error_message );

				return new \WP_Error( 'validation_failed', $error_message );
			}

			$this->update_progress( 'analyzing', 20, __( 'Analyzing content...', 'etch-fusion-suite' ) );
			$analysis = $this->content_service->analyze_content();
			$this->update_progress(
				'analyzing',
				25,
				sprintf(
					__( 'Found %1$d Bricks posts, %2$d Gutenberg posts, %3$d media files (%4$d total)', 'etch-fusion-suite' ),
					$analysis['bricks_posts'],
					$analysis['gutenberg_posts'],
					$analysis['media'],
					$analysis['total']
				)
			);

			$migrator_result = $this->execute_migrators( $target_url, $api_key );
			if ( is_wp_error( $migrator_result ) ) {
				return $migrator_result;
			}

			$this->update_progress( 'media', 60, __( 'Migrating media files...', 'etch-fusion-suite' ) );
			$media_result = $this->media_service->migrate_media( $target_url, $api_key );
			if ( is_wp_error( $media_result ) ) {
				return $media_result;
			}

			$this->update_progress( 'css_classes', 70, __( 'Converting CSS classes...', 'etch-fusion-suite' ) );
			$css_result = $this->css_service->migrate_css_classes( $target_url, $api_key );
			if ( is_wp_error( $css_result ) || ( is_array( $css_result ) && isset( $css_result['success'] ) && ! $css_result['success'] ) ) {
				return is_wp_error( $css_result ) ? $css_result : new \WP_Error( 'css_migration_failed', $css_result['message'] );
			}

			$this->update_progress( 'posts', 80, __( 'Migrating posts and content...', 'etch-fusion-suite' ) );
			$posts_result = $this->content_service->migrate_posts( $target_url, $api_key, $this->api_client );
			if ( is_wp_error( $posts_result ) ) {
				return $posts_result;
			}

			$this->update_progress( 'finalization', 95, __( 'Finalizing migration...', 'etch-fusion-suite' ) );
			$finalization_result = $this->finalize_migration( $posts_result );
			if ( is_wp_error( $finalization_result ) ) {
				return $finalization_result;
			}

			$this->update_progress( 'completed', 100, __( 'Migration completed successfully!', 'etch-fusion-suite' ) );

			$migration_stats                   = $this->migration_repository->get_stats();
			$migration_stats['last_migration'] = current_time( 'mysql' );
			$migration_stats['status']         = 'completed';
			$this->migration_repository->save_stats( $migration_stats );

			return array(
				'progress'  => $this->get_progress_data(),
				'steps'     => $this->get_steps_state(),
				'completed' => true,
				'message'   => __( 'Migration completed successfully!', 'etch-fusion-suite' ),
				'details'   => array(
					'media' => $media_result,
					'css'   => $css_result,
					'posts' => $posts_result,
				),
			);
		} catch ( \Exception $exception ) {
			$error_message = sprintf(
				'Migration process failed: %s (File: %s, Line: %d)',
				$exception->getMessage(),
				basename( $exception->getFile() ),
				$exception->getLine()
			);

			$this->error_handler->log_error(
				'E201',
				array(
					'message' => $exception->getMessage(),
					'file'    => $exception->getFile(),
					'line'    => $exception->getLine(),
					'trace'   => $exception->getTraceAsString(),
					'action'  => 'Migration process failed',
				)
			);

			$this->update_progress( 'error', 0, $error_message );

			return new \WP_Error( 'migration_failed', $error_message );
		}
	}

	/**
	 * Retrieve current progress.
	 *
	 * @param string $migration_id
	 *
	 * @return array
	 */
	public function get_progress( $migration_id = '' ) {
		return array(
			'progress'  => $this->get_progress_data(),
			'steps'     => $this->get_steps_state(),
			'completed' => $this->is_migration_complete(),
		);
	}

	/**
	 * Process batch placeholder for async migrations.
	 *
	 * @param string $migration_id
	 * @param array  $batch
	 *
	 * @return array
	 */
	public function process_batch( $migration_id, $batch ) {
		return $this->get_progress( $migration_id );
	}

	/**
	 * Cancel migration and reset progress.
	 *
	 * @param string $migration_id
	 *
	 * @return array
	 */
	public function cancel_migration( $migration_id = '' ) {
		$this->migration_repository->delete_progress();
		$this->migration_repository->delete_steps();

		$this->error_handler->log_warning(
			'W900',
			array(
				'action' => 'Migration cancelled by user',
			)
		);

		return array(
			'message'   => __( 'Migration cancelled.', 'etch-fusion-suite' ),
			'progress'  => $this->get_progress_data(),
			'steps'     => $this->get_steps_state(),
			'completed' => false,
		);
	}

	/**
	 * Generate migration report.
	 *
	 * @return array
	 */
	public function generate_report() {
		$progress = $this->get_progress_data();
		$stats    = $this->migration_repository->get_stats();
		$steps    = $this->get_steps_state();

		return array(
			'progress' => $progress,
			'stats'    => $stats,
			'steps'    => $steps,
		);
	}

	/**
	 * Migrate a single post (used for batch processing).
	 *
	 * @param \WP_Post $post
	 *
	 * @return array|\WP_Error
	 */
	public function migrate_single_post( $post ) {
		return $this->content_service->convert_bricks_to_gutenberg( $post->ID, $this->api_client );
	}

	/**
	 * Validate target site requirements.
	 *
	 * @return array
	 */
	public function validate_target_site_requirements() {
		$result = $this->plugin_detector->validate_migration_requirements();

		if ( ! is_array( $result ) ) {
			return array(
				'valid'  => false,
				'errors' => array( __( 'Unknown validation response.', 'etch-fusion-suite' ) ),
			);
		}

		return $result;
	}

	/**
	 * Start import process on target site.
	 *
	 * @param string $source_domain
	 * @param string $token
	 *
	 * @return mixed
	 */
	public function start_import_process( $source_domain, $token ) {
		if ( method_exists( $this->api_client, 'start_import_process' ) ) {
			return $this->api_client->start_import_process( $source_domain, $token );
		}

		return new \WP_Error( 'not_supported', __( 'Start import process is not available on the current API client version.', 'etch-fusion-suite' ) );
	}

	/**
	 * Initialize progress state.
	 */
	public function init_progress() {
		$progress = array(
			'status'       => 'running',
			'current_step' => 'validation',
			'percentage'   => 0,
			'started_at'   => current_time( 'mysql' ),
			'completed_at' => null,
		);

		$this->migration_repository->save_progress( $progress );
		$this->set_steps_state( $this->initialize_steps() );
	}

	/**
	 * Update progress data.
	 *
	 * @param string $step
	 * @param int    $percentage
	 * @param string $message
	 */
	public function update_progress( $step, $percentage, $message ) {
		$progress                 = $this->migration_repository->get_progress();
		$progress['current_step'] = $step;
		$progress['percentage']   = $percentage;
		$progress['message']      = $message;

		if ( 'completed' === $step ) {
			$progress['status']       = 'completed';
			$progress['completed_at'] = current_time( 'mysql' );
		} elseif ( 'error' === $step ) {
			$progress['status']       = 'error';
			$progress['completed_at'] = current_time( 'mysql' );
		}

		$this->migration_repository->save_progress( $progress );

		$steps = $this->get_steps_state();
		if ( isset( $steps[ $step ] ) ) {
			$steps[ $step ]['status']     = ( 'error' === $step ) ? 'error' : 'completed';
			$steps[ $step ]['updated_at'] = current_time( 'mysql' );
			$this->set_steps_state( $steps );
		}
	}

	/**
	 * Get raw progress data.
	 *
	 * @return array
	 */
	public function get_progress_data() {
		$progress = $this->migration_repository->get_progress();

		if ( empty( $progress ) ) {
			return array(
				'status'       => 'idle',
				'current_step' => '',
				'percentage'   => 0,
			);
		}

		return $progress;
	}

	/**
	 * Retrieve step state map.
	 *
	 * @return array
	 */
	public function get_steps_state() {
		$steps = $this->migration_repository->get_steps();

		return is_array( $steps ) ? $steps : array();
	}

	/**
	 * Persist step state map.
	 *
	 * @param array $steps
	 */
	public function set_steps_state( array $steps ) {
		$this->migration_repository->save_steps( $steps );
	}

	/**
	 * Check if migration is complete.
	 *
	 * @return bool
	 */
	public function is_migration_complete() {
		$progress = $this->get_progress_data();

		return isset( $progress['status'] ) && 'completed' === $progress['status'];
	}

	/**
	 * Initialize default steps.
	 *
	 * @return array
	 */
	private function initialize_steps() {
		$steps = array(
			'validation' => array( 'status' => 'pending' ),
			'analyzing'  => array( 'status' => 'pending' ),
		);

		$supported = $this->migrator_registry->get_supported();
		foreach ( $supported as $migrator ) {
			$steps[ $this->get_migrator_step_key( $migrator ) ] = array( 'status' => 'pending' );
		}

		$steps += array(
			'media'        => array( 'status' => 'pending' ),
			'css_classes'  => array( 'status' => 'pending' ),
			'posts'        => array( 'status' => 'pending' ),
			'finalization' => array( 'status' => 'pending' ),
		);

		$timestamp = current_time( 'mysql' );
		foreach ( $steps as &$step ) {
			$step['updated_at'] = $timestamp;
		}

		return $steps;
	}

	/**
	 * Finalize migration.
	 *
	 * @param array $results
	 *
	 * @return true|\WP_Error
	 */
	private function finalize_migration( array $results ) {
		$this->error_handler->log_error(
			'I010',
			array(
				'action'  => 'Finalizing migration',
				'results' => $results,
			)
		);

		return true;
	}

	/**
	 * Execute all registered migrators via registry.
	 */
	private function execute_migrators( $target_url, $api_key ) {
		$supported_migrators = $this->migrator_registry->get_supported();

		if ( empty( $supported_migrators ) ) {
			$this->error_handler->log_warning(
				'W002',
				array(
					'message' => 'No migrators available to execute.',
					'action'  => 'Migrator execution skipped',
				)
			);

			return true;
		}

		$count           = count( $supported_migrators );
		$base_percentage = 30;
		$end_percentage  = 60;
		$range           = max( 1, $end_percentage - $base_percentage );
		$increment       = $range / $count;
		$index           = 0;

		foreach ( $supported_migrators as $migrator ) {
			$progress = (int) floor( $base_percentage + ( $increment * $index ) );
			$step_key = $this->get_migrator_step_key( $migrator );
			$this->update_progress( $step_key, $progress, sprintf( __( 'Migrating %s...', 'etch-fusion-suite' ), $migrator->get_name() ) );

			$validation = $migrator->validate();
			if ( isset( $validation['valid'] ) && ! $validation['valid'] ) {
				$this->error_handler->log_warning(
					'W002',
					array(
						'migrator' => $migrator->get_type(),
						'errors'   => $validation['errors'] ?? array(),
						'action'   => 'Migrator validation failed',
					)
				);
				++$index;
				continue;
			}

			$result = $migrator->migrate( $target_url, $api_key );
			if ( is_wp_error( $result ) ) {
				$this->error_handler->log_error(
					'E201',
					array(
						'migrator' => $migrator->get_type(),
						'error'    => $result->get_error_message(),
						'action'   => 'Migrator execution failed',
					)
				);

				return $result;
			}

			++$index;
		}

		return true;
	}

	/**
	 * Derive progress step key from migrator type.
	 */
	private function get_migrator_step_key( Migrator_Interface $migrator ) {
		$type = $migrator->get_type();

		$mapping = array(
			'cpt'           => 'cpts',
			'acf'           => 'acf_field_groups',
			'metabox'       => 'metabox_configs',
			'custom_fields' => 'custom_fields',
		);

		return $mapping[ $type ] ?? $type;
	}
}
