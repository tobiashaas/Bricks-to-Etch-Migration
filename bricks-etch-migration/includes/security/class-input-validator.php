<?php
/**
 * Input Validator
 *
 * Provides comprehensive input validation and sanitization for all endpoints.
 * Validates URLs, text, integers, arrays, JSON, API keys, and tokens.
 *
 * @package    Bricks2Etch
 * @subpackage Security
 * @since      0.5.0
 */

namespace Bricks2Etch\Security;

/**
 * Input Validator Class
 *
 * Comprehensive validation methods for all input types with security-first approach.
 */
class B2E_Input_Validator {

	/**
	 * Validate URL
	 *
	 * @param string $url      URL to validate.
	 * @param bool   $required Whether the field is required (default: true).
	 * @return string|null Validated URL or null if not required and empty.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_url( $url, $required = true ) {
		// Check if required
		if ( empty( $url ) ) {
			if ( $required ) {
				throw new \InvalidArgumentException( 'URL is required' );
			}
			return null;
		}

		// Sanitize URL
		$url = esc_url_raw( $url );

		// Validate URL format
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new \InvalidArgumentException( 'Invalid URL format' );
		}

		// Check allowed protocols (http, https only)
		$parsed = wp_parse_url( $url );
		if ( ! isset( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
			throw new \InvalidArgumentException( 'URL must use http or https protocol' );
		}

		return $url;
	}

	/**
	 * Validate text
	 *
	 * @param string $text       Text to validate.
	 * @param int    $max_length Maximum length (default: 255).
	 * @param bool   $required   Whether the field is required (default: true).
	 * @return string|null Validated text or null if not required and empty.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_text( $text, $max_length = 255, $required = true ) {
		// Check if required
		if ( empty( $text ) && $text !== '0' ) {
			if ( $required ) {
				throw new \InvalidArgumentException( 'Text is required' );
			}
			return null;
		}

		// Sanitize text
		$text = sanitize_text_field( $text );

		// Check length
		if ( strlen( $text ) > $max_length ) {
			throw new \InvalidArgumentException( "Text exceeds maximum length of {$max_length} characters" );
		}

		return $text;
	}

	/**
	 * Validate integer
	 *
	 * @param mixed $value    Value to validate.
	 * @param int   $min      Minimum value (optional).
	 * @param int   $max      Maximum value (optional).
	 * @param bool  $required Whether the field is required (default: true).
	 * @return int|null Validated integer or null if not required and empty.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_integer( $value, $min = null, $max = null, $required = true ) {
		// Check if required
		if ( $value === null || $value === '' ) {
			if ( $required ) {
				throw new \InvalidArgumentException( 'Integer is required' );
			}
			return null;
		}

		// Validate integer
		if ( ! is_numeric( $value ) ) {
			throw new \InvalidArgumentException( 'Value must be an integer' );
		}

		$value = intval( $value );

		// Check min/max
		if ( $min !== null && $value < $min ) {
			throw new \InvalidArgumentException( "Value must be at least {$min}" );
		}

		if ( $max !== null && $value > $max ) {
			throw new \InvalidArgumentException( "Value must be at most {$max}" );
		}

		return $value;
	}

	/**
	 * Validate array
	 *
	 * @param mixed $array        Array to validate.
	 * @param array $allowed_keys Allowed keys (optional, empty = allow all).
	 * @param bool  $required     Whether the field is required (default: true).
	 * @return array|null Validated array or null if not required and empty.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_array( $array, $allowed_keys = array(), $required = true ) {
		// Check if required
		if ( empty( $array ) ) {
			if ( $required ) {
				throw new \InvalidArgumentException( 'Array is required' );
			}
			return null;
		}

		// Validate array type
		if ( ! is_array( $array ) ) {
			throw new \InvalidArgumentException( 'Value must be an array' );
		}

		// Check allowed keys if specified
		if ( ! empty( $allowed_keys ) ) {
			foreach ( array_keys( $array ) as $key ) {
				if ( ! in_array( $key, $allowed_keys, true ) ) {
					throw new \InvalidArgumentException( "Invalid array key: {$key}" );
				}
			}
		}

		// Sanitize array recursively
		return $this->sanitize_array_recursive( $array );
	}

	/**
	 * Validate JSON
	 *
	 * @param string $json     JSON string to validate.
	 * @param bool   $required Whether the field is required (default: true).
	 * @return array|null Decoded JSON array or null if not required and empty.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_json( $json, $required = true ) {
		// Check if required
		if ( empty( $json ) ) {
			if ( $required ) {
				throw new \InvalidArgumentException( 'JSON is required' );
			}
			return null;
		}

		// Decode JSON
		$decoded = json_decode( $json, true );

		// Check for JSON errors
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \InvalidArgumentException( 'Invalid JSON: ' . json_last_error_msg() );
		}

		return $decoded;
	}

	/**
	 * Validate API key
	 *
	 * @param string $key API key to validate.
	 * @return string Validated API key.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_api_key( $key ) {
		if ( empty( $key ) ) {
			throw new \InvalidArgumentException( 'API key is required' );
		}

		// Sanitize
		$key = sanitize_text_field( $key );

		// Check minimum length (20 characters - relaxed from 32)
		if ( strlen( $key ) < 20 ) {
			throw new \InvalidArgumentException( 'API key must be at least 20 characters' );
		}

		// Check format (alphanumeric, underscores, hyphens, and dots - relaxed to allow common safe characters)
		if ( ! preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $key ) ) {
			throw new \InvalidArgumentException( 'API key contains invalid characters (allowed: letters, numbers, _, -, .)' );
		}

		return $key;
	}

	/**
	 * Validate migration token
	 *
	 * @param string $token Migration token to validate.
	 * @return string Validated token.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_token( $token ) {
		if ( empty( $token ) ) {
			throw new \InvalidArgumentException( 'Token is required' );
		}

		// Sanitize
		$token = sanitize_text_field( $token );

		// Check minimum length (64 characters)
		if ( strlen( $token ) < 64 ) {
			throw new \InvalidArgumentException( 'Token must be at least 64 characters' );
		}

		// Check format (alphanumeric only)
		if ( ! preg_match( '/^[a-zA-Z0-9]+$/', $token ) ) {
			throw new \InvalidArgumentException( 'Token contains invalid characters' );
		}

		return $token;
	}

	/**
	 * Validate post ID
	 *
	 * @param int $id Post ID to validate.
	 * @return int Validated post ID.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public function validate_post_id( $id ) {
		// Validate integer
		$id = $this->validate_integer( $id, 1 );

		// Check if post exists
		if ( ! get_post( $id ) ) {
			throw new \InvalidArgumentException( 'Post does not exist' );
		}

		return $id;
	}

	/**
	 * Sanitize array recursively
	 *
	 * @param array $array Array to sanitize.
	 * @return array Sanitized array.
	 */
	public function sanitize_array_recursive( $array ) {
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = $this->sanitize_array_recursive( $value );
			} elseif ( is_string( $value ) ) {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		return $array;
	}

	/**
	 * Validate request data against rules
	 *
	 * Static helper method to validate multiple fields at once.
	 *
	 * @param array $data  Data to validate.
	 * @param array $rules Validation rules (field => [type, required, options]).
	 * @return array Validated data.
	 * @throws \InvalidArgumentException If validation fails.
	 */
	public static function validate_request_data( $data, $rules ) {
		$validator = new self();
		$validated = array();

		foreach ( $rules as $field => $rule ) {
			$type     = $rule['type'] ?? 'text';
			$required = $rule['required'] ?? true;
			$value    = $data[ $field ] ?? null;

			try {
				switch ( $type ) {
					case 'url':
						$validated[ $field ] = $validator->validate_url( $value, $required );
						break;
					case 'text':
						$max_length          = $rule['max_length'] ?? 255;
						$validated[ $field ] = $validator->validate_text( $value, $max_length, $required );
						break;
					case 'integer':
						$min                 = $rule['min'] ?? null;
						$max                 = $rule['max'] ?? null;
						$validated[ $field ] = $validator->validate_integer( $value, $min, $max, $required );
						break;
					case 'array':
						$allowed_keys        = $rule['allowed_keys'] ?? array();
						$validated[ $field ] = $validator->validate_array( $value, $allowed_keys, $required );
						break;
					case 'json':
						$validated[ $field ] = $validator->validate_json( $value, $required );
						break;
					case 'api_key':
						$validated[ $field ] = $validator->validate_api_key( $value );
						break;
					case 'token':
						$validated[ $field ] = $validator->validate_token( $value );
						break;
					case 'post_id':
						$validated[ $field ] = $validator->validate_post_id( $value );
						break;
					default:
						$validated[ $field ] = sanitize_text_field( $value );
				}
			} catch ( \InvalidArgumentException $e ) {
				throw new \InvalidArgumentException( "Field '{$field}': " . $e->getMessage() );
			}
		}

		return $validated;
	}
}

// Backward compatibility alias
class_alias( 'Bricks2Etch\Security\B2E_Input_Validator', 'B2E_Input_Validator' );
