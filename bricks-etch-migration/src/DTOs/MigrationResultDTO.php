<?php
/**
 * Migration Result Data Transfer Object
 * 
 * Represents the result of a migration operation
 */

namespace BricksEtchMigration\DTOs;

class MigrationResultDTO {
    public function __construct(
        public readonly bool $success,
        public readonly int $itemsProcessed,
        public readonly int $itemsSucceeded,
        public readonly int $itemsFailed,
        public readonly array $errors = [],
        public readonly array $metadata = []
    ) {}
    
    /**
     * Create success result
     * 
     * @param int $itemsProcessed
     * @param array $metadata
     * @return self
     */
    public static function success(int $itemsProcessed, array $metadata = []): self {
        return new self(
            true,
            $itemsProcessed,
            $itemsProcessed,
            0,
            [],
            $metadata
        );
    }
    
    /**
     * Create failure result
     * 
     * @param int $itemsProcessed
     * @param int $itemsFailed
     * @param array $errors
     * @return self
     */
    public static function failure(int $itemsProcessed, int $itemsFailed, array $errors): self {
        return new self(
            false,
            $itemsProcessed,
            $itemsProcessed - $itemsFailed,
            $itemsFailed,
            $errors
        );
    }
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array {
        return [
            'success' => $this->success,
            'items_processed' => $this->itemsProcessed,
            'items_succeeded' => $this->itemsSucceeded,
            'items_failed' => $this->itemsFailed,
            'errors' => $this->errors,
            'metadata' => $this->metadata
        ];
    }
}
