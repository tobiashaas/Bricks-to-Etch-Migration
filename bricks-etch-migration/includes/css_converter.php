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
        error_log('🎨 CSS Converter: Starting conversion...');
        
        $bricks_classes = get_option('bricks_global_classes', array());
        error_log('🎨 CSS Converter: Found ' . count($bricks_classes) . ' Bricks classes');
        
        $etch_styles = array();
        $style_map = array(); // Maps Bricks ID => Etch Style ID
        
        // Add Etch element styles (readonly)
        $etch_styles = array_merge($etch_styles, $this->get_etch_element_styles());
        
        // Add CSS variables (custom type) - DISABLED
        // Locally scoped variables should stay in their classes, not be moved to :root
        // $etch_styles = array_merge($etch_styles, $this->get_etch_css_variables());
        
        // Step 1: Collect all custom CSS into a temporary stylesheet
        $custom_css_stylesheet = '';
        
        // Collect custom CSS from global classes
        foreach ($bricks_classes as $class) {
            if (!empty($class['settings']['_cssCustom'])) {
                $class_name = !empty($class['name']) ? $class['name'] : $class['id'];
                $class_name = preg_replace('/^acss_import_/', '', $class_name);
                
                $custom_css = str_replace('%root%', '.' . $class_name, $class['settings']['_cssCustom']);
                $custom_css_stylesheet .= "\n" . $custom_css . "\n";
            }
        }
        
        // Collect inline CSS from code blocks (stored during content parsing)
        global $wpdb;
        $inline_css_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'b2e_inline_css_%'",
            ARRAY_A
        );
        
        foreach ($inline_css_options as $option) {
            $custom_css_stylesheet .= "\n" . $option['option_value'] . "\n";
            // Clean up after collecting
            delete_option($option['option_name']);
        }
        
        // Step 2: Convert user classes (without custom CSS for now)
        $converted_count = 0;
        foreach ($bricks_classes as $class) {
            $converted_class = $this->convert_bricks_class_to_etch($class);
            if ($converted_class) {
                // Generate unique ID like Etch does (uniqid is fine!)
                $style_id = substr(uniqid(), -7);
                
                // Add style WITH ID as key (Etch won't overwrite existing IDs!)
                $etch_styles[$style_id] = $converted_class;
                $converted_count++;
                
                // DEBUG: Log first 3 styles to verify selectors
                if ($converted_count <= 3) {
                    error_log('B2E CSS: Style ' . $style_id . ' selector: ' . ($converted_class['selector'] ?? 'NULL'));
                }
                
                // Map Bricks ID to Etch Style ID
                if (!empty($class['id'])) {
                    $style_map[$class['id']] = $style_id;
                }
            }
        }
        error_log('🎨 CSS Converter: Converted ' . $converted_count . ' user classes');
        
        // Step 3: Parse custom CSS stylesheet and merge with existing styles
        if (!empty($custom_css_stylesheet)) {
            $custom_styles = $this->parse_custom_css_stylesheet($custom_css_stylesheet);
            
            // Merge custom CSS with existing styles (combine CSS for same selector)
            foreach ($custom_styles as $style_id => $custom_style) {
                if (isset($etch_styles[$style_id])) {
                    // Combine CSS for same selector
                    $existing_css = trim($etch_styles[$style_id]['css']);
                    $custom_css = trim($custom_style['css']);
                    
                    // Convert custom CSS to logical properties
                    $custom_css = $this->convert_to_logical_properties($custom_css);
                    
                    if (!empty($existing_css) && !empty($custom_css)) {
                        $etch_styles[$style_id]['css'] = $existing_css . "\n  " . $custom_css;
                    } elseif (!empty($custom_css)) {
                        $etch_styles[$style_id]['css'] = $custom_css;
                    }
                } else {
                    // New style from custom CSS - convert to logical properties
                    $custom_style['css'] = $this->convert_to_logical_properties($custom_style['css']);
                    $etch_styles[$style_id] = $custom_style;
                }
            }
        }
        
        // Save style map for use during content migration
        update_option('b2e_style_map', $style_map);
        
        $total_styles = count($etch_styles);
        error_log('🎨 CSS Converter: Returning ' . $total_styles . ' total styles');
        error_log('🎨 CSS Converter: Style map has ' . count($style_map) . ' entries');
        
        if ($total_styles === 0) {
            error_log('⚠️ CSS Converter: WARNING - No styles generated!');
        }
        
        // Return both styles AND style map
        return array(
            'styles' => $etch_styles,
            'style_map' => $style_map
        );
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
        
        // Gradient (Bricks uses _gradient setting)
        if (!empty($settings['_gradient'])) {
            $gradient_css = $this->convert_gradient($settings['_gradient']);
            if (!empty($gradient_css)) {
                $css_properties[] = $gradient_css;
            }
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
            $color_value = is_array($background['color']) ? ($background['color']['raw'] ?? '') : $background['color'];
            if (!empty($color_value)) {
                $css[] = 'background-color: ' . $color_value . ';';
            }
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
     * Convert gradient settings
     */
    private function convert_gradient($gradient) {
        if (empty($gradient['colors']) || !is_array($gradient['colors'])) {
            return '';
        }
        
        // Build color stops
        $color_stops = array();
        foreach ($gradient['colors'] as $color_data) {
            $color_value = '';
            
            // Extract color value
            if (is_array($color_data['color'])) {
                $color_value = $color_data['color']['raw'] ?? '';
            } else {
                $color_value = $color_data['color'] ?? '';
            }
            
            if (empty($color_value)) {
                continue;
            }
            
            // Add stop position if available
            if (!empty($color_data['stop'])) {
                $color_stops[] = $color_value . ' ' . $color_data['stop'];
            } else {
                $color_stops[] = $color_value;
            }
        }
        
        if (empty($color_stops)) {
            return '';
        }
        
        // Build gradient CSS
        $gradient_type = $gradient['type'] ?? 'linear';
        $angle = $gradient['angle'] ?? '180deg';
        
        if ($gradient_type === 'radial') {
            return 'background-image: radial-gradient(' . implode(', ', $color_stops) . ');';
        } else {
            // Linear gradient (default)
            return 'background-image: linear-gradient(' . implode(', ', $color_stops) . ');';
        }
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
            $color_value = is_array($border['color']) ? ($border['color']['raw'] ?? '') : $border['color'];
            if (!empty($color_value)) {
                $css[] = 'border-color: ' . $color_value . ';';
            }
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
            $color_value = is_array($typography['color']) ? ($typography['color']['raw'] ?? '') : $typography['color'];
            if (!empty($color_value)) {
                $css[] = 'color: ' . $color_value . ';';
            }
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
        
        // Grid gap (shorthand)
        if (!empty($settings['_gridGap'])) {
            $css[] = 'gap: ' . $settings['_gridGap'] . ';';
        }
        
        if (!empty($settings['_gridColumnGap'])) {
            $css[] = 'column-gap: ' . $settings['_gridColumnGap'] . ';';
        }
        
        if (!empty($settings['_gridRowGap'])) {
            $css[] = 'row-gap: ' . $settings['_gridRowGap'] . ';';
        }
        
        // Grid alignment
        if (!empty($settings['_justifyContentGrid'])) {
            $css[] = 'justify-content: ' . $settings['_justifyContentGrid'] . ';';
        }
        
        if (!empty($settings['_alignItemsGrid'])) {
            $css[] = 'align-items: ' . $settings['_alignItemsGrid'] . ';';
        }
        
        if (!empty($settings['_justifyItemsGrid'])) {
            $css[] = 'justify-items: ' . $settings['_justifyItemsGrid'] . ';';
        }
        
        if (!empty($settings['_alignContentGrid'])) {
            $css[] = 'align-content: ' . $settings['_alignContentGrid'] . ';';
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
     * Convert sizing properties to Logical Properties
     */
    private function convert_sizing($settings) {
        $css = array();
        
        // Convert to logical properties
        if (!empty($settings['_width'])) {
            $css[] = 'inline-size: ' . $settings['_width'] . ';';
        }
        
        if (!empty($settings['_height'])) {
            $css[] = 'block-size: ' . $settings['_height'] . ';';
        }
        
        if (!empty($settings['_minWidth'])) {
            $css[] = 'min-inline-size: ' . $settings['_minWidth'] . ';';
        }
        
        if (!empty($settings['_minHeight'])) {
            $css[] = 'min-block-size: ' . $settings['_minHeight'] . ';';
        }
        
        if (!empty($settings['_maxWidth'])) {
            $css[] = 'max-inline-size: ' . $settings['_maxWidth'] . ';';
        }
        
        if (!empty($settings['_maxHeight'])) {
            $css[] = 'max-block-size: ' . $settings['_maxHeight'] . ';';
        }
        
        if (!empty($settings['_aspectRatio'])) {
            $css[] = 'aspect-ratio: ' . $settings['_aspectRatio'] . ';';
        }
        
        return $css;
    }
    
    /**
     * Convert margin and padding (Bricks format) to Logical Properties
     */
    private function convert_margin_padding($settings) {
        $css = array();
        
        // Margin - convert to logical properties
        if (!empty($settings['_margin'])) {
            $margin = $settings['_margin'];
            if (is_array($margin)) {
                // Individual sides using logical properties
                if (isset($margin['top'])) $css[] = 'margin-block-start: ' . $margin['top'] . ';';
                if (isset($margin['right'])) $css[] = 'margin-inline-end: ' . $margin['right'] . ';';
                if (isset($margin['bottom'])) $css[] = 'margin-block-end: ' . $margin['bottom'] . ';';
                if (isset($margin['left'])) $css[] = 'margin-inline-start: ' . $margin['left'] . ';';
            } else {
                $css[] = 'margin: ' . $margin . ';';
            }
        }
        
        // Individual margin properties (e.g., _marginTop, _marginBottom)
        if (isset($settings['_marginTop'])) $css[] = 'margin-block-start: ' . $settings['_marginTop'] . ';';
        if (isset($settings['_marginRight'])) $css[] = 'margin-inline-end: ' . $settings['_marginRight'] . ';';
        if (isset($settings['_marginBottom'])) $css[] = 'margin-block-end: ' . $settings['_marginBottom'] . ';';
        if (isset($settings['_marginLeft'])) $css[] = 'margin-inline-start: ' . $settings['_marginLeft'] . ';';
        
        // Padding - convert to logical properties
        if (!empty($settings['_padding'])) {
            $padding = $settings['_padding'];
            if (is_array($padding)) {
                // Individual sides using logical properties
                if (isset($padding['top'])) $css[] = 'padding-block-start: ' . $padding['top'] . ';';
                if (isset($padding['right'])) $css[] = 'padding-inline-end: ' . $padding['right'] . ';';
                if (isset($padding['bottom'])) $css[] = 'padding-block-end: ' . $padding['bottom'] . ';';
                if (isset($padding['left'])) $css[] = 'padding-inline-start: ' . $padding['left'] . ';';
            } else {
                $css[] = 'padding: ' . $padding . ';';
            }
        }
        
        // Individual padding properties
        if (isset($settings['_paddingTop'])) $css[] = 'padding-block-start: ' . $settings['_paddingTop'] . ';';
        if (isset($settings['_paddingRight'])) $css[] = 'padding-inline-end: ' . $settings['_paddingRight'] . ';';
        if (isset($settings['_paddingBottom'])) $css[] = 'padding-block-end: ' . $settings['_paddingBottom'] . ';';
        if (isset($settings['_paddingLeft'])) $css[] = 'padding-inline-start: ' . $settings['_paddingLeft'] . ';';
        
        return $css;
    }
    
    /**
     * Convert position properties to Logical Properties
     */
    private function convert_position($settings) {
        $css = array();
        
        if (!empty($settings['_position'])) {
            $css[] = 'position: ' . $settings['_position'] . ';';
        }
        
        // Convert to logical properties (inset-*)
        // Use isset() instead of !empty() to allow "0" values
        if (isset($settings['_top']) && $settings['_top'] !== '') {
            $css[] = 'inset-block-start: ' . $settings['_top'] . ';';
        }
        
        if (isset($settings['_right']) && $settings['_right'] !== '') {
            $css[] = 'inset-inline-end: ' . $settings['_right'] . ';';
        }
        
        if (isset($settings['_bottom']) && $settings['_bottom'] !== '') {
            $css[] = 'inset-block-end: ' . $settings['_bottom'] . ';';
        }
        
        if (isset($settings['_left']) && $settings['_left'] !== '') {
            $css[] = 'inset-inline-start: ' . $settings['_left'] . ';';
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
     * Convert physical properties to logical properties in CSS
     */
    private function convert_to_logical_properties($css) {
        // Map of physical to logical properties
        $property_map = array(
            // Margin
            'margin-top' => 'margin-block-start',
            'margin-right' => 'margin-inline-end',
            'margin-bottom' => 'margin-block-end',
            'margin-left' => 'margin-inline-start',
            // Padding
            'padding-top' => 'padding-block-start',
            'padding-right' => 'padding-inline-end',
            'padding-bottom' => 'padding-block-end',
            'padding-left' => 'padding-inline-start',
            // Border
            'border-top' => 'border-block-start',
            'border-right' => 'border-inline-end',
            'border-bottom' => 'border-block-end',
            'border-left' => 'border-inline-start',
            // Position
            'top' => 'inset-block-start',
            'right' => 'inset-inline-end',
            'bottom' => 'inset-block-end',
            'left' => 'inset-inline-start',
            // Size
            'width' => 'inline-size',
            'height' => 'block-size',
            'min-width' => 'min-inline-size',
            'min-height' => 'min-block-size',
            'max-width' => 'max-inline-size',
            'max-height' => 'max-block-size',
        );
        
        // Replace each physical property with logical equivalent
        foreach ($property_map as $physical => $logical) {
            // Match property with colon and optional whitespace
            $css = preg_replace('/\b' . preg_quote($physical, '/') . '\s*:/i', $logical . ':', $css);
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
     * Parse custom CSS stylesheet and extract individual rules per selector
     */
    private function parse_custom_css_stylesheet($stylesheet) {
        $styles = array();
        
        // Remove comments
        $stylesheet = preg_replace('/\/\*.*?\*\//s', '', $stylesheet);
        
        // Simple regex to extract CSS rules: selector { properties }
        // This handles basic selectors and nested rules
        preg_match_all('/([^{]+)\{([^}]+)\}/s', $stylesheet, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $selector = trim($match[1]);
            $css = trim($match[2]);
            
            if (empty($selector) || empty($css)) {
                continue;
            }
            
            // Extract class name from selector (e.g., ".my-class" or ".my-class > *")
            // Use the base class for the hash
            if (preg_match('/\.([a-zA-Z0-9_-]+)/', $selector, $class_match)) {
                $class_name = $class_match[1];
                $style_id = $this->generate_style_hash($class_name);
                
                // If this selector has child selectors (e.g., ".class > *"), store as raw CSS
                if (strpos($selector, ' ') !== false || strpos($selector, '>') !== false) {
                    // Complex selector - store the full rule
                    $full_rule = $selector . ' { ' . $css . ' }';
                    
                    if (isset($styles[$style_id])) {
                        // Append to existing style
                        $styles[$style_id]['css'] .= "\n" . $full_rule;
                    } else {
                        // Create new style with the base selector
                        $styles[$style_id] = array(
                            'type' => 'class',
                            'selector' => '.' . $class_name,
                            'collection' => 'default',
                            'css' => $full_rule,
                            'readonly' => false,
                        );
                    }
                } else {
                    // Simple selector - store just the properties
                    if (isset($styles[$style_id])) {
                        // Append to existing style
                        $styles[$style_id]['css'] .= "\n  " . $css;
                    } else {
                        // Create new style
                        $styles[$style_id] = array(
                            'type' => 'class',
                            'selector' => $selector,
                            'collection' => 'default',
                            'css' => $css,
                            'readonly' => false,
                        );
                    }
                }
            }
        }
        
        return $styles;
    }
    
    /**
     * Generate 7-character hash ID for styles
     */
    private function generate_style_hash($class_name) {
        // Use the same ID generation as Etch: substr(uniqid(), -7)
        // Note: This generates random IDs, not deterministic ones
        // But it matches Etch's format exactly
        return substr(uniqid(), -7);
    }
    
    /**
     * Import Etch styles to target site
     */
    public function import_etch_styles($data) {
        if (empty($data) || !is_array($data)) {
            return new WP_Error('invalid_styles', 'Invalid styles data provided');
        }
        
        // Extract styles and style_map from data
        $etch_styles = $data['styles'] ?? $data; // Fallback to old format
        $style_map = $data['style_map'] ?? array();
        
        error_log('B2E: import_etch_styles called with ' . count($etch_styles) . ' styles');
        error_log('B2E: import_etch_styles received style map with ' . count($style_map) . ' entries');
        
        // Get existing etch_styles (for Etch Editor)
        $existing_styles = get_option('etch_styles', array());
        
        // Merge with new styles
        $merged_styles = array_merge($existing_styles, $etch_styles);
        
        // NOTE: We only save to etch_styles, NOT etch_global_stylesheets
        // - etch_styles: Used by Etch's StylesRegister to render styles on pages that use them
        // - etch_global_stylesheets: Only for manually entered global CSS (not for classes)
        
        // TEST: Bypass Etch API to check if selectors are preserved
        $bypass_api = true; // Set to false to use Etch API again
        
        if ($bypass_api) {
            error_log('B2E: 🚫 BYPASSING Etch API - using direct update_option()');
            
            // DEBUG: Log first 3 styles BEFORE saving
            $style_keys = array_keys($merged_styles);
            for ($i = 0; $i < min(3, count($style_keys)); $i++) {
                $key = $style_keys[$i];
                $style = $merged_styles[$key];
                error_log('B2E CSS BEFORE SAVE: ' . $key . ' selector: ' . ($style['selector'] ?? 'NULL'));
            }
            
            // Save directly to database
            $update_result = update_option('etch_styles', $merged_styles);
            
            if (!$update_result) {
                error_log('B2E: ⚠️ update_option returned false (option may already exist with same value)');
            } else {
                error_log('B2E: ✅ update_option returned true');
            }
            
            // DEBUG: Log first 3 styles AFTER saving (verify from DB)
            $saved_styles = get_option('etch_styles', array());
            error_log('B2E: Retrieved ' . count($saved_styles) . ' styles from DB');
            
            for ($i = 0; $i < min(3, count($style_keys)); $i++) {
                $key = $style_keys[$i];
                $saved_style = $saved_styles[$key] ?? null;
                if ($saved_style) {
                    error_log('B2E CSS AFTER SAVE: ' . $key . ' selector: ' . ($saved_style['selector'] ?? 'NULL'));
                } else {
                    error_log('B2E CSS AFTER SAVE: ' . $key . ' NOT FOUND in DB!');
                }
            }
            
            // Manually trigger cache invalidation
            wp_cache_delete('etch_styles', 'options');
            
            // Trigger CSS rebuild
            $this->trigger_etch_css_rebuild();
            
            error_log('B2E: ✅ Direct save complete - ' . count($merged_styles) . ' styles saved');
            
            // Style map was already created during conversion!
            // Just save it to WordPress options
            update_option('b2e_style_map', $style_map);
            error_log('B2E: ✅ Saved style map with ' . count($style_map) . ' entries');
            
            // Log first few mappings for debugging
            $map_entries = array_slice($style_map, 0, 3, true);
            foreach ($map_entries as $bricks_id => $etch_id) {
                error_log('B2E: Style Map: ' . $bricks_id . ' → ' . $etch_id);
            }
            
            return true;
            
        } elseif (class_exists('Etch\RestApi\Routes\StylesRoutes')) {
            try {
                $routes = new \Etch\RestApi\Routes\StylesRoutes();
                
                // Create proper REST request with JSON body
                $request = new \WP_REST_Request('POST', '/etch-api/styles');
                $request->set_header('Content-Type', 'application/json');
                $json_body = json_encode($merged_styles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $request->set_body($json_body);
                
                // DEBUG: Check if JSON encoding breaks selectors
                $decoded_test = json_decode($json_body, true);
                $test_keys = array_keys($decoded_test);
                if (count($test_keys) > 3) {
                    error_log('B2E CSS JSON TEST: ' . $test_keys[3] . ' selector after json_encode/decode: ' . ($decoded_test[$test_keys[3]]['selector'] ?? 'NULL'));
                }
                
                error_log('B2E: Calling Etch API with ' . count($merged_styles) . ' styles');
                
                // DEBUG: Log first 3 styles BEFORE API call
                $style_keys = array_keys($merged_styles);
                for ($i = 0; $i < min(3, count($style_keys)); $i++) {
                    $key = $style_keys[$i];
                    $style = $merged_styles[$key];
                    error_log('B2E CSS BEFORE API: ' . $key . ' selector: ' . ($style['selector'] ?? 'NULL'));
                }
                
                $response = $routes->update_styles($request);
                
                // DEBUG: Log first 3 styles AFTER API call (from DB)
                $saved_styles = get_option('etch_styles', array());
                for ($i = 0; $i < min(3, count($style_keys)); $i++) {
                    $key = $style_keys[$i];
                    $saved_style = $saved_styles[$key] ?? null;
                    if ($saved_style) {
                        error_log('B2E CSS AFTER API: ' . $key . ' selector: ' . ($saved_style['selector'] ?? 'NULL'));
                    }
                }
                
                if (is_wp_error($response)) {
                    error_log('B2E: Etch API error: ' . $response->get_error_message());
                    return new WP_Error('api_failed', 'Etch API error: ' . $response->get_error_message());
                }
                
                error_log('B2E: Etch API success - styles saved and processed');
                
                // Trigger Etch CSS rebuild
                $this->trigger_etch_css_rebuild();
                
                // API call successful - Etch handles everything internally
                return true;
                
            } catch (Exception $e) {
                error_log('B2E: Etch API exception: ' . $e->getMessage());
                return new WP_Error('api_exception', 'Exception calling Etch API: ' . $e->getMessage());
            }
        } else {
            // Fallback to direct DB access if Etch API not available
            error_log('B2E: WARNING - Etch API not available, using fallback (styles may not render correctly)');
            
            $result = update_option('etch_styles', $merged_styles);
            
            if (!$result && empty($existing_styles)) {
                return new WP_Error('save_failed', 'Failed to save styles to database');
            }
            
            // Manual cache invalidation for fallback
            $current_version = get_option('etch_svg_version', 1);
            update_option('etch_svg_version', $current_version + 1);
            wp_cache_delete('etch_global_data', 'etch');
            wp_cache_flush();
            
            // Trigger Etch CSS rebuild
            $this->trigger_etch_css_rebuild();
            
            return true;
        }
    }
    
    /**
     * Trigger Etch CSS rebuild
     * Forces Etch to regenerate CSS files from styles
     */
    private function trigger_etch_css_rebuild() {
        error_log('B2E: Triggering Etch CSS rebuild...');
        
        // Method 1: Increment SVG version (forces cache invalidation)
        $current_version = get_option('etch_svg_version', 1);
        $new_version = $current_version + 1;
        update_option('etch_svg_version', $new_version);
        error_log('B2E: Updated etch_svg_version from ' . $current_version . ' to ' . $new_version);
        
        // Method 2: Clear all Etch caches
        wp_cache_delete('etch_global_data', 'etch');
        wp_cache_delete('etch_styles', 'etch');
        wp_cache_flush();
        error_log('B2E: Cleared Etch caches');
        
        // Method 3: Trigger WordPress actions that Etch might listen to
        do_action('etch_styles_updated');
        do_action('etch_rebuild_css');
        error_log('B2E: Triggered Etch action hooks');
        
        // Note: We don't call Etch's internal classes directly because:
        // - StylesheetService has protected constructor (Singleton)
        // - StylesRegister handles rendering automatically when blocks are processed
        // - Cache invalidation via etch_svg_version is sufficient
        
        error_log('B2E: CSS rebuild trigger complete');
    }
    
    /**
     * Save styles to etch_global_stylesheets for frontend rendering
     * 
     * Etch uses etch_global_stylesheets to render CSS in the frontend
     * This converts our etch_styles format to the global stylesheet format
     */
    private function save_to_global_stylesheets($etch_styles) {
        error_log('B2E: Saving to etch_global_stylesheets for frontend rendering...');
        
        // Get existing global stylesheets
        $existing_global = get_option('etch_global_stylesheets', array());
        
        // Convert etch_styles format to global stylesheet format
        // Global stylesheets format: array of {name, css} objects
        $new_stylesheets = array();
        
        foreach ($etch_styles as $style_id => $style) {
            // Skip element styles (they're built-in)
            if (isset($style['type']) && $style['type'] === 'element') {
                continue;
            }
            
            // Create stylesheet entry
            $stylesheet_name = isset($style['selector']) ? $style['selector'] : $style_id;
            $stylesheet_css = isset($style['css']) ? $style['css'] : '';
            
            // Skip empty styles
            if (empty($stylesheet_css)) {
                continue;
            }
            
            // Wrap CSS with selector if not already wrapped
            if (!empty($stylesheet_name) && strpos($stylesheet_css, $stylesheet_name) === false) {
                $wrapped_css = $stylesheet_name . ' { ' . $stylesheet_css . ' }';
            } else {
                $wrapped_css = $stylesheet_css;
            }
            
            $new_stylesheets[$style_id] = array(
                'name' => $stylesheet_name,
                'css' => $wrapped_css
            );
        }
        
        // Merge with existing global stylesheets
        $merged_global = array_merge($existing_global, $new_stylesheets);
        
        // Save to database
        $result = update_option('etch_global_stylesheets', $merged_global);
        
        if ($result) {
            error_log('B2E: Saved ' . count($new_stylesheets) . ' stylesheets to etch_global_stylesheets');
            error_log('B2E: Total global stylesheets: ' . count($merged_global));
        } else {
            error_log('B2E: WARNING - Failed to update etch_global_stylesheets');
        }
        
        return $result;
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
