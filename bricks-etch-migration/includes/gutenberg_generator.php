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
     * Generate Gutenberg blocks from Bricks elements
     */
    public function generate_gutenberg_blocks($bricks_elements) {
        if (empty($bricks_elements) || !is_array($bricks_elements)) {
            return '';
        }
        
        $gutenberg_blocks = array();
        
        foreach ($bricks_elements as $element) {
            $block_html = $this->generate_block_html($element);
            if ($block_html) {
                $gutenberg_blocks[] = $block_html;
            }
        }
        
        return implode("\n", $gutenberg_blocks);
    }
    
    /**
     * Generate HTML for a single block
     */
    private function generate_block_html($element) {
        $etch_type = $element['etch_type'] ?? 'generic';
        
        switch ($etch_type) {
            case 'section':
            case 'container':
            case 'flex-div':
            case 'iframe':
                return $this->generate_etch_group_block($element);
                
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
    private function generate_etch_group_block($element) {
        $etch_data = $element['etch_data'] ?? array();
        $content = $element['content'] ?? '';
        
        // Convert dynamic data in content
        $content = $this->dynamic_data_converter->convert_content($content);
        
        // Extract style IDs
        $style_ids = $this->extract_style_ids($element['settings'] ?? array());
        
        // Build etchData
        $etch_data_array = array(
            'origin' => 'etch',
            'name' => ucfirst($element['etch_type']),
            'styles' => $style_ids,
            'attributes' => $etch_data,
            'block' => array(
                'type' => 'html',
                'tag' => $this->get_html_tag($element['etch_type']),
            ),
        );
        
        // Generate Gutenberg block
        $block_content = $this->generate_block_content($element, $content);
        
        $gutenberg_html = sprintf(
            '<!-- wp:group {"metadata":{"name":"%s","etchData":%s}} -->',
            $etch_data_array['name'],
            json_encode($etch_data_array)
        );
        
        $gutenberg_html .= "\n<div class=\"wp-block-group\">";
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
        $content = $element['content'] ?? '';
        $etch_data = $element['etch_data'] ?? array();
        
        // Convert dynamic data in content
        $content = $this->dynamic_data_converter->convert_content($content);
        
        switch ($etch_type) {
            case 'heading':
                $level = $etch_data['level'] ?? 'h2';
                $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
                
                return sprintf(
                    '<!-- wp:heading {"level":%d} -->',
                    intval(str_replace('h', '', $level))
                ) . "\n" . 
                '<' . $level . $class . '>' . $content . '</' . $level . '>' . "\n" .
                '<!-- /wp:heading -->';
                
            case 'paragraph':
                $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
                
                return '<!-- wp:paragraph -->' . "\n" .
                       '<p' . $class . '>' . $content . '</p>' . "\n" .
                       '<!-- /wp:paragraph -->';
                       
            case 'image':
                $src = $etch_data['src'] ?? '';
                $alt = $etch_data['alt'] ?? '';
                $class = !empty($etch_data['class']) ? ' class="' . esc_attr($etch_data['class']) . '"' : '';
                
                if (empty($src)) {
                    return ''; // Skip images without source
                }
                
                return '<!-- wp:image -->' . "\n" .
                       '<figure class="wp-block-image' . $class . '">' . "\n" .
                       '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" />' . "\n" .
                       '</figure>' . "\n" .
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
    private function generate_block_content($element, $content) {
        // Handle children elements
        if (!empty($element['children']) && is_array($element['children'])) {
            $children_content = array();
            
            foreach ($element['children'] as $child_id) {
                // Find child element (this would need to be passed from parent)
                // For now, just return the content
                $children_content[] = $content;
            }
            
            return implode("\n", $children_content);
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
