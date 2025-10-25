<?php
/**
 * API Endpoints for Etch Fusion Suite
 *
 * Handles REST API endpoints for communication between source and target sites
 */

namespace Bricks2Etch\Api;

use Bricks2Etch\Core\EFS_Migration_Token_Manager;
use Bricks2Etch\Core\EFS_Migration_Manager;
use Bricks2Etch\Migrators\EFS_Migrator_Registry;
use Bricks2Etch\Migrators\Interfaces\Migrator_Interface;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_API_Endpoints {
	/**
	 * @var \Bricks2Etch\Container\EFS_Service_Container|null
	 */
	private static $container;

	/**
	 * @var \Bricks2Etch\Repositories\Interfaces\Settings_Repository_Interface|null
	 */
	private static $settings_repository;

	/**
	 * @var \Bricks2Etch\Repositories\Interfaces\Migration_Repository_Interface|null
	 */
	private static $migration_repository;

	/**
	 * @var \Bricks2Etch\Repositories\Interfaces\Style_Repository_Interface|null
	 */
	private static $style_repository;

	/**
	 * @var \Bricks2Etch\Security\EFS_Rate_Limiter|null
	 */
	private static $rate_limiter;

	/**
	 * @var \Bricks2Etch\Security\EFS_Input_Validator|null
	 */
	private static $input_validator;

	/**
	 * @var \Bricks2Etch\Security\EFS_Audit_Logger|null
	 */
	private static $audit_logger;

	/**
	 * @var \Bricks2Etch\Security\EFS_CORS_Manager|null
	 */
	private static $cors_manager;

	/**
	 * @var EFS_Migrator_Registry|null
	 */
	private static $migrator_registry;

	/**
	 * Cached template controller instance.
	 *
	 * @var \Bricks2Etch\Controllers\EFS_Template_Controller|null
	 */
	private static $template_controller;

	/**
	 * Initialize the API endpoints
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );

		// Add global CORS enforcement filter
		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'enforce_cors_globally' ), 10, 3 );
	}

	/**
	 * Handle template extraction via REST.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function extract_template_rest( $request ) {
		$rate = self::enforce_template_rate_limit( 'extract', 15 );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$template_controller = self::resolve( 'template_controller' );
		if ( ! $template_controller ) {
			return new \WP_Error( 'template_service_unavailable', __( 'Template extractor is not available.', 'etch-fusion-suite' ), array( 'status' => 500 ) );
		}

		$params = self::validate_request_data(
			$request->get_json_params(),
			array(
				'source'      => array( 'type' => 'text', 'required' => true, 'max_length' => 2048 ),
				'source_type' => array( 'type' => 'text', 'required' => true, 'max_length' => 10 ),
			)
		);

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$source      = $params['source'];
		$source_type = $params['source_type'];

		$result = $template_controller->extract_template( $source, $source_type );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Provide saved templates listing via REST.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_saved_templates_rest( $request ) {
		$rate = self::enforce_template_rate_limit( 'list', 30 );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$template_controller = self::resolve( 'template_controller' );
		if ( ! $template_controller ) {
			return new \WP_Error( 'template_service_unavailable', __( 'Template controller not available.', 'etch-fusion-suite' ), array( 'status' => 500 ) );
		}

		return new \WP_REST_Response( $template_controller->get_saved_templates(), 200 );
	}

	/**
	 * Preview template content via REST.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function preview_template_rest( $request ) {
		$rate = self::enforce_template_rate_limit( 'preview', 25 );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$template_controller = self::resolve( 'template_controller' );
		if ( ! $template_controller ) {
			return new \WP_Error( 'template_service_unavailable', __( 'Template controller not available.', 'etch-fusion-suite' ), array( 'status' => 500 ) );
		}

		$template_id = (int) $request->get_param( 'id' );
		if ( $template_id <= 0 ) {
			return new \WP_Error( 'invalid_template_id', __( 'Template ID must be a positive integer.', 'etch-fusion-suite' ), array( 'status' => 400 ) );
		}

		$result = $template_controller->preview_template( $template_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * Delete a saved template via REST.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function delete_template_rest( $request ) {
		$rate = self::enforce_template_rate_limit( 'delete', 15 );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$template_controller = self::resolve( 'template_controller' );
		if ( ! $template_controller ) {
			return new \WP_Error( 'template_service_unavailable', __( 'Template controller not available.', 'etch-fusion-suite' ), array( 'status' => 500 ) );
		}

		$template_id = (int) $request->get_param( 'id' );
		if ( $template_id <= 0 ) {
			return new \WP_Error( 'invalid_template_id', __( 'Template ID must be provided.', 'etch-fusion-suite' ), array( 'status' => 400 ) );
		}

		$result = $template_controller->delete_template( $template_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( array( 'deleted' => true ), 200 );
	}

	/**
	 * Import template payload via REST.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function import_template_rest( $request ) {
		$rate = self::enforce_template_rate_limit( 'import', 10 );
		if ( is_wp_error( $rate ) ) {
			return $rate;
		}

		$template_controller = self::resolve( 'template_controller' );
		if ( ! $template_controller ) {
			return new \WP_Error( 'template_service_unavailable', __( 'Template controller not available.', 'etch-fusion-suite' ), array( 'status' => 500 ) );
		}

		$params = self::validate_request_data(
			$request->get_json_params(),
			array(
				'payload' => array( 'type' => 'array', 'required' => true ),
				'name'    => array( 'type' => 'text', 'required' => false, 'max_length' => 255 ),
			)
		);

		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$payload = $params['payload'];
		$name    = isset( $params['name'] ) ? $params['name'] : null;

		$result = $template_controller->import_template( $payload, $name );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( array( 'id' => $result ), 201 );
	}

	/**
	 * Set the service container instance.
	 *
	 * @param \Bricks2Etch\Container\EFS_Service_Container $container
	 */
	public static function set_container( $container ) {
		self::$container = $container;

		// Resolve repositories from container
		if ( $container->has( 'settings_repository' ) ) {
			self::$settings_repository = $container->get( 'settings_repository' );
		}
		if ( $container->has( 'migration_repository' ) ) {
			self::$migration_repository = $container->get( 'migration_repository' );
		}
		if ( $container->has( 'style_repository' ) ) {
			self::$style_repository = $container->get( 'style_repository' );
		}

		// Resolve security services from container
		if ( $container->has( 'rate_limiter' ) ) {
			self::$rate_limiter = $container->get( 'rate_limiter' );
		}
		if ( $container->has( 'input_validator' ) ) {
			self::$input_validator = $container->get( 'input_validator' );
		}
		if ( $container->has( 'audit_logger' ) ) {
			self::$audit_logger = $container->get( 'audit_logger' );
		}
		if ( $container->has( 'cors_manager' ) ) {
			self::$cors_manager = $container->get( 'cors_manager' );
		}
		if ( $container->has( 'migrator_registry' ) ) {
			self::$migrator_registry = $container->get( 'migrator_registry' );
		}
		if ( $container->has( 'template_controller' ) ) {
			self::$template_controller = $container->get( 'template_controller' );
		}
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	private static function resolve( $id ) {
		if ( 'template_controller' === $id && self::$template_controller ) {
			return self::$template_controller;
		}
		if ( self::$container && self::$container->has( $id ) ) {
			return self::$container->get( $id );
		}

		if ( class_exists( $id ) ) {
			return new $id();
		}

		return null;
	}

	/**
	 * Check CORS origin for REST API endpoint
	 *
	 * @return \WP_Error|bool True if origin allowed, WP_Error if denied.
	 */
	private static function check_cors_origin() {
		if ( ! self::$cors_manager ) {
			return true; // No CORS check if manager not available
		}

		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';

		if ( ! self::$cors_manager->is_origin_allowed( $origin ) ) {
			// Log CORS violation
			if ( self::$audit_logger ) {
				self::$audit_logger->log_security_event(
					'cors_violation',
					'medium',
					'REST API request from unauthorized origin',
					array( 'origin' => $origin )
				);
			}

			return new \WP_Error(
				'cors_violation',
				'Origin not allowed',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Enforce CORS globally for all REST API requests
	 *
	 * This filter runs before any REST API callback and provides a safety net
	 * to ensure no endpoint can bypass CORS validation.
	 *
	 * @param \WP_HTTP_Response|\WP_Error $response Result to send to the client.
	 * @param \WP_REST_Server             $server   Server instance.
	 * @param \WP_REST_Request            $request  Request used to generate the response.
	 * @return \WP_HTTP_Response|\WP_Error Modified response or error.
	 */
	public static function enforce_cors_globally( $response, $server, $request ) {
		// Skip OPTIONS preflight requests (headers already handled by EFS_CORS_Manager::add_cors_headers())
		if ( $request->get_method() === 'OPTIONS' ) {
			return $response;
		}

		// Only check our own endpoints
		$route = $request->get_route();
		if ( strpos( $route, '/efs/v1/' ) !== 0 ) {
			return $response;
		}

		// Perform CORS check
		$cors_check = self::check_cors_origin();
		if ( is_wp_error( $cors_check ) ) {
			// Log the violation with route information
			if ( self::$audit_logger ) {
				$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';
				self::$audit_logger->log_security_event(
					'cors_violation',
					'medium',
					'Global CORS enforcement blocked request',
					array(
						'origin' => $origin,
						'route'  => $route,
						'method' => $request->get_method(),
					)
				);
			}
			return $cors_check;
		}

		return $response;
	}

	/**
	 * Get plugin status
	 */
	public static function get_plugin_status( $request ) {
		// Check rate limit (30 requests per minute)
		$rate_check = self::check_rate_limit( 'get_plugin_status', 30 );
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$plugin_detector = self::resolve( 'plugin_detector' );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'plugins' => array(
					'bricks_active' => $plugin_detector->is_bricks_active(),
					'etch_active'   => $plugin_detector->is_etch_active(),
				),
			),
			200
		);
	}

	/**
	 * Handle key-based migration request
	 */
	public static function handle_key_migration( $request ) {
		// Check CORS origin
		$cors_check = self::check_cors_origin();
		if ( is_wp_error( $cors_check ) ) {
			return $cors_check;
		}

		try {
			$params = $request->get_params();

			// Extract migration parameters
			$domain  = $params['domain'] ?? null;
			$token   = $params['token'] ?? null;
			$expires = isset( $params['expires'] ) ? (int) $params['expires'] : null;

			if ( empty( $domain ) || empty( $token ) || empty( $expires ) ) {
				return new \WP_Error( 'missing_params', 'Missing required migration parameters', array( 'status' => 400 ) );
			}

			// Validate migration token
			$token_manager = self::resolve( 'token_manager' );
			$validation    = $token_manager->validate_migration_token( $token, $domain, $expires );

			if ( is_wp_error( $validation ) ) {
				return new \WP_Error( 'invalid_token', $validation->get_error_message(), array( 'status' => 401 ) );
			}

			// Start import process (this runs on TARGET site)
			$migration_manager = self::resolve( 'migration_manager' );
			$result            = $migration_manager->start_import_process( $domain, $token );

			if ( is_wp_error( $result ) ) {
				return new \WP_Error( 'migration_failed', $result->get_error_message(), array( 'status' => 500 ) );
			}

			// Return success response with migration details
			return new \WP_REST_Response(
				array(
					'success'       => true,
					'message'       => 'Key-based migration started successfully!',
					'migration_url' => $request->get_route(),
					'source_domain' => $domain,
					'target_domain' => home_url(),
					'started_at'    => current_time( 'mysql' ),
				),
				200
			);

		} catch ( \Exception $e ) {
			return new \WP_Error( 'migration_error', 'Migration failed: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')', array( 'status' => 500 ) );
		}
	}

	/**
	 * Generate migration key endpoint
	 * Creates a new migration token and returns the migration key URL
	 */
	public static function generate_migration_key( $request ) {
		try {
			// Create token manager
			$token_manager = self::resolve( 'token_manager' );

			// Generate migration token
			$token_data = $token_manager->generate_migration_token();

			if ( is_wp_error( $token_data ) ) {
				return new \WP_Error( 'token_generation_failed', $token_data->get_error_message(), array( 'status' => 500 ) );
			}

			// Build migration key URL
			$migration_key = add_query_arg(
				array(
					'domain'  => home_url(),
					'token'   => $token_data['token'],
					'expires' => $token_data['expires'],
				),
				home_url()
			);

			// Return response
			return new \WP_REST_Response(
				array(
					'success'       => true,
					'migration_key' => $migration_key,
					'token'         => $token_data['token'],
					'domain'        => home_url(),
					'expires'       => $token_data['expires'],
					'expires_at'    => date( 'Y-m-d H:i:s', $token_data['expires'] ),
					'valid_for'     => '24 hours',
					'generated_at'  => current_time( 'mysql' ),
				),
				200
			);

		} catch ( \Exception $e ) {
			return new \WP_Error( 'generation_error', 'Failed to generate migration key: ' . $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Validate migration token endpoint
	 * Used by the "Validate Key" button to test API connection
	 */
	public static function validate_migration_token( $request ) {
		// Check CORS origin
		$cors_check = self::check_cors_origin();
		if ( is_wp_error( $cors_check ) ) {
			return $cors_check;
		}

		try {
			// Get request body
			$body = $request->get_json_params();

			if ( empty( $body['token'] ) || empty( $body['expires'] ) ) {
				return new \WP_Error( 'missing_parameters', 'Token and expires parameters are required', array( 'status' => 400 ) );
			}

			$token   = sanitize_text_field( $body['token'] );
			$expires = intval( $body['expires'] );

			// Validate token using token manager
			$token_manager     = self::resolve( 'token_manager' );
			$validation_result = $token_manager->validate_migration_token( $token, '', $expires );

			if ( is_wp_error( $validation_result ) ) {
				return new \WP_Error( 'token_validation_failed', $validation_result->get_error_message(), array( 'status' => 401 ) );
			}

			// Token is valid - generate or retrieve API key
			$api_key_data = self::$settings_repository ? self::$settings_repository->get_api_key() : get_option( 'efs_api_key' );

			// If no API key exists, generate one
			if ( empty( $api_key_data ) ) {
				$api_client = self::resolve( 'api_client' );
				$api_key    = $api_client ? $api_client->create_api_key() : wp_generate_password( 32, false );
			} else {
				// Extract key from array if it's an array
				if ( is_array( $api_key_data ) && isset( $api_key_data['key'] ) ) {
					$api_key = $api_key_data['key'];
				} else {
					$api_key = $api_key_data; // Fallback for old format
				}
			}

			// Return success response with API key
			return new \WP_REST_Response(
				array(
					'success'           => true,
					'message'           => 'Token validation successful',
					'api_key'           => $api_key,
					'target_domain'     => home_url(),
					'site_name'         => get_bloginfo( 'name' ),
					'wordpress_version' => get_bloginfo( 'version' ),
					'etch_active'       => class_exists( 'Etch\Plugin' ) || function_exists( 'etch_run_plugin' ),
					'validated_at'      => current_time( 'mysql' ),
				),
				200
			);

		} catch ( \Exception $e ) {
			return new \WP_Error( 'validation_error', 'Token validation failed: ' . $e->getMessage(), array( 'status' => 500 ) );
		}
	}
}

