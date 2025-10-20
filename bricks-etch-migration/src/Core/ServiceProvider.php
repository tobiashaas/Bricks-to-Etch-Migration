<?php
/**
 * Service Provider
 * 
 * Registers all services in the DI container
 */

namespace BricksEtchMigration\Core;

use BricksEtchMigration\Services\Storage\StyleRepository;
use BricksEtchMigration\Services\Storage\StyleMapRepository;
use BricksEtchMigration\Services\CSS\IDGeneratorService;
use BricksEtchMigration\Services\CSS\SelectorGeneratorService;
use BricksEtchMigration\Services\CSS\StyleMapService;
use BricksEtchMigration\Services\CSS\CSSConverterService;
use BricksEtchMigration\Services\CSS\CSSPropertyConverter;

class ServiceProvider {
    /**
     * Register all services
     * 
     * @param Container $container
     * @return void
     */
    public function register(Container $container): void {
        // Repositories
        $container->register('style_repository', function() {
            return new StyleRepository();
        });
        
        $container->register('style_map_repository', function() {
            return new StyleMapRepository();
        });
        
        // Utilities
        $container->register('id_generator', function() {
            return new IDGeneratorService();
        });
        
        $container->register('selector_generator', function() {
            return new SelectorGeneratorService();
        });
        
        $container->register('property_converter', function() {
            return new CSSPropertyConverter();
        });
        
        // Services
        $container->register('style_map_service', function($c) {
            return new StyleMapService(
                $c->get('style_map_repository')
            );
        });
        
        $container->register('css_converter_service', function($c) {
            return new CSSConverterService(
                $c->get('style_repository'),
                $c->get('style_map_service'),
                $c->get('selector_generator'),
                $c->get('id_generator'),
                $c->get('property_converter')
            );
        });
    }
}
