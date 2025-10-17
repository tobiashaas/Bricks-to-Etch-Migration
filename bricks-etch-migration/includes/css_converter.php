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
        
        // Add CSS variables (custom type) - DISABLED
        // Locally scoped variables should stay in their classes, not be moved to :root
        // $etch_styles = array_merge($etch_styles, $this->get_etch_css_variables());
        
        // Step 1: Collect all custom CSS into a temporary stylesheet
        $custom_css_stylesheet = '';
        foreach ($bricks_classes as $class) {
            if (!empty($class['settings']['_cssCustom'])) {
                $class_name = !empty($class['name']) ? $class['name'] : $class['id'];
                $class_name = preg_replace('/^acss_import_/', '', $class_name);
                
                $custom_css = str_replace('%root%', '.' . $class_name, $class['settings']['_cssCustom']);
                $custom_css_stylesheet .= "\n" . $custom_css . "\n";
            }
        }
        
        // Step 2: Convert user classes (without custom CSS for now)
        foreach ($bricks_classes as $class) {
            $converted_class = $this->convert_bricks_class_to_etch($class);
            if ($converted_class) {
                // Use name for hash if available, otherwise use ID
                $hash_base = !empty($class['name']) ? $class['name'] : $class['id'];
                $style_id = $this->generate_style_hash($hash_base);
                $etch_styles[$style_id] = $converted_class;
            }
        }
        
        // Step 3: Parse custom CSS stylesheet and extract all rules
        if (!empty($custom_css_stylesheet)) {
            $custom_styles = $this->parse_custom_css_stylesheet($custom_css_stylesheet);
            $etch_styles = array_merge($etch_styles, $custom_styles);
        }
        
        return $etch_styles;
    }
    
    /**
     * Convert single Bricks class to Etch format
     */
    public function convert_bricks_class_to_etch($bricks_class) {
        // Use human-readable name instead of internal ID
        // Fallback to ID if name is not available
        $class_name = !empty($bricks_class['name']) ? $bricks_class['name'] : $bricks_class['id'];
        
        // Remove ACSS import prefix from class names
        $class_name = preg_replace('/^acss_import_/', '', $class_name);
        
        // Convert base settings (if any)
        $css = '';
        if (!empty($bricks_class['settings'])) {
            $css = $this->convert_bricks_settings_to_css($bricks_class['settings'], $class_name);
            
            // Convert responsive variants
            $responsive_css = $this->convert_responsive_variants($bricks_class['settings']);
            if (!empty($responsive_css)) {
                $css .= ' ' . $responsive_css;
            }
        }
        
        // Create entry even for empty classes (utility classes from CSS frameworks)
        return array(
            'type' => 'class',
            'selector' => '.' . $class_name,
            'collection' => 'default',
            'css' => trim($css),
            'readonly' => false,
        );
    }
    
    /**
     * Convert responsive variants to media queries
     */
    private function convert_responsive_variants($settings) {
        $responsive_css = '';
        
        // Bricks breakpoints
        $breakpoints = array(
            'mobile_portrait' => '(max-width: 478px)',
            'mobile_landscape' => '(min-width: 479px) and (max-width: 767px)',
            'tablet_portrait' => '(min-width: 768px) and (max-width: 991px)',
            'tablet_landscape' => '(min-width: 992px) and (max-width: 1199px)',
            'desktop' => '(min-width: 1200px)',
        );
        
        foreach ($breakpoints as $breakpoint => $media_query) {
            $breakpoint_settings = array();
            
            // Extract all properties for this breakpoint
            foreach ($settings as $key => $value) {
                if (strpos($key, ':' . $breakpoint) !== false) {
                    // Remove breakpoint suffix to get base property name
                    $base_key = str_replace(':' . $breakpoint, '', $key);
                    $breakpoint_settings[$base_key] = $value;
                }
            }
            
            if (!empty($breakpoint_settings)) {
                $breakpoint_css = $this->convert_bricks_settings_to_css($breakpoint_settings);
                if (!empty($breakpoint_css)) {
                    $responsive_css .= "\n@media " . $media_query . " {\n  " . str_replace(';', ";\n  ", trim($breakpoint_css)) . "\n}";
                }
            }
        }
        
        return $responsive_css;
    }
    
    /**
     * Convert Bricks settings to CSS
     */
    private function convert_bricks_settings_to_css($settings, $class_name = '') {
        $css_properties = array();
        
        // Layout & Display
        $css_properties = array_merge($css_properties, $this->convert_layout($settings));
        
        // Flexbox Properties
        $css_properties = array_merge($css_properties, $this->convert_flexbox($settings));
        
        // Grid Properties
        $css_properties = array_merge($css_properties, $this->convert_grid($settings));
        
        // Sizing
        $css_properties = array_merge($css_properties, $this->convert_sizing($settings));
        
        // Background
        if (!empty($settings['background'])) {
            $css_properties = array_merge($css_properties, $this->convert_background($settings['background']));
        }
        
        // Border
        if (!empty($settings['border'])) {
            $css_properties = array_merge($css_properties, $this->convert_border($settings['border']));
        }
        
        // Typography
        if (!empty($settings['typography']) || !empty($settings['_typography'])) {
            $typography = !empty($settings['_typography']) ? $settings['_typography'] : $settings['typography'];
            $css_properties = array_merge($css_properties, $this->convert_typography($typography));
        }
        
        // Spacing
        if (!empty($settings['spacing'])) {
            $css_properties = array_merge($css_properties, $this->convert_spacing($settings['spacing']));
        }
        
        // Margin & Padding (Bricks format)
        $css_properties = array_merge($css_properties, $this->convert_margin_padding($settings));
        
        // Position
        $css_properties = array_merge($css_properties, $this->convert_position($settings));
        
        // Transform & Effects
        $css_properties = array_merge($css_properties, $this->convert_effects($settings));
        
        // Custom CSS is handled separately in parse_custom_css_stylesheet()
        // Don't include here to avoid duplication
        
        // Filter empty values
        $css_properties = array_filter($css_properties);
        
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
        
        // Font properties
        if (!empty($typography['font-size'])) {
            $css[] = 'font-size: ' . $typography['font-size'] . ';';
        } elseif (!empty($typography['fontSize'])) {
            $css[] = 'font-size: ' . $typography['fontSize'] . ';';
        }
        
        if (!empty($typography['font-weight'])) {
            $css[] = 'font-weight: ' . $typography['font-weight'] . ';';
        } elseif (!empty($typography['fontWeight'])) {
            $css[] = 'font-weight: ' . $typography['fontWeight'] . ';';
        }
        
        if (!empty($typography['font-family'])) {
            $css[] = 'font-family: ' . $typography['font-family'] . ';';
        } elseif (!empty($typography['fontFamily'])) {
            $css[] = 'font-family: ' . $typography['fontFamily'] . ';';
        }
        
        if (!empty($typography['font-style'])) {
            $css[] = 'font-style: ' . $typography['font-style'] . ';';
        } elseif (!empty($typography['fontStyle'])) {
            $css[] = 'font-style: ' . $typography['fontStyle'] . ';';
        }
        
        // Line properties
        if (!empty($typography['line-height'])) {
            $css[] = 'line-height: ' . $typography['line-height'] . ';';
        } elseif (!empty($typography['lineHeight'])) {
            $css[] = 'line-height: ' . $typography['lineHeight'] . ';';
        }
        
        if (!empty($typography['letter-spacing'])) {
            $css[] = 'letter-spacing: ' . $typography['letter-spacing'] . ';';
        } elseif (!empty($typography['letterSpacing'])) {
            $css[] = 'letter-spacing: ' . $typography['letterSpacing'] . ';';
        }
        
        if (!empty($typography['word-spacing'])) {
            $css[] = 'word-spacing: ' . $typography['word-spacing'] . ';';
        } elseif (!empty($typography['wordSpacing'])) {
            $css[] = 'word-spacing: ' . $typography['wordSpacing'] . ';';
        }
        
        // Text properties
        if (!empty($typography['text-align'])) {
            $css[] = 'text-align: ' . $typography['text-align'] . ';';
        } elseif (!empty($typography['textAlign'])) {
            $css[] = 'text-align: ' . $typography['textAlign'] . ';';
        }
        
        if (!empty($typography['text-transform'])) {
            $css[] = 'text-transform: ' . $typography['text-transform'] . ';';
        } elseif (!empty($typography['textTransform'])) {
            $css[] = 'text-transform: ' . $typography['textTransform'] . ';';
        }
        
        if (!empty($typography['text-decoration'])) {
            $css[] = 'text-decoration: ' . $typography['text-decoration'] . ';';
        } elseif (!empty($typography['textDecoration'])) {
            $css[] = 'text-decoration: ' . $typography['textDecoration'] . ';';
        }
        
        if (!empty($typography['text-indent'])) {
            $css[] = 'text-indent: ' . $typography['text-indent'] . ';';
        } elseif (!empty($typography['textIndent'])) {
            $css[] = 'text-indent: ' . $typography['textIndent'] . ';';
        }
        
        // Color
        if (!empty($typography['color'])) {
            $css[] = 'color: ' . $typography['color'] . ';';
        }
        
        // Vertical align
        if (!empty($typography['vertical-align'])) {
            $css[] = 'vertical-align: ' . $typography['vertical-align'] . ';';
        } elseif (!empty($typography['verticalAlign'])) {
            $css[] = 'vertical-align: ' . $typography['verticalAlign'] . ';';
        }
        
        // White space
        if (!empty($typography['white-space'])) {
            $css[] = 'white-space: ' . $typography['white-space'] . ';';
        } elseif (!empty($typography['whiteSpace'])) {
            $css[] = 'white-space: ' . $typography['whiteSpace'] . ';';
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
     * Convert layout properties
     */
    private function convert_layout($settings) {
        $css = array();
        
        if (!empty($settings['_display'])) {
            $css[] = 'display: ' . $settings['_display'] . ';';
        }
        
        if (!empty($settings['_overflow'])) {
            $css[] = 'overflow: ' . $settings['_overflow'] . ';';
        }
        
        if (!empty($settings['_overflowX'])) {
            $css[] = 'overflow-x: ' . $settings['_overflowX'] . ';';
        }
        
        if (!empty($settings['_overflowY'])) {
            $css[] = 'overflow-y: ' . $settings['_overflowY'] . ';';
        }
        
        if (!empty($settings['_visibility'])) {
            $css[] = 'visibility: ' . $settings['_visibility'] . ';';
        }
        
        if (!empty($settings['_opacity'])) {
            $css[] = 'opacity: ' . $settings['_opacity'] . ';';
        }
        
        if (!empty($settings['_zIndex'])) {
            $css[] = 'z-index: ' . $settings['_zIndex'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert flexbox properties
     */
    private function convert_flexbox($settings) {
        $css = array();
        
        // Flex container properties
        if (!empty($settings['_flexDirection'])) {
            $css[] = 'flex-direction: ' . $settings['_flexDirection'] . ';';
        }
        
        if (!empty($settings['_flexWrap'])) {
            $css[] = 'flex-wrap: ' . $settings['_flexWrap'] . ';';
        }
        
        if (!empty($settings['_justifyContent'])) {
            $css[] = 'justify-content: ' . $settings['_justifyContent'] . ';';
        }
        
        if (!empty($settings['_alignItems'])) {
            $css[] = 'align-items: ' . $settings['_alignItems'] . ';';
        }
        
        if (!empty($settings['_alignContent'])) {
            $css[] = 'align-content: ' . $settings['_alignContent'] . ';';
        }
        
        if (!empty($settings['_rowGap'])) {
            $css[] = 'row-gap: ' . $settings['_rowGap'] . ';';
        }
        
        if (!empty($settings['_columnGap'])) {
            $css[] = 'column-gap: ' . $settings['_columnGap'] . ';';
        }
        
        if (!empty($settings['_gap'])) {
            $css[] = 'gap: ' . $settings['_gap'] . ';';
        }
        
        // Flex item properties
        if (!empty($settings['_flexGrow'])) {
            $css[] = 'flex-grow: ' . $settings['_flexGrow'] . ';';
        }
        
        if (!empty($settings['_flexShrink'])) {
            $css[] = 'flex-shrink: ' . $settings['_flexShrink'] . ';';
        }
        
        if (!empty($settings['_flexBasis'])) {
            $css[] = 'flex-basis: ' . $settings['_flexBasis'] . ';';
        }
        
        if (!empty($settings['_alignSelf'])) {
            $css[] = 'align-self: ' . $settings['_alignSelf'] . ';';
        }
        
        if (!empty($settings['_order'])) {
            $css[] = 'order: ' . $settings['_order'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert grid properties
     */
    private function convert_grid($settings) {
        $css = array();
        
        if (!empty($settings['_gridTemplateColumns'])) {
            $css[] = 'grid-template-columns: ' . $settings['_gridTemplateColumns'] . ';';
        }
        
        if (!empty($settings['_gridTemplateRows'])) {
            $css[] = 'grid-template-rows: ' . $settings['_gridTemplateRows'] . ';';
        }
        
        if (!empty($settings['_gridColumnGap'])) {
            $css[] = 'column-gap: ' . $settings['_gridColumnGap'] . ';';
        }
        
        if (!empty($settings['_gridRowGap'])) {
            $css[] = 'row-gap: ' . $settings['_gridRowGap'] . ';';
        }
        
        if (!empty($settings['_gridAutoFlow'])) {
            $css[] = 'grid-auto-flow: ' . $settings['_gridAutoFlow'] . ';';
        }
        
        // Grid item placement
        if (!empty($settings['_gridItemColumnSpan'])) {
            $css[] = 'grid-column: span ' . $settings['_gridItemColumnSpan'] . ';';
        }
        
        if (!empty($settings['_gridItemRowSpan'])) {
            $css[] = 'grid-row: span ' . $settings['_gridItemRowSpan'] . ';';
        }
        
        if (!empty($settings['_gridItemColumnStart'])) {
            $css[] = 'grid-column-start: ' . $settings['_gridItemColumnStart'] . ';';
        }
        
        if (!empty($settings['_gridItemColumnEnd'])) {
            $css[] = 'grid-column-end: ' . $settings['_gridItemColumnEnd'] . ';';
        }
        
        if (!empty($settings['_gridItemRowStart'])) {
            $css[] = 'grid-row-start: ' . $settings['_gridItemRowStart'] . ';';
        }
        
        if (!empty($settings['_gridItemRowEnd'])) {
            $css[] = 'grid-row-end: ' . $settings['_gridItemRowEnd'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert sizing properties
     */
    private function convert_sizing($settings) {
        $css = array();
        
        if (!empty($settings['_width'])) {
            $css[] = 'width: ' . $settings['_width'] . ';';
        }
        
        if (!empty($settings['_height'])) {
            $css[] = 'height: ' . $settings['_height'] . ';';
        }
        
        if (!empty($settings['_minWidth'])) {
            $css[] = 'min-width: ' . $settings['_minWidth'] . ';';
        }
        
        if (!empty($settings['_minHeight'])) {
            $css[] = 'min-height: ' . $settings['_minHeight'] . ';';
        }
        
        if (!empty($settings['_maxWidth'])) {
            $css[] = 'max-width: ' . $settings['_maxWidth'] . ';';
        }
        
        if (!empty($settings['_maxHeight'])) {
            $css[] = 'max-height: ' . $settings['_maxHeight'] . ';';
        }
        
        if (!empty($settings['_aspectRatio'])) {
            $css[] = 'aspect-ratio: ' . $settings['_aspectRatio'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert margin and padding (Bricks format)
     */
    private function convert_margin_padding($settings) {
        $css = array();
        
        // Margin
        if (!empty($settings['_margin'])) {
            $margin = $settings['_margin'];
            if (is_array($margin)) {
                $values = array();
                if (isset($margin['top'])) $values[] = $margin['top'];
                if (isset($margin['right'])) $values[] = $margin['right'];
                if (isset($margin['bottom'])) $values[] = $margin['bottom'];
                if (isset($margin['left'])) $values[] = $margin['left'];
                if (!empty($values)) {
                    $css[] = 'margin: ' . implode(' ', $values) . ';';
                }
            } else {
                $css[] = 'margin: ' . $margin . ';';
            }
        }
        
        // Padding
        if (!empty($settings['_padding'])) {
            $padding = $settings['_padding'];
            if (is_array($padding)) {
                $values = array();
                if (isset($padding['top'])) $values[] = $padding['top'];
                if (isset($padding['right'])) $values[] = $padding['right'];
                if (isset($padding['bottom'])) $values[] = $padding['bottom'];
                if (isset($padding['left'])) $values[] = $padding['left'];
                if (!empty($values)) {
                    $css[] = 'padding: ' . implode(' ', $values) . ';';
                }
            } else {
                $css[] = 'padding: ' . $padding . ';';
            }
        }
        
        return $css;
    }
    
    /**
     * Convert position properties
     */
    private function convert_position($settings) {
        $css = array();
        
        if (!empty($settings['_position'])) {
            $css[] = 'position: ' . $settings['_position'] . ';';
        }
        
        if (!empty($settings['_top'])) {
            $css[] = 'top: ' . $settings['_top'] . ';';
        }
        
        if (!empty($settings['_right'])) {
            $css[] = 'right: ' . $settings['_right'] . ';';
        }
        
        if (!empty($settings['_bottom'])) {
            $css[] = 'bottom: ' . $settings['_bottom'] . ';';
        }
        
        if (!empty($settings['_left'])) {
            $css[] = 'left: ' . $settings['_left'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert transform and effects
     */
    private function convert_effects($settings) {
        $css = array();
        
        if (!empty($settings['_transform'])) {
            $css[] = 'transform: ' . $settings['_transform'] . ';';
        }
        
        if (!empty($settings['_transition'])) {
            $css[] = 'transition: ' . $settings['_transition'] . ';';
        }
        
        if (!empty($settings['_cssTransition'])) {
            $css[] = 'transition: ' . $settings['_cssTransition'] . ';';
        }
        
        if (!empty($settings['_filter'])) {
            $css[] = 'filter: ' . $settings['_filter'] . ';';
        }
        
        if (!empty($settings['_backdropFilter'])) {
            $css[] = 'backdrop-filter: ' . $settings['_backdropFilter'] . ';';
        }
        
        if (!empty($settings['_boxShadow'])) {
            $css[] = 'box-shadow: ' . $settings['_boxShadow'] . ';';
        }
        
        if (!empty($settings['_textShadow'])) {
            $css[] = 'text-shadow: ' . $settings['_textShadow'] . ';';
        }
        
        if (!empty($settings['_objectFit'])) {
            $css[] = 'object-fit: ' . $settings['_objectFit'] . ';';
        }
        
        if (!empty($settings['_objectPosition'])) {
            $css[] = 'object-position: ' . $settings['_objectPosition'] . ';';
        }
        
        if (!empty($settings['_isolation'])) {
            $css[] = 'isolation: ' . $settings['_isolation'] . ';';
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
     * Parse custom CSS stylesheet and extract all rules as separate styles
     * This handles nested selectors, media queries, etc. by storing the raw CSS
     */
    private function parse_custom_css_stylesheet($stylesheet) {
        $styles = array();
        
        // Remove comments
        $stylesheet = preg_replace('/\/\*.*?\*\//s', '', $stylesheet);
        
        // For now, store the entire custom CSS as one "raw" style entry
        // Etch will handle the parsing and rendering
        if (!empty(trim($stylesheet))) {
            $styles['custom-css-raw'] = array(
                'type' => 'raw',
                'selector' => '', // No wrapper selector
                'collection' => 'default',
                'css' => trim($stylesheet),
                'readonly' => false,
            );
        }
        
        return $styles;
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
        
        // Increment Etch version to invalidate cache and force style reload
        $current_version = get_option('etch_svg_version', 1);
        update_option('etch_svg_version', $current_version + 1);
        
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
    
    /**
     * Clean custom CSS - remove redundant class wrappers but keep media queries
     */
    private function clean_custom_css($custom_css, $class_name) {
        if (empty($custom_css) || empty($class_name)) {
            return $custom_css;
        }
        
        // Replace Bricks %root% placeholder with actual class name
        $custom_css = str_replace('%root%', '.' . $class_name, $custom_css);
        
        // Strategy: Remove ONLY the redundant .class-name { } wrappers
        // Keep everything else (media queries, child selectors, etc.)
        
        // Pattern to match: .class-name { content } where content doesn't start with another selector
        // This removes the redundant wrapper but keeps nested media queries
        $pattern = '/\.' . preg_quote($class_name, '/') . '\s*\{\s*([^{}]*(?:\{[^}]*\}[^{}]*)*)\s*\}/s';
        
        $cleaned_parts = array();
        
        // Find all .class-name { ... } blocks
        if (preg_match_all($pattern, $custom_css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $content = trim($match[1]);
                
                // Check if content contains media queries or other nested rules
                if (preg_match('/@media|@supports|@container/', $content)) {
                    // Keep media queries as-is
                    $cleaned_parts[] = $content;
                } else if (preg_match('/^\s*\.' . preg_quote($class_name, '/') . '\s/', $content)) {
                    // Skip if it's another nested .class-name (redundant)
                    continue;
                } else {
                    // Regular CSS properties - add them
                    $cleaned_parts[] = $content;
                }
            }
        }
        
        // If we couldn't extract anything useful, return original
        if (empty($cleaned_parts)) {
            return $custom_css;
        }
        
        return implode("\n", $cleaned_parts);
    }
}
