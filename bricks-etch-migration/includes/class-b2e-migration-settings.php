<?php
/**
 * Migration Settings Manager for Bricks to Etch Migration Plugin
 * 
 * Manages selective migration settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Migration_Settings {
    
    /**
     * Settings option name
     */
    private $option_name = 'b2e_migration_settings';
    
    /**
     * Get default settings
     */
    public function get_defaults() {
        return array(
            // Core migration - always enabled
            'migrate_posts' => true,
            'migrate_pages' => true,
            'migrate_css' => true,
            'migrate_media' => true,
            
            // Advanced features - enabled by default
            'migrate_cpts' => true,
            'migrate_acf' => true,
            'migrate_metabox' => true,
            
            
            // Advanced settings - use defaults
            'selected_post_types' => array(),
            'selected_post_statuses' => array('publish'),
            
            // Conversion options - enabled by default
            'convert_div_to_flex' => true
        );
    }
    
    /**
     * Get current settings
     */
    public function get_settings() {
        $saved = get_option($this->option_name, array());
        return array_merge($this->get_defaults(), $saved);
    }
    
    /**
     * Save settings
     */
    public function save_settings($settings) {
        $defaults = $this->get_defaults();
        $validated = array();
        
        // Validate and sanitize each setting
        foreach ($defaults as $key => $default_value) {
            if (isset($settings[$key])) {
                if (is_bool($default_value)) {
                    $validated[$key] = (bool) $settings[$key];
                } elseif (is_array($default_value)) {
                    $validated[$key] = array_map('sanitize_text_field', (array) $settings[$key]);
                } else {
                    $validated[$key] = sanitize_text_field($settings[$key]);
                }
            } else {
                $validated[$key] = $default_value;
            }
        }
        
        update_option($this->option_name, $validated);
        
        return $validated;
    }
    
    /**
     * Check if should migrate posts
     */
    public function should_migrate_posts() {
        $settings = $this->get_settings();
        return $settings['migrate_posts'];
    }
    
    /**
     * Check if should migrate pages
     */
    public function should_migrate_pages() {
        $settings = $this->get_settings();
        return $settings['migrate_pages'];
    }
    
    /**
     * Check if should migrate CSS
     */
    public function should_migrate_css() {
        $settings = $this->get_settings();
        return $settings['migrate_css'];
    }
    
    /**
     * Check if should migrate custom post types
     */
    public function should_migrate_cpts() {
        $settings = $this->get_settings();
        return $settings['migrate_cpts'];
    }
    
    /**
     * Check if should migrate ACF
     */
    public function should_migrate_acf() {
        $settings = $this->get_settings();
        return $settings['migrate_acf'];
    }
    
    /**
     * Check if should migrate MetaBox
     */
    public function should_migrate_metabox() {
        $settings = $this->get_settings();
        return $settings['migrate_metabox'];
    }
    
    /**
     * Get selected post types
     */
    public function get_selected_post_types() {
        $settings = $this->get_settings();
        
        $selected = array();
        
        if ($settings['migrate_posts']) {
            $selected[] = 'post';
        }
        
        if ($settings['migrate_pages']) {
            $selected[] = 'page';
        }
        
        if ($settings['migrate_cpts'] && !empty($settings['selected_post_types'])) {
            $selected = array_merge($selected, $settings['selected_post_types']);
        }
        
        return array_unique($selected);
    }
    
    /**
     * Get selected post statuses
     */
    public function get_selected_post_statuses() {
        $settings = $this->get_settings();
        return $settings['selected_post_statuses'];
    }
    
    /**
     * Check if should cleanup Bricks meta
     */
    public function should_cleanup_bricks_meta() {
        $settings = $this->get_settings();
        return $settings['cleanup_bricks_meta'];
    }
    
    /**
     * Check if should convert div to flex
     */
    public function should_convert_div_to_flex() {
        $settings = $this->get_settings();
        return $settings['convert_div_to_flex'];
    }
    
    /**
     * Get migration scope summary
     */
    public function get_scope_summary() {
        $settings = $this->get_settings();
        $scope = array();
        
        if ($settings['migrate_posts']) {
            $scope[] = 'Posts';
        }
        
        if ($settings['migrate_pages']) {
            $scope[] = 'Pages';
        }
        
        if ($settings['migrate_css']) {
            $scope[] = 'CSS Classes';
        }
        
        if ($settings['migrate_cpts']) {
            if (!empty($settings['selected_post_types'])) {
                $scope[] = count($settings['selected_post_types']) . ' Custom Post Types';
            } else {
                $scope[] = 'All Custom Post Types';
            }
        }
        
        if ($settings['migrate_acf']) {
            $scope[] = 'ACF Fields';
        }
        
        if ($settings['migrate_metabox']) {
            $scope[] = 'MetaBox Fields';
        }
        
        if ($settings['migrate_jetengine']) {
            $scope[] = 'JetEngine Fields';
        }
        
        return $scope;
    }
    
    /**
     * Reset to defaults
     */
    public function reset_to_defaults() {
        delete_option($this->option_name);
        return $this->get_defaults();
    }
}

