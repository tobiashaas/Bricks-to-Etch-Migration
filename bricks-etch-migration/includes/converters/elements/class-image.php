<?php
/**
 * Image Element Converter
 * 
 * Converts Bricks Image to Gutenberg Image with Etch metadata
 * IMPORTANT: Uses 'figure' tag, not 'img'!
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-element.php';

class B2E_Element_Image extends B2E_Base_Element {
    
    protected $element_type = 'image';
    
    /**
     * Convert image element
     * 
     * @param array $element Bricks element
     * @param array $children Not used for images
     * @return string Gutenberg block HTML
     */
    public function convert($element, $children = array()) {
        // Get style IDs
        $style_ids = $this->get_style_ids($element);
        
        // Get CSS classes
        $css_classes = $this->get_css_classes($style_ids);
        
        // Get label
        $label = $this->get_label($element);
        
        // Get image data
        $image_id = $element['settings']['image']['id'] ?? 0;
        $image_url = $element['settings']['image']['url'] ?? '';
        $alt_text = $element['settings']['alt'] ?? '';
        
        // Build Etch attributes
        $etch_attributes = array();
        
        if (!empty($css_classes)) {
            $etch_attributes['class'] = $css_classes;
        }
        
        // IMPORTANT: Use 'figure' tag, not 'img'!
        $tag = 'figure';
        
        // Build block attributes
        $attrs = $this->build_attributes($label, $style_ids, $etch_attributes, $tag);
        
        // Add image-specific attributes
        if ($image_id) {
            $attrs['id'] = $image_id;
        }
        
        // Convert to JSON
        $attrs_json = json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Build block HTML
        $html = '<!-- wp:image ' . $attrs_json . ' -->' . "\n";
        $html .= '<figure class="wp-block-image">';
        $html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt_text) . '"';
        if ($image_id) {
            $html .= ' class="wp-image-' . $image_id . '"';
        }
        $html .= ' />';
        $html .= '</figure>' . "\n";
        $html .= '<!-- /wp:image -->';
        
        return $html;
    }
}
