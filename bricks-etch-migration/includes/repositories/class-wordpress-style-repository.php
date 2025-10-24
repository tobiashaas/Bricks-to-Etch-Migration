<?php
/**
 * WordPress Style Repository
 *
 * WordPress-backed implementation of Style Repository using Options API.
 *
 * @package Bricks2Etch\Repositories
 * @since 1.0.0
 */

namespace Bricks2Etch\Repositories;

use Bricks2Etch\Repositories\Interfaces\Style_Repository_Interface;

/**
 * Class B2E_WordPress_Style_Repository
 *
 * Manages CSS styles, style maps, and Etch-specific options with transient caching.
 */
class B2E_WordPress_Style_Repository implements Style_Repository_Interface {

	/**
	 * Cache expiration for styles (5 minutes).
	 */
	const CACHE_EXPIRATION_STYLES = 300;

	/**
	 * Cache expiration for version checks (1 minute).
	 */
	const CACHE_EXPIRATION_VERSION = 60;

	/**
	 * Get Etch styles.
	 *
	 * @return array Etch styles array.
	 */
	public function get_etch_styles(): array {
		$cache_key = 'b2e_cache_etch_styles';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$styles = get_option( 'etch_styles', array() );
		set_transient( $cache_key, $styles, self::CACHE_EXPIRATION_STYLES );

		return $styles;
	}

	/**
	 * Save Etch styles.
	 *
	 * @param array $styles Styles to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_etch_styles( array $styles ): bool {
		$this->invalidate_cache( 'b2e_cache_etch_styles' );
		return update_option( 'etch_styles', $styles );
	}

	/**
	 * Get style map (Bricks to Etch style ID mapping).
	 *
	 * @return array Style map array.
	 */
	public function get_style_map(): array {
		$cache_key = 'b2e_cache_style_map';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$map = get_option( 'b2e_style_map', array() );
		set_transient( $cache_key, $map, self::CACHE_EXPIRATION_STYLES );

		return $map;
	}

	/**
	 * Save style map.
	 *
	 * @param array $map Style map to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_style_map( array $map ): bool {
		$this->invalidate_cache( 'b2e_cache_style_map' );
		return update_option( 'b2e_style_map', $map );
	}

	/**
	 * Get SVG version number.
	 *
	 * @return int SVG version number.
	 */
	public function get_svg_version(): int {
		$cache_key = 'b2e_cache_svg_version';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$version = (int) get_option( 'etch_svg_version', 1 );
		set_transient( $cache_key, $version, self::CACHE_EXPIRATION_VERSION );

		return $version;
	}

	/**
	 * Increment SVG version and invalidate cache.
	 *
	 * @return int New SVG version number.
	 */
	public function increment_svg_version(): int {
		$current_version = $this->get_svg_version();
		$new_version     = $current_version + 1;

		$this->invalidate_cache( 'b2e_cache_svg_version' );
		update_option( 'etch_svg_version', $new_version );

		return $new_version;
	}

	/**
	 * Get global stylesheets.
	 *
	 * @return array Global stylesheets array.
	 */
	public function get_global_stylesheets(): array {
		$cache_key = 'b2e_cache_global_stylesheets';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$stylesheets = get_option( 'etch_global_stylesheets', array() );
		set_transient( $cache_key, $stylesheets, self::CACHE_EXPIRATION_STYLES );

		return $stylesheets;
	}

	/**
	 * Save global stylesheets.
	 *
	 * @param array $stylesheets Stylesheets to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_global_stylesheets( array $stylesheets ): bool {
		$this->invalidate_cache( 'b2e_cache_global_stylesheets' );
		return update_option( 'etch_global_stylesheets', $stylesheets );
	}

	/**
	 * Get Bricks global classes.
	 *
	 * @return array Bricks global classes array.
	 */
	public function get_bricks_global_classes(): array {
		$cache_key = 'b2e_cache_bricks_global_classes';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$classes = get_option( 'bricks_global_classes', array() );
		set_transient( $cache_key, $classes, self::CACHE_EXPIRATION_STYLES );

		return $classes;
	}

	/**
	 * Invalidate all style-related caches.
	 *
	 * @return bool True on success.
	 */
	public function invalidate_style_cache(): bool {
		$this->invalidate_cache( 'b2e_cache_etch_styles' );
		$this->invalidate_cache( 'b2e_cache_style_map' );
		$this->invalidate_cache( 'b2e_cache_svg_version' );
		$this->invalidate_cache( 'b2e_cache_global_stylesheets' );
		$this->invalidate_cache( 'b2e_cache_bricks_global_classes' );

		wp_cache_delete( 'etch_styles', 'options' );

		return true;
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
class_alias( 'Bricks2Etch\Repositories\B2E_WordPress_Style_Repository', 'B2E_WordPress_Style_Repository' );
