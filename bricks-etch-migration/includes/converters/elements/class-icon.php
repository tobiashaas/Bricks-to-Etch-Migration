<?php
/**
 * Icon Element Converter
 * 
 * Converts Bricks icon elements to Etch icon elements
 * 
 * @package Bricks_Etch_Migration
 * @subpackage Converters\Elements
 * @since 0.5.0
 */

namespace Bricks2Etch\Converters\Elements;

use Bricks2Etch\Converters\B2E_Base_Element;

if (!defined('ABSPATH')) {
    exit;
}

class B2E_Icon_Converter extends B2E_Base_Element {
    
    protected $element_type = 'icon';
    
    /**
     * Convert Bricks icon to Etch icon
     * 
     * TODO: Implement icon conversion
     * - Extract icon library (FontAwesome, etc.)
     * - Extract icon name
     * - Map to Etch icon format
     * - Handle icon size, color, etc.
     */
    public function convert($element, $children = array()) {
        $settings = $element['settings'] ?? array();
        
        // TODO: Extract icon data
        $icon = $settings['icon'] ?? array();
        $icon_library = $icon['library'] ?? 'fontawesome';
        $icon_value = $icon['value'] ?? '';
        
        // Get style IDs
        $style_ids = $this->get_style_ids($element);
        
        // Get CSS classes
        $css_classes = $this->get_css_classes($style_ids);
        
        // Get label
        $label = $this->get_label($element);
        
        // TODO: Implement icon conversion
        // For now, return a placeholder paragraph
        error_log('⚠️ B2E Icon: Icon conversion not yet implemented - ' . $icon_library . ':' . $icon_value);
        
        // Placeholder: Return empty paragraph
        $attrs = array(
            'metadata' => array(
                'name' => $label ?: 'Icon (TODO)',
                'etchData' => array(
                    'origin' => 'etch',
                    'block' => array(
                        'type' => 'html',
                        'tag' => 'p'
                    )
                )
            )
        );
        
        $attrs_json = json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        return '<!-- wp:paragraph ' . $attrs_json . ' -->' . "\n" .
               '<p>[Icon: ' . esc_html($icon_library . ':' . $icon_value) . ']</p>' . "\n" .
               '<!-- /wp:html -->';
    }
}

\class_alias(__NAMESPACE__ . '\\B2E_Icon_Converter', 'B2E_Icon_Converter');
