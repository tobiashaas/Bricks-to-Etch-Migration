<?php
/**
 * Legacy Adapter
 * 
 * Bridges old code to new architecture
 * Allows gradual migration without breaking existing functionality
 */

namespace BricksEtchMigration\Core;

use BricksEtchMigration\Services\CSS\CSSConverterService;
use BricksEtchMigration\Services\CSS\StyleMapService;

class LegacyAdapter {
    private CSSConverterService $cssConverter;
    private StyleMapService $styleMapService;
    
    public function __construct(
        CSSConverterService $cssConverter,
        StyleMapService $styleMapService
    ) {
        $this->cssConverter = $cssConverter;
        $this->styleMapService = $styleMapService;
    }
    
    /**
     * Adapt old convert_bricks_classes_to_etch() method
     * 
     * @return array ['styles' => array, 'style_map' => array]
     */
    public function convert_bricks_classes_to_etch(): array {
        // Get Bricks classes from WordPress option (legacy way)
        $bricksClasses = get_option('bricks_global_classes', []);
        
        // Use new service
        return $this->cssConverter->execute(['classes' => $bricksClasses]);
    }
    
    /**
     * Adapt old get_element_style_ids() method
     * 
     * @param array $element Bricks element
     * @return array Etch style IDs
     */
    public function get_element_style_ids(array $element): array {
        $styleIds = [];
        
        // Method 1: _cssGlobalClasses
        if (isset($element['settings']['_cssGlobalClasses']) && is_array($element['settings']['_cssGlobalClasses'])) {
            $bricksIds = $element['settings']['_cssGlobalClasses'];
            $styleIds = array_merge($styleIds, $this->styleMapService->resolveStyleIds($bricksIds));
        }
        
        // Method 2: _cssClasses (custom classes)
        if (isset($element['settings']['_cssClasses']) && !empty($element['settings']['_cssClasses'])) {
            // Get class names and resolve via Bricks classes
            $classNames = is_string($element['settings']['_cssClasses']) 
                ? explode(' ', $element['settings']['_cssClasses'])
                : $element['settings']['_cssClasses'];
            
            $bricksClasses = get_option('bricks_global_classes', []);
            
            foreach ($classNames as $className) {
                foreach ($bricksClasses as $bricksClass) {
                    $bricksClassName = !empty($bricksClass['name']) ? $bricksClass['name'] : $bricksClass['id'];
                    $bricksClassName = preg_replace('/^acss_import_/', '', $bricksClassName);
                    
                    if ($bricksClassName === $className && !empty($bricksClass['id'])) {
                        $etchIds = $this->styleMapService->resolveStyleIds([$bricksClass['id']]);
                        $styleIds = array_merge($styleIds, $etchIds);
                        break;
                    }
                }
            }
        }
        
        return array_unique($styleIds);
    }
}
