<?php
/**
 * Style Map Data Transfer Object
 * 
 * Represents mapping between Bricks and Etch style IDs
 */

namespace BricksEtchMigration\DTOs;

class StyleMapDTO {
    public function __construct(
        public readonly string $bricksId,
        public readonly string $etchId
    ) {}
    
    /**
     * Create from array
     * 
     * @param array $map Associative array [bricks_id => etch_id]
     * @return array<self>
     */
    public static function fromMap(array $map): array {
        $dtos = [];
        
        foreach ($map as $bricksId => $etchId) {
            $dtos[] = new self($bricksId, $etchId);
        }
        
        return $dtos;
    }
    
    /**
     * Convert array of DTOs to map
     * 
     * @param array<self> $dtos
     * @return array Associative array [bricks_id => etch_id]
     */
    public static function toMap(array $dtos): array {
        $map = [];
        
        foreach ($dtos as $dto) {
            $map[$dto->bricksId] = $dto->etchId;
        }
        
        return $map;
    }
}
