<?php
/**
 * Paragraph Element Converter
 *
 * Converts Bricks Text/Text-Basic to Gutenberg Paragraph
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

namespace Bricks2Etch\Converters\Elements;

use Bricks2Etch\Converters\EFS_Base_Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Element_Paragraph extends EFS_Base_Element {

	protected $element_type = 'paragraph';

	/**
	 * Convert paragraph element
	 *
	 * @param array $element Bricks element
	 * @param array $children Not used for paragraphs
	 * @return string Gutenberg block HTML
	 */
	public function convert( $element, $children = array() ) {
		// Get style IDs
		$style_ids = $this->get_style_ids( $element );

		// Get CSS classes
		$css_classes = $this->get_css_classes( $style_ids );

		// Get tag (usually 'p')
		$tag = $this->get_tag( $element, 'p' );

		// Get label
		$label = $this->get_label( $element );

		// Get text content
		$text = $element['settings']['text'] ?? '';

		// Build Etch attributes
		$etch_attributes = array();

		if ( ! empty( $css_classes ) ) {
			$etch_attributes['class'] = $css_classes;
		}

		// Build block attributes
		$attrs = $this->build_attributes( $label, $style_ids, $etch_attributes, $tag );

		// Convert to JSON
		$attrs_json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		// Build block HTML
		return '<!-- wp:paragraph ' . $attrs_json . ' -->' . "\n" .
				'<p>' . wp_kses_post( $text ) . '</p>' . "\n" .
				'<!-- /wp:paragraph -->';
	}
}
