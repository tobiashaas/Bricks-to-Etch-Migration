<?php
/**
 * Element Factory
 * 
 * Creates the appropriate element converter based on element type
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class B2E_Element_Factory {
    
    /**
     * Style map
     */
    private $style_map;
    
    /**
     * Element converters cache
     */
    private $converters = array();
    
    /**
     * Constructor
     * 
     * @param array $style_map Style map for CSS classes
     */
    public function __construct($style_map = array()) {
        $this->style_map = $style_map;
        $this->load_converters();
    }
    
    /**
     * Load all element converters
     */
    private function load_converters() {
        $elements_dir = dirname(__FILE__) . '/elements/';
        
        // Load base element
        require_once dirname(__FILE__) . '/class-base-element.php';
        
        // Load all element converters
        require_once $elements_dir . 'class-container.php';
        require_once $elements_dir . 'class-section.php';
        require_once $elements_dir . 'class-heading.php';
        require_once $elements_dir . 'class-paragraph.php';
        require_once $elements_dir . 'class-image.php';
        require_once $elements_dir . 'class-div.php';
    }
    
    /**
     * Get converter for element type
     * 
     * @param string $element_type Bricks element type
     * @return B2E_Base_Element|null Element converter
     */
    public function get_converter($element_type) {
        // Map Bricks element types to converter classes
        $type_map = array(
            'container' => 'B2E_Element_Container',
            'section' => 'B2E_Element_Section',
            'heading' => 'B2E_Element_Heading',
            'text-basic' => 'B2E_Element_Paragraph',
            'text' => 'B2E_Element_Paragraph',
            'image' => 'B2E_Element_Image',
            'div' => 'B2E_Element_Div',
            'block' => 'B2E_Element_Div', // Bricks 'block' = Etch 'flex-div'
        );
        
        // Get converter class
        $converter_class = $type_map[$element_type] ?? null;
        
        if (!$converter_class) {
            error_log("⚠️ B2E Factory: No converter found for element type: {$element_type}");
            return null;
        }
        
        // Create converter instance (with caching)
        if (!isset($this->converters[$converter_class])) {
            $this->converters[$converter_class] = new $converter_class($this->style_map);
        }
        
        return $this->converters[$converter_class];
    }
    
    /**
     * Convert element using appropriate converter
     * 
     * @param array $element Bricks element
     * @param array $children Child elements (already converted HTML)
     * @return string|null Gutenberg block HTML
     */
    public function convert_element($element, $children = array()) {
        $element_type = $element['name'] ?? '';
        
        if (empty($element_type)) {
            error_log("⚠️ B2E Factory: Element has no type");
            return null;
        }
        
        $converter = $this->get_converter($element_type);
        
        if (!$converter) {
            error_log("⚠️ B2E Factory: Unsupported element type: {$element_type}");
            return null;
        }
        
        return $converter->convert($element, $children);
    }
}
