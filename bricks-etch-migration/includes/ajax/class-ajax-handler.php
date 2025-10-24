<?php
/**
 * Main AJAX Handler
 *
 * Initializes all AJAX handlers
 *
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

namespace Bricks2Etch\Ajax;

use Bricks2Etch\Ajax\Handlers\B2E_CSS_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\B2E_Content_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\B2E_Media_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\B2E_Validation_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\B2E_Logs_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\B2E_Connection_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\B2E_Cleanup_Ajax_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class B2E_Ajax_Handler {

	/**
	 * Handler instances
	 */
	private $handlers = array();

	/**
	 * Constructor
	 */
	public function __construct(
		B2E_CSS_Ajax_Handler $css_handler,
		B2E_Content_Ajax_Handler $content_handler,
		B2E_Media_Ajax_Handler $media_handler,
		B2E_Validation_Ajax_Handler $validation_handler,
		B2E_Logs_Ajax_Handler $logs_handler,
		B2E_Connection_Ajax_Handler $connection_handler,
		B2E_Cleanup_Ajax_Handler $cleanup_handler
	) {
		$this->init_handlers(
			$css_handler,
			$content_handler,
			$media_handler,
			$validation_handler,
			$logs_handler,
			$connection_handler,
			$cleanup_handler
		);
	}

	/**
	 * Initialize all handlers
	 */
	private function init_handlers(
		B2E_CSS_Ajax_Handler $css_handler,
		B2E_Content_Ajax_Handler $content_handler,
		B2E_Media_Ajax_Handler $media_handler,
		B2E_Validation_Ajax_Handler $validation_handler,
		B2E_Logs_Ajax_Handler $logs_handler,
		B2E_Connection_Ajax_Handler $connection_handler,
		B2E_Cleanup_Ajax_Handler $cleanup_handler
	) {
		$this->handlers['css']        = $css_handler;
		$this->handlers['content']    = $content_handler;
		$this->handlers['media']      = $media_handler;
		$this->handlers['validation'] = $validation_handler;
		$this->handlers['logs']       = $logs_handler;
		$this->handlers['connection'] = $connection_handler;
		$this->handlers['cleanup']    = $cleanup_handler;
	}

	/**
	 * Get handler instance
	 *
	 * @param string $type Handler type
	 * @return object|null Handler instance
	 */
	public function get_handler( $type ) {
		return $this->handlers[ $type ] ?? null;
	}
}

\class_alias( __NAMESPACE__ . '\\B2E_Ajax_Handler', 'B2E_Ajax_Handler' );
