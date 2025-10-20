<?php
/**
 * Dependency Injection Container
 * 
 * Simple DI container for service management
 */

namespace BricksEtchMigration\Core;

use Exception;

class Container {
    private array $services = [];
    private array $instances = [];
    
    /**
     * Register a service factory
     * 
     * @param string $id Service identifier
     * @param callable $factory Factory function
     * @param bool $singleton Create only one instance
     * @return void
     */
    public function register(string $id, callable $factory, bool $singleton = true): void {
        $this->services[$id] = [
            'factory' => $factory,
            'singleton' => $singleton
        ];
    }
    
    /**
     * Get service instance
     * 
     * @param string $id Service identifier
     * @return mixed Service instance
     * @throws Exception If service not found
     */
    public function get(string $id): mixed {
        if (!isset($this->services[$id])) {
            throw new Exception("Service not found: {$id}");
        }
        
        $service = $this->services[$id];
        
        // Return cached instance for singletons
        if ($service['singleton'] && isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        // Create new instance
        $instance = $service['factory']($this);
        
        // Cache singleton
        if ($service['singleton']) {
            $this->instances[$id] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Check if service exists
     * 
     * @param string $id Service identifier
     * @return bool
     */
    public function has(string $id): bool {
        return isset($this->services[$id]);
    }
    
    /**
     * Set service instance directly
     * 
     * @param string $id Service identifier
     * @param mixed $instance Service instance
     * @return void
     */
    public function set(string $id, mixed $instance): void {
        $this->instances[$id] = $instance;
        
        // Register as singleton
        $this->register($id, fn() => $instance, true);
    }
}
