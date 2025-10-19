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
     * - All content stored in _bricks_page_content (our test data)
     * - Additional meta: _bricks_template_type, _bricks_editor_mode
     */
    public function parse_bricks_content($post_id) {
        // Get the actual Bricks content
        // Try _bricks_page_content_2 first (newer Bricks versions), then _bricks_page_content
        $bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);
        
        if (empty($bricks_content)) {
            $bricks_content = get_post_meta($post_id, '_bricks_page_content', true);
        }
        
        if (empty($bricks_content) || $bricks_content === '[]' || $bricks_content === array()) {
            // No Bricks content found - this is a normal WordPress post
            // Return false so the migration uses the original post_content
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
        
        $processed_elements = $this->process_bricks_elements($bricks_content, $post_id);
        
        return array(
            'elements' => $processed_elements
        );
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
                'label' => $element['label'] ?? '', // Custom element name from Structure Panel
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
                
            case 'block':
                // Bricks block element (brxe-block) - treat as container
                return $this->process_container_element($element, $post_id);
                
            case 'div':
                return $this->process_div_element($element, $post_id);
                
            case 'heading':
                return $this->process_heading_element($element, $post_id);
                
            case 'text':
            case 'text-basic':
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
                
            case 'gutenberg':
                return $this->process_gutenberg_element($element, $post_id);
                
            case 'code':
                // Code blocks with CSS/JS - store for later processing
                if (!empty($element['settings']['cssCode'])) {
                    // Store CSS code globally for CSS converter to pick up
                    update_option('b2e_inline_css_' . $post_id, $element['settings']['cssCode'], false);
                }
                
                if (!empty($element['settings']['javascriptCode'])) {
                    // Store JavaScript code for Gutenberg generator to pick up
                    $existing_js = get_option('b2e_inline_js_' . $post_id, '');
                    $new_js = $existing_js . "\n" . $element['settings']['javascriptCode'];
                    update_option('b2e_inline_js_' . $post_id, $new_js, false);
                }
                
                // Skip code elements in content (CSS/JS handled separately)
                $element['etch_type'] = 'skip';
                return $element;
                
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
            'content' => $element['settings']['text'] ?? '',
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
            'content' => $element['settings']['text'] ?? '',
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
     * Process Gutenberg element (regular WordPress content)
     */
    private function process_gutenberg_element($element, $post_id) {
        $element['etch_type'] = 'gutenberg';
        $element['etch_data'] = array(
            'content' => $element['settings']['content'] ?? '',
            'is_gutenberg' => true
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
        
        // Extract global CSS classes and convert Bricks IDs to class names
        if (!empty($settings['_cssGlobalClasses']) && is_array($settings['_cssGlobalClasses'])) {
            // Get Bricks global classes
            $bricks_classes = get_option('bricks_global_classes', array());
            
            foreach ($settings['_cssGlobalClasses'] as $bricks_id) {
                // Find the class by ID
                $found_class = null;
                foreach ($bricks_classes as $bricks_class) {
                    if ($bricks_class['id'] === $bricks_id) {
                        $found_class = $bricks_class;
                        break;
                    }
                }
                
                // Use the name if found, otherwise use the ID
                if ($found_class && !empty($found_class['name'])) {
                    $class_name = $found_class['name'];
                } else {
                    $class_name = $bricks_id;
                }
                
                // Remove ACSS import prefix from class names
                $class_name = preg_replace('/^acss_import_/', '', $class_name);
                $classes[] = $class_name;
            }
        }
        
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Get posts that have Bricks content
     * These posts have _bricks_page_content_2 meta data
     */
    public function get_bricks_posts() {
        // Query for posts with Bricks meta data
        $args = array(
            'post_type' => 'any',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_bricks_page_content_2',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        return get_posts($args);
    }
    
    /**
     * Get posts that DON'T have Bricks content (Gutenberg/Classic Editor)
     * These are posts without _bricks_page_content_2 meta data
     */
    public function get_gutenberg_posts() {
        // Query for posts WITHOUT Bricks meta data
        $args = array(
            'post_type' => array('post', 'page'), // Only posts and pages, not CPTs
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_bricks_page_content_2',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        
        return get_posts($args);
    }
    
    /**
     * Get all media/attachments
     */
    public function get_media() {
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => -1
        );
        
        return get_posts($args);
    }
    
    /**
     * Get ALL content (Bricks + Gutenberg + Media)
     * Used for total count
     */
    public function get_all_content() {
        $bricks_posts = $this->get_bricks_posts();
        $gutenberg_posts = $this->get_gutenberg_posts();
        $media = $this->get_media();
        
        return array_merge($bricks_posts, $gutenberg_posts, $media);
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
    
    /**
     * Convert Bricks elements to Etch-compatible format (REAL CONVERSION!)
     */
    public function convert_to_etch_format($bricks_content) {
        if (empty($bricks_content) || !is_array($bricks_content)) {
            return false;
        }
        
        $etch_content = array();
        
        // Handle both formats:
        // 1. Direct array of elements (from _bricks_page_content_2)
        // 2. Nested structure with ['elements'] key
        $elements = isset($bricks_content['elements']) ? $bricks_content['elements'] : $bricks_content;
        
        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }
            
            $converted_element = $this->convert_bricks_element($element);
            
            if ($converted_element) {
                $etch_content[] = $converted_element;
            }
        }
        
        return $etch_content;
    }
    
    /**
     * Convert individual Bricks element to Etch format
     */
    private function convert_bricks_element($element) {
        if (empty($element['name'])) {
            return false;
        }
        
        $element_type = $element['name'];
        $settings = $element['settings'] ?? array();
        
        // Convert based on element type
        switch ($element_type) {
            case 'text':
                return $this->convert_text_element($settings);
                
            case 'heading':
                return $this->convert_heading_element($settings);
                
            case 'container':
                return $this->convert_container_element($element);
                
            case 'section':
                return $this->convert_section_element($element);
                
            case 'div':
                return $this->convert_div_element($element);
                
            case 'button':
                return $this->convert_button_element($settings);
                
            case 'image':
                return $this->convert_image_element($settings);
                
            case 'gutenberg':
                return $this->convert_gutenberg_element($settings);
                
            default:
                // Log unsupported element
                $this->error_handler->log_error('E003', array(
                    'element_type' => $element_type,
                    'element_id' => $element['id'] ?? 'unknown',
                    'action' => 'Unsupported Bricks Element - Bricks-specific element cannot be automatically migrated'
                ));
                return false;
        }
    }
    
    /**
     * Convert Bricks text element to Gutenberg paragraph
     */
    private function convert_text_element($settings) {
        $text = $settings['text'] ?? '';
        
        if (empty($text)) {
            return false;
        }
        
        // Generate proper Gutenberg block HTML
        $block_html = '<!-- wp:paragraph -->' . "\n";
        $block_html .= '<p>' . wp_kses_post($text) . '</p>' . "\n";
        $block_html .= '<!-- /wp:paragraph -->';
        
        return array(
            'blockName' => 'core/paragraph',
            'attrs' => array(),
            'innerHTML' => $block_html,
            'innerContent' => array($block_html)
        );
    }
    
    /**
     * Convert Bricks heading element to Gutenberg heading
     */
    private function convert_heading_element($settings) {
        $text = $settings['text'] ?? '';
        $tag = $settings['tag'] ?? 'h2';
        $level = intval(str_replace('h', '', $tag));
        
        if (empty($text)) {
            return false;
        }
        
        // Generate proper Gutenberg block HTML
        $block_html = '<!-- wp:heading {"level":' . $level . '} -->' . "\n";
        $block_html .= '<' . $tag . '>' . wp_kses_post($text) . '</' . $tag . '>' . "\n";
        $block_html .= '<!-- /wp:heading -->';
        
        return array(
            'blockName' => 'core/heading',
            'attrs' => array(
                'level' => $level
            ),
            'innerHTML' => $block_html,
            'innerContent' => array($block_html)
        );
    }
    
    /**
     * Convert Bricks container element to Etch group
     */
    private function convert_container_element($element) {
        $settings = $element['settings'] ?? array();
        $children = $element['children'] ?? array();
        
        $inner_blocks = array();
        
        // Convert children recursively
        foreach ($children as $child) {
            $converted_child = $this->convert_bricks_element($child);
            if ($converted_child) {
                $inner_blocks[] = $converted_child;
            }
        }
        
        // Build container attributes
        $attrs = array();
        
        // Handle background color
        if (isset($settings['background']['color'])) {
            $attrs['backgroundColor'] = $settings['background']['color'];
        }
        
        return array(
            'blockName' => 'core/group',
            'attrs' => $attrs,
            'innerBlocks' => $inner_blocks,
            'innerHTML' => '',
            'innerContent' => array()
        );
    }
    
    /**
     * Convert Bricks section element to Etch cover
     */
    private function convert_section_element($element) {
        $settings = $element['settings'] ?? array();
        $children = $element['children'] ?? array();
        
        $inner_blocks = array();
        
        // Convert children recursively
        foreach ($children as $child) {
            $converted_child = $this->convert_bricks_element($child);
            if ($converted_child) {
                $inner_blocks[] = $converted_child;
            }
        }
        
        return array(
            'blockName' => 'core/cover',
            'attrs' => array(),
            'innerBlocks' => $inner_blocks,
            'innerHTML' => '',
            'innerContent' => array()
        );
    }
    
    /**
     * Convert Bricks div element to Etch group
     */
    private function convert_div_element($element) {
        return $this->convert_container_element($element);
    }
    
    /**
     * Convert Bricks button element to Etch button
     */
    private function convert_button_element($settings) {
        $text = $settings['text'] ?? 'Button';
        $link = $settings['link'] ?? '#';
        
        return array(
            'blockName' => 'core/button',
            'attrs' => array(
                'url' => esc_url($link)
            ),
            'innerHTML' => '<div class="wp-block-button"><a class="wp-block-button__link" href="' . esc_url($link) . '">' . wp_kses_post($text) . '</a></div>',
            'innerContent' => array('<div class="wp-block-button"><a class="wp-block-button__link" href="' . esc_url($link) . '">' . wp_kses_post($text) . '</a></div>')
        );
    }
    
    /**
     * Convert Bricks image element to Etch image
     */
    private function convert_image_element($settings) {
        $src = $settings['image']['url'] ?? '';
        $alt = $settings['image']['alt'] ?? '';
        
        if (empty($src)) {
            return false;
        }
        
        return array(
            'blockName' => 'core/image',
            'attrs' => array(
                'url' => esc_url($src),
                'alt' => sanitize_text_field($alt)
            ),
            'innerHTML' => '<figure class="wp-block-image"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '"/></figure>',
            'innerContent' => array('<figure class="wp-block-image"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '"/></figure>')
        );
    }
    
    /**
     * Convert Gutenberg element (just return the content as-is)
     */
    private function convert_gutenberg_element($settings) {
        $content = $settings['content'] ?? '';
        
        if (empty($content)) {
            return false;
        }
        
        // Gutenberg content is already in the right format, just return it
        return array(
            'blockName' => 'core/html',
            'attrs' => array(),
            'innerHTML' => $content,
            'innerContent' => array($content)
        );
    }
}
