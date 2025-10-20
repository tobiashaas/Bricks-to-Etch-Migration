<?php
/**
 * Service Interface
 * 
 * Defines contract for service layer implementations
 */

namespace BricksEtchMigration\Interfaces;

interface ServiceInterface {
    /**
     * Execute service operation
     * 
     * @param array $params Operation parameters
     * @return mixed Operation result
     */
    public function execute(array $params): mixed;
}
