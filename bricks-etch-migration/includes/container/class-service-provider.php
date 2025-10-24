<?php
namespace Bricks2Etch\Container;

class B2E_Service_Provider
{
    /**
     * Register core services in the container.
     *
     * @param B2E_Service_Container $container
     */
    public function register(B2E_Service_Container $container)
    {
        // Repository Services
        $container->singleton('settings_repository', function ($c) {
            return new \Bricks2Etch\Repositories\B2E_WordPress_Settings_Repository();
        });

        $container->singleton('migration_repository', function ($c) {
            return new \Bricks2Etch\Repositories\B2E_WordPress_Migration_Repository();
        });

        $container->singleton('style_repository', function ($c) {
            return new \Bricks2Etch\Repositories\B2E_WordPress_Style_Repository();
        });

        // Security Services
        $container->singleton('cors_manager', function ($c) {
            return new \Bricks2Etch\Security\B2E_CORS_Manager($c->get('settings_repository'));
        });

        $container->singleton('rate_limiter', function ($c) {
            return new \Bricks2Etch\Security\B2E_Rate_Limiter();
        });

        $container->singleton('input_validator', function ($c) {
            return new \Bricks2Etch\Security\B2E_Input_Validator();
        });

        $container->singleton('security_headers', function ($c) {
            return new \Bricks2Etch\Security\B2E_Security_Headers();
        });

        $container->singleton('audit_logger', function ($c) {
            return new \Bricks2Etch\Security\B2E_Audit_Logger($c->get('error_handler'));
        });

        $container->singleton('environment_detector', function ($c) {
            return new \Bricks2Etch\Security\B2E_Environment_Detector();
        });

        // Core Services
        $container->singleton('error_handler', function ($c) {
            return new \Bricks2Etch\Core\B2E_Error_Handler();
        });

        $container->singleton('plugin_detector', function ($c) {
            return new \Bricks2Etch\Core\B2E_Plugin_Detector($c->get('error_handler'));
        });

        // API Services
        $container->singleton('api_client', function ($c) {
            return new \Bricks2Etch\Api\B2E_API_Client($c->get('error_handler'));
        });

        // Parser Services
        $container->singleton('content_parser', function ($c) {
            return new \Bricks2Etch\Parsers\B2E_Content_Parser($c->get('error_handler'));
        });

        $container->singleton('dynamic_data_converter', function ($c) {
            return new \Bricks2Etch\Parsers\B2E_Dynamic_Data_Converter($c->get('error_handler'));
        });

        $container->singleton('css_converter', function ($c) {
            return new \Bricks2Etch\Parsers\B2E_CSS_Converter($c->get('error_handler'), $c->get('style_repository'));
        });

        // Converter Services
        $container->singleton('element_factory', function ($c) {
            return new \Bricks2Etch\Converters\B2E_Element_Factory();
        });

        $container->singleton('gutenberg_generator', function ($c) {
            return new \Bricks2Etch\Parsers\B2E_Gutenberg_Generator(
                $c->get('error_handler'),
                $c->get('dynamic_data_converter'),
                $c->get('content_parser')
            );
        });

        // Migrator Services
        $container->singleton('media_migrator', function ($c) {
            return new \Bricks2Etch\Migrators\B2E_Media_Migrator(
                $c->get('error_handler'),
                $c->get('api_client')
            );
        });

        $container->singleton('cpt_migrator', function ($c) {
            return new \Bricks2Etch\Migrators\B2E_CPT_Migrator(
                $c->get('error_handler'),
                $c->get('api_client')
            );
        });

        $container->singleton('acf_migrator', function ($c) {
            return new \Bricks2Etch\Migrators\B2E_ACF_Field_Groups_Migrator(
                $c->get('error_handler'),
                $c->get('api_client')
            );
        });

        $container->singleton('metabox_migrator', function ($c) {
            return new \Bricks2Etch\Migrators\B2E_MetaBox_Migrator(
                $c->get('error_handler'),
                $c->get('api_client')
            );
        });

        $container->singleton('custom_fields_migrator', function ($c) {
            return new \Bricks2Etch\Migrators\B2E_Custom_Fields_Migrator(
                $c->get('error_handler'),
                $c->get('api_client')
            );
        });

        $container->singleton('migrator_registry', function ($c) {
            return \Bricks2Etch\Migrators\B2E_Migrator_Registry::instance();
        });

        $container->singleton('migrator_discovery', function ($c) {
            return new \Bricks2Etch\Migrators\B2E_Migrator_Discovery();
        });

        // Business Services
        $container->singleton('css_service', function ($c) {
            return new \Bricks2Etch\Services\B2E_CSS_Service(
                $c->get('css_converter'),
                $c->get('api_client'),
                $c->get('error_handler')
            );
        });

        $container->singleton('media_service', function ($c) {
            return new \Bricks2Etch\Services\B2E_Media_Service(
                $c->get('media_migrator'),
                $c->get('error_handler')
            );
        });

        $container->singleton('content_service', function ($c) {
            return new \Bricks2Etch\Services\B2E_Content_Service(
                $c->get('content_parser'),
                $c->get('gutenberg_generator'),
                $c->get('error_handler')
            );
        });

        $container->singleton('migration_service', function ($c) {
            return new \Bricks2Etch\Services\B2E_Migration_Service(
                $c->get('error_handler'),
                $c->get('plugin_detector'),
                $c->get('content_parser'),
                $c->get('css_service'),
                $c->get('media_service'),
                $c->get('content_service'),
                $c->get('api_client'),
                $c->get('migrator_registry'),
                $c->get('migration_repository')
            );
        });

        // Controller Services
        $container->singleton('settings_controller', function ($c) {
            return new \Bricks2Etch\Controllers\B2E_Settings_Controller($c->get('api_client'), $c->get('settings_repository'));
        });

        $container->singleton('migration_controller', function ($c) {
            return new \Bricks2Etch\Controllers\B2E_Migration_Controller(
                new \Bricks2Etch\Core\B2E_Migration_Manager($c->get('migration_service'), $c->get('migration_repository')),
                $c->get('api_client')
            );
        });

        $container->singleton('dashboard_controller', function ($c) {
            return new \Bricks2Etch\Controllers\B2E_Dashboard_Controller(
                $c->get('plugin_detector'),
                $c->get('error_handler'),
                $c->get('migration_service'),
                $c->get('settings_controller')
            );
        });

        // AJAX Handler Services
        $container->singleton('validation_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_Validation_Ajax_Handler(
                $c->get('api_client'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('content_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_Content_Ajax_Handler(
                $c->get('migration_service'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('css_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_CSS_Ajax_Handler(
                $c->get('css_service'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('media_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_Media_Ajax_Handler(
                $c->get('media_service'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('logs_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_Logs_Ajax_Handler(
                $c->get('error_handler'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('connection_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_Connection_Ajax_Handler(
                $c->get('api_client'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('cleanup_ajax', function ($c) {
            return new \Bricks2Etch\Ajax\Handlers\B2E_Cleanup_Ajax_Handler(
                $c->get('api_client'),
                $c->get('rate_limiter'),
                $c->get('input_validator'),
                $c->get('audit_logger')
            );
        });

        $container->singleton('ajax_handler', function ($c) {
            return new \Bricks2Etch\Ajax\B2E_Ajax_Handler(
                $c->get('css_ajax'),
                $c->get('content_ajax'),
                $c->get('media_ajax'),
                $c->get('validation_ajax'),
                $c->get('logs_ajax'),
                $c->get('connection_ajax'),
                $c->get('cleanup_ajax')
            );
        });

        // Admin Interface
        $container->singleton('admin_interface', function ($c) {
            return new \Bricks2Etch\Admin\B2E_Admin_Interface(
                $c->get('dashboard_controller'),
                $c->get('settings_controller'),
                $c->get('migration_controller'),
                true
            );
        });
    }

    /**
     * List of provided services.
     *
     * @return array<int, string>
     */
    public function provides()
    {
        return [
            'settings_repository',
            'migration_repository',
            'style_repository',
            'cors_manager',
            'rate_limiter',
            'input_validator',
            'security_headers',
            'audit_logger',
            'environment_detector',
            'error_handler',
            'plugin_detector',
            'api_client',
            'content_parser',
            'dynamic_data_converter',
            'css_converter',
            'element_factory',
            'gutenberg_generator',
            'media_migrator',
            'cpt_migrator',
            'acf_migrator',
            'metabox_migrator',
            'custom_fields_migrator',
            'migrator_registry',
            'migrator_discovery',
            'css_service',
            'media_service',
            'content_service',
            'migration_service',
            'settings_controller',
            'migration_controller',
            'dashboard_controller',
            'validation_ajax',
            'content_ajax',
            'css_ajax',
            'media_ajax',
            'logs_ajax',
            'connection_ajax',
            'cleanup_ajax',
            'ajax_handler',
            'admin_interface',
        ];
    }
}
