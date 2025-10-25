<?php
namespace Bricks2Etch\Templates;

use Bricks2Etch\Core\EFS_Error_Handler;
use Bricks2Etch\Converters\EFS_Element_Factory;
use Bricks2Etch\Repositories\Interfaces\Style_Repository_Interface;
use DOMElement;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts sanitized Framer DOM fragments into Etch compatible block definitions.
 */
class EFS_Framer_To_Etch_Converter {
	/**
	 * @var EFS_Error_Handler
	 */
	protected $error_handler;

	/**
	 * @var EFS_Element_Factory
	 */
	protected $element_factory;

	/**
	 * @var Style_Repository_Interface
	 */
	protected $style_repository;

	/**
	 * Constructor.
	 */
	public function __construct( EFS_Error_Handler $error_handler, EFS_Element_Factory $element_factory, Style_Repository_Interface $style_repository ) {
		$this->error_handler     = $error_handler;
		$this->element_factory   = $element_factory;
		$this->style_repository  = $style_repository;
	}

	/**
	 * Converts a DOM element to an Etch block array.
	 *
	 * @param DOMElement $element
	 * @param array      $analysis_context
	 * @return array|null
	 */
	public function convert_element( DOMElement $element, array $analysis_context = array() ) {
		$type = strtolower( $element->getAttribute( 'data-framer-component-type' ) );

		try {
			switch ( $type ) {
				case 'text':
					return $this->convert_text_component( $element );
				case 'image':
					return $this->convert_image_component( $element );
				case 'button':
					return $this->convert_button_component( $element );
				default:
					$tag = strtolower( $element->tagName );
					if ( 'section' === $tag ) {
						return $this->convert_section( $element, $analysis_context['children'] ?? array() );
					}
					if ( in_array( $tag, array( 'div', 'header', 'nav', 'footer' ), true ) ) {
						return $this->convert_container( $element, $analysis_context['children'] ?? array() );
					}
			}
		} catch ( \Throwable $exception ) {
			$this->error_handler->log_error(
				'B2E_FRAMER_CONVERTER',
				array(
					'message' => $exception->getMessage(),
					'tag'     => $element->tagName,
					'type'    => $type,
				),
				'error'
			);
		}

		return null;
	}

	/**
	 * Converts textual components to Etch paragraph/heading blocks.
	 *
	 * @param DOMElement $element
	 * @return array
	 */
	public function convert_text_component( DOMElement $element ) {
		$text = trim( preg_replace( '/\s+/', ' ', $element->textContent ) );
		if ( '' === $text ) {
			return array();
		}

		$tag = strtolower( $element->tagName );
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
			$tag = 'p';
		}

		return array(
			'type'       => 'text',
			'tag'        => $tag,
			'content'    => wp_kses_post( $text ),
			'attributes' => $this->extract_etch_attributes( $element ),
			'styles'     => $this->map_framer_classes_to_etch( $element ),
		);
	}

	/**
	 * Converts image components to Etch image block definitions.
	 *
	 * @param DOMElement $element
	 * @return array
	 */
	public function convert_image_component( DOMElement $element ) {
		$src = $element->getAttribute( 'src' );
		if ( ! $src ) {
			$nested = $element->getElementsByTagName( 'img' )->item( 0 );
			$src    = $nested instanceof DOMElement ? $nested->getAttribute( 'src' ) : '';
		}

		return array(
			'type'       => 'image',
			'src'        => esc_url_raw( $src ),
			'alt'        => $element->getAttribute( 'alt' ) ?: $element->getAttribute( 'data-framer-name' ),
			'attributes' => $this->extract_etch_attributes( $element ),
			'styles'     => $this->map_framer_classes_to_etch( $element ),
		);
	}

	/**
	 * Converts Framer buttons/links to Etch button definitions.
	 *
	 * @param DOMElement $element
	 * @return array
	 */
	public function convert_button_component( DOMElement $element ) {
		$href = '#';
		if ( 'a' === strtolower( $element->tagName ) ) {
			$href = $element->getAttribute( 'href' ) ?: '#';
		}

		return array(
			'type'       => 'button',
			'label'      => trim( $element->textContent ),
			'href'       => esc_url_raw( $href ),
			'attributes' => $this->extract_etch_attributes( $element ),
			'styles'     => $this->map_framer_classes_to_etch( $element ),
		);
	}

	/**
	 * Converts section containers to Etch section groups.
	 *
	 * @param DOMElement $element
	 * @param array      $children
	 * @return array
	 */
	public function convert_section( DOMElement $element, array $children ) {
		return array(
			'type'       => 'section',
			'label'      => $element->getAttribute( 'data-framer-name' ) ?: 'Section',
			'children'   => $children,
			'attributes' => $this->extract_etch_attributes( $element ),
			'styles'     => $this->map_framer_classes_to_etch( $element ),
			'tag'        => 'section',
		);
	}

	/**
	 * Converts general containers (div, header, nav, footer) to Etch groups.
	 *
	 * @param DOMElement $element
	 * @param array      $children
	 * @return array
	 */
	public function convert_container( DOMElement $element, array $children ) {
		return array(
			'type'       => 'container',
			'label'      => $element->getAttribute( 'data-framer-name' ) ?: ucfirst( strtolower( $element->tagName ) ),
			'children'   => $children,
			'attributes' => $this->extract_etch_attributes( $element ),
			'styles'     => $this->map_framer_classes_to_etch( $element ),
			'tag'        => strtolower( $element->tagName ),
		);
	}

	/**
	 * Maps Framer classes to Etch style identifiers via the style repository.
	 *
	 * @param DOMElement $element
	 * @return array<int,string>
	 */
	public function map_framer_classes_to_etch( DOMElement $element ) {
		$classes = array();
		if ( $element->hasAttribute( 'class' ) ) {
			$classes = array_filter( preg_split( '/\s+/', $element->getAttribute( 'class' ) ) );
		}

		if ( empty( $classes ) ) {
			return array();
		}

		$style_map = $this->style_repository->get_style_map();
		$style_ids = array();

		foreach ( $classes as $class_name ) {
			foreach ( $style_map as $bricks_id => $data ) {
				if ( is_array( $data ) && ! empty( $data['selector'] ) && ltrim( $data['selector'], '.' ) === $class_name ) {
					$style_ids[] = $data['id'];
					break;
				}
			}
		}

		return array_values( array_unique( $style_ids ) );
	}

	/**
	 * Extracts Etch compatible attributes from a DOM element.
	 *
	 * @param DOMElement $element
	 * @return array<string,string>
	 */
	public function extract_etch_attributes( DOMElement $element ) {
		$attributes = array();

		foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
			if ( in_array( $attribute->name, array( 'class', 'id', 'role', 'aria-label' ), true ) ) {
				$attributes[ $attribute->name ] = $attribute->value;
			}
		}

		return $attributes;
	}
}

class_alias( __NAMESPACE__ . '\\EFS_Framer_To_Etch_Converter', __NAMESPACE__ . '\\B2E_Framer_To_Etch_Converter' );
