<?php
/**
 * CSS Property Converter
 * 
 * Converts CSS properties to logical properties
 */

namespace BricksEtchMigration\Services\CSS;

class CSSPropertyConverter {
    /**
     * Property mapping: physical → logical
     */
    private array $propertyMap = [
        // Margin
        'margin-top' => 'margin-block-start',
        'margin-bottom' => 'margin-block-end',
        'margin-left' => 'margin-inline-start',
        'margin-right' => 'margin-inline-end',
        
        // Padding
        'padding-top' => 'padding-block-start',
        'padding-bottom' => 'padding-block-end',
        'padding-left' => 'padding-inline-start',
        'padding-right' => 'padding-inline-end',
        
        // Border
        'border-top' => 'border-block-start',
        'border-bottom' => 'border-block-end',
        'border-left' => 'border-inline-start',
        'border-right' => 'border-inline-end',
        
        // Dimensions
        'width' => 'inline-size',
        'height' => 'block-size',
        'min-width' => 'min-inline-size',
        'min-height' => 'min-block-size',
        'max-width' => 'max-inline-size',
        'max-height' => 'max-block-size',
        
        // Position
        'top' => 'inset-block-start',
        'bottom' => 'inset-block-end',
        'left' => 'inset-inline-start',
        'right' => 'inset-inline-end',
    ];
    
    /**
     * Convert CSS properties
     * 
     * @param array $properties Bricks CSS properties
     * @return string CSS string
     */
    public function convert(array $properties): string {
        $css = [];
        
        foreach ($properties as $property => $value) {
            $logicalProperty = $this->propertyMap[$property] ?? $property;
            $css[] = $logicalProperty . ': ' . $value . ';';
        }
        
        return implode("\n", $css);
    }
}
