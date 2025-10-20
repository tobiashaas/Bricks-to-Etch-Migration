<?php
/**
 * Style Data Transfer Object
 * 
 * Immutable object representing a CSS style
 */

namespace BricksEtchMigration\DTOs;

class StyleDTO {
    public function __construct(
        public readonly string $id,
        public readonly string $selector,
        public readonly string $css,
        public readonly string $type,
        public readonly string $collection = 'default',
        public readonly bool $readonly = false
    ) {}
    
    /**
     * Create from array
     * 
     * @param string $id Style ID
     * @param array $data Style data
     * @return self
     */
    public static function fromArray(string $id, array $data): self {
        return new self(
            $id,
            $data['selector'] ?? '',
            $data['css'] ?? '',
            $data['type'] ?? 'class',
            $data['collection'] ?? 'default',
            $data['readonly'] ?? false
        );
    }
    
    /**
     * Convert to array for storage
     * 
     * @return array
     */
    public function toArray(): array {
        return [
            'selector' => $this->selector,
            'css' => $this->css,
            'type' => $this->type,
            'collection' => $this->collection,
            'readonly' => $this->readonly
        ];
    }
    
    /**
     * Check if style is valid
     * 
     * @return bool
     */
    public function isValid(): bool {
        return !empty($this->selector) && !empty($this->type);
    }
}
