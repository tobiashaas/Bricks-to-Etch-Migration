<?php
/**
 * CSS Converter Service
 * 
 * Orchestrates CSS conversion from Bricks to Etch
 */

namespace BricksEtchMigration\Services\CSS;

use BricksEtchMigration\Interfaces\ServiceInterface;
use BricksEtchMigration\Services\Storage\StyleRepository;
use BricksEtchMigration\DTOs\StyleDTO;
use BricksEtchMigration\DTOs\MigrationResultDTO;

class CSSConverterService implements ServiceInterface {
    public function __construct(
        private StyleRepository $styleRepository,
        private StyleMapService $styleMapService,
        private SelectorGeneratorService $selectorGenerator,
        private IDGeneratorService $idGenerator
    ) {}
    
    /**
     * Execute CSS conversion
     * 
     * @param array $params ['classes' => array]
     * @return array ['styles' => array, 'style_map' => array]
     */
    public function execute(array $params): mixed {
        $bricksClasses = $params['classes'] ?? [];
        
        $styles = [];
        $map = [];
        
        foreach ($bricksClasses as $class) {
            $etchId = $this->idGenerator->generate();
            $style = $this->convertClass($class);
            
            $styles[$etchId] = $style;
            
            if (!empty($class['id'])) {
                $map[$class['id']] = $etchId;
            }
        }
        
        return [
            'styles' => $styles,
            'style_map' => $map
        ];
    }
    
    /**
     * Convert single Bricks class to Etch format
     * 
     * @param array $class Bricks class data
     * @return array Etch style data
     */
    private function convertClass(array $class): array {
        $className = !empty($class['name']) ? $class['name'] : $class['id'];
        
        return [
            'selector' => $this->selectorGenerator->generate($className),
            'css' => $this->convertCSS($class),
            'type' => 'class',
            'collection' => 'default',
            'readonly' => false
        ];
    }
    
    /**
     * Convert CSS properties
     * 
     * @param array $class Bricks class data
     * @return string CSS string
     */
    private function convertCSS(array $class): string {
        // TODO: Implement full CSS conversion logic
        // For now, return raw CSS if available
        return $class['css'] ?? '';
    }
}
