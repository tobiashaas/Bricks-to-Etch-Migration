<?php
/**
 * Migration Analyzer for Bricks to Etch Migration Plugin
 * 
 * Analyzes and reports what will be migrated
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Migration_Analyzer {
    
    /**
     * Content parser instance
     */
    private $content_parser;
    
    /**
     * Plugin detector instance
     */
    private $plugin_detector;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->content_parser = new B2E_Content_Parser();
        $this->plugin_detector = new B2E_Plugin_Detector();
    }
    
    /**
     * Generate comprehensive migration report
     */
    public function generate_report() {
        $report = array(
            'posts' => $this->analyze_posts(),
            'css' => $this->analyze_css(),
            'custom_post_types' => $this->analyze_custom_post_types(),
            'custom_fields' => $this->analyze_custom_fields(),
            'summary' => array(),
            'warnings' => array(),
            'estimated_time' => 0,
            'estimated_size' => 0
        );
        
        // Calculate summary
        $report['summary'] = $this->calculate_summary($report);
        
        // Calculate estimates
        $report['estimated_time'] = $this->estimate_migration_time($report);
        $report['estimated_size'] = $this->estimate_migration_size($report);
        
        // Add warnings
        $report['warnings'] = $this->generate_warnings($report);
        
        return $report;
    }
    
    /**
     * Analyze posts
     */
    private function analyze_posts() {
        $bricks_posts = $this->content_parser->get_bricks_posts();
        
        $posts_by_type = array();
        $posts_by_status = array();
        $total_content_size = 0;
        
        foreach ($bricks_posts as $post) {
            // Count by type
            if (!isset($posts_by_type[$post->post_type])) {
                $posts_by_type[$post->post_type] = 0;
            }
            $posts_by_type[$post->post_type]++;
            
            // Count by status
            if (!isset($posts_by_status[$post->post_status])) {
                $posts_by_status[$post->post_status] = 0;
            }
            $posts_by_status[$post->post_status]++;
            
            // Estimate size
            $bricks_content = get_post_meta($post->ID, '_bricks_page_content_2', true);
            if ($bricks_content) {
                $total_content_size += strlen(maybe_serialize($bricks_content));
            }
        }
        
        return array(
            'total' => count($bricks_posts),
            'by_type' => $posts_by_type,
            'by_status' => $posts_by_status,
            'content_size_bytes' => $total_content_size,
            'content_size_formatted' => size_format($total_content_size)
        );
    }
    
    /**
     * Analyze CSS classes
     */
    private function analyze_css() {
        $bricks_classes = get_option('bricks_global_classes', array());
        
        $css_stats = array(
            'total_classes' => 0,
            'has_media_queries' => 0,
            'has_variables' => 0,
            'total_size_bytes' => 0
        );
        
        if (!empty($bricks_classes)) {
            $css_stats['total_classes'] = count($bricks_classes);
            
            foreach ($bricks_classes as $class) {
                $css_string = maybe_serialize($class);
                $css_stats['total_size_bytes'] += strlen($css_string);
                
                // Check for media queries
                if (isset($class['settings'])) {
                    foreach ($class['settings'] as $setting) {
                        if (is_array($setting) && isset($setting['breakpoint'])) {
                            $css_stats['has_media_queries']++;
                            break;
                        }
                    }
                }
                
                // Check for CSS variables
                if (isset($class['css']) && strpos($class['css'], 'var(--') !== false) {
                    $css_stats['has_variables']++;
                }
            }
        }
        
        $css_stats['size_formatted'] = size_format($css_stats['total_size_bytes']);
        
        return $css_stats;
    }
    
    /**
     * Analyze custom post types
     */
    private function analyze_custom_post_types() {
        $public_post_types = get_post_types(array('public' => true), 'objects');
        $custom_post_types = array();
        
        // Exclude built-in types
        $exclude = array('post', 'page', 'attachment');
        
        foreach ($public_post_types as $post_type) {
            if (!in_array($post_type->name, $exclude)) {
                $count = wp_count_posts($post_type->name);
                $custom_post_types[$post_type->name] = array(
                    'label' => $post_type->label,
                    'name' => $post_type->name,
                    'count' => $count->publish + $count->draft + $count->private,
                    'published' => $count->publish,
                    'draft' => $count->draft
                );
            }
        }
        
        return array(
            'total' => count($custom_post_types),
            'types' => $custom_post_types
        );
    }
    
    /**
     * Analyze custom fields
     */
    private function analyze_custom_fields() {
        $plugins = $this->plugin_detector->get_installed_plugins();
        $field_groups = array();
        
        // ACF
        if ($plugins['acf']) {
            $acf_groups = get_posts(array(
                'post_type' => 'acf-field-group',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            $field_groups['acf'] = array(
                'active' => true,
                'field_groups' => count($acf_groups),
                'names' => array_map(function($group) {
                    return $group->post_title;
                }, $acf_groups)
            );
        } else {
            $field_groups['acf'] = array('active' => false);
        }
        
        // MetaBox
        if ($plugins['metabox']) {
            $metabox_configs = get_posts(array(
                'post_type' => 'meta-box',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            $field_groups['metabox'] = array(
                'active' => true,
                'configs' => count($metabox_configs),
                'names' => array_map(function($config) {
                    return $config->post_title;
                }, $metabox_configs)
            );
        } else {
            $field_groups['metabox'] = array('active' => false);
        }
        
        // JetEngine
        if ($plugins['jetengine']) {
            $field_groups['jetengine'] = array(
                'active' => true,
                'note' => 'JetEngine fields will be exported'
            );
        } else {
            $field_groups['jetengine'] = array('active' => false);
        }
        
        return $field_groups;
    }
    
    /**
     * Calculate summary
     */
    private function calculate_summary($report) {
        $summary = array();
        
        // Total items
        $summary['total_items'] = 
            $report['posts']['total'] + 
            $report['css']['total_classes'] + 
            $report['custom_post_types']['total'];
        
        // Post types breakdown
        $summary['post_types'] = array();
        foreach ($report['posts']['by_type'] as $type => $count) {
            $post_type_obj = get_post_type_object($type);
            $summary['post_types'][] = array(
                'type' => $type,
                'label' => $post_type_obj ? $post_type_obj->label : $type,
                'count' => $count
            );
        }
        
        // Active plugins
        $summary['active_plugins'] = array();
        if ($report['custom_fields']['acf']['active']) {
            $summary['active_plugins'][] = 'Advanced Custom Fields';
        }
        if ($report['custom_fields']['metabox']['active']) {
            $summary['active_plugins'][] = 'MetaBox';
        }
        if ($report['custom_fields']['jetengine']['active']) {
            $summary['active_plugins'][] = 'JetEngine';
        }
        
        return $summary;
    }
    
    /**
     * Estimate migration time
     */
    private function estimate_migration_time($report) {
        // Base time estimates (in seconds)
        $time_per_post = 2; // 2 seconds per post
        $time_per_css_class = 0.1; // 0.1 second per CSS class
        $time_per_cpt = 1; // 1 second per CPT
        $base_overhead = 10; // 10 seconds base overhead
        
        $estimated_seconds = 
            ($report['posts']['total'] * $time_per_post) +
            ($report['css']['total_classes'] * $time_per_css_class) +
            ($report['custom_post_types']['total'] * $time_per_cpt) +
            $base_overhead;
        
        return array(
            'seconds' => round($estimated_seconds),
            'formatted' => $this->format_time($estimated_seconds),
            'range' => array(
                'min' => $this->format_time($estimated_seconds * 0.8),
                'max' => $this->format_time($estimated_seconds * 1.5)
            )
        );
    }
    
    /**
     * Estimate migration size
     */
    private function estimate_migration_size($report) {
        $total_bytes = 
            $report['posts']['content_size_bytes'] + 
            $report['css']['total_size_bytes'];
        
        return array(
            'bytes' => $total_bytes,
            'formatted' => size_format($total_bytes)
        );
    }
    
    /**
     * Generate warnings
     */
    private function generate_warnings($report) {
        $warnings = array();
        
        // No posts warning
        if ($report['posts']['total'] === 0) {
            $warnings[] = array(
                'level' => 'error',
                'message' => 'No Bricks content found. Nothing to migrate.'
            );
        }
        
        // No CSS warning
        if ($report['css']['total_classes'] === 0) {
            $warnings[] = array(
                'level' => 'warning',
                'message' => 'No Bricks global classes found.'
            );
        }
        
        // Large migration warning
        if ($report['posts']['total'] > 500) {
            $warnings[] = array(
                'level' => 'info',
                'message' => 'Large migration detected (' . $report['posts']['total'] . ' posts). This may take a while.'
            );
        }
        
        // No custom fields warning
        if (!$report['custom_fields']['acf']['active'] && 
            !$report['custom_fields']['metabox']['active'] && 
            !$report['custom_fields']['jetengine']['active']) {
            $warnings[] = array(
                'level' => 'info',
                'message' => 'No custom field plugins detected. Custom fields will not be migrated.'
            );
        }
        
        return $warnings;
    }
    
    /**
     * Format time duration
     */
    private function format_time($seconds) {
        if ($seconds < 60) {
            return round($seconds) . ' seconds';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . ' minutes';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $minutes . ' minutes';
        }
    }
}

