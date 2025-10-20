<?php
/**
 * Style Map Repository
 * 
 * Handles persistence of Bricks → Etch ID mappings
 */

namespace BricksEtchMigration\Services\Storage;

use BricksEtchMigration\DTOs\StyleMapDTO;

class StyleMapRepository {
    private string $optionName = 'b2e_style_map';
    
    /**
     * Get Etch ID for Bricks ID
     * 
     * @param string $bricksId Bricks style ID
     * @return string|null Etch style ID or null
     */
    public function getMapping(string $bricksId): ?string {
        $map = $this->getAll();
        return $map[$bricksId] ?? null;
    }
    
    /**
     * Get all mappings
     * 
     * @return array Associative array [bricks_id => etch_id]
     */
    public function getAll(): array {
        return get_option($this->optionName, []);
    }
    
    /**
     * Save mapping
     * 
     * @param array $map Associative array [bricks_id => etch_id]
     * @return bool Success status
     */
    public function save(array $map): bool {
        return update_option($this->optionName, $map);
    }
    
    /**
     * Save from DTOs
     * 
     * @param array<StyleMapDTO> $dtos
     * @return bool Success status
     */
    public function saveDTOs(array $dtos): bool {
        $map = StyleMapDTO::toMap($dtos);
        return $this->save($map);
    }
    
    /**
     * Add single mapping
     * 
     * @param string $bricksId
     * @param string $etchId
     * @return bool Success status
     */
    public function addMapping(string $bricksId, string $etchId): bool {
        $map = $this->getAll();
        $map[$bricksId] = $etchId;
        return $this->save($map);
    }
    
    /**
     * Resolve multiple Bricks IDs to Etch IDs
     * 
     * @param array $bricksIds
     * @return array Etch IDs (only found ones)
     */
    public function resolveMany(array $bricksIds): array {
        $map = $this->getAll();
        $etchIds = [];
        
        foreach ($bricksIds as $bricksId) {
            if (isset($map[$bricksId])) {
                $etchIds[] = $map[$bricksId];
            }
        }
        
        return $etchIds;
    }
    
    /**
     * Clear all mappings
     * 
     * @return bool Success status
     */
    public function clear(): bool {
        return delete_option($this->optionName);
    }
    
    /**
     * Get count of mappings
     * 
     * @return int
     */
    public function count(): int {
        return count($this->getAll());
    }
}
