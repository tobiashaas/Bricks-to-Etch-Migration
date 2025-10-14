<?php
/**
 * Cross-Plugin Converter for Bricks to Etch Migration Plugin
 * 
 * Handles conversion between different custom field plugins (ACF, MetaBox, JetEngine)
 * This is a V1.1 feature for advanced users
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Cross_Plugin_Converter {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new B2E_Error_Handler();
    }
    
    /**
     * Convert ACF field group to MetaBox format
     * 
     * V1.1 Feature: Advanced cross-plugin conversion
     */
    public function acf_field_group_to_metabox($acf_field_group) {
        if (empty($acf_field_group) || !is_array($acf_field_group)) {
            return null;
        }
        
        $metabox_config = array(
            'title' => $acf_field_group['title'] ?? 'Converted from ACF',
            'post_name' => sanitize_title($acf_field_group['title'] ?? 'converted-from-acf'),
            'post_content' => $acf_field_group['description'] ?? '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'post_type' => 'meta-box',
            'meta' => array(
                '_meta_box_title' => $acf_field_group['title'] ?? 'Converted from ACF',
                '_meta_box_context' => 'normal',
                '_meta_box_priority' => 'high',
                '_meta_box_autosave' => 'off',
                '_meta_box_closed' => 'off',
                '_meta_box_collapsible' => 'off',
                '_meta_box_style' => 'default',
                '_meta_box_fields' => array(),
            ),
        );
        
        // Convert fields
        if (!empty($acf_field_group['fields']) && is_array($acf_field_group['fields'])) {
            foreach ($acf_field_group['fields'] as $acf_field) {
                $metabox_field = $this->convert_acf_field_to_metabox($acf_field);
                if ($metabox_field) {
                    $metabox_config['meta']['_meta_box_fields'][] = $metabox_field;
                }
            }
        }
        
        // Convert location rules
        if (!empty($acf_field_group['location'])) {
            $metabox_config['meta']['_meta_box_post_types'] = $this->convert_acf_location_to_metabox($acf_field_group['location']);
        }
        
        return $metabox_config;
    }
    
    /**
     * Convert MetaBox configuration to ACF format
     * 
     * V1.1 Feature: Advanced cross-plugin conversion
     */
    public function metabox_field_group_to_acf($metabox_config) {
        if (empty($metabox_config) || !is_array($metabox_config)) {
            return null;
        }
        
        $acf_field_group = array(
            'title' => $metabox_config['title'] ?? 'Converted from MetaBox',
            'key' => 'group_' . uniqid(),
            'fields' => array(),
            'location' => array(),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => array(),
            'active' => true,
        );
        
        // Convert fields
        if (!empty($metabox_config['meta']['_meta_box_fields']) && is_array($metabox_config['meta']['_meta_box_fields'])) {
            foreach ($metabox_config['meta']['_meta_box_fields'] as $metabox_field) {
                $acf_field = $this->convert_metabox_field_to_acf($metabox_field);
                if ($acf_field) {
                    $acf_field_group['fields'][] = $acf_field;
                }
            }
        }
        
        // Convert post types
        if (!empty($metabox_config['meta']['_meta_box_post_types'])) {
            $acf_field_group['location'] = $this->convert_metabox_post_types_to_acf($metabox_config['meta']['_meta_box_post_types']);
        }
        
        return $acf_field_group;
    }
    
    /**
     * Convert ACF field to MetaBox field
     */
    private function convert_acf_field_to_metabox($acf_field) {
        if (empty($acf_field) || !is_array($acf_field)) {
            return null;
        }
        
        $metabox_field = array(
            'name' => $acf_field['name'] ?? '',
            'label' => $acf_field['label'] ?? '',
            'type' => $this->convert_acf_field_type_to_metabox($acf_field['type'] ?? 'text'),
            'description' => $acf_field['instructions'] ?? '',
            'required' => $acf_field['required'] ?? false,
            'placeholder' => $acf_field['placeholder'] ?? '',
            'default' => $acf_field['default_value'] ?? '',
        );
        
        // Add type-specific settings
        switch ($acf_field['type']) {
            case 'text':
            case 'textarea':
            case 'email':
            case 'url':
            case 'password':
                $metabox_field['placeholder'] = $acf_field['placeholder'] ?? '';
                break;
                
            case 'number':
                $metabox_field['min'] = $acf_field['min'] ?? '';
                $metabox_field['max'] = $acf_field['max'] ?? '';
                $metabox_field['step'] = $acf_field['step'] ?? '';
                break;
                
            case 'select':
            case 'checkbox':
            case 'radio':
                $metabox_field['options'] = $acf_field['choices'] ?? array();
                break;
                
            case 'date_picker':
                $metabox_field['date_format'] = $acf_field['display_format'] ?? 'Y-m-d';
                break;
                
            case 'time_picker':
                $metabox_field['time_format'] = $acf_field['display_format'] ?? 'H:i';
                break;
                
            case 'image':
                $metabox_field['return_format'] = $acf_field['return_format'] ?? 'array';
                break;
                
            case 'gallery':
                $metabox_field['return_format'] = $acf_field['return_format'] ?? 'array';
                break;
                
            case 'repeater':
                $metabox_field['min'] = $acf_field['min'] ?? '';
                $metabox_field['max'] = $acf_field['max'] ?? '';
                $metabox_field['layout'] = $acf_field['layout'] ?? 'table';
                break;
        }
        
        return $metabox_field;
    }
    
    /**
     * Convert MetaBox field to ACF field
     */
    private function convert_metabox_field_to_acf($metabox_field) {
        if (empty($metabox_field) || !is_array($metabox_field)) {
            return null;
        }
        
        $acf_field = array(
            'name' => $metabox_field['name'] ?? '',
            'label' => $metabox_field['label'] ?? '',
            'type' => $this->convert_metabox_field_type_to_acf($metabox_field['type'] ?? 'text'),
            'instructions' => $metabox_field['description'] ?? '',
            'required' => $metabox_field['required'] ?? false,
            'default_value' => $metabox_field['default'] ?? '',
            'placeholder' => $metabox_field['placeholder'] ?? '',
        );
        
        // Add type-specific settings
        switch ($metabox_field['type']) {
            case 'text':
            case 'textarea':
            case 'email':
            case 'url':
            case 'password':
                $acf_field['placeholder'] = $metabox_field['placeholder'] ?? '';
                break;
                
            case 'number':
                $acf_field['min'] = $metabox_field['min'] ?? '';
                $acf_field['max'] = $metabox_field['max'] ?? '';
                $acf_field['step'] = $metabox_field['step'] ?? '';
                break;
                
            case 'select':
            case 'checkbox_list':
            case 'radio':
                $acf_field['choices'] = $metabox_field['options'] ?? array();
                break;
                
            case 'date':
                $acf_field['display_format'] = $metabox_field['date_format'] ?? 'Y-m-d';
                $acf_field['return_format'] = $metabox_field['date_format'] ?? 'Y-m-d';
                break;
                
            case 'time':
                $acf_field['display_format'] = $metabox_field['time_format'] ?? 'H:i';
                $acf_field['return_format'] = $metabox_field['time_format'] ?? 'H:i';
                break;
                
            case 'image':
                $acf_field['return_format'] = $metabox_field['return_format'] ?? 'array';
                break;
                
            case 'image_advanced':
                $acf_field['return_format'] = $metabox_field['return_format'] ?? 'array';
                break;
        }
        
        return $acf_field;
    }
    
    /**
     * Convert ACF field type to MetaBox type
     */
    private function convert_acf_field_type_to_metabox($acf_type) {
        $mapping = array(
            'text' => 'text',
            'textarea' => 'textarea',
            'number' => 'number',
            'email' => 'email',
            'url' => 'url',
            'password' => 'password',
            'wysiwyg' => 'wysiwyg',
            'image' => 'image',
            'file' => 'file',
            'gallery' => 'image_advanced',
            'select' => 'select',
            'checkbox' => 'checkbox_list',
            'radio' => 'radio',
            'button_group' => 'button_group',
            'true_false' => 'switch',
            'date_picker' => 'date',
            'time_picker' => 'time',
            'date_time_picker' => 'datetime',
            'color_picker' => 'color',
            'range' => 'slider',
            'repeater' => 'group',
            'flexible_content' => 'group',
            'clone' => 'group',
            'group' => 'group',
        );
        
        return $mapping[$acf_type] ?? 'text';
    }
    
    /**
     * Convert MetaBox field type to ACF type
     */
    private function convert_metabox_field_type_to_acf($metabox_type) {
        $mapping = array(
            'text' => 'text',
            'textarea' => 'textarea',
            'number' => 'number',
            'email' => 'email',
            'url' => 'url',
            'password' => 'password',
            'wysiwyg' => 'wysiwyg',
            'image' => 'image',
            'file' => 'file',
            'image_advanced' => 'gallery',
            'select' => 'select',
            'checkbox_list' => 'checkbox',
            'radio' => 'radio',
            'button_group' => 'button_group',
            'switch' => 'true_false',
            'date' => 'date_picker',
            'time' => 'time_picker',
            'datetime' => 'date_time_picker',
            'color' => 'color_picker',
            'slider' => 'range',
            'group' => 'group',
        );
        
        return $mapping[$metabox_type] ?? 'text';
    }
    
    /**
     * Convert ACF location rules to MetaBox post types
     */
    private function convert_acf_location_to_metabox($location_rules) {
        $post_types = array();
        
        if (empty($location_rules) || !is_array($location_rules)) {
            return $post_types;
        }
        
        foreach ($location_rules as $rule_group) {
            if (!is_array($rule_group)) {
                continue;
            }
            
            foreach ($rule_group as $rule) {
                if (!is_array($rule) || empty($rule['param'])) {
                    continue;
                }
                
                if ($rule['param'] === 'post_type' && !empty($rule['value'])) {
                    $post_types[] = $rule['value'];
                }
            }
        }
        
        return array_unique($post_types);
    }
    
    /**
     * Convert MetaBox post types to ACF location rules
     */
    private function convert_metabox_post_types_to_acf($post_types) {
        if (empty($post_types) || !is_array($post_types)) {
            return array();
        }
        
        $location_rules = array();
        
        foreach ($post_types as $post_type) {
            $location_rules[] = array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => $post_type,
                ),
            );
        }
        
        return $location_rules;
    }
    
    /**
     * Get conversion statistics
     */
    public function get_conversion_stats() {
        $stats = array(
            'acf_to_metabox' => 0,
            'metabox_to_acf' => 0,
            'total_conversions' => 0,
        );
        
        // This would be implemented based on actual conversion logs
        // For now, return empty stats
        
        return $stats;
    }
    
    /**
     * Log conversion activity
     */
    public function log_conversion($from_plugin, $to_plugin, $field_group_name, $success = true) {
        $this->error_handler->log_info('Cross-plugin conversion', array(
            'from_plugin' => $from_plugin,
            'to_plugin' => $to_plugin,
            'field_group_name' => $field_group_name,
            'success' => $success,
            'timestamp' => current_time('mysql'),
        ));
    }
}
