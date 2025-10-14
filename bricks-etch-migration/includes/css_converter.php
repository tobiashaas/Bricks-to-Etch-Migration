<?php
/**
 * CSS Converter for Bricks to Etch Migration Plugin
 * 
 * Converts Bricks global classes to Etch-compatible CSS format
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_CSS_Converter {
    
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
     * Convert Bricks global classes to Etch format
     * 
     * Generates Etch-compatible etch_styles array structure
     */
    public function convert_bricks_classes_to_etch() {
        $bricks_classes = get_option('bricks_global_classes', array());
        $etch_styles = array();
        
        // Add Etch element styles (readonly)
        $etch_styles = array_merge($etch_styles, $this->get_etch_element_styles());
        
        // Add CSS variables (custom type)
        $etch_styles = array_merge($etch_styles, $this->get_etch_css_variables());
        
        // Convert user classes (class type)
        foreach ($bricks_classes as $class) {
            $converted_class = $this->convert_bricks_class_to_etch($class);
            if ($converted_class) {
                $style_id = $this->generate_style_hash($class['id']);
                $etch_styles[$style_id] = $converted_class;
            }
        }
        
        return $etch_styles;
    }
    
    /**
     * Convert single Bricks class to Etch format
     */
    public function convert_bricks_class_to_etch($bricks_class) {
        if (empty($bricks_class['settings'])) {
            return null;
        }
        
        $css = $this->convert_bricks_settings_to_css($bricks_class['settings']);
        
        if (empty($css)) {
            return null;
        }
        
        return array(
            'type' => 'class',
            'selector' => '.' . $bricks_class['id'],
            'collection' => 'default',
            'css' => $css,
            'readonly' => false,
        );
    }
    
    /**
     * Convert Bricks settings to CSS
     */
    private function convert_bricks_settings_to_css($settings) {
        $css_properties = array();
        
        // Background
        if (!empty($settings['background'])) {
            $css_properties = array_merge($css_properties, $this->convert_background($settings['background']));
        }
        
        // Border
        if (!empty($settings['border'])) {
            $css_properties = array_merge($css_properties, $this->convert_border($settings['border']));
        }
        
        // Typography
        if (!empty($settings['typography'])) {
            $css_properties = array_merge($css_properties, $this->convert_typography($settings['typography']));
        }
        
        // Spacing
        if (!empty($settings['spacing'])) {
            $css_properties = array_merge($css_properties, $this->convert_spacing($settings['spacing']));
        }
        
        // Custom CSS
        if (!empty($settings['_cssCustom'])) {
            $css_properties[] = $settings['_cssCustom'];
        }
        
        return implode(' ', $css_properties);
    }
    
    /**
     * Convert background settings
     */
    private function convert_background($background) {
        $css = array();
        
        if (!empty($background['color'])) {
            $css[] = 'background-color: ' . $background['color'] . ';';
        }
        
        if (!empty($background['image']['url'])) {
            $css[] = 'background-image: url(' . $background['image']['url'] . ');';
            
            if (!empty($background['image']['size'])) {
                $css[] = 'background-size: ' . $background['image']['size'] . ';';
            }
            
            if (!empty($background['image']['position'])) {
                $css[] = 'background-position: ' . $background['image']['position'] . ';';
            }
            
            if (!empty($background['image']['repeat'])) {
                $css[] = 'background-repeat: ' . $background['image']['repeat'] . ';';
            }
        }
        
        return $css;
    }
    
    /**
     * Convert border settings
     */
    private function convert_border($border) {
        $css = array();
        
        if (!empty($border['width'])) {
            $css[] = 'border-width: ' . $border['width'] . ';';
        }
        
        if (!empty($border['style'])) {
            $css[] = 'border-style: ' . $border['style'] . ';';
        }
        
        if (!empty($border['color'])) {
            $css[] = 'border-color: ' . $border['color'] . ';';
        }
        
        if (!empty($border['radius'])) {
            $css[] = 'border-radius: ' . $border['radius'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert typography settings
     */
    private function convert_typography($typography) {
        $css = array();
        
        if (!empty($typography['fontSize'])) {
            $css[] = 'font-size: ' . $typography['fontSize'] . ';';
        }
        
        if (!empty($typography['fontWeight'])) {
            $css[] = 'font-weight: ' . $typography['fontWeight'] . ';';
        }
        
        if (!empty($typography['fontFamily'])) {
            $css[] = 'font-family: ' . $typography['fontFamily'] . ';';
        }
        
        if (!empty($typography['lineHeight'])) {
            $css[] = 'line-height: ' . $typography['lineHeight'] . ';';
        }
        
        if (!empty($typography['letterSpacing'])) {
            $css[] = 'letter-spacing: ' . $typography['letterSpacing'] . ';';
        }
        
        if (!empty($typography['textAlign'])) {
            $css[] = 'text-align: ' . $typography['textAlign'] . ';';
        }
        
        if (!empty($typography['textTransform'])) {
            $css[] = 'text-transform: ' . $typography['textTransform'] . ';';
        }
        
        if (!empty($typography['color'])) {
            $css[] = 'color: ' . $typography['color'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert spacing settings
     */
    private function convert_spacing($spacing) {
        $css = array();
        
        if (!empty($spacing['margin'])) {
            $css[] = 'margin: ' . $spacing['margin'] . ';';
        }
        
        if (!empty($spacing['padding'])) {
            $css[] = 'padding: ' . $spacing['padding'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Get Etch element styles (readonly)
     */
    private function get_etch_element_styles() {
        return array(
            'etch-section-style' => array(
                'type' => 'element',
                'selector' => ':where([data-etch-element="section"])',
                'collection' => 'default',
                'css' => 'inline-size: 100%; display: flex; flex-direction: column; align-items: center;',
                'readonly' => true,
            ),
            'etch-container-style' => array(
                'type' => 'element',
                'selector' => ':where([data-etch-element="container"])',
                'collection' => 'default',
                'css' => 'inline-size: 100%; display: flex; flex-direction: column; max-width: var(--content-width, 1366px); align-self: center;',
                'readonly' => true,
            ),
            'etch-flex-div-style' => array(
                'type' => 'element',
                'selector' => ':where([data-etch-element="flex-div"])',
                'collection' => 'default',
                'css' => 'inline-size: 100%; display: flex; flex-direction: column;',
                'readonly' => true,
            ),
            'etch-iframe-style' => array(
                'type' => 'element',
                'selector' => ':where([data-etch-element="iframe"])',
                'collection' => 'default',
                'css' => 'inline-size: 100%; height: auto; aspect-ratio: 16/9;',
                'readonly' => true,
            ),
        );
    }
    
    /**
     * Get Etch CSS variables (custom type)
     */
    private function get_etch_css_variables() {
        $css_variables = array();
        
        // Extract CSS variables from Bricks global classes
        $bricks_classes = get_option('bricks_global_classes', array());
        
        foreach ($bricks_classes as $class) {
            if (!empty($class['settings']['_cssCustom'])) {
                $custom_css = $class['settings']['_cssCustom'];
                
                // Extract CSS variables (--variable-name: value;)
                if (preg_match_all('/--([a-zA-Z0-9_-]+):\s*([^;]+);/', $custom_css, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $css_variables['--' . $match[1]] = trim($match[2]);
                    }
                }
            }
        }
        
        if (empty($css_variables)) {
            return array();
        }
        
        $css_string = '';
        foreach ($css_variables as $variable => $value) {
            $css_string .= $variable . ': ' . $value . '; ';
        }
        
        return array(
            'etch-global-variable-style' => array(
                'type' => 'custom',
                'selector' => ':root',
                'collection' => 'default',
                'css' => trim($css_string),
                'readonly' => false,
            ),
        );
    }
    
    /**
     * Generate 7-character hash ID for styles
     */
    private function generate_style_hash($class_name) {
        return substr(md5($class_name), 0, 7);
    }
    
    /**
     * Import Etch styles to target site
     */
    public function import_etch_styles($etch_styles) {
        if (empty($etch_styles) || !is_array($etch_styles)) {
            return new WP_Error('invalid_styles', 'Invalid styles data provided');
        }
        
        // Get existing etch_styles
        $existing_styles = get_option('etch_styles', array());
        
        // Merge with new styles
        $merged_styles = array_merge($existing_styles, $etch_styles);
        
        // Save to database
        $result = update_option('etch_styles', $merged_styles);
        
        if (!$result) {
            return new WP_Error('save_failed', 'Failed to save styles to database');
        }
        
        return true;
    }
    
    /**
     * Validate CSS syntax
     */
    public function validate_css_syntax($css) {
        // Basic CSS validation
        if (empty($css)) {
            return true;
        }
        
        // Check for basic CSS syntax errors
        $errors = array();
        
        // Check for unclosed brackets
        if (substr_count($css, '{') !== substr_count($css, '}')) {
            $errors[] = 'Unclosed CSS brackets';
        }
        
        // Check for unclosed quotes
        $single_quotes = substr_count($css, "'");
        $double_quotes = substr_count($css, '"');
        
        if ($single_quotes % 2 !== 0) {
            $errors[] = 'Unclosed single quotes';
        }
        
        if ($double_quotes % 2 !== 0) {
            $errors[] = 'Unclosed double quotes';
        }
        
        if (!empty($errors)) {
            $this->error_handler->log_error('E002', array(
                'css' => $css,
                'errors' => $errors,
                'action' => 'CSS syntax validation failed'
            ));
            return false;
        }
        
        return true;
    }
    
    /**
     * Fix common CSS issues
     */
    public function fix_css_issues($css) {
        if (empty($css)) {
            return $css;
        }
        
        // Fix common issues
        $css = str_replace('; ;', ';', $css); // Remove double semicolons
        $css = preg_replace('/\s+/', ' ', $css); // Normalize whitespace
        $css = trim($css);
        
        return $css;
    }
}
