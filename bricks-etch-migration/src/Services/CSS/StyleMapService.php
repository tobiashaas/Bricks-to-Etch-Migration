<?php
/**
 * Style Map Service
 * 
 * Manages Bricks → Etch ID mappings
 */

namespace BricksEtchMigration\Services\CSS;

use BricksEtchMigration\Services\Storage\StyleMapRepository;
use BricksEtchMigration\DTOs\StyleMapDTO;

class StyleMapService {
    public function __construct(
        private StyleMapRepository $repository
    ) {}
    
    /**
     * Resolve Bricks IDs to Etch IDs
     * 
     * @param array $bricksIds Array of Bricks style IDs
     * @return array Array of Etch style IDs
     */
    public function resolveStyleIds(array $bricksIds): array {
        return $this->repository->resolveMany($bricksIds);
    }
    
    /**
     * Create mapping from styles array
     * 
     * @param array $styles Styles with _bricks_id key
     * @return array Mapping [bricks_id => etch_id]
     */
    public function createMapping(array $styles): array {
        $map = [];
        
        foreach ($styles as $etchId => $style) {
            if (isset($style['_bricks_id'])) {
                $map[$style['_bricks_id']] = $etchId;
            }
        }
        
        return $map;
    }
    
    /**
     * Save mapping
     * 
     * @param array $map Mapping [bricks_id => etch_id]
     * @return bool Success status
     */
    public function saveMapping(array $map): bool {
        return $this->repository->save($map);
    }
    
    /**
     * Get mapping count
     * 
     * @return int
     */
    public function getMappingCount(): int {
        return $this->repository->count();
    }
}
