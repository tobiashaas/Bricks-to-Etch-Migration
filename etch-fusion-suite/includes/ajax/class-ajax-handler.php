<?php
/**
 * Main AJAX Handler
 *
 * Initializes all AJAX handlers
 *
 * @package Etch_Fusion_Suite
 * @since 0.5.1
 */

namespace Bricks2Etch\Ajax;

use Bricks2Etch\Ajax\Handlers\EFS_CSS_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Content_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Media_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Validation_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Logs_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Connection_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Cleanup_Ajax_Handler;
use Bricks2Etch\Ajax\Handlers\EFS_Template_Ajax_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EFS_Ajax_Handler {

	/**
	 * Handler instances
	 */
	private $handlers = array();

	/**
	 * Constructor
	 */
	public function __construct(
		EFS_CSS_Ajax_Handler $css_handler,
		EFS_Content_Ajax_Handler $content_handler,
		EFS_Media_Ajax_Handler $media_handler,
		EFS_Validation_Ajax_Handler $validation_handler,
		EFS_Logs_Ajax_Handler $logs_handler,
		EFS_Connection_Ajax_Handler $connection_handler,
		EFS_Cleanup_Ajax_Handler $cleanup_handler,
		EFS_Template_Ajax_Handler $template_handler
	) {
		$this->init_handlers(
			$css_handler,
			$content_handler,
			$media_handler,
			$validation_handler,
			$logs_handler,
			$connection_handler,
			$cleanup_handler,
			$template_handler
		);
	}

	/**
	 * Initialize all handlers
	 */
	private function init_handlers(
		EFS_CSS_Ajax_Handler $css_handler,
		EFS_Content_Ajax_Handler $content_handler,
		EFS_Media_Ajax_Handler $media_handler,
		EFS_Validation_Ajax_Handler $validation_handler,
		EFS_Logs_Ajax_Handler $logs_handler,
		EFS_Connection_Ajax_Handler $connection_handler,
		EFS_Cleanup_Ajax_Handler $cleanup_handler,
		EFS_Template_Ajax_Handler $template_handler
	) {
		$this->handlers['css']        = $css_handler;
		$this->handlers['content']    = $content_handler;
		$this->handlers['media']      = $media_handler;
		$this->handlers['validation'] = $validation_handler;
		$this->handlers['logs']       = $logs_handler;
		$this->handlers['connection'] = $connection_handler;
		$this->handlers['cleanup']    = $cleanup_handler;
		$this->handlers['template']   = $template_handler;
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

// Legacy alias for backward compatibility
\class_alias( __NAMESPACE__ . '\\EFS_Ajax_Handler', 'B2E_Ajax_Handler' );
class_alias( __NAMESPACE__ . '\EFS_Ajax_Handler', __NAMESPACE__ . '\B2E_Ajax_Handler' );
