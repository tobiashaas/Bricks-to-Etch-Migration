<?php
/**
 * Repository Interface
 * 
 * Defines contract for data persistence layer
 */

namespace BricksEtchMigration\Interfaces;

interface RepositoryInterface {
    /**
     * Find entity by ID
     * 
     * @param string $id Entity ID
     * @return array|null Entity data or null if not found
     */
    public function find(string $id): ?array;
    
    /**
     * Find all entities
     * 
     * @return array All entities
     */
    public function findAll(): array;
    
    /**
     * Save entity
     * 
     * @param string $id Entity ID
     * @param array $data Entity data
     * @return bool True on success, false on failure
     */
    public function save(string $id, array $data): bool;
    
    /**
     * Delete entity
     * 
     * @param string $id Entity ID
     * @return bool True on success, false on failure
     */
    public function delete(string $id): bool;
}
