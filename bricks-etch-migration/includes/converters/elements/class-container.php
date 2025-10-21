<?php
/**
 * Container Element Converter
 * 
 * Converts Bricks Container to Etch Container
 * Supports custom tags (ul, ol, etc.)
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-element.php';

class B2E_Element_Container extends B2E_Base_Element {
    
    protected $element_type = 'container';
    
    /**
     * Convert container element
     * 
     * @param array $element Bricks element
     * @param array $children Child elements (already converted HTML)
     * @return string Gutenberg block HTML
     */
    public function convert($element, $children = array()) {
        // Get style IDs
        $style_ids = $this->get_style_ids($element);
        
        // Add default container style
        array_unshift($style_ids, 'etch-container-style');
        
        // Get CSS classes
        $css_classes = $this->get_css_classes($style_ids);
        
        // Get custom tag (e.g., ul, ol)
        $tag = $this->get_tag($element, 'div');
        
        // Get label
        $label = $this->get_label($element);
        
        // Build Etch attributes
        $etch_attributes = array(
            'data-etch-element' => 'container'
        );
        
        if (!empty($css_classes)) {
            $etch_attributes['class'] = $css_classes;
        }
        
        // Build block attributes
        $attrs = $this->build_attributes($label, $style_ids, $etch_attributes, $tag);
        
        // Convert to JSON
        $attrs_json = json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Build children HTML
        $children_html = is_array($children) ? implode("\n", $children) : $children;
        
        // Build block HTML
        return '<!-- wp:group ' . $attrs_json . ' -->' . "\n" .
               '<div class="wp-block-group">' . "\n" .
               $children_html . "\n" .
               '</div>' . "\n" .
               '<!-- /wp:group -->';
    }
}
