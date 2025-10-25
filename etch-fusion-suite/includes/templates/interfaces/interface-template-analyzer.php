<?php
namespace Bricks2Etch\Templates\Interfaces;

use DOMDocument;
use DOMElement;

/**
 * Contract for template analyzer components that derive structure from sanitized DOM trees.
 */
interface EFS_Template_Analyzer_Interface {
	/**
	 * Performs complete analysis on the provided DOM and returns structured report data.
	 *
	 * @param DOMDocument $dom Sanitized DOM document.
	 * @return array<string,mixed> Structured analysis report (sections, components, layout, etc.).
	 */
	public function analyze( DOMDocument $dom );

	/**
	 * Identifies major sections (hero, features, cta, footer, etc.) within the DOM.
	 *
	 * @param DOMDocument $dom DOM document to inspect.
	 * @return array<int,array<string,mixed>> List of detected section descriptors.
	 */
	public function identify_sections( DOMDocument $dom );

	/**
	 * Detects component types present within a given DOM element subtree.
	 *
	 * @param DOMElement $element Element to analyze.
	 * @return array<string,mixed> Component detection data (counts, labels, metadata).
	 */
	public function detect_components( DOMElement $element );

	/**
	 * Extracts layout hierarchy and structural metadata from the DOM.
	 *
	 * @param DOMDocument $dom DOM to analyze for layout information.
	 * @return array<string,mixed> Layout structure details (depth, grids, flex containers, etc.).
	 */
	public function extract_layout_structure( DOMDocument $dom );

	/**
	 * Analyzes typography usage including heading hierarchy and font usage.
	 *
	 * @param DOMDocument $dom DOM to inspect for typographic elements.
	 * @return array<string,mixed> Typography analysis data.
	 */
	public function analyze_typography( DOMDocument $dom );

	/**
	 * Detects media elements (images, videos, svgs) in the DOM and extracts metadata.
	 *
	 * @param DOMDocument $dom DOM to inspect.
	 * @return array<int,array<string,mixed>> Media element metadata.
	 */
	public function detect_media_elements( DOMDocument $dom );

	/**
	 * Returns the computed complexity score for the most recent analysis run.
	 *
	 * @return int Score between 0 and 100.
	 */
	public function get_complexity_score();
}
