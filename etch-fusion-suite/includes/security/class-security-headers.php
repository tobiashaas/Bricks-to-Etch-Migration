<?php
/**
 * Security Headers
 *
 * Adds security headers to HTTP responses including CSP, X-Frame-Options, etc.
 * Protects against XSS, clickjacking, and other common web vulnerabilities.
 *
 * @package    Bricks2Etch
 * @subpackage Security
 * @since      0.5.0
 */

namespace Bricks2Etch\Security;

/**
 * Security Headers Class
 *
 * Manages HTTP security headers with environment-aware CSP policies.
 */
class EFS_Security_Headers {

	/**
	 * Add security headers to response
	 *
	 * Sets all security headers via header() function.
	 * Called via WordPress send_headers action.
	 *
	 * @return void
	 */
	public function add_security_headers() {
		// Don't add headers if we shouldn't
		if ( ! $this->should_add_headers() ) {
			return;
		}

		// X-Frame-Options: Prevent clickjacking
		header( 'X-Frame-Options: SAMEORIGIN' );

		// X-Content-Type-Options: Prevent MIME sniffing
		header( 'X-Content-Type-Options: nosniff' );

		// X-XSS-Protection: Enable XSS filter
		header( 'X-XSS-Protection: 1; mode=block' );

		// Referrer-Policy: Control referrer information
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );

		// Permissions-Policy: Disable unnecessary browser features
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );

		// Content-Security-Policy: Comprehensive XSS protection
		$csp = $this->get_csp_policy();
		if ( ! empty( $csp ) ) {
			header( 'Content-Security-Policy: ' . $csp );
		}
	}

	/**
	 * Get Content Security Policy
	 *
	 * Returns CSP string based on current page context.
	 * Uses relaxed policy for admin pages and frontend to accommodate WordPress behavior.
	 *
	 * @return string CSP policy string.
	 */
	public function get_csp_policy() {
		// Use relaxed policy for admin pages (WordPress admin requires inline scripts)
		if ( $this->is_admin_page() ) {
			return "default-src 'self'; " .
					"script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
					"style-src 'self' 'unsafe-inline'; " .
					"img-src 'self' data: https:; " .
					"font-src 'self' data:; " .
					"connect-src 'self'";
		}

		// Relaxed policy for frontend (WordPress themes often use inline scripts/styles)
		return "default-src 'self'; " .
				"script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
				"style-src 'self' 'unsafe-inline'; " .
				"img-src 'self' data: https:; " .
				"font-src 'self' data:; " .
				"connect-src 'self'";
	}

	/**
	 * Check if current request is admin page
	 *
	 * @return bool True if admin page, false otherwise.
	 */
	public function is_admin_page() {
		// Check if we're in admin area
		if ( is_admin() ) {
			return true;
		}

		// Check if this is an AJAX request from admin
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Check referer to determine if it's from admin
			$referer = wp_get_referer();
			if ( $referer && strpos( $referer, admin_url() ) === 0 ) {
				return true;
			}
		}

		// Check request URI
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $request_uri, '/wp-admin/' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if headers should be added
	 *
	 * Determines if security headers should be added to current request.
	 *
	 * @return bool True if headers should be added, false otherwise.
	 */
	public function should_add_headers() {
		// Don't add headers if already sent
		if ( headers_sent() ) {
			return false;
		}

		// Don't add headers for wp-login.php (can interfere with login)
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( strpos( $request_uri, 'wp-login.php' ) !== false ) {
			return false;
		}

		// Don't add headers for wp-cron.php
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false;
		}

		// Don't add headers for REST API OPTIONS requests (handled by CORS)
		if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
			return false;
		}

		return true;
	}
}
