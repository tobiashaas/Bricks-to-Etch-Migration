<?php
/**
 * Converter Interface
 * 
 * Defines contract for all converter implementations
 */

namespace BricksEtchMigration\Interfaces;

interface ConverterInterface {
    /**
     * Convert input data to output format
     * 
     * @param array $input Input data to convert
     * @return array Converted data
     */
    public function convert(array $input): array;
    
    /**
     * Validate input data before conversion
     * 
     * @param array $input Input data to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(array $input): bool;
}
