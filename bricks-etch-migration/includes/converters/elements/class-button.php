<?php
/**
 * Button Element Converter
 * 
 * Converts Bricks button elements to Etch link elements
 * 
 * @package Bricks_Etch_Migration
 * @subpackage Converters\Elements
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-element.php';

class B2E_Button_Converter extends B2E_Base_Element {
    
    protected $element_type = 'button';
    
    /**
     * Convert Bricks button to Etch link
     */
    public function convert($element, $children = array()) {
        $settings = $element['settings'] ?? array();
        
        // Extract button data
        $text = $settings['text'] ?? '';
        
        // Extract link URL - handle both array and string formats
        $link = '#';
        if (isset($settings['link'])) {
            if (is_array($settings['link'])) {
                $link = $settings['link']['url'] ?? '#';
            } else {
                $link = $settings['link'];
            }
        }
        
        $style = $settings['style'] ?? 'primary'; // primary, secondary, outline, etc.
        
        // Get style IDs
        $style_ids = $this->get_style_ids($element);
        
        // Get CSS classes
        $css_classes = $this->get_css_classes($style_ids);
        
        // Map .bricks-button to .btn--primary (or other style)
        $button_class = $this->map_button_class($style, $css_classes);
        
        // Generate unique ref for nested link
        $link_ref = $this->generate_ref();
        
        // Build Etch metadata
        $etch_data = array(
            'removeWrapper' => true,
            'block' => array(
                'type' => 'html',
                'tag' => 'p'
            ),
            'origin' => 'etch',
            'nestedData' => array(
                $link_ref => array(
                    'origin' => 'etch',
                    'name' => $text,
                    'styles' => $style_ids,
                    'attributes' => array(
                        'href' => $link,
                        'class' => $button_class
                    ),
                    'block' => array(
                        'type' => 'html',
                        'tag' => 'a'
                    )
                )
            )
        );
        
        // Build block attributes
        $attrs = array(
            'metadata' => array(
                'etchData' => $etch_data
            )
        );
        
        // Convert to JSON
        $attrs_json = json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Build block HTML (return as string, not array!)
        return '<!-- wp:paragraph ' . $attrs_json . ' -->' . "\n" .
               '<p><a data-etch-ref="' . $link_ref . '">' . esc_html($text) . '</a></p>' . "\n" .
               '<!-- /wp:paragraph -->';
    }
    
    /**
     * Map Bricks button style to CSS class
     */
    private function map_button_class($style, $existing_classes) {
        // If style already contains btn--, use it directly
        // (Bricks sometimes stores the full class name as style)
        if (strpos($style, 'btn--') === 0) {
            $button_class = $style;
        } else {
            // Map Bricks styles to btn classes
            $style_map = array(
                'primary' => 'btn--primary',
                'secondary' => 'btn--secondary',
                'outline' => 'btn--outline',
                'text' => 'btn--text',
            );
            
            $button_class = $style_map[$style] ?? 'btn--primary';
        }
        
        // Combine with existing classes
        if (!empty($existing_classes)) {
            return $existing_classes . ' ' . $button_class;
        }
        
        return $button_class;
    }
    
    /**
     * Generate unique reference ID
     */
    private function generate_ref() {
        return substr(md5(uniqid(rand(), true)), 0, 7);
    }
}
