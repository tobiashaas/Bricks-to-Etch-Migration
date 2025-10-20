<?php
/**
 * Selector Generator Service
 * 
 * Generates CSS selectors from class names
 */

namespace BricksEtchMigration\Services\CSS;

class SelectorGeneratorService {
    /**
     * Generate CSS selector from class name
     * 
     * @param string $className Class name (without dot)
     * @return string CSS selector (with dot)
     */
    public function generate(string $className): string {
        // Remove ACSS prefix if present
        $className = preg_replace('/^acss_import_/', '', $className);
        
        // Add dot prefix
        return '.' . $className;
    }
    
    /**
     * Generate element selector
     * 
     * @param string $elementType Element type (e.g., 'section', 'container')
     * @return string CSS selector for Etch element
     */
    public function generateElementSelector(string $elementType): string {
        return ':where([data-etch-element="' . $elementType . '"])';
    }
}
