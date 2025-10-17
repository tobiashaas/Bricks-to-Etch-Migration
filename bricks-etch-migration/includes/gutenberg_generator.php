<?php
/**
 * Gutenberg Generator for Bricks to Etch Migration Plugin
 * 
 * Generates Etch-compatible Gutenberg blocks from Bricks elements
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Gutenberg_Generator {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Dynamic data converter instance
     */
    private $dynamic_data_converter;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new B2E_Error_Handler();
        $this->dynamic_data_converter = new B2E_Dynamic_Data_Converter();
    }
    
    /**
     * Generate Gutenberg blocks from Bricks elements (REAL CONVERSION!)
     */
    public function generate_gutenberg_blocks($bricks_elements) {
        if (empty($bricks_elements) || !is_array($bricks_elements)) {
            return '';
        }
        
        // Build element lookup map (id => element)
        $element_map = array();
        foreach ($bricks_elements as $element) {
            $element_map[$element['id']] = $element;
        }
        
        // Find top-level elements (parent = 0 or parent not in map)
        $top_level_elements = array();
        foreach ($bricks_elements as $element) {
            $parent_id = $element['parent'] ?? 0;
            if ($parent_id === 0 || $parent_id === '0' || !isset($element_map[$parent_id])) {
                $top_level_elements[] = $element;
            }
        }
        
        // Generate blocks for top-level elements (recursively includes children)
        $gutenberg_blocks = array();
        foreach ($top_level_elements as $element) {
            $block_html = $this->generate_block_html($element, $element_map);
            if ($block_html) {
                $gutenberg_blocks[] = $block_html;
            }
        }
        
        return implode("\n", $gutenberg_blocks);
    }
    
    /**
     * Convert Bricks to Gutenberg and save to database (FOR ECH PROCESSING!)
     */
    public function convert_bricks_to_gutenberg($post) {
        if (empty($post)) {
            return false;
        }
        
        // Get Bricks content
        $bricks_content = get_post_meta($post->ID, '_bricks_page_content', true);
        
        if (empty($bricks_content)) {
            $this->error_handler->log_error('I020', array(
                'post_id' => $post->ID,
                'action' => 'No Bricks content found for conversion'
            ));
            return false;
        }
        
        // Parse and convert Bricks elements to Gutenberg format
        $content_parser = new B2E_Content_Parser();
        $parsed_elements = $content_parser->convert_to_etch_format($bricks_content);
        
        if (empty($parsed_elements)) {
            $this->error_handler->log_error('I021', array(
                'post_id' => $post->ID,
                'action' => 'Failed to parse Bricks elements'
            ));
            return false;
        }
        
        // Generate Gutenberg blocks HTML
        $gutenberg_content = $this->generate_gutenberg_blocks($parsed_elements);
        
        if (empty($gutenberg_content)) {
            $this->error_handler->log_error('I022', array(
                'post_id' => $post->ID,
                'action' => 'Failed to generate Gutenberg blocks'
            ));
            return false;
        }
        
        // Save Gutenberg content to database (Etch will process it automatically)
        $update_result = wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $gutenberg_content
        ));
        
        if (is_wp_error($update_result)) {
            $this->error_handler->log_error('I024', array(
                'post_id' => $post->ID,
                'error' => $update_result->get_error_message(),
                'action' => 'Failed to save Gutenberg content to database'
            ));
            return false;
        }
        
        // Remove Bricks meta (cleanup)
        delete_post_meta($post->ID, '_bricks_page_content');
        delete_post_meta($post->ID, '_bricks_page_settings');
        
        // Log successful conversion and database save
        $this->error_handler->log_error('I023', array(
            'post_id' => $post->ID,
            'post_title' => $post->post_title,
            'elements_converted' => count($parsed_elements),
            'gutenberg_length' => strlen($gutenberg_content),
            'database_updated' => true,
            'bricks_meta_cleaned' => true,
            'action' => 'Bricks converted to Gutenberg and saved to database - Etch will process automatically'
        ));
        
        return $gutenberg_content;
    }
    
    /**
     * Generate HTML for a single block
     */
    private function generate_block_html($element, $element_map = array()) {
        $etch_type = $element['etch_type'] ?? 'generic';
        
        switch ($etch_type) {
            case 'section':
            case 'container':
            case 'flex-div':
            case 'iframe':
                return $this->generate_etch_group_block($element, $element_map);
                
            case 'heading':
            case 'paragraph':
            case 'image':
            case 'button':
                return $this->generate_standard_block($element);
                
            case 'skip':
                return ''; // Skip this element
                
            default:
                return $this->generate_generic_block($element);
        }
    }
    
    /**
     * Generate Etch group block (wp:group with etchData)
     */
    private function generate_etch_group_block($element, $element_map = array()) {
        $etch_data = $element['etch_data'] ?? array();
        $content = $element['content'] ?? '';
        
        // Convert dynamic data in content
        $content = $this->dynamic_data_converter->convert_content($content);
        
        // Extract style IDs
        $style_ids = $this->extract_style_ids($element['settings'] ?? array());
        
        // Use custom label if available, otherwise use element type
        $element_name = !empty($element['label']) ? $element['label'] : ucfirst($element['etch_type']);
        
        // Build etchData
        $etch_data_array = array(
            'origin' => 'etch',
            'name' => $element_name,
            'styles' => $style_ids,
            'attributes' => $etch_data,
            'block' => array(
                'type' => 'html',
                'tag' => $this->get_html_tag($element['etch_type']),
            ),
        );
        
        // Generate Gutenberg block
        $block_content = $this->generate_block_content($element, $content, $element_map);
        
        // Build block attributes
        $block_attrs = array(
            'metadata' => array(
                'name' => $etch_data_array['name'],
                'etchData' => $etch_data_array
            )
        );
        
        // Add className for Gutenberg (in addition to HTML class attribute)
        if (!empty($etch_data['class'])) {
            $block_attrs['className'] = $etch_data['class'];
        }
        
        // Add tagName for non-div elements (section, article, etc.)
        $html_tag = $this->get_html_tag($element['etch_type']);
        if ($html_tag !== 'div') {
            $block_attrs['tagName'] = $html_tag;
        }
        
        $gutenberg_html = sprintf(
            '<!-- wp:group %s -->',
            json_encode($block_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        
        // Add classes to the HTML element
        $classes = array('wp-block-group');
        if (!empty($etch_data['class'])) {
            $classes[] = $etch_data['class'];
        }
        $class_attr = ' class="' . esc_attr(implode(' ', $classes)) . '"';
        
        $gutenberg_html .= "\n<div" . $class_attr . ">";
        $gutenberg_html .= "\n" . $block_content;
        $gutenberg_html .= "\n</div>";
        $gutenberg_html .= "\n<!-- /wp:group -->";
        
        return $gutenberg_html;
    }
    
    /**
     * Generate standard Gutenberg block
     */
    private function generate_standard_block($element) {
        $etch_type = $element['etch_type'];
        $etch_data = $element['etch_data'] ?? array();
        
        // Get content from etch_data or element
        $content = $etch_data['content'] ?? $element['content'] ?? '';
        
        // Convert dynamic data in content
        $content = $this->dynamic_data_converter->convert_content($content);
        
        switch ($etch_type) {
            case 'heading':
                $level = $etch_data['level'] ?? 'h2';
                $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
                
                // Build block attributes
                $block_attrs = array('level' => intval(str_replace('h', '', $level)));
                if (!empty($etch_data['class'])) {
                    $block_attrs['className'] = $etch_data['class'];
                }
                
                return sprintf(
                    '<!-- wp:heading %s -->',
                    json_encode($block_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ) . "\n" . 
                '<' . $level . $class . '>' . $content . '</' . $level . '>' . "\n" .
                '<!-- /wp:heading -->';
                
            case 'paragraph':
                $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
                
                // Build block attributes
                $block_attrs = array();
                if (!empty($etch_data['class'])) {
                    $block_attrs['className'] = $etch_data['class'];
                }
                
                $attrs_json = !empty($block_attrs) ? ' ' . json_encode($block_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
                
                return '<!-- wp:paragraph' . $attrs_json . ' -->' . "\n" .
                       '<p' . $class . '>' . $content . '</p>' . "\n" .
                       '<!-- /wp:paragraph -->';
                       
            case 'image':
                $src = $etch_data['src'] ?? '';
                $alt = $etch_data['alt'] ?? '';
                $class = !empty($etch_data['class']) ? ' ' . esc_attr($etch_data['class']) : '';
                
                if (empty($src)) {
                    return ''; // Skip images without source
                }
                
                // Gutenberg expects inline HTML for images (no line breaks)
                return '<!-- wp:image -->' . "\n" .
                       '<figure class="wp-block-image' . $class . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '"/></figure>' . "\n" .
                       '<!-- /wp:image -->';
                       
            case 'button':
                $href = $etch_data['href'] ?? '#';
                $target = $etch_data['target'] ?? '_self';
                $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
                
                return '<!-- wp:buttons -->' . "\n" .
                       '<div class="wp-block-buttons">' . "\n" .
                       '<!-- wp:button -->' . "\n" .
                       '<div class="wp-block-button' . $class . '">' . "\n" .
                       '<a class="wp-block-button__link" href="' . esc_url($href) . '" target="' . esc_attr($target) . '">' . $content . '</a>' . "\n" .
                       '</div>' . "\n" .
                       '<!-- /wp:button -->' . "\n" .
                       '</div>' . "\n" .
                       '<!-- /wp:buttons -->';
                       
            default:
                return $this->generate_generic_block($element);
        }
    }
    
    /**
     * Generate generic block (fallback)
     */
    private function generate_generic_block($element) {
        $content = $element['content'] ?? '';
        $etch_data = $element['etch_data'] ?? array();
        
        // Convert dynamic data in content
        $content = $this->dynamic_data_converter->convert_content($content);
        
        $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
        
        return '<!-- wp:html -->' . "\n" .
               '<div' . $class . '>' . $content . '</div>' . "\n" .
               '<!-- /wp:html -->';
    }
    
    /**
     * Generate block content
     */
    private function generate_block_content($element, $content, $element_map = array()) {
        // Handle children elements
        if (!empty($element['children']) && is_array($element['children']) && !empty($element_map)) {
            $children_blocks = array();
            
            foreach ($element['children'] as $child_id) {
                // Find child element in map
                if (isset($element_map[$child_id])) {
                    $child_element = $element_map[$child_id];
                    $child_html = $this->generate_block_html($child_element, $element_map);
                    if ($child_html) {
                        $children_blocks[] = $child_html;
                    }
                }
            }
            
            return implode("\n", $children_blocks);
        }
        
        return $content;
    }
    
    /**
     * Extract style IDs from element settings
     */
    private function extract_style_ids($settings) {
        $style_ids = array();
        
        // Add Etch element style
        $etch_type = $settings['etch_type'] ?? '';
        if ($etch_type) {
            $style_ids[] = 'etch-' . $etch_type . '-style';
        }
        
        // Extract CSS classes and convert to style IDs
        if (!empty($settings['_cssClasses'])) {
            $style_ids[] = $this->generate_style_hash($settings['_cssClasses']);
        }
        
        if (!empty($settings['_cssGlobalClasses']) && is_array($settings['_cssGlobalClasses'])) {
            foreach ($settings['_cssGlobalClasses'] as $global_class) {
                $style_ids[] = $this->generate_style_hash($global_class);
            }
        }
        
        return array_unique($style_ids);
    }
    
    /**
     * Generate 7-character hash ID for styles
     */
    private function generate_style_hash($class_name) {
        return substr(md5($class_name), 0, 7);
    }
    
    /**
     * Get HTML tag for Etch element type
     */
    private function get_html_tag($etch_type) {
        switch ($etch_type) {
            case 'section':
                return 'section';
            case 'container':
                return 'div';
            case 'flex-div':
                return 'div';
            case 'iframe':
                return 'iframe';
            default:
                return 'div';
        }
    }
    
    /**
     * Process element by type (called from Content Parser)
     */
    public function process_element_by_type($element, $post_id) {
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
     * Process generic element
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
        
        if (!empty($settings['_cssClasses'])) {
            $classes[] = $settings['_cssClasses'];
        }
        
        if (!empty($settings['_cssGlobalClasses']) && is_array($settings['_cssGlobalClasses'])) {
            $classes = array_merge($classes, $settings['_cssGlobalClasses']);
        }
        
        return implode(' ', array_filter($classes));
    }
}
