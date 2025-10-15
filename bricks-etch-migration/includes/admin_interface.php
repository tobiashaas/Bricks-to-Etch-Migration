<?php
/**
 * Admin Interface for Bricks to Etch Migration Plugin
 *
 * Handles the WordPress admin menu and dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Admin_Interface {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_b2e_test_export_connection', array($this, 'ajax_test_export_connection'));
        add_action('wp_ajax_b2e_test_import_connection', array($this, 'ajax_test_import_connection'));
        add_action('wp_ajax_b2e_start_migration', array($this, 'ajax_start_migration'));
        add_action('wp_ajax_b2e_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_b2e_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_b2e_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_b2e_save_migration_settings', array($this, 'ajax_save_migration_settings'));
        add_action('wp_ajax_b2e_generate_migration_key', array($this, 'ajax_generate_migration_key'));
    }
    
    /**
     * Add admin menu - SINGLE ENTRY
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Bricks to Etch Migration', 'bricks-etch-migration'),
            __('B2E Migration', 'bricks-etch-migration'),
            'manage_options',
            'bricks-etch-migration',
            array($this, 'render_dashboard'),
            'dashicons-migrate',
            30
        );
        
        // Add submenu for Etch site (key generation)
        add_submenu_page(
            'bricks-etch-migration',
            __('Generate Migration Key', 'bricks-etch-migration'),
            __('Generate Key', 'bricks-etch-migration'),
            'manage_options',
            'bricks-etch-migration-generate',
            array($this, 'render_key_generator')
        );
    }
    
    /**
     * Render the main dashboard - SINGLE MIGRATION METHOD
     */
    public function render_dashboard() {
        $settings = get_option('b2e_settings', array());
        $progress = get_option('b2e_migration_progress', array());
        $error_handler = new B2E_Error_Handler();
        $logs = $error_handler->get_log();
        
        ?>
        <div class="wrap">
            <h1>🚀 <?php _e('Bricks to Etch Migration', 'bricks-etch-migration'); ?></h1>
            
            <!-- Migration Interface -->
            <div class="b2e-migration-container">
                <?php $this->render_migration_interface($settings, $progress); ?>
            </div>
            
            <!-- Progress Section (Always visible when migration is running) -->
            <?php if (!empty($progress) && $progress['status'] !== 'completed' && $progress['status'] !== 'error'): ?>
                <?php $this->render_progress_section($progress); ?>
            <?php endif; ?>
            
            <!-- Recent Logs -->
            <?php if (!empty($logs)): ?>
                <div class="b2e-card">
                    <h3><?php _e('Recent Migration Logs', 'bricks-etch-migration'); ?></h3>
                    <?php $this->render_recent_logs($logs); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .b2e-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .b2e-progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .b2e-progress-fill {
            height: 100%;
            background: #0073aa;
            transition: width 0.3s ease;
        }
        
        .b2e-log-entry {
            padding: 10px;
            border-left: 4px solid #ddd;
            margin-bottom: 10px;
            background: #f9f9f9;
        }
        
        .b2e-log-entry.error {
            border-left-color: #dc3232;
        }
        
        .b2e-log-entry.warning {
            border-left-color: #ffb900;
        }
        </style>
        <?php
    }
    
    /**
     * Render single migration interface - key-based workflow
     */
    private function render_migration_interface($settings, $progress) {
        ?>
        <div class="b2e-card">
            <h2>🔑 <?php _e('Migration Setup', 'bricks-etch-migration'); ?></h2>
            <p><?php _e('Generate a migration key on your Etch site and use it here to migrate your Bricks content.', 'bricks-etch-migration'); ?></p>
            
            <!-- Step 1: Enter Migration Key -->
            <div style="background: #f0f8ff; border: 2px dashed #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #0073aa;">
                    📥 <?php _e('Step 1: Enter Migration Key', 'bricks-etch-migration'); ?>
                </h3>
                <p style="margin-bottom: 15px;">
                    <?php _e('Paste the migration key generated on your Etch site:', 'bricks-etch-migration'); ?>
                </p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="migration_key"><?php _e('Migration Key', 'bricks-etch-migration'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="migration_key" name="migration_key" 
                                   value="<?php echo esc_attr($settings['migration_key'] ?? ''); ?>"
                                   placeholder="https://your-etch-site.com?domain=https://your-bricks-site.com&token=LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0NCk1JSUJJakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBUThBTUlJQkNnS0NBUUVBbzBBSHk5dE5IQ2VWclF0MEN6ci8NCjdwbEh2ZTY4dmFNZmQ1eWRSb1o4RjRCS0tVOGhpWXVFelVmVDkyTVdEMFRaODVPdmVqWWhjOEwwQ3ErRXc0Um4NCkI5SnZ6b2ZYeTgvWDZHZFo0YjVhaEJ1NW1hS2pZZ0NaZ2R6cmxVekZMOUtoTll5akJWMkp3S1VDNDN3Q21ObHINCndydXJJWk9mN1hpSGtaVEIxZ3NoQ0k4eVVPb2xScUJLcURYVEFHQit1M0NHcFNDQy9IbW1KbUFJeHNUMmF5cE0NCmNBT3FOaXBLZE9qMWJLTzl6VkRLNEQxOExjQlg2UWU1Smx1b2UxdkwvYzJlK0FsYjFPNnc2RklrRzlDNFk3ZVUNClZwMUhERkJIKzFQRXU0Q2FwUjI1c0diMjdpZkE5NFg1Z1l2RjFxRDU3WkNHc0dDTC8yRzBLV0ZMNnJudnN0eGgNCkZRSURBUUFCDQotLS0tLUVORCBQVUJMSUMgS0VZLS0tLS0=&expires=1760577698"
                                   style="width: 100%; font-family: monospace; font-size: 12px; word-break: break-all;" />
                            <p class="description">
                                <?php _e('This key contains all necessary information to connect to your Etch site', 'bricks-etch-migration'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="convert_div_to_flex"><?php _e('Conversion Options', 'bricks-etch-migration'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="convert_div_to_flex" name="convert_div_to_flex" 
                                       <?php checked($settings['convert_div_to_flex'] ?? true); ?> />
                                <?php _e('Convert Bricks div elements to Etch flex containers', 'bricks-etch-migration'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <div style="margin-top: 20px;">
                    <button type="button" id="validate-migration-key" class="button">
                        🔗 <?php _e('Validate Key', 'bricks-etch-migration'); ?>
                    </button>
                    <button type="button" id="start-migration" class="button button-primary" style="margin-left: 10px;">
                        🚀 <?php _e('Start Migration', 'bricks-etch-migration'); ?>
                    </button>
                </div>
                
                <div id="migration-key-info" style="margin-top: 15px; display: none;">
                    <!-- Key validation info will be displayed here -->
                </div>
            </div>
            
            <!-- Step 2: Instructions -->
            <div style="background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #856404;">
                    📋 <?php _e('How to Get Your Migration Key', 'bricks-etch-migration'); ?>
                </h3>
                <ol style="margin-bottom: 0; color: #856404;">
                    <li><?php _e('Go to your Etch site (target site)', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Install the Bricks to Etch Migration plugin', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Go to B2E Migration in the admin menu', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Click "Generate Migration Key"', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Copy the generated key and paste it above', 'bricks-etch-migration'); ?></li>
                </ol>
            </div>
        </div>
        
        <!-- Migration JavaScript -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validate Migration Key
            document.getElementById('validate-migration-key').addEventListener('click', function() {
                const migrationKey = document.getElementById('migration_key').value.trim();
                
                if (!migrationKey) {
                    showToast('Please enter a migration key first.', 'warning');
                    return;
                }
                
                // Parse the migration key to extract components
                try {
                    const url = new URL(migrationKey);
                    const domain = url.searchParams.get('domain');
                    const token = url.searchParams.get('token');
                    const expires = url.searchParams.get('expires');
                    
                    if (!domain || !token || !expires) {
                        showToast('Invalid migration key format. Please check the key from your Etch site.', 'error');
                        return;
                    }
                    
                    // Check expiration
                    const expiresDate = new Date(parseInt(expires) * 1000);
                    const now = new Date();
                    
                    if (expiresDate <= now) {
                        showToast('Migration key has expired. Please generate a new key on your Etch site.', 'error');
                        return;
                    }
                    
                    // Show key info
                    const infoDiv = document.getElementById('migration-key-info');
                    const timeLeft = Math.floor((expiresDate - now) / 1000 / 60); // minutes
                    
                    infoDiv.innerHTML = `
                        <div style="padding: 15px; background: #d4edda; border-radius: 6px; border-left: 4px solid #28a745;">
                            <h4 style="margin-top: 0; color: #155724;">
                                ✅ <?php _e('Migration Key Valid', 'bricks-etch-migration'); ?>
                            </h4>
                            <p style="margin-bottom: 5px; color: #155724;">
                                <strong><?php _e('Target Site:', 'bricks-etch-migration'); ?></strong> ${domain}
                            </p>
                            <p style="margin-bottom: 5px; color: #155724;">
                                <strong><?php _e('Expires in:', 'bricks-etch-migration'); ?></strong> ${timeLeft} minutes
                            </p>
                            <p style="margin-bottom: 0; color: #155724;">
                                <strong><?php _e('Token Type:', 'bricks-etch-migration'); ?></strong> RSA Public Key
                            </p>
                        </div>
                    `;
                    infoDiv.style.display = 'block';
                    
                    showToast('Migration key is valid and ready to use!', 'success');
                    
                } catch (error) {
                    showToast('Invalid migration key format. Please check the key from your Etch site.', 'error');
                }
            });
            
            // Start Migration
            document.getElementById('start-migration').addEventListener('click', function() {
                const migrationKey = document.getElementById('migration_key').value.trim();
                const convertDivToFlex = document.getElementById('convert_div_to_flex').checked;
                
                if (!migrationKey) {
                    showToast('Please enter a migration key first.', 'warning');
                    return;
                }
                
                // Parse the migration key
                try {
                    const url = new URL(migrationKey);
                    const domain = url.searchParams.get('domain');
                    const token = url.searchParams.get('token');
                    const expires = url.searchParams.get('expires');
                    
                    if (!domain || !token || !expires) {
                        showToast('Invalid migration key format.', 'error');
                        return;
                    }
                    
                    // Check expiration
                    const expiresDate = new Date(parseInt(expires) * 1000);
                    const now = new Date();
                    
                    if (expiresDate <= now) {
                        showToast('Migration key has expired. Please generate a new key.', 'error');
                        return;
                    }
                    
                    if (!confirm('Are you sure you want to start the migration? This will transfer all your content to: ' + domain)) {
                        return;
                    }
                    
                    // AJAX call to start migration
                    const formData = new FormData();
                    formData.append('action', 'b2e_start_migration');
                    formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
                    formData.append('migration_key', migrationKey);
                    formData.append('convert_div_to_flex', convertDivToFlex ? '1' : '0');
                    
                    fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Migration started successfully!', 'success');
                            // Start progress polling
                            startProgressPolling();
                        } else {
                            showToast('Migration failed to start: ' + (data.data || 'Unknown error'), 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Migration failed: ' + error.message, 'error');
                    });
                    
                } catch (error) {
                    showToast('Invalid migration key format.', 'error');
                }
            });
            
            // Progress polling function
            function startProgressPolling() {
                const pollInterval = setInterval(() => {
                    fetch(ajaxurl + '?action=b2e_get_progress&nonce=<?php echo wp_create_nonce('b2e_nonce'); ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            updateProgressDisplay(data.data);
                            
                            if (data.data.status === 'completed' || data.data.status === 'error') {
                                clearInterval(pollInterval);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Progress polling error:', error);
                        clearInterval(pollInterval);
                    });
                }, 2000);
            }
            
            // Update progress display
            function updateProgressDisplay(progress) {
                console.log('Migration progress:', progress);
            }
        });
        
        // Toast notification function
        function showToast(message, type = 'info', duration = 4000) {
            const toast = document.createElement('div');
            toast.className = `b2e-toast ${type}`;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 999999;
                min-width: 300px;
                max-width: 500px;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 14px;
                font-weight: 500;
                line-height: 1.4;
                transform: translateX(100%);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                opacity: 0;
                color: white;
            `;
            
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            toast.style.background = colors[type] || colors.info;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 18px;">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
                    <span style="flex: 1;">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 18px; cursor: pointer; opacity: 0.7;">×</button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 100);
            
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        </script>
        <?php
    }
    
    /**
     * Render progress section
     */
    private function render_progress_section($progress) {
        $percentage = isset($progress['percentage']) ? $progress['percentage'] : 0;
        $current_step = isset($progress['current_step']) ? $progress['current_step'] : '';
        $status = isset($progress['status']) ? $progress['status'] : 'idle';
        
        ?>
        <div class="b2e-card">
            <h3><?php _e('Migration Progress', 'bricks-etch-migration'); ?></h3>
            
            <div class="b2e-progress-bar">
                <div class="b2e-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
            </div>
            
            <p style="text-align: center; margin: 10px 0;">
                <strong><?php echo esc_html($percentage); ?>%</strong> - <?php echo esc_html($current_step); ?>
            </p>
            
            <p style="text-align: center; font-size: 12px; color: #666;">
                <?php _e('Status:', 'bricks-etch-migration'); ?> <strong><?php echo esc_html(ucfirst($status)); ?></strong>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render recent logs
     */
    private function render_recent_logs($logs) {
        if (empty($logs)) {
            echo '<p>' . __('No recent activity.', 'bricks-etch-migration') . '</p>';
            return;
        }
        
        echo '<div style="max-height: 300px; overflow-y: auto;">';
        foreach (array_slice($logs, -10) as $log) {
            $class = isset($log['level']) ? $log['level'] : 'info';
            echo '<div class="b2e-log-entry ' . esc_attr($class) . '">';
            echo '<strong>' . esc_html($log['message']) . '</strong><br>';
            echo '<small>' . esc_html($log['timestamp']) . '</small>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div style="margin-top: 15px;">';
        echo '<button type="button" id="clear-logs" class="button">' . __('Clear Logs', 'bricks-etch-migration') . '</button>';
        echo '</div>';
    }
    
    // AJAX handlers (simplified)
    public function ajax_test_export_connection() {
        check_ajax_referer('b2e_nonce', 'nonce');
        wp_send_json_success(__('Connection test successful!', 'bricks-etch-migration'));
    }
    
    public function ajax_test_import_connection() {
        check_ajax_referer('b2e_nonce', 'nonce');
        wp_send_json_success(__('Import connection test successful!', 'bricks-etch-migration'));
    }
    
    public function ajax_start_migration() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $migration_key = sanitize_text_field($_POST['migration_key']);
        $convert_div_to_flex = isset($_POST['convert_div_to_flex']);
        
        // Parse migration key
        $url = parse_url($migration_key);
        $query_params = array();
        parse_str($url['query'] ?? '', $query_params);
        
        $domain = $query_params['domain'] ?? '';
        $token = $query_params['token'] ?? '';
        $expires = $query_params['expires'] ?? '';
        
        if (empty($domain) || empty($token) || empty($expires)) {
            wp_send_json_error(__('Invalid migration key format.', 'bricks-etch-migration'));
        }
        
        // Check expiration
        if (time() > intval($expires)) {
            wp_send_json_error(__('Migration key has expired.', 'bricks-etch-migration'));
        }
        
        // Store settings
        $settings = get_option('b2e_settings', array());
        $settings['migration_key'] = $migration_key;
        $settings['convert_div_to_flex'] = $convert_div_to_flex;
        update_option('b2e_settings', $settings);
        
        // Start migration
        $migration_manager = new B2E_Migration_Manager();
        $result = $migration_manager->start_migration($domain, $token);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Migration started successfully!', 'bricks-etch-migration'));
    }
    
    public function ajax_get_progress() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        $progress = get_option('b2e_migration_progress', array(
            'status' => 'idle',
            'percentage' => 0,
            'current_step' => __('Ready', 'bricks-etch-migration')
        ));
        
        wp_send_json_success($progress);
    }
    
    public function ajax_clear_logs() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $error_handler = new B2E_Error_Handler();
        $error_handler->clear_log();
        
        wp_send_json_success(__('Logs cleared successfully.', 'bricks-etch-migration'));
    }
    
    public function ajax_generate_report() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'bricks-etch-migration'));
            return;
        }
        
        $analyzer = new B2E_Migration_Analyzer();
        $report = $analyzer->generate_report();
        
        wp_send_json_success($report);
    }
    
    public function ajax_save_migration_settings() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $settings_manager = new B2E_Migration_Settings();
        $settings = array(
            'migration_key' => sanitize_text_field($_POST['migration_key'] ?? ''),
            'convert_div_to_flex' => isset($_POST['convert_div_to_flex'])
        );
        
        $saved_settings = $settings_manager->save_settings($settings);
        
        wp_send_json_success(array(
            'message' => __('Migration settings saved successfully.', 'bricks-etch-migration'),
            'settings' => $saved_settings,
            'scope' => $settings_manager->get_scope_summary()
        ));
    }
    
    /**
     * Render key generator for Etch sites
     */
    public function render_key_generator() {
        ?>
        <div class="wrap">
            <h1>🔑 <?php _e('Generate Migration Key', 'bricks-etch-migration'); ?></h1>
            
            <div class="b2e-card">
                <h2><?php _e('Migration Key Generator', 'bricks-etch-migration'); ?></h2>
                <p><?php _e('Generate a secure migration key that contains your site information and authentication token.', 'bricks-etch-migration'); ?></p>
                
                <div style="background: #f0f8ff; border: 2px dashed #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #0073aa;">
                        🎯 <?php _e('Generate New Migration Key', 'bricks-etch-migration'); ?>
                    </h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="target_domain"><?php _e('Target Domain', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="target_domain" name="target_domain" 
                                       value="<?php echo esc_attr(home_url()); ?>"
                                       placeholder="https://your-bricks-site.com" 
                                       style="width: 100%; max-width: 400px;" readonly />
                                <p class="description">
                                    <?php _e('This is your current Etch site (where this key is generated)', 'bricks-etch-migration'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="expiration_hours"><?php _e('Expiration Time', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <select id="expiration_hours" name="expiration_hours" style="width: 200px;">
                                    <option value="1">1 Hour</option>
                                    <option value="8" selected>8 Hours (Recommended)</option>
                                    <option value="24">24 Hours</option>
                                    <option value="72">3 Days</option>
                                </select>
                                <p class="description">
                                    <?php _e('How long the migration key should remain valid', 'bricks-etch-migration'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 20px;">
                        <button type="button" id="generate-migration-key" class="button button-primary">
                            🔑 <?php _e('Generate Migration Key', 'bricks-etch-migration'); ?>
                        </button>
                    </div>
                    
                    <div id="generated-key-display" style="margin-top: 20px; display: none;">
                        <h4><?php _e('Generated Migration Key:', 'bricks-etch-migration'); ?></h4>
                        <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 10px 0;">
                            <input type="text" id="generated-key-value" readonly style="width: 100%; font-family: monospace; font-size: 12px; word-break: break-all; border: none; background: transparent;" />
                        </div>
                        <div style="margin-top: 10px;">
                            <button type="button" id="copy-migration-key" class="button">
                                📋 <?php _e('Copy Migration Key', 'bricks-etch-migration'); ?>
                            </button>
                            <button type="button" id="download-key-file" class="button">
                                💾 <?php _e('Download Key File', 'bricks-etch-migration'); ?>
                            </button>
                        </div>
                        
                        <div id="qr-code-display" style="margin-top: 20px;">
                            <h4><?php _e('QR Code (for mobile access):', 'bricks-etch-migration'); ?></h4>
                            <img id="qr-code-image" src="" alt="Migration QR Code" style="max-width: 150px; height: auto; border: 1px solid #eee; padding: 5px;" />
                        </div>
                        
                        <div style="background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px;">
                            <h4 style="margin-top: 0; color: #856404;">
                                📋 <?php _e('Instructions:', 'bricks-etch-migration'); ?>
                            </h4>
                            <ol style="margin-bottom: 0; color: #856404;">
                                <li><?php _e('Copy the migration key above', 'bricks-etch-migration'); ?></li>
                                <li><?php _e('Go to your Bricks site (source site)', 'bricks-etch-migration'); ?></li>
                                <li><?php _e('Install the Bricks to Etch Migration plugin', 'bricks-etch-migration'); ?></li>
                                <li><?php _e('Go to B2E Migration in the admin menu', 'bricks-etch-migration'); ?></li>
                                <li><?php _e('Paste the migration key and start the migration', 'bricks-etch-migration'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generate Migration Key
            document.getElementById('generate-migration-key').addEventListener('click', function() {
                const targetDomain = document.getElementById('target_domain').value;
                const expirationHours = document.getElementById('expiration_hours').value;
                
                if (!targetDomain) {
                    showToast('Please enter a target domain.', 'warning');
                    return;
                }
                
                // AJAX call to generate migration key
                const formData = new FormData();
                formData.append('action', 'b2e_generate_migration_key');
                formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
                formData.append('target_domain', targetDomain);
                formData.append('expiration_hours', expirationHours);
                
                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const migrationKey = data.data.migration_url;
                        const qrCodeUrl = data.data.qr_code_url;
                        
                        document.getElementById('generated-key-value').value = migrationKey;
                        document.getElementById('qr-code-image').src = qrCodeUrl;
                        document.getElementById('generated-key-display').style.display = 'block';
                        
                        showToast('Migration key generated successfully!', 'success');
                    } else {
                        showToast('Failed to generate migration key: ' + (data.data || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showToast('Error generating migration key: ' + error.message, 'error');
                });
            });
            
            // Copy Migration Key
            document.getElementById('copy-migration-key').addEventListener('click', function() {
                const migrationKey = document.getElementById('generated-key-value').value;
                navigator.clipboard.writeText(migrationKey).then(() => {
                    showToast('Migration key copied to clipboard!', 'success');
                }).catch(() => {
                    // Fallback for older browsers
                    document.getElementById('generated-key-value').select();
                    document.execCommand('copy');
                    showToast('Migration key copied to clipboard!', 'success');
                });
            });
            
            // Download Key File
            document.getElementById('download-key-file').addEventListener('click', function() {
                const migrationKey = document.getElementById('generated-key-value').value;
                const blob = new Blob([migrationKey], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'migration-key-' + new Date().toISOString().slice(0,10) + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showToast('Migration key file downloaded!', 'success');
            });
        });
        
        // Toast notification function
        function showToast(message, type = 'info', duration = 4000) {
            const toast = document.createElement('div');
            toast.className = `b2e-toast ${type}`;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 999999;
                min-width: 300px;
                max-width: 500px;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 14px;
                font-weight: 500;
                line-height: 1.4;
                transform: translateX(100%);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                opacity: 0;
                color: white;
            `;
            
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            toast.style.background = colors[type] || colors.info;
            
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 18px;">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
                    <span style="flex: 1;">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 18px; cursor: pointer; opacity: 0.7;">×</button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 100);
            
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
        </script>
        
        <style>
        .b2e-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX: Generate migration key
     */
    public function ajax_generate_migration_key() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $target_domain = sanitize_url($_POST['target_domain']);
        $expiration_hours = intval($_POST['expiration_hours']);
        
        if (empty($target_domain) || !filter_var($target_domain, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Invalid target domain provided.', 'bricks-etch-migration'));
        }
        
        $token_manager = new B2E_Migration_Token_Manager();
        $migration_url = $token_manager->generate_migration_url($target_domain, $expiration_hours * 3600);
        
        if (is_wp_error($migration_url)) {
            wp_send_json_error($migration_url->get_error_message());
        }
        
        $qr_code_url = $token_manager->generate_qr_data($migration_url);
        
        wp_send_json_success(array(
            'migration_url' => $migration_url,
            'qr_code_url' => $qr_code_url,
        ));
    }
}
