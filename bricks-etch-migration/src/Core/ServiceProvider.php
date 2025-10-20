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
use BricksEtchMigration\Services\Content\BlockGeneratorService;
use BricksEtchMigration\Services\Content\ContentMigrationService;
use BricksEtchMigration\Services\API\APIClientService;
use BricksEtchMigration\UI\AdminPageService;

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
        
        // Content Services
        $container->register('block_generator', function($c) {
            return new BlockGeneratorService(
                $c->get('style_map_service')
            );
        });
        
        $container->register('content_migration_service', function($c) {
            return new ContentMigrationService(
                $c->get('block_generator')
            );
        });
        
        // API Services
        $container->register('api_client', function() {
            return new APIClientService();
        });
        
        // UI Services
        $container->register('admin_page', function($c) {
            return new AdminPageService(
                $c->get('css_converter_service'),
                $c->get('content_migration_service'),
                $c->get('api_client')
            );
        });
    }
}
