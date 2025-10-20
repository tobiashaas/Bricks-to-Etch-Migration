<?php
/**
 * Main Plugin Class
 * 
 * Bootstraps the plugin and initializes services
 */

namespace BricksEtchMigration\Core;

class Plugin {
    private static ?Plugin $instance = null;
    private Container $container;
    
    /**
     * Get singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor (Singleton)
     */
    private function __construct() {
        $this->container = new Container();
        $this->registerServices();
    }
    
    /**
     * Register all services
     * 
     * @return void
     */
    private function registerServices(): void {
        $provider = new ServiceProvider();
        $provider->register($this->container);
    }
    
    /**
     * Get container
     * 
     * @return Container
     */
    public function getContainer(): Container {
        return $this->container;
    }
    
    /**
     * Get service from container
     * 
     * @param string $id Service identifier
     * @return mixed
     */
    public function get(string $id): mixed {
        return $this->container->get($id);
    }
    
    /**
     * Initialize plugin
     * 
     * @return void
     */
    public function init(): void {
        // Hook into WordPress
        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
    }
    
    /**
     * Plugins loaded hook
     * 
     * @return void
     */
    public function onPluginsLoaded(): void {
        // Initialize admin interface
        if (is_admin()) {
            $this->container->get('admin_page');
        }
    }
}
