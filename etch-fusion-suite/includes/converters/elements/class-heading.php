<?php
/**
 * Heading Element Converter
 *
 * Converts Bricks Heading to Gutenberg Heading with Etch metadata
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

namespace Bricks2Etch\Converters\Elements;

use Bricks2Etch\Converters\EFS_Base_Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Element_Heading extends EFS_Base_Element {

	protected $element_type = 'heading';

	/**
	 * Convert heading element
	 *
	 * @param array $element Bricks element
	 * @param array $children Not used for headings
	 * @return string Gutenberg block HTML
	 */
	public function convert( $element, $children = array() ) {
		// Get style IDs
		$style_ids = $this->get_style_ids( $element );

		// Get CSS classes
		$css_classes = $this->get_css_classes( $style_ids );

		// Get tag (h1, h2, h3, etc.)
		$tag = $this->get_tag( $element, 'h2' );

		// Get label
		$label = $this->get_label( $element );

		// Get text content
		$text = $element['settings']['text'] ?? 'Heading';

		// Build Etch attributes
		$etch_attributes = array();

		if ( ! empty( $css_classes ) ) {
			$etch_attributes['class'] = $css_classes;
		}

		// Build block attributes
		$attrs = $this->build_attributes( $label, $style_ids, $etch_attributes, $tag );

		// Add heading level
		$level          = (int) str_replace( 'h', '', $tag );
		$attrs['level'] = $level;

		// Convert to JSON
		$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		// Build block HTML
		return '<!-- wp:heading ' . $attrs_json . ' -->' . "\n" .
				'<' . $tag . ' class="wp-block-heading">' . esc_html( $text ) . '</' . $tag . '>' . "\n" .
				'<!-- /wp:heading -->';
	}
}

\class_alias( __NAMESPACE__ . '\\B2E_Element_Heading', 'B2E_Element_Heading' );

// Legacy alias for backward compatibility
class_alias( __NAMESPACE__ . '\EFS_Element_Heading', __NAMESPACE__ . '\B2E_Element_Heading' );
