<?php
/**
 * ID Generator Service
 * 
 * Generates Etch-compatible style IDs
 */

namespace BricksEtchMigration\Services\CSS;

class IDGeneratorService {
    /**
     * Generate Etch-compatible ID
     * 
     * Uses uniqid() like Etch does in frontend
     * 
     * @return string 7-character ID
     */
    public function generate(): string {
        return substr(uniqid(), -7);
    }
    
    /**
     * Generate multiple IDs
     * 
     * @param int $count Number of IDs to generate
     * @return array<string>
     */
    public function generateMany(int $count): array {
        $ids = [];
        
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->generate();
        }
        
        return $ids;
    }
}
