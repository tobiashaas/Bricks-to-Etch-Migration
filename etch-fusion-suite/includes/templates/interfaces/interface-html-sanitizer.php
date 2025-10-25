<?php
namespace Bricks2Etch\Templates\Interfaces;

use DOMDocument;
use DOMElement;

/**
 * Defines behaviour for HTML sanitizers used in template extraction.
 */
interface EFS_HTML_Sanitizer_Interface {
	/**
	 * Sanitizes the provided DOM document in-place and returns the sanitized instance.
	 *
	 * Example:
	 * $sanitized = $sanitizer->sanitize( $dom );
	 *
	 * @param DOMDocument $dom Parsed DOM tree.
	 * @return DOMDocument Sanitized DOM document instance.
	 */
	public function sanitize( DOMDocument $dom );

	/**
	 * Removes unnecessary wrapper elements, typically single-child div containers.
	 *
	 * Example: <div><div class="content"></div></div> → <div class="content"></div>
	 *
	 * @param DOMElement $element Root element to process recursively.
	 * @return void
	 */
	public function remove_unnecessary_wrappers( DOMElement $element );

	/**
	 * Converts generic elements into semantic HTML where possible (e.g. div → header).
	 *
	 * Example: <div data-framer-name="Header"> → <header>
	 *
	 * @param DOMDocument $dom DOM tree to process.
	 * @return void
	 */
	public function semanticize_elements( DOMDocument $dom );

	/**
	 * Cleans unwanted attributes and normalizes remaining ones on the given element.
	 *
	 * Example: Removes data-framer-* noise, preserves essentials like src and alt.
	 *
	 * @param DOMElement $element Element whose attributes should be cleaned.
	 * @return void
	 */
	public function clean_attributes( DOMElement $element );

	/**
	 * Extracts inline CSS variable definitions from the DOM.
	 *
	 * Example: style="--framer-primary-color: #ff00ff;" returns
	 * ['framer-primary-color' => '#ff00ff'].
	 *
	 * @param DOMDocument $dom DOM tree to inspect.
	 * @return array<string,string> Map of variable names to values.
	 */
	public function extract_inline_styles( DOMDocument $dom );

	/**
	 * Normalizes class names on the element to human-readable slugs.
	 *
	 * Example: data-framer-name="Hero Section" → class="hero-section".
	 *
	 * @param DOMElement $element Element to normalize.
	 * @return void
	 */
	public function normalize_class_names( DOMElement $element );
}
