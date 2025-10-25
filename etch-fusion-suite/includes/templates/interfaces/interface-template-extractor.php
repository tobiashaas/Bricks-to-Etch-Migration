<?php
namespace Bricks2Etch\Templates\Interfaces;

use WP_Error;

/**
 * Interface for template extractor services.
 */
interface EFS_Template_Extractor_Interface {
	/**
	 * Fetches and extracts a template from a remote URL.
	 *
	 * @param string $url Fully qualified Framer or supported source URL.
	 * @return array|WP_Error Associative array with template data or WP_Error on failure.
	 */
	public function extract_from_url( string $url );

	/**
	 * Extracts a template from a raw HTML string.
	 *
	 * @param string $html Raw HTML markup to process.
	 * @return array|WP_Error Associative array with template data or WP_Error on failure.
	 */
	public function extract_from_html( string $html );

	/**
	 * Validates an extracted template payload.
	 *
	 * @param array $template Template payload.
	 * @return array{valid:bool,errors:array<int,string>} Validation result and error messages.
	 */
	public function validate_template( array $template );

	/**
	 * Returns the supported extraction sources handled by this extractor implementation.
	 *
	 * @return array<int,string> List of supported source identifiers (e.g. framer, webflow).
	 */
	public function get_supported_sources();

	/**
	 * Returns statistics about the most recent extraction run.
	 *
	 * @return array<string,mixed> Associative array with timing, counts and warnings.
	 */
	public function get_extraction_stats();
}

class_alias( __NAMESPACE__ . '\\EFS_Template_Extractor_Interface', __NAMESPACE__ . '\\B2E_Template_Extractor_Interface' );
