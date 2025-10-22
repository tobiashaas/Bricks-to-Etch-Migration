<?php
/**
 * Main AJAX Handler
 * 
 * Initializes all AJAX handlers
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

if (!defined('ABSPATH')) {
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
    public function __construct() {
        $this->load_handlers();
        $this->init_handlers();
    }
    
    /**
     * Load all handler classes
     */
    private function load_handlers() {
        $handlers_dir = dirname(__FILE__) . '/handlers/';
        
        require_once dirname(__FILE__) . '/class-base-ajax-handler.php';
        require_once $handlers_dir . 'class-css-ajax.php';
        require_once $handlers_dir . 'class-content-ajax.php';
        require_once $handlers_dir . 'class-media-ajax.php';
        require_once $handlers_dir . 'class-validation-ajax.php';
    }
    
    /**
     * Initialize all handlers
     */
    private function init_handlers() {
        $this->handlers['css'] = new B2E_CSS_Ajax_Handler();
        $this->handlers['content'] = new B2E_Content_Ajax_Handler();
        $this->handlers['media'] = new B2E_Media_Ajax_Handler();
        $this->handlers['validation'] = new B2E_Validation_Ajax_Handler();
    }
    
    /**
     * Get handler instance
     * 
     * @param string $type Handler type
     * @return object|null Handler instance
     */
    public function get_handler($type) {
        return $this->handlers[$type] ?? null;
    }
}
