<?php
/**
 * Style Repository
 * 
 * Handles persistence of Etch styles
 */

namespace BricksEtchMigration\Services\Storage;

use BricksEtchMigration\Interfaces\RepositoryInterface;
use BricksEtchMigration\DTOs\StyleDTO;

class StyleRepository implements RepositoryInterface {
    private string $optionName = 'etch_styles';
    
    /**
     * Find style by ID
     * 
     * @param string $id Style ID
     * @return array|null Style data or null
     */
    public function find(string $id): ?array {
        $styles = $this->findAll();
        return $styles[$id] ?? null;
    }
    
    /**
     * Find all styles
     * 
     * @return array All styles
     */
    public function findAll(): array {
        return get_option($this->optionName, []);
    }
    
    /**
     * Save single style
     * 
     * @param string $id Style ID
     * @param array $data Style data
     * @return bool Success status
     */
    public function save(string $id, array $data): bool {
        $styles = $this->findAll();
        $styles[$id] = $data;
        return update_option($this->optionName, $styles);
    }
    
    /**
     * Save multiple styles
     * 
     * @param array $styles Associative array [id => data]
     * @return bool Success status
     */
    public function saveMany(array $styles): bool {
        $existing = $this->findAll();
        $merged = array_merge($existing, $styles);
        return update_option($this->optionName, $merged);
    }
    
    /**
     * Save styles from DTOs
     * 
     * @param array<StyleDTO> $styleDTOs
     * @return bool Success status
     */
    public function saveDTOs(array $styleDTOs): bool {
        $styles = [];
        
        foreach ($styleDTOs as $dto) {
            if ($dto->isValid()) {
                $styles[$dto->id] = $dto->toArray();
            }
        }
        
        return $this->saveMany($styles);
    }
    
    /**
     * Delete style
     * 
     * @param string $id Style ID
     * @return bool Success status
     */
    public function delete(string $id): bool {
        $styles = $this->findAll();
        
        if (!isset($styles[$id])) {
            return false;
        }
        
        unset($styles[$id]);
        return update_option($this->optionName, $styles);
    }
    
    /**
     * Clear all styles
     * 
     * @return bool Success status
     */
    public function clear(): bool {
        return delete_option($this->optionName);
    }
    
    /**
     * Get count of styles
     * 
     * @return int
     */
    public function count(): int {
        return count($this->findAll());
    }
}
