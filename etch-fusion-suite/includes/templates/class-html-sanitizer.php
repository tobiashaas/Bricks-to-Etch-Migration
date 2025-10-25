<?php
namespace Bricks2Etch\Templates;

use Bricks2Etch\Core\EFS_Error_Handler;
use Bricks2Etch\Security\EFS_Input_Validator;
use Bricks2Etch\Templates\Interfaces\EFS_HTML_Sanitizer_Interface;
use DOMDocument;
use DOMElement;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base sanitizer that coordinates DOM sanitization steps for template extraction.
 */
class EFS_HTML_Sanitizer implements EFS_HTML_Sanitizer_Interface {
	/**
	 * @var EFS_Error_Handler
	 */
	protected $error_handler;

	/**
	 * @var EFS_Input_Validator
	 */
	protected $input_validator;

	/**
	 * Last extracted CSS variables during sanitization.
	 *
	 * @var array<string,string>
	 */
	protected $css_variables = array();

	/**
	 * Constructor.
	 */
	public function __construct( EFS_Error_Handler $error_handler, EFS_Input_Validator $input_validator ) {
		$this->error_handler    = $error_handler;
		$this->input_validator  = $input_validator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function sanitize( DOMDocument $dom ) {
		if ( ! $dom->documentElement instanceof DOMElement ) {
			$this->error_handler->log_error( 'B2E_SANITIZER_DOM_MISSING', array(), 'error' );
			return $dom;
		}

		// Default no-op implementations expected to be overridden in specialized sanitizers.
		$this->remove_framer_scripts( $dom );
		$this->traverse_and_clean( $dom->documentElement );
		$this->semanticize_elements( $dom );

		$this->css_variables = $this->extract_inline_styles( $dom );

		return $dom;
	}

	/**
	 * Removes Framer related scripts. Subclasses may override.
	 *
	 * @param DOMDocument $dom DOM document.
	 * @return void
	 */
	protected function remove_framer_scripts( DOMDocument $dom ) {
		$xpath = new \DOMXPath( $dom );
		$nodes = $xpath->query( '//script[contains(@src, "framer.com") or contains(@src, "framerusercontent.com")]' );
		if ( empty( $nodes ) ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( isset( $node->parentNode ) && $node->parentNode instanceof \DOMNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	/**
	 * Recursively traverses the DOM tree to clean attributes and unwrap wrappers.
	 *
	 * @param DOMElement $element Element to process.
	 * @return void
	 */
	protected function traverse_and_clean( DOMElement $element ) {
		$this->clean_attributes( $element );
		$this->normalize_class_names( $element );
		$this->remove_unnecessary_wrappers( $element );

		foreach ( iterator_to_array( $element->childNodes ) as $child ) {
			if ( $child instanceof DOMElement ) {
				$this->traverse_and_clean( $child );
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function remove_unnecessary_wrappers( DOMElement $element ) {
		// Base implementation only handles simple div wrappers.
		if ( 'div' !== strtolower( $element->tagName ) ) {
			return;
		}

		if ( 1 !== $element->childNodes->length ) {
			return;
		}

		$child = $element->firstChild;
		if ( ! $child instanceof DOMElement ) {
			return;
		}

		if ( $element->hasAttributes() ) {
			return;
		}

		$parent = $element->parentNode;
		if ( $parent ) {
			$parent->replaceChild( $child, $element );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function semanticize_elements( DOMDocument $dom ) {
		// Base implementation leaves DOM untouched. Specialized sanitizers should override.
	}

	/**
	 * {@inheritdoc}
	 */
	public function clean_attributes( DOMElement $element ) {
		// Remove empty attributes.
		foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
			if ( '' === trim( $attribute->value ) ) {
				$element->removeAttribute( $attribute->name );
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function extract_inline_styles( DOMDocument $dom ) {
		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function normalize_class_names( DOMElement $element ) {
		// Default does nothing, subclasses can override.
	}

	/**
	 * Returns the CSS variables collected during the last sanitize() invocation.
	 *
	 * @return array<string,string>
	 */
	public function get_css_variables() {
		return $this->css_variables;
	}
}

class_alias( __NAMESPACE__ . '\\EFS_HTML_Sanitizer', __NAMESPACE__ . '\\B2E_HTML_Sanitizer' );
