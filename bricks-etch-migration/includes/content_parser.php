<?php
/**
 * Content Parser for Bricks to Etch Migration Plugin
 * 
 * Parses Bricks Builder content structure and converts it to processable format
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Content_Parser {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new B2E_Error_Handler();
    }
    
    /**
     * Parse Bricks content structure from post meta (ENHANCED!)
     * 
     * REAL DB STRUCTURE:
     * - post_content is EMPTY!
     * - All content stored in _bricks_page_content_2 (serialized array)
     * - Additional meta: _bricks_template_type, _bricks_editor_mode
     */
    public function parse_bricks_content($post_id) {
        // Check if this is actually a Bricks page
        $template_type = get_post_meta($post_id, '_bricks_template_type', true);
        $editor_mode = get_post_meta($post_id, '_bricks_editor_mode', true);
        
        if ($template_type !== 'content' || $editor_mode !== 'bricks') {
            // Not a Bricks page, skip
            $this->error_handler->log_warning('W001', array(
                'post_id' => $post_id,
                'template_type' => $template_type,
                'editor_mode' => $editor_mode,
                'action' => 'Skipping non-Bricks page'
            ));
            return false;
        }
        
        // Get the actual Bricks content (serialized array)
        $bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);
        
        if (empty($bricks_content)) {
            $this->error_handler->log_error('E003', array(
                'post_id' => $post_id,
                'message' => 'No Bricks content found for this post'
            ));
            return false;
        }
        
        // Handle both serialized string and array
        if (is_string($bricks_content)) {
            $bricks_content = maybe_unserialize($bricks_content);
        }
        
        // Validate it's an array
        if (!is_array($bricks_content)) {
            $this->error_handler->log_error('E101', array(
                'post_id' => $post_id,
                'content_type' => gettype($bricks_content),
                'action' => 'Expected array, got ' . gettype($bricks_content)
            ));
            return false;
        }
        
        return $this->process_bricks_elements($bricks_content, $post_id);
    }
    
    /**
     * Process Bricks elements recursively
     */
    private function process_bricks_elements($elements, $post_id) {
        $processed_elements = array();
        
        foreach ($elements as $element) {
            if (!isset($element['id']) || !isset($element['name'])) {
                continue; // Skip invalid elements
            }
            
            $processed_element = array(
                'id' => $element['id'],
                'name' => $element['name'],
                'parent' => $element['parent'] ?? 0,
                'children' => $element['children'] ?? array(),
                'settings' => $element['settings'] ?? array(),
                'content' => $element['content'] ?? '',
            );
            
            // Process element based on type
            $processed_element = $this->process_element_by_type($processed_element, $post_id);
            
            $processed_elements[] = $processed_element;
        }
        
        return $processed_elements;
    }
    
    /**
     * Process element based on its type
     */
    private function process_element_by_type($element, $post_id) {
        $element_name = $element['name'];
        
        switch ($element_name) {
            case 'section':
                return $this->process_section_element($element, $post_id);
                
            case 'container':
                return $this->process_container_element($element, $post_id);
                
            case 'div':
                return $this->process_div_element($element, $post_id);
                
            case 'heading':
                return $this->process_heading_element($element, $post_id);
                
            case 'text':
                return $this->process_text_element($element, $post_id);
                
            case 'image':
                return $this->process_image_element($element, $post_id);
                
            case 'button':
                return $this->process_button_element($element, $post_id);
                
            case 'video':
            case 'video-iframe':
                return $this->process_video_element($element, $post_id);
                
            case 'iframe':
                return $this->process_iframe_element($element, $post_id);
                
            default:
                // Log unsupported element
                $this->error_handler->log_error('E003', array(
                    'post_id' => $post_id,
                    'element_id' => $element['id'],
                    'element_name' => $element_name,
                    'message' => 'Unsupported Bricks element type'
                ));
                
                // Return as generic element
                return $this->process_generic_element($element, $post_id);
        }
    }
    
    /**
     * Process section element
     */
    private function process_section_element($element, $post_id) {
        $element['etch_type'] = 'section';
        $element['etch_data'] = array(
            'data-etch-element' => 'section',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process container element
     */
    private function process_container_element($element, $post_id) {
        $element['etch_type'] = 'container';
        $element['etch_data'] = array(
            'data-etch-element' => 'container',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process div element
     */
    private function process_div_element($element, $post_id) {
        $settings = get_option('b2e_settings', array());
        $convert_div_to_flex = $settings['convert_div_to_flex'] ?? true;
        
        if ($convert_div_to_flex) {
            $element['etch_type'] = 'flex-div';
            $element['etch_data'] = array(
                'data-etch-element' => 'flex-div',
                'class' => $this->extract_css_classes($element['settings']),
            );
        } else {
            // Skip div elements if conversion is disabled
            $element['etch_type'] = 'skip';
        }
        
        return $element;
    }
    
    /**
     * Process heading element
     */
    private function process_heading_element($element, $post_id) {
        $element['etch_type'] = 'heading';
        $element['etch_data'] = array(
            'level' => $element['settings']['tag'] ?? 'h2',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process text element
     */
    private function process_text_element($element, $post_id) {
        $element['etch_type'] = 'paragraph';
        $element['etch_data'] = array(
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process image element
     */
    private function process_image_element($element, $post_id) {
        $element['etch_type'] = 'image';
        $element['etch_data'] = array(
            'src' => $element['settings']['image']['url'] ?? '',
            'alt' => $element['settings']['image']['alt'] ?? '',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process button element
     */
    private function process_button_element($element, $post_id) {
        $element['etch_type'] = 'button';
        $element['etch_data'] = array(
            'href' => $element['settings']['link']['url'] ?? '#',
            'target' => $element['settings']['link']['target'] ?? '_self',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process video element
     */
    private function process_video_element($element, $post_id) {
        $element['etch_type'] = 'video-iframe';
        $element['etch_data'] = array(
            'src' => $element['settings']['video']['url'] ?? '',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process iframe element
     */
    private function process_iframe_element($element, $post_id) {
        $element['etch_type'] = 'iframe';
        $element['etch_data'] = array(
            'data-etch-element' => 'iframe',
            'src' => $element['settings']['iframe']['url'] ?? '',
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Process generic element (fallback)
     */
    private function process_generic_element($element, $post_id) {
        $element['etch_type'] = 'generic';
        $element['etch_data'] = array(
            'class' => $this->extract_css_classes($element['settings']),
        );
        
        return $element;
    }
    
    /**
     * Extract CSS classes from element settings
     */
    private function extract_css_classes($settings) {
        $classes = array();
        
        // Extract main CSS classes
        if (!empty($settings['_cssClasses'])) {
            $classes[] = $settings['_cssClasses'];
        }
        
        // Extract global CSS classes
        if (!empty($settings['_cssGlobalClasses']) && is_array($settings['_cssGlobalClasses'])) {
            $classes = array_merge($classes, $settings['_cssGlobalClasses']);
        }
        
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Get all Bricks posts
     */
    public function get_bricks_posts() {
        // Get migration settings
        $settings_manager = new B2E_Migration_Settings();
        $settings = $settings_manager->get_settings();
        
        // Determine which post types to migrate
        $post_types = array();
        if (!empty($settings['selected_post_types']) && is_array($settings['selected_post_types'])) {
            $post_types = $settings['selected_post_types'];
        } else {
            // Default: migrate posts and pages
            if ($settings['migrate_posts']) {
                $post_types[] = 'post';
            }
            if ($settings['migrate_pages']) {
                $post_types[] = 'page';
            }
            if ($settings['migrate_cpts']) {
                // Get all custom post types
                $custom_post_types = get_post_types(array('_builtin' => false), 'names');
                $post_types = array_merge($post_types, $custom_post_types);
            }
        }
        
        // If no post types selected, use 'any'
        if (empty($post_types)) {
            $post_types = 'any';
        }
        
        // Determine which post statuses to migrate
        $post_statuses = array('publish');
        if (!empty($settings['selected_post_statuses']) && is_array($settings['selected_post_statuses'])) {
            $post_statuses = $settings['selected_post_statuses'];
        }
        
        // Query for Bricks posts with the key that contains actual content
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => $post_statuses,
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_bricks_page_content_2',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        return $posts;
    }
    
    /**
     * Validate Bricks installation
     */
    public function validate_bricks_installation() {
        $validation_results = array(
            'bricks_active' => false,
            'bricks_posts_found' => 0,
            'bricks_global_classes' => 0,
            'errors' => array(),
        );
        
        // Check if Bricks is active
        if (class_exists('Bricks\Bricks')) {
            $validation_results['bricks_active'] = true;
        } else {
            $validation_results['errors'][] = 'Bricks Builder is not active';
        }
        
        // Count Bricks posts
        $bricks_posts = $this->get_bricks_posts();
        $validation_results['bricks_posts_found'] = count($bricks_posts);
        
        // Count global classes
        $global_classes = get_option('bricks_global_classes', array());
        $validation_results['bricks_global_classes'] = count($global_classes);
        
        return $validation_results;
    }
}
