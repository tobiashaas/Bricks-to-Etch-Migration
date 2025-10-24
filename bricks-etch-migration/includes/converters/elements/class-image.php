<?php
/**
 * Image Element Converter
 *
 * Converts Bricks Image to Gutenberg Image with Etch metadata
 * IMPORTANT: Uses 'figure' tag, not 'img'!
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

namespace Bricks2Etch\Converters\Elements;

use Bricks2Etch\Converters\B2E_Base_Element;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class B2E_Element_Image extends B2E_Base_Element {

	protected $element_type = 'image';

	/**
	 * Convert image element
	 *
	 * @param array $element Bricks element
	 * @param array $children Not used for images
	 * @return string Gutenberg block HTML
	 */
	public function convert( $element, $children = array() ) {
		// Get style IDs
		$style_ids = $this->get_style_ids( $element );

		// Get CSS classes
		$css_classes = $this->get_css_classes( $style_ids );

		// Get label
		$label = $this->get_label( $element );

		// Get image data
		$image_id  = $element['settings']['image']['id'] ?? 0;
		$image_url = $element['settings']['image']['url'] ?? '';
		$alt_text  = $element['settings']['alt'] ?? '';

		// Build Etch structure with nestedData for img
		// Styles and classes go on the IMG, not the FIGURE!
		$img_attributes = array(
			'src' => $image_url,
			'alt' => $alt_text,
		);

		if ( ! empty( $css_classes ) ) {
			$img_attributes['class'] = $css_classes;
		}

		$etch_data = array(
			'origin'     => 'etch',
			'nestedData' => array(
				'img' => array(
					'origin'     => 'etch',
					'attributes' => $img_attributes,
					'block'      => array(
						'type' => 'html',
						'tag'  => 'img',
					),
				),
			),
		);

		// Add styles to img nestedData
		if ( ! empty( $style_ids ) ) {
			$etch_data['nestedData']['img']['styles'] = $style_ids;
		}

		// Add label if present
		if ( ! empty( $label ) ) {
			$etch_data['name'] = $label;
		}

		// Build block attributes
		$attrs = array(
			'metadata'        => array(
				'etchData' => $etch_data,
			),
			'sizeSlug'        => 'full',
			'linkDestination' => 'none',
		);

		// Add image ID
		if ( $image_id ) {
			$attrs['id'] = $image_id;
		}

		// Convert to JSON
		$attrs_json = json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		// Build block HTML
		$html  = '<!-- wp:image ' . $attrs_json . ' -->' . "\n";
		$html .= '<figure class="wp-block-image size-full">';
		$html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt_text ) . '" />';
		$html .= '</figure>' . "\n";
		$html .= '<!-- /wp:image -->';

		return $html;
	}
}

\class_alias( __NAMESPACE__ . '\\B2E_Element_Image', 'B2E_Element_Image' );
