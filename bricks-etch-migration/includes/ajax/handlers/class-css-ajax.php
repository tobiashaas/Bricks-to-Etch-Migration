<?php
/**
 * CSS AJAX Handler
 * 
 * Handles CSS migration AJAX requests
 * 
 * @package Bricks_Etch_Migration
 * @since 0.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(dirname(__FILE__)) . '/class-base-ajax-handler.php';

class B2E_CSS_Ajax_Handler extends B2E_Base_Ajax_Handler {
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action('wp_ajax_b2e_migrate_css', array($this, 'migrate_css'));
    }
    
    /**
     * AJAX handler to migrate CSS
     */
    public function migrate_css() {
        $this->log('========================================');
        $this->log('🎨 CSS Migration: AJAX handler called - START');
        $this->log('🎨 CSS Migration: POST data: ' . print_r($_POST, true));
        $this->log('========================================');
        
        // Verify nonce
        if (!$this->verify_nonce()) {
            $this->log('❌ CSS Migration: Invalid nonce');
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = $this->sanitize_url($this->get_post('target_url', ''));
        $api_key = $this->sanitize_text($this->get_post('api_key', ''));
        
        $this->log('🎨 CSS Migration: target_url=' . $target_url . ', api_key=' . substr($api_key, 0, 20) . '...');
        
        if (empty($target_url) || empty($api_key)) {
            $this->log('❌ CSS Migration: Missing required parameters');
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Convert localhost:8081 to b2e-etch for Docker internal communication
        $internal_url = $this->convert_to_internal_url($target_url);
        
        // Save settings temporarily with internal URL
        update_option('b2e_settings', array(
            'target_url' => $internal_url,
            'api_key' => $api_key
        ), false);
        
        // Migrate CSS
        try {
            // Step 1: Convert Bricks classes to Etch styles
            $this->log('🎨 CSS Migration: Step 1 - Converting Bricks classes to Etch styles...');
            $css_converter = new B2E_CSS_Converter();
            $result = $css_converter->convert_bricks_classes_to_etch();
            
            if (is_wp_error($result)) {
                $this->log('❌ CSS Migration: Converter returned error: ' . $result->get_error_message());
                wp_send_json_error($result->get_error_message());
                return;
            }
            
            // Extract styles and style_map from result
            $etch_styles = $result['styles'] ?? array();
            $style_map = $result['style_map'] ?? array();
            
            $styles_count = count($etch_styles);
            $this->log('✅ CSS Migration: Converted ' . $styles_count . ' styles');
            $this->log('✅ CSS Migration: Created style map with ' . count($style_map) . ' entries');
            
            if ($styles_count === 0) {
                $this->log('⚠️ CSS Migration: No styles to migrate (empty array)');
                wp_send_json_success(array(
                    'message' => 'No CSS styles found to migrate',
                    'styles_count' => 0
                ));
                return;
            }
            
            // Step 2: Send styles AND style_map to Etch via API
            $this->log('🎨 CSS Migration: Step 2 - Sending ' . $styles_count . ' styles to Etch API...');
            $api_client = new B2E_API_Client();
            $api_result = $api_client->send_css_styles($internal_url, $api_key, $result);
            
            if (is_wp_error($api_result)) {
                $this->log('❌ CSS Migration: API error: ' . $api_result->get_error_message());
                wp_send_json_error('Failed to send styles to Etch: ' . $api_result->get_error_message());
                return;
            }
            
            // Step 3: Save style map from API response
            if (isset($api_result['style_map']) && is_array($api_result['style_map'])) {
                update_option('b2e_style_map', $api_result['style_map']);
                $this->log('✅ CSS Migration: Saved style map with ' . count($api_result['style_map']) . ' entries');
            } else {
                $this->log('⚠️ CSS Migration: No style map in API response!');
            }
            
            $this->log('✅ CSS Migration: SUCCESS - ' . $styles_count . ' styles migrated');
            wp_send_json_success(array(
                'message' => 'CSS migrated successfully',
                'styles_count' => $styles_count
            ));
        } catch (Exception $e) {
            $this->log('❌ CSS Migration: Exception: ' . $e->getMessage());
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Convert localhost URL to internal Docker URL
     * 
     * @param string $url
     * @return string
     */
    private function convert_to_internal_url($url) {
        if (strpos($url, 'localhost:8081') !== false) {
            return str_replace('localhost:8081', 'b2e-etch', $url);
        }
        return $url;
    }
}
