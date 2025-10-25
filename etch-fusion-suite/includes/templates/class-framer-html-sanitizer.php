<?php
namespace Bricks2Etch\Templates;

use Bricks2Etch\Core\EFS_Error_Handler;
use Bricks2Etch\Templates\Interfaces\EFS_HTML_Sanitizer_Interface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Framer specific HTML sanitizer implementation.
 */
class EFS_Framer_HTML_Sanitizer extends EFS_HTML_Sanitizer implements EFS_HTML_Sanitizer_Interface {
	/** @var array<string,string> */
	protected $css_variables = array();

	/**
	 * {@inheritdoc}
	 */
	public function sanitize( DOMDocument $dom ) {
		if ( ! $dom->documentElement instanceof DOMElement ) {
			return $dom;
		}

		$this->remove_framer_scripts( $dom );
		$this->clean_dom_attributes( $dom->documentElement );
		$this->remove_unnecessary_wrappers( $dom->documentElement );
		$this->semanticize_elements( $dom );
		$this->normalize_class_names( $dom->documentElement );

		$this->css_variables = $this->extract_inline_styles( $dom );

		return $dom;
	}

	/**
	 * Iterates through DOM and cleans attributes on each element.
	 *
	 * @param DOMElement $element
	 * @return void
	 */
	protected function clean_dom_attributes( DOMElement $element ) {
		$this->clean_attributes( $element );

		foreach ( iterator_to_array( $element->childNodes ) as $child ) {
			if ( $child instanceof DOMElement ) {
				$this->clean_dom_attributes( $child );
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function remove_unnecessary_wrappers( DOMElement $element ) {
		foreach ( iterator_to_array( $element->childNodes ) as $child ) {
			if ( $child instanceof DOMElement ) {
				$this->remove_unnecessary_wrappers( $child );
			}
		}

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
			// Ignore aria and semantic attributes that are meaningful.
			$attributes = array();
			foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
				$attributes[] = $attribute->name;
			}
			// Allow data-framer-name for semantic hints only if the child has none.
			if ( count( $attributes ) > 1 ) {
				return;
			}
		}

		$parent = $element->parentNode;
		if ( $parent instanceof DOMElement ) {
			$parent->replaceChild( $child, $element );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function remove_framer_scripts( DOMDocument $dom ) {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( "//script[contains(@src, 'framer.com') or contains(@src, 'framerusercontent.com')]" );

		if ( ! $nodes ) {
			return;
		}

		foreach ( iterator_to_array( $nodes ) as $node ) {
			if ( null !== $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function clean_attributes( DOMElement $element ) {
		$allowed_data_attributes = array( 'data-framer-component-type', 'data-framer-name' );

		$classes = array();
		if ( $element->hasAttribute( 'class' ) ) {
			$class_parts = preg_split( '/\s+/', $element->getAttribute( 'class' ) );
			foreach ( $class_parts as $class_name ) {
				if ( empty( $class_name ) ) {
					continue;
				}
				if ( preg_match( '/^framer-[a-z0-9]{5,}$/i', $class_name ) ) {
					continue;
				}
				$classes[] = $class_name;
			}
			if ( empty( $classes ) ) {
				$element->removeAttribute( 'class' );
			} else {
				$element->setAttribute( 'class', implode( ' ', array_unique( $classes ) ) );
			}
		}

		foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
			$name  = $attribute->name;
			$value = $attribute->value;

			if ( 0 === strpos( $name, 'data-framer-' ) && ! in_array( $name, $allowed_data_attributes, true ) ) {
				$element->removeAttribute( $name );
				continue;
			}

			if ( in_array( $name, array( 'style', 'onclick', 'onmouseover', 'onmouseout' ), true ) ) {
				if ( 'style' === $name ) {
					$filtered_style = $this->filter_style_attribute( $value );
					if ( '' === $filtered_style ) {
						$element->removeAttribute( 'style' );
					} else {
						$element->setAttribute( 'style', $filtered_style );
					}
				} else {
					$element->removeAttribute( $name );
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function semanticize_elements( DOMDocument $dom ) {
		$xpath = new DOMXPath( $dom );

		// Header / Nav / Footer semantics.
		$this->convert_by_data_name( $xpath, 'Header', 'header' );
		$this->convert_by_data_name( $xpath, 'Nav', 'nav' );
		$this->convert_by_data_name( $xpath, 'Footer', 'footer' );
		$this->convert_sections( $xpath );
		$this->convert_text_components( $xpath );
		$this->convert_media_components( $xpath );
		$this->convert_button_components( $xpath );
	}

	/**
	 * {@inheritdoc}
	 */
	public function extract_inline_styles( DOMDocument $dom ) {
		$xpath    = new DOMXPath( $dom );
		$nodes    = $xpath->query( '//*[@style]' );
		$vars_map = array();

		if ( $nodes ) {
			foreach ( $nodes as $node ) {
				if ( ! $node instanceof DOMElement ) {
					continue;
				}
				$style = $node->getAttribute( 'style' );
				preg_match_all( '/--framer-([a-z0-9\-]+)\s*:\s*([^;]+);?/', $style, $matches, PREG_SET_ORDER );
				if ( ! empty( $matches ) ) {
					foreach ( $matches as $match ) {
						$vars_map[ $match[1] ] = trim( $match[2] );
					}
				}
			}
		}

		return $vars_map;
	}

	/**
	 * {@inheritdoc}
	 */
	public function normalize_class_names( DOMElement $element ) {
		if ( $element->hasAttribute( 'data-framer-name' ) ) {
			$slug = sanitize_title( $element->getAttribute( 'data-framer-name' ) );
			if ( $slug ) {
				$current = $element->hasAttribute( 'class' ) ? explode( ' ', $element->getAttribute( 'class' ) ) : array();
				$current[] = $slug;
				$element->setAttribute( 'class', implode( ' ', array_unique( array_filter( $current ) ) ) );
			}
		}

		foreach ( iterator_to_array( $element->childNodes ) as $child ) {
			if ( $child instanceof DOMElement ) {
				$this->normalize_class_names( $child );
			}
		}
	}

	/**
	 * Returns collected CSS variable definitions.
	 *
	 * @return array<string,string>
	 */
	public function get_css_variables() {
		return $this->css_variables;
	}

	/**
	 * Helper to convert elements by data-framer-name keyword.
	 *
	 * @param DOMXPath $xpath
	 * @param string   $keyword
	 * @param string   $tag
	 * @return void
	 */
	protected function convert_by_data_name( DOMXPath $xpath, $keyword, $tag ) {
		$nodes = $xpath->query( sprintf( "//div[contains(translate(@data-framer-name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '%s')]", strtolower( $keyword ) ) );

		if ( ! $nodes ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( $node instanceof DOMElement ) {
				$this->rename_element( $node, $tag );
			}
		}
	}

	/**
	 * Converts section-like elements to semantic section tags with extra classes.
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	protected function convert_sections( DOMXPath $xpath ) {
		$nodes = $xpath->query( "//div[@data-framer-name]" );
		if ( ! $nodes ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			$name = strtolower( $node->getAttribute( 'data-framer-name' ) );
			if ( preg_match( '/hero|feature|cta|testimonial|about|contact/', $name ) ) {
				$this->rename_element( $node, 'section' );
			}
		}
	}

	/**
	 * Converts text components based on data-framer-component-type.
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	protected function convert_text_components( DOMXPath $xpath ) {
		$nodes = $xpath->query( "//*[@data-framer-component-type='Text']" );
		if ( ! $nodes ) {
			return;
		}

		$has_h1 = ! ! $xpath->query( '//h1' )->length;

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$text = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );
			if ( '' === $text ) {
				continue;
			}

			$word_count = str_word_count( $text );
			$tag        = 'p';

			if ( ! $has_h1 && $word_count <= 12 ) {
				$tag    = 'h1';
				$has_h1 = true;
			} elseif ( $word_count <= 16 && preg_match( '/hero|heading|title/', strtolower( (string) $node->getAttribute( 'data-framer-name' ) ) ) ) {
				$tag = 'h2';
			}

			$this->rename_element( $node, $tag );
		}
	}

	/**
	 * Converts image related components into img tags.
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	protected function convert_media_components( DOMXPath $xpath ) {
		$nodes = $xpath->query( "//*[@data-framer-component-type='Image']" );
		if ( ! $nodes ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$src = $node->getAttribute( 'data-framer-image-url' );
			if ( ! $src ) {
				$img = $node->getElementsByTagName( 'img' )->item( 0 );
				$src = $img instanceof DOMElement ? $img->getAttribute( 'src' ) : '';
			}

			if ( '' === $src ) {
				continue;
			}

			$img = $node->ownerDocument->createElement( 'img' );
			$img->setAttribute( 'src', esc_url_raw( $src ) );

			if ( $node->hasAttribute( 'data-framer-name' ) ) {
				$img->setAttribute( 'alt', $node->getAttribute( 'data-framer-name' ) );
			}

			foreach ( iterator_to_array( $node->attributes ) as $attribute ) {
				if ( in_array( $attribute->name, array( 'class', 'style' ), true ) && '' !== $attribute->value ) {
					$img->setAttribute( $attribute->name, $attribute->value );
				}
			}

			if ( $node->parentNode ) {
				if ( $node->parentNode instanceof DOMElement || $node->parentNode instanceof \DOMNode ) {
					$node->parentNode->replaceChild( $img, $node );
				}
			}
		}
	}

	/**
	 * Converts button like components into button elements.
	 *
	 * @param DOMXPath $xpath
	 * @return void
	 */
	protected function convert_button_components( DOMXPath $xpath ) {
		$nodes = $xpath->query( "//*[@data-framer-component-type='Button' or @role='button' or @onclick]" );
		if ( ! $nodes ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$tag = strtolower( $node->tagName );
			if ( 'a' === $tag ) {
				$node->setAttribute( 'role', 'button' );
				continue;
			}

			$this->rename_element( $node, 'button' );
			$node->removeAttribute( 'onclick' );
		}
	}

	/**
	 * Renames an element preserving attributes and children.
	 *
	 * @param DOMElement $element
	 * @param string     $new_tag
	 * @return void
	 */
	protected function rename_element( DOMElement $element, $new_tag ) {
		if ( strtolower( $element->tagName ) === strtolower( $new_tag ) ) {
			return;
		}

		$document    = $element->ownerDocument;
		$new_element = $document->createElement( $new_tag );

		foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
			$new_element->setAttribute( $attribute->name, $attribute->value );
		}

		while ( $element->firstChild ) {
			$new_element->appendChild( $element->firstChild );
		}

		if ( $element->parentNode instanceof DOMElement || $element->parentNode instanceof \DOMNode ) {
			$element->parentNode->replaceChild( $new_element, $element );
		}
	}

	/**
	 * Filters style attribute keeping CSS variables only.
	 *
	 * @param string $style
	 * @return string
	 */
	protected function filter_style_attribute( $style ) {
		$variables = array();
		preg_match_all( '/--framer-[a-z0-9\-]+\s*:\s*[^;]+;?/', $style, $matches );
		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[0] as $match ) {
				$variables[] = trim( $match, '; ' ) . ';';
			}
		}

		return implode( ' ', $variables );
	}
}

class_alias( __NAMESPACE__ . '\\EFS_Framer_HTML_Sanitizer', __NAMESPACE__ . '\\B2E_Framer_HTML_Sanitizer' );
