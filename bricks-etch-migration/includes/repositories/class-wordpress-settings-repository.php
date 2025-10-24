<?php
/**
 * WordPress Settings Repository
 *
 * WordPress-backed implementation of Settings Repository using Options API.
 *
 * @package Bricks2Etch\Repositories
 * @since 1.0.0
 */

namespace Bricks2Etch\Repositories;

use Bricks2Etch\Repositories\Interfaces\Settings_Repository_Interface;

/**
 * Class B2E_WordPress_Settings_Repository
 *
 * Manages plugin settings, API keys, and migration settings with transient caching.
 */
class B2E_WordPress_Settings_Repository implements Settings_Repository_Interface {

	/**
	 * Cache expiration time in seconds (5 minutes).
	 */
	const CACHE_EXPIRATION = 300;

	/**
	 * Get all plugin settings.
	 *
	 * @return array Plugin settings array.
	 */
	public function get_plugin_settings(): array {
		$cache_key = 'b2e_cache_settings_plugin';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$settings = get_option( 'b2e_settings', array() );
		set_transient( $cache_key, $settings, self::CACHE_EXPIRATION );

		return $settings;
	}

	/**
	 * Save plugin settings.
	 *
	 * @param array $settings Settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_plugin_settings( array $settings ): bool {
		$this->invalidate_cache( 'b2e_cache_settings_plugin' );
		return update_option( 'b2e_settings', $settings );
	}

	/**
	 * Get API key.
	 *
	 * @return string API key or empty string if not set.
	 */
	public function get_api_key(): string {
		$cache_key = 'b2e_cache_settings_api_key';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$api_key = get_option( 'b2e_api_key', '' );
		set_transient( $cache_key, $api_key, self::CACHE_EXPIRATION );

		return $api_key;
	}

	/**
	 * Save API key.
	 *
	 * @param string $key API key to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_api_key( string $key ): bool {
		$this->invalidate_cache( 'b2e_cache_settings_api_key' );
		return update_option( 'b2e_api_key', $key );
	}

	/**
	 * Delete API key.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_api_key(): bool {
		$this->invalidate_cache( 'b2e_cache_settings_api_key' );
		return delete_option( 'b2e_api_key' );
	}

	/**
	 * Get migration settings.
	 *
	 * @return array Migration settings array.
	 */
	public function get_migration_settings(): array {
		$cache_key = 'b2e_cache_settings_migration';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$settings = get_option( 'b2e_migration_settings', array() );
		set_transient( $cache_key, $settings, self::CACHE_EXPIRATION );

		return $settings;
	}

	/**
	 * Save migration settings.
	 *
	 * @param array $settings Migration settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_migration_settings( array $settings ): bool {
		$this->invalidate_cache( 'b2e_cache_settings_migration' );
		return update_option( 'b2e_migration_settings', $settings );
	}

	/**
	 * Clear all settings.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_all_settings(): bool {
		$this->invalidate_cache( 'b2e_cache_settings_plugin' );
		$this->invalidate_cache( 'b2e_cache_settings_api_key' );
		$this->invalidate_cache( 'b2e_cache_settings_migration' );
		$this->invalidate_cache( 'b2e_cache_cors_origins' );
		$this->invalidate_cache( 'b2e_cache_security_settings' );

		$result = true;
		$result = delete_option( 'b2e_settings' ) && $result;
		$result = delete_option( 'b2e_api_key' ) && $result;
		$result = delete_option( 'b2e_migration_settings' ) && $result;
		$result = delete_option( 'b2e_cors_allowed_origins' ) && $result;
		$result = delete_option( 'b2e_security_settings' ) && $result;

		return $result;
	}

	/**
	 * Get CORS allowed origins.
	 *
	 * @return array Array of allowed CORS origin URLs.
	 */
	public function get_cors_allowed_origins(): array {
		$cache_key = 'b2e_cache_cors_origins';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$origins = get_option( 'b2e_cors_allowed_origins', array() );
		set_transient( $cache_key, $origins, self::CACHE_EXPIRATION );

		return $origins;
	}

	/**
	 * Save CORS allowed origins.
	 *
	 * @param array $origins Array of allowed origin URLs.
	 * @return bool True on success, false on failure.
	 */
	public function save_cors_allowed_origins( array $origins ): bool {
		// Validate array
		if ( ! is_array( $origins ) ) {
			return false;
		}

		$this->invalidate_cache( 'b2e_cache_cors_origins' );
		return update_option( 'b2e_cors_allowed_origins', $origins );
	}

	/**
	 * Get security settings.
	 *
	 * Returns security-related settings like rate limits and environment config.
	 *
	 * @return array Security settings array.
	 */
	public function get_security_settings(): array {
		$cache_key = 'b2e_cache_security_settings';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$defaults = array(
			'rate_limit_enabled'    => true,
			'rate_limit_requests'   => 60,
			'rate_limit_window'     => 60,
			'audit_logging_enabled' => true,
			'require_https'         => false,
		);

		$settings = get_option( 'b2e_security_settings', $defaults );
		$settings = wp_parse_args( $settings, $defaults );

		set_transient( $cache_key, $settings, self::CACHE_EXPIRATION );

		return $settings;
	}

	/**
	 * Save security settings.
	 *
	 * @param array $settings Security settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_security_settings( array $settings ): bool {
		// Validate array
		if ( ! is_array( $settings ) ) {
			return false;
		}

		$this->invalidate_cache( 'b2e_cache_security_settings' );
		return update_option( 'b2e_security_settings', $settings );
	}

	/**
	 * Invalidate a specific cache key.
	 *
	 * @param string $cache_key Cache key to invalidate.
	 */
	private function invalidate_cache( string $cache_key ): void {
		delete_transient( $cache_key );
	}
}

// Backward compatibility class alias
class_alias( 'Bricks2Etch\Repositories\B2E_WordPress_Settings_Repository', 'B2E_WordPress_Settings_Repository' );
