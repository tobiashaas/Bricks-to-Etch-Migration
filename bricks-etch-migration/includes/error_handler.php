<?php
/**
 * Error Handler for Bricks to Etch Migration Plugin
 * 
 * Handles all errors, warnings, and logging for the migration process
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Error_Handler {
    
    /**
     * Error codes with descriptions and solutions
     */
    const ERROR_CODES = array(
        // Content Errors (E0xx)
        'E001' => array(
            'title' => 'Missing Media File',
            'description' => 'Image or media file referenced in Bricks content not found',
            'solution' => 'Check if the media file exists in the source site media library'
        ),
        'E002' => array(
            'title' => 'Invalid CSS Syntax',
            'description' => 'CSS syntax error detected in Bricks global class',
            'solution' => 'Auto-fix attempted. Review the migrated CSS for accuracy'
        ),
        'E003' => array(
            'title' => 'Unsupported Bricks Element',
            'description' => 'Bricks-specific element cannot be automatically migrated',
            'solution' => 'Recreate this element manually in Etch (slider, accordion, etc.)'
        ),
        'E004' => array(
            'title' => 'Dynamic Data Tag Not Mappable',
            'description' => 'Bricks dynamic data tag has no Etch equivalent',
            'solution' => 'Manually update the dynamic data reference in Etch'
        ),
        'E005' => array(
            'title' => 'Custom Field Not Found',
            'description' => 'ACF or custom field referenced but not found',
            'solution' => 'Ensure custom fields are migrated and field names match'
        ),
        
        // API Errors (E1xx)
        'E101' => array(
            'title' => 'Invalid Bricks Content Structure',
            'description' => 'Bricks page content is not in expected array format',
            'solution' => 'Check if _bricks_page_content_2 contains valid serialized array'
        ),
        'E102' => array(
            'title' => 'Bricks Page Validation Failed',
            'description' => 'Page does not have required Bricks meta keys',
            'solution' => 'Verify _bricks_template_type and _bricks_editor_mode are set'
        ),
        'E103' => array(
            'title' => 'API Connection Failed',
            'description' => 'Unable to connect to target site API',
            'solution' => 'Check API URL, verify plugin is installed on target site'
        ),
        'E104' => array(
            'title' => 'API Key Expired',
            'description' => 'API key has exceeded 8-hour validity period',
            'solution' => 'Generate a new API key and retry the migration'
        ),
        'E105' => array(
            'title' => 'API Request Timeout',
            'description' => 'API request exceeded timeout limit',
            'solution' => 'Increase timeout setting or check server resources'
        ),
        
        // Migration Process Errors (E2xx)
        'E201' => array(
            'title' => 'Post Creation Failed',
            'description' => 'Failed to create post on target site',
            'solution' => 'Check target site permissions and database connectivity'
        ),
        'E202' => array(
            'title' => 'CSS Conversion Failed',
            'description' => 'Failed to convert Bricks CSS to Etch format',
            'solution' => 'Review CSS syntax and try manual conversion'
        ),
        'E203' => array(
            'title' => 'Dynamic Data Conversion Failed',
            'description' => 'Failed to convert Bricks dynamic data tags',
            'solution' => 'Check dynamic data syntax and Etch compatibility'
        ),
        
        // Custom Fields & Post Meta Errors
        'E301' => array(
            'title' => 'Custom Field Migration Failed',
            'description' => 'Failed to migrate custom field data for post',
            'solution' => 'Check custom field plugin compatibility and data structure'
        ),
        'E302' => array(
            'title' => 'ACF Field Group Import Failed',
            'description' => 'Failed to import ACF field group configuration',
            'solution' => 'Verify ACF plugin is active and field group data is valid'
        ),
    );
    
    /**
     * Warning codes with descriptions
     */
    const WARNING_CODES = array(
        'W001' => array(
            'title' => 'Non-Bricks Page Skipped',
            'description' => 'Page does not appear to be a Bricks page and was skipped',
            'solution' => 'Verify page was created with Bricks Builder'
        ),
        'W002' => array(
            'title' => 'Custom Field Plugin Missing',
            'description' => 'Custom field plugin not detected on target site',
            'solution' => 'Install required custom field plugin on target site'
        ),
        'W003' => array(
            'title' => 'Custom Post Type Not Registered',
            'description' => 'Custom post type not found on target site',
            'solution' => 'Register custom post type on target site before migration'
        ),
    );
    
    /**
     * Log an error
     * 
     * @param string $code Error code
     * @param array $context Additional context data
     */
    public function log_error($code, $context = array()) {
        if (!isset(self::ERROR_CODES[$code])) {
            $code = 'E000'; // Unknown error
        }
        
        $error_info = self::ERROR_CODES[$code];
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => 'error',
            'code' => $code,
            'title' => $error_info['title'],
            'description' => $error_info['description'],
            'solution' => $error_info['solution'],
            'context' => $context,
        );
        
        $this->add_to_log($log_entry);
        
        // Also log to WordPress error log
        error_log(sprintf(
            '[B2E Migration] %s: %s - %s',
            $code,
            $error_info['title'],
            $error_info['description']
        ));
    }
    
    /**
     * Log a warning
     * 
     * @param string $code Warning code
     * @param array $context Additional context data
     */
    public function log_warning($code, $context = array()) {
        if (!isset(self::WARNING_CODES[$code])) {
            $code = 'W000'; // Unknown warning
        }
        
        $warning_info = self::WARNING_CODES[$code];
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => 'warning',
            'code' => $code,
            'title' => $warning_info['title'],
            'description' => $warning_info['description'],
            'solution' => $warning_info['solution'],
            'context' => $context,
        );
        
        $this->add_to_log($log_entry);
    }
    
    /**
     * Add entry to migration log
     * 
     * @param array $log_entry Log entry data
     */
    private function add_to_log($log_entry) {
        $log = get_option('b2e_migration_log', array());
        $log[] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($log) > 1000) {
            $log = array_slice($log, -1000);
        }
        
        update_option('b2e_migration_log', $log);
    }
    
    /**
     * Get migration log
     * 
     * @param string $type Filter by type (error, warning, all)
     * @return array
     */
    public function get_log($type = 'all') {
        $log = get_option('b2e_migration_log', array());
        
        if ($type !== 'all') {
            $log = array_filter($log, function($entry) use ($type) {
                return $entry['type'] === $type;
            });
        }
        
        return $log;
    }
    
    /**
     * Clear migration log
     */
    public function clear_log() {
        delete_option('b2e_migration_log');
    }
    
    /**
     * Get error information by code
     * 
     * @param string $code Error code
     * @return array|null
     */
    public function get_error_info($code) {
        return isset(self::ERROR_CODES[$code]) ? self::ERROR_CODES[$code] : null;
    }
    
    /**
     * Get warning information by code
     * 
     * @param string $code Warning code
     * @return array|null
     */
    public function get_warning_info($code) {
        return isset(self::WARNING_CODES[$code]) ? self::WARNING_CODES[$code] : null;
    }
}
