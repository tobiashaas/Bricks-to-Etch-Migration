<?php
/**
 * Div/Flex-Div Element Converter
 *
 * Converts Bricks Div/Block to Etch Flex-Div
 * Supports semantic tags (li, span, article, etc.)
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

namespace Bricks2Etch\Converters\Elements;

use Bricks2Etch\Converters\EFS_Base_Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Element_Div extends EFS_Base_Element {

	protected $element_type = 'div';

	/**
	 * Convert div element
	 *
	 * @param array $element Bricks element
	 * @param array $children Child elements (already converted HTML)
	 * @return string Gutenberg block HTML
	 */
	public function convert( $element, $children = array() ) {
		// Get style IDs
		$style_ids = $this->get_style_ids( $element );

		// Add default flex-div style
		array_unshift( $style_ids, 'etch-flex-div-style' );

		// Get CSS classes
		$css_classes = $this->get_css_classes( $style_ids );

		// Get tag (can be li, span, article, etc.)
		$tag = $this->get_tag( $element, 'div' );

		// Get label
		$label = $this->get_label( $element );

		// Build Etch attributes
		$etch_attributes = array(
			'data-etch-element' => 'flex-div',
		);

		if ( ! empty( $css_classes ) ) {
			$etch_attributes['class'] = $css_classes;
		}

		// Build block attributes
		$attrs = $this->build_attributes( $label, $style_ids, $etch_attributes, $tag );

		// Convert to JSON
		$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		// Build children HTML
		$children_html = is_array( $children ) ? implode( "\n", $children ) : $children;

		// Build block HTML
		return '<!-- wp:group ' . $attrs_json . ' -->' . "\n" .
				'<div class="wp-block-group">' . "\n" .
				$children_html . "\n" .
				'</div>' . "\n" .
				'<!-- /wp:group -->';
	}
}

\class_alias( __NAMESPACE__ . '\\B2E_Element_Div', 'B2E_Element_Div' );

// Legacy alias for backward compatibility
class_alias( __NAMESPACE__ . '\EFS_Element_Div', __NAMESPACE__ . '\B2E_Element_Div' );
