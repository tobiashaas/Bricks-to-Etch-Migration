<?php
/**
 * Element Factory
 *
 * Creates the appropriate element converter based on element type
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

namespace Bricks2Etch\Converters;

use Bricks2Etch\Converters\Elements\B2E_Element_Container;
use Bricks2Etch\Converters\Elements\B2E_Element_Section;
use Bricks2Etch\Converters\Elements\B2E_Element_Heading;
use Bricks2Etch\Converters\Elements\B2E_Element_Paragraph;
use Bricks2Etch\Converters\Elements\B2E_Element_Image;
use Bricks2Etch\Converters\Elements\B2E_Element_Div;
use Bricks2Etch\Converters\Elements\B2E_Button_Converter;
use Bricks2Etch\Converters\Elements\B2E_Icon_Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Element_Factory {

	/**
	 * Style map
	 */
	private $style_map;

	/**
	 * Element converters cache
	 */
	private $converters = array();

	/**
	 * Constructor
	 *
	 * @param array $style_map Style map for CSS classes
	 */
	public function __construct( $style_map = array() ) {
		$this->style_map = $style_map;
		$this->load_converters();
	}

	/**
	 * Load all element converters
	 */
	private function load_converters() {
		// Converters are now autoloaded via namespace
	}

	/**
	 * Get converter for element type
	 *
	 * @param string $element_type Bricks element type
	 * @return EFS_Base_Element|null Element converter
	 */
	public function get_converter( $element_type ) {
		// Elements to skip (not shown in frontend or not supported)
		$skip_elements = array(
			'fr-notes',      // Bricks Builder notes (not frontend)
			'code',          // Code blocks (TODO)
			'form',          // Forms (TODO)
			'map',           // Maps (TODO)
		);

		// Skip elements silently
		if ( in_array( $element_type, $skip_elements ) ) {
			return null;
		}

		// Map Bricks element types to converter classes
		$type_map = array(
			'container'  => 'B2E_Element_Container',
			'section'    => 'B2E_Element_Section',
			'heading'    => 'B2E_Element_Heading',
			'text-basic' => 'B2E_Element_Paragraph',
			'text'       => 'B2E_Element_Paragraph',
			'image'      => 'B2E_Element_Image',
			'div'        => 'B2E_Element_Div',
			'block'      => 'B2E_Element_Div', // Bricks 'block' = Etch 'flex-div'
			'button'     => 'B2E_Button_Converter',
			'icon'       => 'B2E_Icon_Converter',
		);

		// Get converter class
		$converter_class = $type_map[ $element_type ] ?? null;

		if ( ! $converter_class ) {
			error_log( "⚠️ B2E Factory: No converter found for element type: {$element_type}" );
			return null;
		}

		// Create converter instance (with caching)
		if ( ! isset( $this->converters[ $converter_class ] ) ) {
			$this->converters[ $converter_class ] = new $converter_class( $this->style_map );
		}

		return $this->converters[ $converter_class ];
	}

	/**
	 * Convert element using appropriate converter
	 *
	 * @param array $element Bricks element
	 * @param array $children Child elements (already converted HTML)
	 * @return string|null Gutenberg block HTML
	 */
	public function convert_element( $element, $children = array() ) {
		$element_type = $element['name'] ?? '';

		if ( empty( $element_type ) ) {
			error_log( '⚠️ B2E Factory: Element has no type' );
			return null;
		}

		$converter = $this->get_converter( $element_type );

		if ( ! $converter ) {
			error_log( "⚠️ B2E Factory: Unsupported element type: {$element_type}" );
			return null;
		}

		return $converter->convert( $element, $children );
	}
}

// Legacy alias for backward compatibility
class_alias( __NAMESPACE__ . '\EFS_Element_Factory', __NAMESPACE__ . '\B2E_Element_Factory' );
