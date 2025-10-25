<?php
/**
 * Settings Repository Interface
 *
 * Defines the contract for managing plugin settings, API keys, and migration settings.
 *
 * @package Bricks2Etch\Repositories\Interfaces
 * @since 1.0.0
 */

namespace Bricks2Etch\Repositories\Interfaces;

/**
 * Interface Settings_Repository_Interface
 *
 * Provides methods for accessing and managing plugin settings data.
 */
interface Settings_Repository_Interface {

	/**
	 * Get all plugin settings.
	 *
	 * @return array Plugin settings array.
	 */
	public function get_plugin_settings(): array;

	/**
	 * Save plugin settings.
	 *
	 * @param array $settings Settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_plugin_settings( array $settings ): bool;

	/**
	 * Get API key.
	 *
	 * @return string API key or empty string if not set.
	 */
	public function get_api_key(): string;

	/**
	 * Save API key.
	 *
	 * @param string $key API key to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_api_key( string $key ): bool;

	/**
	 * Delete API key.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_api_key(): bool;

	/**
	 * Get migration settings.
	 *
	 * @return array Migration settings array.
	 */
	public function get_migration_settings(): array;

	/**
	 * Save migration settings.
	 *
	 * @param array $settings Migration settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_migration_settings( array $settings ): bool;

	/**
	 * Clear all settings.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_all_settings(): bool;

	/**
	 * Get CORS allowed origins.
	 *
	 * @return array Array of allowed CORS origin URLs.
	 */
	public function get_cors_allowed_origins(): array;

	/**
	 * Save CORS allowed origins.
	 *
	 * @param array $origins Array of allowed origin URLs.
	 * @return bool True on success, false on failure.
	 */
	public function save_cors_allowed_origins( array $origins ): bool;

	/**
	 * Get security settings.
	 *
	 * Returns security-related settings like rate limits and environment config.
	 *
	 * @return array Security settings array.
	 */
	public function get_security_settings(): array;

	/**
	 * Save security settings.
	 *
	 * @param array $settings Security settings to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_security_settings( array $settings ): bool;
}
