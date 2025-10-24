<?php
/**
 * Style Repository Interface
 *
 * Defines the contract for managing CSS styles, style maps, and Etch-specific options.
 *
 * @package Bricks2Etch\Repositories\Interfaces
 * @since 1.0.0
 */

namespace Bricks2Etch\Repositories\Interfaces;

/**
 * Interface Style_Repository_Interface
 *
 * Provides methods for accessing and managing style-related data.
 */
interface Style_Repository_Interface {

	/**
	 * Get Etch styles.
	 *
	 * @return array Etch styles array.
	 */
	public function get_etch_styles(): array;

	/**
	 * Save Etch styles.
	 *
	 * @param array $styles Styles to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_etch_styles( array $styles ): bool;

	/**
	 * Get style map (Bricks to Etch style ID mapping).
	 *
	 * @return array Style map array.
	 */
	public function get_style_map(): array;

	/**
	 * Save style map.
	 *
	 * @param array $map Style map to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_style_map( array $map ): bool;

	/**
	 * Get SVG version number.
	 *
	 * @return int SVG version number.
	 */
	public function get_svg_version(): int;

	/**
	 * Increment SVG version and invalidate cache.
	 *
	 * @return int New SVG version number.
	 */
	public function increment_svg_version(): int;

	/**
	 * Get global stylesheets.
	 *
	 * @return array Global stylesheets array.
	 */
	public function get_global_stylesheets(): array;

	/**
	 * Save global stylesheets.
	 *
	 * @param array $stylesheets Stylesheets to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_global_stylesheets( array $stylesheets ): bool;

	/**
	 * Get Bricks global classes.
	 *
	 * @return array Bricks global classes array.
	 */
	public function get_bricks_global_classes(): array;

	/**
	 * Invalidate all style-related caches.
	 *
	 * @return bool True on success.
	 */
	public function invalidate_style_cache(): bool;
}
