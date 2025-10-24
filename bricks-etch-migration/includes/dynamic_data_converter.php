<?php
/**
 * Dynamic Data Converter for Bricks to Etch Migration Plugin
 * 
 * Converts Bricks dynamic data tags to Etch equivalents
 */

namespace Bricks2Etch\Parsers;

use Bricks2Etch\Core\B2E_Error_Handler;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Dynamic_Data_Converter {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Mapping of Bricks dynamic tags to Etch keys
     */
    private $mapping = array(
        // Post data
        'post_title' => 'this.title',
        'post_content' => 'this.content',
        'post_excerpt' => 'this.excerpt',
        'post_date' => 'this.date',
        'post_modified' => 'this.modified',
        'post_author' => 'this.author.name',
        'post_id' => 'this.id',
        'post_slug' => 'this.slug',
        'post_url' => 'this.url',
        
        // Site data
        'site_title' => 'site.name',
        'site_tagline' => 'site.description',
        'site_url' => 'site.url',
        'site_admin_email' => 'site.admin_email',
        
        // User data
        'user_login' => 'user.login',
        'user_email' => 'user.email',
        'user_display_name' => 'user.display_name',
        'user_first_name' => 'user.first_name',
        'user_last_name' => 'user.last_name',
        
        // Query data
        'query_title' => 'query.title',
        'query_content' => 'query.content',
        'query_excerpt' => 'query.excerpt',
        'query_date' => 'query.date',
        'query_author' => 'query.author.name',
        'query_id' => 'query.id',
        'query_slug' => 'query.slug',
        'query_url' => 'query.url',
        
        // URL parameters
        'url_parameter' => 'url.parameter',
        
        // Meta fields
        'meta' => 'this.meta',
    );
    
    /**
     * Mapping of Bricks modifiers to Etch modifiers
     */
    private $modifier_mapping = array(
        'uppercase' => 'toUpperCase()',
        'lowercase' => 'toLowerCase()',
        'capitalize' => 'capitalize()',
        'date' => 'dateFormat()',
        'time' => 'timeFormat()',
        'number' => 'numberFormat()',
        'currency' => 'currencyFormat()',
        'slug' => 'toSlug()',
        'length' => 'length()',
        'first' => 'at(0)',
        'last' => 'at(-1)',
        'limit' => 'limit()',
        'excerpt' => 'excerpt()',
        'strip_tags' => 'stripTags()',
        'trim' => 'trim()',
    );
    
    /**
     * Constructor
     */
    public function __construct(B2E_Error_Handler $error_handler) {
        $this->error_handler = $error_handler;
    }
    
    /**
     * Convert dynamic data tags in content
     */
    public function convert_content($content) {
        if (empty($content)) {
            return $content;
        }
        
        // Convert basic tags
        $converted = $this->convert_basic_tags($content);
        
        // Convert tags with modifiers
        $converted = $this->convert_tags_with_modifiers($converted);
        
        // Convert ACF fields
        $converted = $this->convert_acf_fields($converted);
        
        // Convert MetaBox fields
        $converted = $this->convert_metabox_fields($converted);
        
        // Convert JetEngine fields
        $converted = $this->convert_jetengine_fields($converted);
        
        // Convert meta fields
        $converted = $this->convert_meta_fields($converted);
        
        // Convert query tags
        $converted = $this->convert_query_tags($converted);
        
        // Convert URL parameters
        $converted = $this->convert_url_parameters($converted);
        
        // Log unconverted tags
        $this->log_unconverted_tags($converted);
        
        return $converted;
    }
    
    /**
     * Convert basic dynamic data tags
     */
    private function convert_basic_tags($content) {
        // Convert basic dynamic data tags using simple string replacement
        foreach ($this->mapping as $bricks_tag => $etch_key) {
            $content = str_replace('{' . $bricks_tag . '}', '{' . $etch_key . '}', $content);
        }
        
        return $content;
    }
    
    /**
     * Convert ACF field tags (ENHANCED!)
     * Bricks: {acf_field_name} or {acf:field_name}
     * Etch: {this.acf.field_name}
     * 
     * Based on: https://docs.etchwp.com/integrations/custom-fields/
     * Supports: Text, Image, Gallery, Repeater, Relationship fields
     */
    private function convert_acf_fields($content) {
        // Pattern: {acf_field_name} or {acf:field_name}
        $pattern = '/\{acf[_:]([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Check if it's an image field (common patterns)
            if (preg_match('/_(image|img|photo|picture|logo|avatar)$/i', $field_name) || 
                preg_match('/^(image|img|photo|picture|logo|avatar)_/i', $field_name)) {
                // Image fields need .url property
                return '{this.acf.' . $field_name . '.url}';
            }
            
            // Check if it's a gallery field
            if (preg_match('/_(gallery|gallery_images|images)$/i', $field_name) || 
                preg_match('/^(gallery|gallery_images|images)_/i', $field_name)) {
                // Gallery fields need loop syntax
                return '{#loop this.acf.' . $field_name . ' as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
            }
            
            // Check if it's a repeater field
            if (preg_match('/_(repeater|items|list|rows)$/i', $field_name) || 
                preg_match('/^(repeater|items|list|rows)_/i', $field_name)) {
                // Repeater fields need loop syntax
                return '{#loop this.acf.' . $field_name . ' as item}<div>{item.sub_field}</div>{/loop}';
            }
            
            // Default: regular text field
            return '{this.acf.' . $field_name . '}';
        }, $content);
        
        // Also handle with modifiers: {acf:field|modifier}
        $pattern_with_modifier = '/\{acf[_:]([a-zA-Z0-9_-]+)\|([a-zA-Z0-9_]+)(?::([^}]+))?\}/';
        
        $content = preg_replace_callback($pattern_with_modifier, function($matches) {
            $field_name = $matches[1];
            $bricks_modifier = $matches[2];
            $modifier_param = isset($matches[3]) ? $matches[3] : null;
            
            $etch_modifier = isset($this->modifier_mapping[$bricks_modifier]) 
                ? $this->modifier_mapping[$bricks_modifier] 
                : $bricks_modifier . '()';
            
            if ($modifier_param) {
                $etch_modifier = str_replace('()', '(' . $modifier_param . ')', $etch_modifier);
            }
            
            return '{this.acf.' . $field_name . '.' . $etch_modifier . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert MetaBox field tags (ENHANCED!)
     * Bricks: {mb_field_name} or {metabox:field_name}
     * Etch: {this.metabox.field_name}
     * 
     * Based on: https://docs.etchwp.com/integrations/custom-fields/
     * Supports: Text, Image, Gallery, Repeater, Relationship fields
     */
    private function convert_metabox_fields($content) {
        // Pattern: {mb_field_name} or {metabox:field_name}
        $pattern = '/\{(?:mb|metabox)[_:]([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Check if it's an image field (common patterns)
            if (preg_match('/_(image|img|photo|picture|logo|avatar)$/i', $field_name) || 
                preg_match('/^(image|img|photo|picture|logo|avatar)_/i', $field_name)) {
                // Image fields need .url property
                return '{this.metabox.' . $field_name . '.url}';
            }
            
            // Check if it's a gallery field
            if (preg_match('/_(gallery|gallery_images|images)$/i', $field_name) || 
                preg_match('/^(gallery|gallery_images|images)_/i', $field_name)) {
                // Gallery fields need loop syntax
                return '{#loop this.metabox.' . $field_name . ' as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
            }
            
            // Check if it's a repeater field
            if (preg_match('/_(repeater|items|list|rows)$/i', $field_name) || 
                preg_match('/^(repeater|items|list|rows)_/i', $field_name)) {
                // Repeater fields need loop syntax
                return '{#loop this.metabox.' . $field_name . ' as item}<div>{item.sub_field}</div>{/loop}';
            }
            
            // Default: regular text field
            return '{this.metabox.' . $field_name . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert JetEngine field tags (ENHANCED!)
     * Bricks: {jet_field_name} or {jetengine:field_name}
     * Etch: {this.jetengine.field_name}
     * 
     * Based on: https://docs.etchwp.com/integrations/custom-fields/
     * Supports: Text, Image, Gallery, Repeater, Relationship fields
     */
    private function convert_jetengine_fields($content) {
        // Pattern: {jet_field_name} or {jetengine:field_name}
        $pattern = '/\{(?:jet|jetengine)[_:]([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            
            // Check if it's an image field (common patterns)
            if (preg_match('/_(image|img|photo|picture|logo|avatar)$/i', $field_name) || 
                preg_match('/^(image|img|photo|picture|logo|avatar)_/i', $field_name)) {
                // Image fields need .url property
                return '{this.jetengine.' . $field_name . '.url}';
            }
            
            // Check if it's a gallery field
            if (preg_match('/_(gallery|gallery_images|images)$/i', $field_name) || 
                preg_match('/^(gallery|gallery_images|images)_/i', $field_name)) {
                // Gallery fields need loop syntax
                return '{#loop this.jetengine.' . $field_name . ' as image}<img src="{image.url}" alt="{image.alt}" />{/loop}';
            }
            
            // Check if it's a repeater field
            if (preg_match('/_(repeater|items|list|rows)$/i', $field_name) || 
                preg_match('/^(repeater|items|list|rows)_/i', $field_name)) {
                // Repeater fields need loop syntax
                return '{#loop this.jetengine.' . $field_name . ' as item}<div>{item.sub_field}</div>{/loop}';
            }
            
            // Default: regular text field
            return '{this.jetengine.' . $field_name . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert meta fields
     */
    private function convert_meta_fields($content) {
        // Pattern: {meta:field_name}
        $pattern = '/\{meta:([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            return '{this.meta.' . $field_name . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert query tags
     */
    private function convert_query_tags($content) {
        // Pattern: {query:field_name}
        $pattern = '/\{query:([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $field_name = $matches[1];
            return '{query.' . $field_name . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert URL parameters
     */
    private function convert_url_parameters($content) {
        // Pattern: {url_parameter:param_name}
        $pattern = '/\{url_parameter:([a-zA-Z0-9_-]+)\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $param_name = $matches[1];
            return '{url.parameter.' . $param_name . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Convert tags with modifiers
     */
    private function convert_tags_with_modifiers($content) {
        // Pattern: {tag|modifier:param}
        $pattern = '/\{([a-zA-Z0-9_-]+)\|([a-zA-Z0-9_]+)(?::([^}]+))?\}/';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $tag = $matches[1];
            $bricks_modifier = $matches[2];
            $modifier_param = isset($matches[3]) ? $matches[3] : null;
            
            // Convert tag if it exists in mapping
            $etch_tag = isset($this->mapping[$tag]) ? $this->mapping[$tag] : $tag;
            
            // Convert modifier
            $etch_modifier = isset($this->modifier_mapping[$bricks_modifier]) 
                ? $this->modifier_mapping[$bricks_modifier] 
                : $bricks_modifier . '()';
            
            if ($modifier_param) {
                $etch_modifier = str_replace('()', '(' . $modifier_param . ')', $etch_modifier);
            }
            
            return '{' . $etch_tag . '.' . $etch_modifier . '}';
        }, $content);
        
        return $content;
    }
    
    /**
     * Log unconverted tags
     */
    private function log_unconverted_tags($content) {
        // Find any remaining {tag} patterns
        if (preg_match_all('/\{([a-zA-Z0-9_:-]+)\}/', $content, $matches)) {
            foreach ($matches[1] as $tag) {
                // Skip already converted tags
                if (strpos($tag, 'this.') === 0 || 
                    strpos($tag, 'site.') === 0 || 
                    strpos($tag, 'user.') === 0 || 
                    strpos($tag, 'query.') === 0 || 
                    strpos($tag, 'url.') === 0) {
                    continue;
                }
                
                $this->error_handler->log_error('E004', array(
                    'tag' => $tag,
                    'message' => 'Dynamic data tag not mappable to Etch format'
                ));
            }
        }
    }
    
    /**
     * Get mapping for testing
     */
    public function get_mapping() {
        return $this->mapping;
    }
    
    /**
     * Get modifier mapping for testing
     */
    public function get_modifier_mapping() {
        return $this->modifier_mapping;
    }
}

\class_alias(__NAMESPACE__ . '\\B2E_Dynamic_Data_Converter', 'B2E_Dynamic_Data_Converter');
