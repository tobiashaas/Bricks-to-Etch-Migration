<?php
/**
 * Admin Interface for Bricks to Etch Migration Plugin
 * 
 * Handles the WordPress admin interface and dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_Admin_Interface {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_b2e_start_migration', array($this, 'ajax_start_migration'));
        add_action('wp_ajax_b2e_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_b2e_test_export_connection', array($this, 'ajax_test_export_connection'));
        add_action('wp_ajax_b2e_test_import_connection', array($this, 'ajax_test_import_connection'));
        add_action('wp_ajax_b2e_save_import_settings', array($this, 'ajax_save_import_settings'));
        add_action('wp_ajax_b2e_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_b2e_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_b2e_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_b2e_save_migration_settings', array($this, 'ajax_save_migration_settings'));
    }
    
    /**
     * Render the main dashboard
     */
    public function render_dashboard() {
        $settings = get_option('b2e_settings', array());
        $progress = get_option('b2e_migration_progress', array());
        $error_handler = new B2E_Error_Handler();
        $logs = $error_handler->get_log();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Bricks to Etch Migration', 'bricks-etch-migration'); ?></h1>
            
            <?php $this->render_status_overview($progress); ?>
            
            <div class="b2e-dashboard">
                <div class="b2e-main-content">
                    <?php $this->render_migration_form($settings); ?>
                    <?php $this->render_progress_section($progress); ?>
                </div>
                
                <div class="b2e-sidebar">
                    <?php $this->render_validation_results(); ?>
                    <?php $this->render_recent_logs($logs); ?>
                </div>
            </div>
        </div>
        
        <style>
        .b2e-dashboard {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .b2e-main-content {
            flex: 2;
        }
        
        .b2e-sidebar {
            flex: 1;
        }
        
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
        
        .b2e-button {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .b2e-button:hover {
            background: #005a87;
        }
        
        .b2e-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Tab Styles */
        .b2e-tab-nav {
            display: flex;
            border-bottom: 1px solid #ccd0d4;
            margin-bottom: 20px;
        }
        
        .b2e-tab-button {
            background: #f1f1f1;
            border: 1px solid #ccd0d4;
            border-bottom: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #555;
            transition: all 0.3s ease;
        }
        
        .b2e-tab-button:first-child {
            border-top-left-radius: 4px;
        }
        
        .b2e-tab-button:last-child {
            border-top-right-radius: 4px;
        }
        
        .b2e-tab-button:hover {
            background: #e8e8e8;
            color: #333;
        }
        
        .b2e-tab-button.active {
            background: #fff;
            color: #0073aa;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        
        .b2e-tab-content {
            display: none;
        }
        
        .b2e-tab-content.active {
            display: block;
        }
        
        .b2e-import-instructions {
            border-radius: 4px;
        }
        
        .b2e-import-instructions h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .b2e-import-instructions ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .b2e-import-instructions li {
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        /* Toast Notification Styles */
        .b2e-toast {
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
        }
        
        .b2e-toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .b2e-toast.success {
            background: #10b981;
            color: white;
            border-left: 4px solid #059669;
        }
        
        .b2e-toast.error {
            background: #ef4444;
            color: white;
            border-left: 4px solid #dc2626;
        }
        
        .b2e-toast.warning {
            background: #f59e0b;
            color: white;
            border-left: 4px solid #d97706;
        }
        
        .b2e-toast.info {
            background: #3b82f6;
            color: white;
            border-left: 4px solid #2563eb;
        }
        
        .b2e-toast .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .b2e-toast .toast-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .b2e-toast .toast-message {
            flex: 1;
        }
        
        .b2e-toast .toast-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: 12px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .b2e-toast .toast-close:hover {
            opacity: 1;
        }
        
        /* Progress Step Styles */
        .b2e-progress-steps {
            margin-top: 20px;
        }
        
        .b2e-progress-steps ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .b2e-progress-steps li {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .b2e-progress-steps li.active {
            border-left-color: #0073aa;
            background: #e7f3ff;
            font-weight: 500;
        }
        
        .b2e-progress-steps li.completed {
            border-left-color: #10b981;
            background: #f0fdf4;
            color: #059669;
        }
        
        .b2e-progress-steps li.completed::before {
            content: "✅ ";
            margin-right: 8px;
        }
        
        .b2e-progress-steps li.active::before {
            content: "🔄 ";
            margin-right: 8px;
        }
        </style>
        
        <script type="text/javascript">
        // Define ajaxurl for AJAX calls
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // Toast Notification System
        function showToast(message, type = 'info', duration = 4000) {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.b2e-toast');
            existingToasts.forEach(toast => toast.remove());
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `b2e-toast ${type}`;
            
            // Set icons based on type
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            toast.innerHTML = `
                <div class="toast-content">
                    <span class="toast-icon">${icons[type] || icons.info}</span>
                    <span class="toast-message">${message}</span>
                    <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.remove();
                        }
                    }, 300);
                }
            }, duration);
        }
        
        // Immediate tab switching - works without external JS
        function switchB2ETab(tabName) {
            console.log('Switching to tab:', tabName);
            
            // Update tab buttons
            const tabButtons = document.querySelectorAll('.b2e-tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }
            
            // Update tab content
            const tabContents = document.querySelectorAll('.b2e-tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            const activeContent = document.getElementById(`${tabName}-tab`);
            if (activeContent) {
                activeContent.classList.add('active');
            }
        }
        
        // Generate API key function
        function generateB2EApiKey() {
            console.log('Generating API key...');
            const button = document.getElementById('generate-export-api-key');
            if (!button) return;
            
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Generating...';
            
            // Generate a secure random API key
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = 'b2e_';
            for (let i = 0; i < 32; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            // Update the input field
            const apiKeyInput = document.getElementById('export_api_key');
            if (apiKeyInput) {
                apiKeyInput.value = result;
            }
            
            // Show the generated key display
            const keyDisplay = document.getElementById('export-api-key-value');
            const keyContainer = document.getElementById('export-generated-key-display');
            
            if (keyDisplay) {
                keyDisplay.textContent = result;
            }
            
            if (keyContainer) {
                keyContainer.style.display = 'block';
            }
            
            button.disabled = false;
            button.textContent = originalText;
            
            showToast('API key generated successfully! Copy it to your target site.', 'success');
        }
        
        // Copy API key function
        function copyB2EApiKey() {
            const keyDisplay = document.getElementById('export-api-key-value');
            if (!keyDisplay) {
                showToast('No API key to copy. Please generate one first.', 'warning');
                return;
            }
            
            const apiKey = keyDisplay.textContent;
            if (!apiKey) {
                showToast('No API key to copy. Please generate one first.', 'warning');
                return;
            }
            
            // Use modern clipboard API if available
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(apiKey).then(function() {
                    showToast('API key copied to clipboard! Paste it in your target site.', 'success');
                }).catch(function() {
                    fallbackCopyToClipboard(apiKey);
                });
            } else {
                fallbackCopyToClipboard(apiKey);
            }
        }
        
        // Fallback copy function
        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showToast('API key copied to clipboard!', 'success');
            } catch (err) {
                showToast('Failed to copy API key. Please copy manually.', 'error');
            }
            
            document.body.removeChild(textArea);
        }
        
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing tab functionality...');
            
            // Add click handlers to tab buttons
            const tabButtons = document.querySelectorAll('.b2e-tab-button');
            console.log('Found tab buttons:', tabButtons.length);
            
            tabButtons.forEach(button => {
                console.log('Adding click handler to:', button.dataset.tab);
                button.addEventListener('click', function() {
                    console.log('Tab clicked:', this.dataset.tab);
                    switchB2ETab(this.dataset.tab);
                });
            });
            
            // Add click handler to generate button
            const generateBtn = document.getElementById('generate-export-api-key');
            if (generateBtn) {
                console.log('Adding click handler to generate button');
                generateBtn.addEventListener('click', generateB2EApiKey);
            }
            
            // Add click handler to copy button
            const copyBtn = document.getElementById('copy-export-api-key');
            if (copyBtn) {
                console.log('Adding click handler to copy button');
                copyBtn.addEventListener('click', copyB2EApiKey);
            }
            
            // Add click handler to test export connection
            const testExportBtn = document.getElementById('test-export-connection');
            if (testExportBtn) {
                console.log('Adding click handler to test export connection');
                testExportBtn.addEventListener('click', testExportConnection);
            }
            
            // Add click handler to start export
            const startExportBtn = document.getElementById('start-export');
            if (startExportBtn) {
                console.log('Adding click handler to start export');
                startExportBtn.addEventListener('click', startExport);
            }
            
            // Add click handler to save import settings
            const saveImportBtn = document.getElementById('save-import-settings');
            if (saveImportBtn) {
                console.log('Adding click handler to save import settings');
                saveImportBtn.addEventListener('click', saveImportSettings);
            }
            
            // Add click handler to test import connection
            const testImportBtn = document.getElementById('test-import-connection');
            if (testImportBtn) {
                console.log('Adding click handler to test import connection');
                testImportBtn.addEventListener('click', testImportConnection);
            }
        });
        
        // Test export connection function
        function testExportConnection() {
            console.log('Testing export connection...');
            const button = this;
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = 'Testing...';
            
            const targetUrl = document.getElementById('target_url').value;
            const apiKey = document.getElementById('export_api_key').value;
            
            if (!targetUrl || !apiKey) {
                showToast('Please enter both target URL and API key.', 'warning');
                button.disabled = false;
                button.textContent = originalText;
                return;
            }
            
            // Real API test
            const formData = new FormData();
            formData.append('action', 'b2e_test_export_connection');
            formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
            formData.append('target_url', targetUrl);
            formData.append('api_key', apiKey);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned non-JSON response. Status: ' + response.status);
                }
                
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100) + '...');
                    }
                });
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success) {
                    showToast('Connection test successful! Target site is reachable.', 'success');
                } else {
                    showToast('Connection test failed: ' + (data.data || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Connection test error:', error);
                showToast('Connection test failed: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        }
        
        // Start export function
        function startExport(e) {
            e.preventDefault();
            console.log('Starting export...');
            
            const targetUrl = document.getElementById('target_url').value;
            const apiKey = document.getElementById('export_api_key').value;
            
            if (!targetUrl || !apiKey) {
                showToast('Please enter both target URL and API key.', 'warning');
                return;
            }
            
            // Show confirmation toast instead of ugly alert
            showToast('Starting migration... This will send data to your target site.', 'info', 2000);
            
            // Start real migration
            startRealMigration(targetUrl, apiKey);
        }
        
        // Real migration function
        function startRealMigration(targetUrl, apiKey) {
            console.log('Starting real migration to:', targetUrl);
            
            // Show progress section
            const progressSection = document.getElementById('progress-section');
            if (progressSection) {
                progressSection.style.display = 'block';
            }
            
            // Update progress
            updateProgress(0, 'Initializing migration...');
            
            // Start real AJAX migration
            startRealAjaxMigration(targetUrl, apiKey);
        }
        
        // Real AJAX migration function
        function startRealAjaxMigration(targetUrl, apiKey) {
            console.log('Starting AJAX migration...');
            console.log('Target URL:', targetUrl);
            console.log('API Key:', apiKey);
            console.log('AJAX URL:', ajaxurl);
            
            const formData = new FormData();
            formData.append('action', 'b2e_start_migration');
            formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
            formData.append('target_url', targetUrl);
            formData.append('api_key', apiKey);
            formData.append('cleanup_bricks_meta', document.getElementById('cleanup_bricks_meta').checked);
            formData.append('convert_div_to_flex', document.getElementById('convert_div_to_flex').checked);
            
            console.log('Sending AJAX request...');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response);
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                console.log('Success:', data.success);
                console.log('Error Data:', data.data);
                
                if (data.success) {
                    showToast('Migration started successfully!', 'success');
                    startProgressPolling();
                } else {
                    console.error('Migration failed:', data);
                    const errorMsg = typeof data.data === 'string' ? data.data : JSON.stringify(data.data);
                    console.error('Error message:', errorMsg);
                    showToast('Migration failed: ' + errorMsg, 'error', 8000);
                }
            })
            .catch(error => {
                console.error('Migration error:', error);
                showToast('Migration failed: ' + error.message, 'error');
            });
        }
        
        // Progress polling function
        function startProgressPolling() {
            console.log('Starting progress polling...');
            
            const pollInterval = setInterval(() => {
                console.log('Polling progress...');
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=b2e_get_progress&nonce=<?php echo wp_create_nonce('b2e_nonce'); ?>'
                })
                .then(response => {
                    console.log('Progress response:', response);
                    
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Progress data:', data);
                    
                    if (data.success) {
                        const progress = data.data;
                        console.log('Progress:', progress);
                        
                        updateProgress(progress.percentage, progress.message || progress.current_step);
                        
                        if (progress.status === 'completed') {
                            clearInterval(pollInterval);
                            showToast('Migration completed successfully! Your Bricks site has been migrated to Etch.', 'success', 6000);
                        } else if (progress.status === 'error') {
                            clearInterval(pollInterval);
                            showToast('Migration failed: ' + (progress.message || 'Unknown error'), 'error');
                        }
                    } else {
                        console.error('Progress polling failed:', data);
                    }
                })
                .catch(error => {
                    console.error('Progress polling error:', error);
                    clearInterval(pollInterval);
                    showToast('Progress polling failed: ' + error.message, 'error');
                });
            }, 1000); // Poll every second
        }
        
        // Update progress function
        function updateProgress(percentage, message) {
            const progressFill = document.querySelector('.b2e-progress-fill');
            const progressText = document.getElementById('progress-text');
            const currentStep = document.getElementById('current-step');
            
            if (progressFill) {
                progressFill.style.width = percentage + '%';
            }
            
            if (progressText) {
                progressText.innerHTML = '<strong>' + percentage + '%</strong> - <span id="current-step">' + message + '</span>';
            }
            
            if (currentStep) {
                currentStep.textContent = message;
            }
        }
        
        // Mark step as active
        function markStepActive(stepName) {
            const stepElement = document.querySelector(`[data-step="${stepName}"]`);
            if (stepElement) {
                stepElement.classList.add('active');
            }
        }
        
        // Mark step as completed
        function markStepCompleted(stepName) {
            const stepElement = document.querySelector(`[data-step="${stepName}"]`);
            if (stepElement) {
                stepElement.classList.remove('active');
                stepElement.classList.add('completed');
            }
        }
        
        // Save import settings function
        function saveImportSettings() {
            console.log('Saving import settings...');
            const button = this;
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = 'Saving...';
            
            const apiKey = document.getElementById('import_api_key').value;
            const autoAccept = document.getElementById('import_auto_accept').checked;
            
            if (!apiKey) {
                showToast('Please enter an API key before saving.', 'warning');
                button.disabled = false;
                button.textContent = originalText;
                return;
            }
            
            // Real AJAX save
            const formData = new FormData();
            formData.append('action', 'b2e_save_import_settings');
            formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
            formData.append('import_api_key', apiKey);
            formData.append('import_auto_accept', autoAccept ? '1' : '0');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Save response status:', response.status);
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned non-JSON response. Status: ' + response.status);
                }
                
                return response.text().then(text => {
                    console.log('Save raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100) + '...');
                    }
                });
            })
            .then(data => {
                console.log('Save parsed data:', data);
                if (data.success) {
                    showToast('Import settings saved successfully! API key and preferences have been stored.', 'success');
                } else {
                    showToast('Save failed: ' + (data.data || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Save settings error:', error);
                showToast('Save failed: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        }
        
        // Test import connection function
        function testImportConnection() {
            console.log('Testing import connection...');
            const button = this;
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = 'Testing...';
            
            const apiKey = document.getElementById('import_api_key').value;
            
            if (!apiKey) {
                showToast('Please enter an API key before testing.', 'warning');
                button.disabled = false;
                button.textContent = originalText;
                return;
            }
            
            // Real API test - test our own API endpoint
            const formData = new FormData();
            formData.append('action', 'b2e_test_import_connection');
            formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
            formData.append('api_key', apiKey);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned non-JSON response. Status: ' + response.status);
                }
                
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100) + '...');
                    }
                });
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success) {
                    showToast('Import connection test successful! This site is ready to receive data.', 'success');
                } else {
                    showToast('Import connection test failed: ' + (data.data || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Import connection test error:', error);
                showToast('Import connection test failed: ' + error.message, 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        }
        
        // ========================================
        // PRE-MIGRATION REPORT & SELECTIVE SETTINGS
        // ========================================
        
        // Generate Migration Report
        document.getElementById('generate-report-btn')?.addEventListener('click', function() {
            const button = this;
            const originalText = button.textContent;
            const reportDiv = document.getElementById('migration-report');
            
            button.disabled = true;
            button.textContent = 'Generating Report...';
            reportDiv.innerHTML = '<p>⏳ Analyzing your site...</p>';
            reportDiv.style.display = 'block';
            
            const formData = new FormData();
            formData.append('action', 'b2e_generate_report');
            formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMigrationReport(data.data);
                    // Update counts in selective migration checkboxes
                    updateMigrationCounts(data.data);
                    showToast('Migration report generated successfully!', 'success');
                } else {
                    reportDiv.innerHTML = '<p style="color: #dc3232;">❌ Failed to generate report: ' + (data.data || 'Unknown error') + '</p>';
                    showToast('Failed to generate report', 'error');
                }
            })
            .catch(error => {
                console.error('Report generation error:', error);
                reportDiv.innerHTML = '<p style="color: #dc3232;">❌ Error: ' + error.message + '</p>';
                showToast('Error generating report', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        });
        
        // Display Migration Report
        function displayMigrationReport(report) {
            const reportDiv = document.getElementById('migration-report');
            
            let html = '<div style="background: #fff; padding: 20px; border-radius: 4px; border: 1px solid #ccc;">';
            
            // Summary
            html += '<h4 style="margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">📊 Migration Summary</h4>';
            html += '<table style="width: 100%; margin-bottom: 20px;">';
            html += '<tr><td><strong>Total Items:</strong></td><td>' + report.summary.total_items + '</td></tr>';
            html += '<tr><td><strong>Estimated Time:</strong></td><td>' + report.estimated_time.formatted + '</td></tr>';
            html += '<tr><td><strong>Estimated Size:</strong></td><td>' + report.estimated_size.formatted + '</td></tr>';
            html += '</table>';
            
            // Posts
            html += '<h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">📝 Posts (' + report.posts.total + ')</h4>';
            html += '<ul style="margin: 10px 0 20px 20px;">';
            for (let postType of report.summary.post_types) {
                html += '<li>' + postType.label + ': <strong>' + postType.count + '</strong></li>';
            }
            html += '</ul>';
            
            // CSS
            html += '<h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">🎨 CSS Classes (' + report.css.total_classes + ')</h4>';
            html += '<ul style="margin: 10px 0 20px 20px;">';
            html += '<li>Total Classes: <strong>' + report.css.total_classes + '</strong></li>';
            html += '<li>With Media Queries: <strong>' + report.css.has_media_queries + '</strong></li>';
            html += '<li>With Variables: <strong>' + report.css.has_variables + '</strong></li>';
            html += '<li>Size: <strong>' + report.css.size_formatted + '</strong></li>';
            html += '</ul>';
            
            // Custom Post Types
            if (report.custom_post_types.total > 0) {
                html += '<h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">📦 Custom Post Types (' + report.custom_post_types.total + ')</h4>';
                html += '<ul style="margin: 10px 0 20px 20px;">';
                for (let cptName in report.custom_post_types.types) {
                    let cpt = report.custom_post_types.types[cptName];
                    html += '<li>' + cpt.label + ': <strong>' + cpt.count + '</strong> (Published: ' + cpt.published + ', Draft: ' + cpt.draft + ')</li>';
                }
                html += '</ul>';
            }
            
            // Custom Fields
            html += '<h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">🔧 Custom Fields</h4>';
            html += '<ul style="margin: 10px 0 20px 20px;">';
            if (report.custom_fields.acf.active) {
                html += '<li>ACF: <strong>' + report.custom_fields.acf.field_groups + ' field groups</strong></li>';
            }
            if (report.custom_fields.metabox.active) {
                html += '<li>MetaBox: <strong>' + report.custom_fields.metabox.configs + ' configurations</strong></li>';
            }
            if (report.custom_fields.jetengine.active) {
                html += '<li>JetEngine: <strong>Active</strong></li>';
            }
            if (!report.custom_fields.acf.active && !report.custom_fields.metabox.active && !report.custom_fields.jetengine.active) {
                html += '<li style="color: #999;">No custom field plugins detected</li>';
            }
            html += '</ul>';
            
            // Warnings
            if (report.warnings && report.warnings.length > 0) {
                html += '<h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px;">⚠️ Warnings</h4>';
                html += '<ul style="margin: 10px 0 20px 20px;">';
                for (let warning of report.warnings) {
                    let color = warning.level === 'error' ? '#dc3232' : (warning.level === 'warning' ? '#f0ad4e' : '#999');
                    html += '<li style="color: ' + color + '">' + warning.message + '</li>';
                }
                html += '</ul>';
            }
            
            html += '</div>';
            
            reportDiv.innerHTML = html;
        }
        
        // Update Migration Counts
        function updateMigrationCounts(report) {
            // Posts
            const postsCount = report.posts.by_type.post || 0;
            document.getElementById('posts-count').textContent = postsCount > 0 ? '(' + postsCount + ' items)' : '';
            
            // Pages
            const pagesCount = report.posts.by_type.page || 0;
            document.getElementById('pages-count').textContent = pagesCount > 0 ? '(' + pagesCount + ' items)' : '';
            
            // CSS
            document.getElementById('css-count').textContent = report.css.total_classes > 0 ? '(' + report.css.total_classes + ' classes)' : '';
            
            // CPTs
            document.getElementById('cpts-count').textContent = report.custom_post_types.total > 0 ? '(' + report.custom_post_types.total + ' types)' : '';
            
            // Display CPT selection if there are any
            if (report.custom_post_types.total > 0) {
                const cptSelection = document.getElementById('cpt-selection');
                let html = '<fieldset style="border: 1px solid #ddd; padding: 10px; background: #fafafa;">';
                html += '<legend style="padding: 0 10px;">Select Custom Post Types:</legend>';
                for (let cptName in report.custom_post_types.types) {
                    let cpt = report.custom_post_types.types[cptName];
                    html += '<label style="display: block; margin: 5px 0;">';
                    html += '<input type="checkbox" name="selected_cpt[]" value="' + cpt.name + '" checked /> ';
                    html += cpt.label + ' (' + cpt.count + ' items)';
                    html += '</label>';
                }
                html += '</fieldset>';
                cptSelection.innerHTML = html;
                cptSelection.style.display = 'block';
            }
            
            // ACF
            if (report.custom_fields.acf.active) {
                document.getElementById('acf-count').textContent = '(' + report.custom_fields.acf.field_groups + ' groups)';
            }
            
            // MetaBox
            if (report.custom_fields.metabox.active) {
                document.getElementById('metabox-count').textContent = '(' + report.custom_fields.metabox.configs + ' configs)';
            }
        }
        
        // Save Migration Settings
        document.getElementById('save-migration-settings-btn')?.addEventListener('click', function() {
            const button = this;
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = 'Saving...';
            
            // Collect all settings
            const formData = new FormData();
            formData.append('action', 'b2e_save_migration_settings');
            formData.append('nonce', '<?php echo wp_create_nonce('b2e_nonce'); ?>');
            formData.append('migrate_posts', document.getElementById('migrate_posts').checked ? '1' : '');
            formData.append('migrate_pages', document.getElementById('migrate_pages').checked ? '1' : '');
            formData.append('migrate_css', document.getElementById('migrate_css').checked ? '1' : '');
            formData.append('migrate_cpts', document.getElementById('migrate_cpts').checked ? '1' : '');
            formData.append('migrate_acf', document.getElementById('migrate_acf').checked ? '1' : '');
            formData.append('migrate_metabox', document.getElementById('migrate_metabox').checked ? '1' : '');
            formData.append('migrate_jetengine', document.getElementById('migrate_jetengine').checked ? '1' : '');
            formData.append('cleanup_bricks_meta', document.getElementById('cleanup_bricks_meta')?.checked ? '1' : '');
            formData.append('convert_div_to_flex', document.getElementById('convert_div_to_flex')?.checked ? '1' : '');
            
            // Post statuses
            const statuses = [];
            if (document.querySelector('input[name="post_status_publish"]')?.checked) statuses.push('publish');
            if (document.querySelector('input[name="post_status_draft"]')?.checked) statuses.push('draft');
            if (document.querySelector('input[name="post_status_private"]')?.checked) statuses.push('private');
            formData.append('selected_post_statuses', statuses.join(','));
            
            // Selected CPTs
            const selectedCpts = [];
            document.querySelectorAll('input[name="selected_cpt[]"]:checked').forEach(checkbox => {
                selectedCpts.push(checkbox.value);
            });
            formData.append('selected_post_types', selectedCpts.join(','));
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update scope summary
                    const scopeSummary = document.getElementById('migration-scope-summary');
                    if (scopeSummary && data.data.scope) {
                        scopeSummary.textContent = data.data.scope.join(', ');
                    }
                    showToast('Migration settings saved successfully!', 'success');
                } else {
                    showToast('Failed to save settings: ' + (data.data || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Settings save error:', error);
                showToast('Error saving settings', 'error');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = originalText;
            });
        });
        
        // Reset Migration Settings
        document.getElementById('reset-migration-settings-btn')?.addEventListener('click', function() {
            if (confirm('Reset all migration settings to defaults?')) {
                // Reset checkboxes to defaults
                document.getElementById('migrate_posts').checked = true;
                document.getElementById('migrate_pages').checked = true;
                document.getElementById('migrate_css').checked = true;
                document.getElementById('migrate_cpts').checked = true;
                document.getElementById('migrate_acf').checked = true;
                document.getElementById('migrate_metabox').checked = true;
                document.getElementById('migrate_jetengine').checked = false;
                document.querySelector('input[name="post_status_publish"]').checked = true;
                document.querySelector('input[name="post_status_draft"]').checked = true;
                document.querySelector('input[name="post_status_private"]').checked = true;
                
                showToast('Settings reset to defaults', 'info');
            }
        });
        
        // Auto-generate report on page load (if Bricks is detected)
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const reportBtn = document.getElementById('generate-report-btn');
                if (reportBtn) {
                    // Auto-click to show report immediately
                    // reportBtn.click(); // Uncomment to enable auto-generation
                }
            }, 1000);
        });
        
        </script>
        <?php
    }
    
    /**
     * Render status overview
     */
    private function render_status_overview($progress) {
        $status = $progress['status'] ?? 'idle';
        $status_class = 'idle';
        $status_text = __('Ready', 'bricks-etch-migration');
        
        switch ($status) {
            case 'running':
                $status_class = 'running';
                $status_text = __('Migration in Progress', 'bricks-etch-migration');
                break;
            case 'completed':
                $status_class = 'completed';
                $status_text = __('Migration Completed', 'bricks-etch-migration');
                break;
            case 'error':
                $status_class = 'error';
                $status_text = __('Migration Failed', 'bricks-etch-migration');
                break;
        }
        
        ?>
        <div class="notice notice-<?php echo esc_attr($status_class); ?>">
            <p><strong><?php echo esc_html($status_text); ?></strong></p>
        </div>
        <?php
    }
    
    /**
     * Render migration form with tabs
     */
    private function render_migration_form($settings) {
        ?>
        <div class="b2e-card">
            <h2><?php _e('Migration Settings', 'bricks-etch-migration'); ?></h2>
            
            <!-- Tab Navigation -->
            <div class="b2e-tab-nav">
                <button type="button" class="b2e-tab-button active" data-tab="export">
                    <?php _e('Export (Source Site)', 'bricks-etch-migration'); ?>
                </button>
                <button type="button" class="b2e-tab-button" data-tab="import">
                    <?php _e('Import (Target Site)', 'bricks-etch-migration'); ?>
                </button>
            </div>
            
            <!-- Export Tab -->
            <div id="export-tab" class="b2e-tab-content active">
                
                <!-- Pre-Migration Report Section -->
                <div class="b2e-report-section" style="margin-bottom: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <h3 style="margin-top: 0;">
                        📊 <?php _e('Pre-Migration Report', 'bricks-etch-migration'); ?>
                    </h3>
                    <p><?php _e('Analyze your site to see what will be migrated.', 'bricks-etch-migration'); ?></p>
                    
                    <button type="button" id="generate-report-btn" class="button button-primary">
                        <?php _e('Generate Migration Report', 'bricks-etch-migration'); ?>
                    </button>
                    
                    <div id="migration-report" style="display: none; margin-top: 20px;"></div>
                </div>
                
                <!-- Selective Migration Settings -->
                <div class="b2e-selective-migration" style="margin-bottom: 30px; padding: 20px; background: #f0f8ff; border: 1px solid #0073aa; border-radius: 4px;">
                    <h3 style="margin-top: 0;">
                        ⚙️ <?php _e('What to Migrate', 'bricks-etch-migration'); ?>
                    </h3>
                    <p><?php _e('Select which content types you want to migrate.', 'bricks-etch-migration'); ?></p>
                    
                    <?php
                    $settings_manager = new B2E_Migration_Settings();
                    $migration_settings = $settings_manager->get_settings();
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Content Types', 'bricks-etch-migration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" id="migrate_posts" name="migrate_posts" 
                                               <?php checked($migration_settings['migrate_posts']); ?> />
                                        <?php _e('Posts', 'bricks-etch-migration'); ?>
                                        <span class="description" id="posts-count"></span>
                                    </label><br />
                                    
                                    <label>
                                        <input type="checkbox" id="migrate_pages" name="migrate_pages" 
                                               <?php checked($migration_settings['migrate_pages']); ?> />
                                        <?php _e('Pages', 'bricks-etch-migration'); ?>
                                        <span class="description" id="pages-count"></span>
                                    </label><br />
                                    
                                    <label>
                                        <input type="checkbox" id="migrate_css" name="migrate_css" 
                                               <?php checked($migration_settings['migrate_css']); ?> />
                                        <?php _e('CSS Classes', 'bricks-etch-migration'); ?>
                                        <span class="description" id="css-count"></span>
                                    </label><br />
                                    
                                    <label>
                                        <input type="checkbox" id="migrate_cpts" name="migrate_cpts" 
                                               <?php checked($migration_settings['migrate_cpts']); ?> />
                                        <?php _e('Custom Post Types', 'bricks-etch-migration'); ?>
                                        <span class="description" id="cpts-count"></span>
                                    </label>
                                    <div id="cpt-selection" style="margin-left: 25px; margin-top: 10px; display: none;"></div>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Custom Fields', 'bricks-etch-migration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" id="migrate_acf" name="migrate_acf" 
                                               <?php checked($migration_settings['migrate_acf']); ?> />
                                        <?php _e('ACF Field Groups', 'bricks-etch-migration'); ?>
                                        <span class="description" id="acf-count"></span>
                                    </label><br />
                                    
                                    <label>
                                        <input type="checkbox" id="migrate_metabox" name="migrate_metabox" 
                                               <?php checked($migration_settings['migrate_metabox']); ?> />
                                        <?php _e('MetaBox Configurations', 'bricks-etch-migration'); ?>
                                        <span class="description" id="metabox-count"></span>
                                    </label><br />
                                    
                                    <label>
                                        <input type="checkbox" id="migrate_jetengine" name="migrate_jetengine" 
                                               <?php checked($migration_settings['migrate_jetengine']); ?> />
                                        <?php _e('JetEngine Fields', 'bricks-etch-migration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Post Status', 'bricks-etch-migration'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="post_status_publish" value="publish" 
                                               <?php checked(in_array('publish', $migration_settings['selected_post_statuses'])); ?> />
                                        <?php _e('Published', 'bricks-etch-migration'); ?>
                                    </label>
                                    
                                    <label style="margin-left: 15px;">
                                        <input type="checkbox" name="post_status_draft" value="draft" 
                                               <?php checked(in_array('draft', $migration_settings['selected_post_statuses'])); ?> />
                                        <?php _e('Draft', 'bricks-etch-migration'); ?>
                                    </label>
                                    
                                    <label style="margin-left: 15px;">
                                        <input type="checkbox" name="post_status_private" value="private" 
                                               <?php checked(in_array('private', $migration_settings['selected_post_statuses'])); ?> />
                                        <?php _e('Private', 'bricks-etch-migration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 15px; padding: 10px; background: #fff; border-left: 4px solid #0073aa;">
                        <strong><?php _e('Selected Scope:', 'bricks-etch-migration'); ?></strong>
                        <span id="migration-scope-summary"><?php echo esc_html(implode(', ', $settings_manager->get_scope_summary())); ?></span>
                    </div>
                    
                    <p class="submit" style="margin-top: 15px; padding-top: 0;">
                        <button type="button" id="save-migration-settings-btn" class="button button-primary">
                            <?php _e('Save Migration Settings', 'bricks-etch-migration'); ?>
                        </button>
                        <button type="button" id="reset-migration-settings-btn" class="button">
                            <?php _e('Reset to Defaults', 'bricks-etch-migration'); ?>
                        </button>
                    </p>
                </div>
                
                <form id="b2e-export-form">
                    <h3><?php _e('Export Settings - Source Site (Bricks)', 'bricks-etch-migration'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="target_url"><?php _e('Target Site URL', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="target_url" name="target_url" 
                                       value="<?php echo esc_attr($settings['target_url'] ?? ''); ?>" 
                                       class="regular-text" required />
                                <p class="description">
                                    <?php _e('The URL of the target site where you want to migrate to.', 'bricks-etch-migration'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="export_api_key"><?php _e('API Key (for Target Site)', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="export_api_key" name="export_api_key" 
                                       value="<?php echo esc_attr($settings['export_api_key'] ?? ''); ?>" 
                                       class="regular-text" required />
                                <button type="button" id="generate-export-api-key" class="button">
                                    <?php _e('Generate New Key', 'bricks-etch-migration'); ?>
                                </button>
                                <p class="description">
                                    <?php _e('Generate an API key and copy it to your target site.', 'bricks-etch-migration'); ?>
                                </p>
                                
                                <!-- Generated Key Display -->
                                <div id="export-generated-key-display" style="display: none; margin-top: 10px;">
                                    <strong><?php _e('Generated API Key:', 'bricks-etch-migration'); ?></strong>
                                    <div style="background: #fff; padding: 10px; border: 1px solid #ddd; margin: 5px 0; font-family: monospace; word-break: break-all;" id="export-api-key-value"></div>
                                    <button type="button" id="copy-export-api-key" class="button button-small">
                                        <?php _e('Copy to Clipboard', 'bricks-etch-migration'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cleanup_bricks_meta"><?php _e('Cleanup Options', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="cleanup_bricks_meta" name="cleanup_bricks_meta" 
                                           <?php checked($settings['cleanup_bricks_meta'] ?? false); ?> />
                                    <?php _e('Remove Bricks meta data after migration', 'bricks-etch-migration'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="convert_div_to_flex"><?php _e('Element Conversion', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="convert_div_to_flex" name="convert_div_to_flex" 
                                           <?php checked($settings['convert_div_to_flex'] ?? true); ?> />
                                    <?php _e('Convert brxe-div elements to flex-div', 'bricks-etch-migration'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" id="start-export" class="b2e-button">
                            <?php _e('Start Export/Migration', 'bricks-etch-migration'); ?>
                        </button>
                        <button type="button" id="test-export-connection" class="button">
                            <?php _e('Test Connection', 'bricks-etch-migration'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Import Tab -->
            <div id="import-tab" class="b2e-tab-content">
                <form id="b2e-import-form">
                    <h3><?php _e('Import Settings - Target Site (Etch)', 'bricks-etch-migration'); ?></h3>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_api_key"><?php _e('API Key (from Source Site)', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="import_api_key" name="import_api_key" 
                                       value="<?php echo esc_attr($settings['import_api_key'] ?? ''); ?>" 
                                       class="regular-text" required />
                                <p class="description">
                                    <?php _e('Paste the API key from your source site here.', 'bricks-etch-migration'); ?>
                                </p>
                                
                                <!-- Import Instructions -->
                                <div class="b2e-import-instructions" style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                                    <h4 style="margin-top: 0;"><?php _e('How to get the API Key:', 'bricks-etch-migration'); ?></h4>
                                    <ol>
                                        <li><strong><?php _e('Go to Source Site:', 'bricks-etch-migration'); ?></strong> <?php _e('Open your Bricks website in another tab.', 'bricks-etch-migration'); ?></li>
                                        <li><strong><?php _e('Generate Key:', 'bricks-etch-migration'); ?></strong> <?php _e('In the Export tab, click "Generate New Key".', 'bricks-etch-migration'); ?></li>
                                        <li><strong><?php _e('Copy Key:', 'bricks-etch-migration'); ?></strong> <?php _e('Click "Copy to Clipboard" on the source site.', 'bricks-etch-migration'); ?></li>
                                        <li><strong><?php _e('Paste Here:', 'bricks-etch-migration'); ?></strong> <?php _e('Paste the key in the field above.', 'bricks-etch-migration'); ?></li>
                                        <li><strong><?php _e('Test Connection:', 'bricks-etch-migration'); ?></strong> <?php _e('Click "Test Connection" to verify.', 'bricks-etch-migration'); ?></li>
                                    </ol>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="import_auto_accept"><?php _e('Auto Accept Import', 'bricks-etch-migration'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="import_auto_accept" name="import_auto_accept" 
                                           <?php checked($settings['import_auto_accept'] ?? true); ?> />
                                    <?php _e('Automatically accept incoming migration data', 'bricks-etch-migration'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, data will be imported automatically without confirmation.', 'bricks-etch-migration'); ?>
                                </p>
                            </td>
                        </tr>
                        
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="test-import-connection" class="button">
                            <?php _e('Test Connection', 'bricks-etch-migration'); ?>
                        </button>
                        <button type="button" id="save-import-settings" class="b2e-button">
                            <?php _e('Save Import Settings', 'bricks-etch-migration'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render progress section
     */
    private function render_progress_section($progress) {
        $percentage = $progress['percentage'] ?? 0;
        $current_step = $progress['current_step'] ?? '';
        
        ?>
        <div class="b2e-card" id="progress-section" style="display: none;">
            <h2><?php _e('Migration Progress', 'bricks-etch-migration'); ?></h2>
            
            <div class="b2e-progress-bar">
                <div class="b2e-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
            </div>
            
            <p id="progress-text">
                <strong><?php echo esc_html($percentage); ?>%</strong> - 
                <span id="current-step"><?php echo esc_html($current_step); ?></span>
            </p>
            
            <div id="progress-details" class="b2e-progress-steps">
                <h4><?php _e('Migration Steps', 'bricks-etch-migration'); ?></h4>
                <ul id="migration-steps">
                    <li data-step="validation"><?php _e('Validation', 'bricks-etch-migration'); ?></li>
                    <li data-step="cpts"><?php _e('Custom Post Types', 'bricks-etch-migration'); ?></li>
                    <li data-step="acf_field_groups"><?php _e('ACF Field Groups', 'bricks-etch-migration'); ?></li>
                    <li data-step="metabox_configs"><?php _e('MetaBox Configurations', 'bricks-etch-migration'); ?></li>
                    <li data-step="css_classes"><?php _e('CSS Classes', 'bricks-etch-migration'); ?></li>
                    <li data-step="posts"><?php _e('Posts & Content', 'bricks-etch-migration'); ?></li>
                    <li data-step="finalization"><?php _e('Finalization', 'bricks-etch-migration'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render validation results
     */
    private function render_validation_results() {
        ?>
        <div class="b2e-card">
            <h3><?php _e('Pre-Migration Validation', 'bricks-etch-migration'); ?></h3>
            
            <div id="validation-results">
                <p><?php _e('Click "Test Connection" to validate your setup.', 'bricks-etch-migration'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recent logs
     */
    private function render_recent_logs($logs) {
        $recent_logs = array_slice($logs, -10); // Last 10 entries
        
        ?>
        <div class="b2e-card">
            <h3><?php _e('Recent Activity', 'bricks-etch-migration'); ?></h3>
            
            <div id="recent-logs">
                <?php if (empty($recent_logs)): ?>
                    <p><?php _e('No recent activity.', 'bricks-etch-migration'); ?></p>
                <?php else: ?>
                    <?php foreach (array_reverse($recent_logs) as $log): ?>
                        <div class="b2e-log-entry <?php echo esc_attr($log['type']); ?>">
                            <strong><?php echo esc_html($log['title']); ?></strong>
                            <p><?php echo esc_html($log['description']); ?></p>
                            <small><?php echo esc_html($log['timestamp']); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <p>
                <button type="button" id="clear-logs" class="button">
                    <?php _e('Clear Logs', 'bricks-etch-migration'); ?>
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * AJAX: Start migration
     */
    public function ajax_start_migration() {
        try {
            // Increase limits for migration
            @ini_set('memory_limit', '512M');
            @ini_set('max_execution_time', '300');
            
            check_ajax_referer('b2e_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
                return;
            }
            
            $target_url = sanitize_url($_POST['target_url']);
            $api_key = sanitize_text_field($_POST['api_key']);
            $cleanup_bricks_meta = isset($_POST['cleanup_bricks_meta']);
            $convert_div_to_flex = isset($_POST['convert_div_to_flex']);
            
            // Update settings
            $settings = array(
                'target_url' => $target_url,
                'api_key' => $api_key,
                'cleanup_bricks_meta' => $cleanup_bricks_meta,
                'convert_div_to_flex' => $convert_div_to_flex,
            );
            update_option('b2e_settings', $settings);
            
            // Also store the API key for this site (target site will use this for validation)
            update_option('b2e_api_key', $api_key);
            
            // Pre-check: Verify required classes exist
            $required_classes = array(
                'B2E_Migration_Manager',
                'B2E_Error_Handler',
                'B2E_Plugin_Detector',
                'B2E_Content_Parser',
                'B2E_CSS_Converter',
                'B2E_Gutenberg_Generator',
                'B2E_API_Client',
            );
            
            $missing_classes = array();
            foreach ($required_classes as $class) {
                if (!class_exists($class)) {
                    $missing_classes[] = $class;
                }
            }
            
            if (!empty($missing_classes)) {
                wp_send_json_error('Missing required classes: ' . implode(', ', $missing_classes));
                return;
            }
            
            // Start migration immediately (simplified for now)
            $migration_manager = new B2E_Migration_Manager();
            $result = $migration_manager->start_migration($target_url, $api_key);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(__('Migration started successfully!', 'bricks-etch-migration'));
            }
        } catch (Exception $e) {
            error_log('B2E Start Migration Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            error_log('B2E Stack Trace: ' . $e->getTraceAsString());
            wp_send_json_error('Migration start failed: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ', Line: ' . $e->getLine() . ')');
        }
    }
    
    /**
     * AJAX: Test export connection
     */
    public function ajax_test_export_connection() {
        try {
            check_ajax_referer('b2e_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
                return;
            }
            
            $target_url = sanitize_url($_POST['target_url']);
            $api_key = sanitize_text_field($_POST['api_key']);
            
            if (empty($target_url) || empty($api_key)) {
                wp_send_json_error(__('Target URL and API key are required.', 'bricks-etch-migration'));
                return;
            }
            
            // Test 1: Basic WordPress REST API
            $test_url = rtrim($target_url, '/') . '/wp-json/';
            $response = wp_remote_get($test_url, array('timeout' => 10));
            
            if (is_wp_error($response)) {
                wp_send_json_error('Cannot reach target site: ' . $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code < 200 || $status_code >= 400) {
                wp_send_json_error('Target site returned status code: ' . $status_code);
            }
            
            // Test 2: Our specific API endpoint with authentication
            $api_test_url = rtrim($target_url, '/') . '/wp-json/b2e/v1/auth/validate';
            $api_response = wp_remote_post($api_test_url, array(
                'timeout' => 10,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $api_key
                ),
                'body' => json_encode(array('api_key' => $api_key))
            ));
            
            if (is_wp_error($api_response)) {
                wp_send_json_error('API endpoint test failed: ' . $api_response->get_error_message());
            }
            
            $api_status_code = wp_remote_retrieve_response_code($api_response);
            $api_body = wp_remote_retrieve_body($api_response);
            
            if ($api_status_code === 401) {
                wp_send_json_error('API Key authentication failed (401). Please check if the API key is correctly set on the target site.');
            } elseif ($api_status_code === 404) {
                wp_send_json_error('API endpoint not found (404). Please ensure the Bricks to Etch Migration plugin is installed on the target site.');
            } elseif ($api_status_code >= 200 && $api_status_code < 400) {
                wp_send_json_success(__('Connection test successful! Target site is reachable and API key is valid.', 'bricks-etch-migration'));
            } else {
                wp_send_json_error('API endpoint returned status code: ' . $api_status_code . '. Response: ' . substr($api_body, 0, 200));
            }
        } catch (Exception $e) {
            error_log('B2E Connection Test Error: ' . $e->getMessage());
            wp_send_json_error('Connection test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Test import connection (tests our own API)
     */
    public function ajax_test_import_connection() {
        try {
            check_ajax_referer('b2e_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
                return;
            }
            
            $api_key = sanitize_text_field($_POST['api_key']);
            
            if (empty($api_key)) {
                wp_send_json_error(__('API key is required.', 'bricks-etch-migration'));
                return;
            }
            
            // Test our own API endpoint
            $test_url = home_url('/wp-json/b2e/v1/auth/test');
            $response = wp_remote_get($test_url, array(
                'timeout' => 10,
                'headers' => array(
                    'X-API-Key' => $api_key
                )
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error('Cannot reach our API endpoint: ' . $response->get_error_message());
                return;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($status_code >= 200 && $status_code < 400) {
                // Check if API key is set correctly
                $stored_api_key = get_option('b2e_api_key', '');
                if ($stored_api_key === $api_key) {
                    wp_send_json_success(__('Import connection test successful! This site is ready to receive data.', 'bricks-etch-migration'));
                } else {
                    wp_send_json_error('API key mismatch. Please save the API key first.');
                }
            } else {
                wp_send_json_error('API endpoint returned status code: ' . $status_code . '. Response: ' . substr($body, 0, 200));
            }
        } catch (Exception $e) {
            error_log('B2E Import Connection Test Error: ' . $e->getMessage());
            wp_send_json_error('Import connection test failed: ' . $e->getMessage());
        }
    }
    
    
    /**
     * AJAX: Get progress
     */
    public function ajax_get_progress() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        $progress = get_option('b2e_migration_progress', array());
        wp_send_json_success($progress);
    }
    
    /**
     * AJAX: Get logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        $error_handler = new B2E_Error_Handler();
        $logs = $error_handler->get_log();
        
        wp_send_json_success($logs);
    }
    
    /**
     * AJAX: Clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $error_handler = new B2E_Error_Handler();
        $error_handler->clear_log();
        
        wp_send_json_success(__('Logs cleared successfully.', 'bricks-etch-migration'));
    }
    
    /**
     * AJAX: Save import settings
     */
    public function ajax_save_import_settings() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $import_api_key = sanitize_text_field($_POST['import_api_key']);
        $import_auto_accept = isset($_POST['import_auto_accept']);
        
        // Update settings
        $settings = get_option('b2e_settings', array());
        $settings['import_api_key'] = $import_api_key;
        $settings['import_auto_accept'] = $import_auto_accept;
        
        update_option('b2e_settings', $settings);
        
        // Also store the API key for validation
        if (!empty($import_api_key)) {
            update_option('b2e_api_key', $import_api_key);
        }
        
        wp_send_json_success(__('Import settings saved successfully.', 'bricks-etch-migration'));
    }
    
    /**
     * AJAX: Generate migration report
     */
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
    
    /**
     * Parse post statuses from string or array
     */
    private function parse_post_statuses($input) {
        if (is_array($input)) {
            // If already array, check if it's a single string element
            if (count($input) === 1 && is_string($input[0]) && strpos($input[0], ',') !== false) {
                return array_map('trim', explode(',', $input[0]));
            }
            return $input;
        }
        
        if (is_string($input) && strpos($input, ',') !== false) {
            return array_map('trim', explode(',', $input));
        }
        
        return array($input);
    }
    
    /**
     * AJAX: Save migration settings
     */
    public function ajax_save_migration_settings() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'bricks-etch-migration'));
            return;
        }
        
        $settings_manager = new B2E_Migration_Settings();
        
        $settings = array(
            'migrate_posts' => isset($_POST['migrate_posts']),
            'migrate_pages' => isset($_POST['migrate_pages']),
            'migrate_css' => isset($_POST['migrate_css']),
            'migrate_cpts' => isset($_POST['migrate_cpts']),
            'migrate_acf' => isset($_POST['migrate_acf']),
            'migrate_metabox' => isset($_POST['migrate_metabox']),
            'migrate_jetengine' => isset($_POST['migrate_jetengine']),
            'selected_post_types' => isset($_POST['selected_post_types']) ? (array) $_POST['selected_post_types'] : array(),
            'selected_post_statuses' => $this->parse_post_statuses($_POST['selected_post_statuses'] ?? 'publish'),
            'cleanup_bricks_meta' => isset($_POST['cleanup_bricks_meta']),
            'convert_div_to_flex' => isset($_POST['convert_div_to_flex'])
        );
        
        $saved_settings = $settings_manager->save_settings($settings);
        
        wp_send_json_success(array(
            'message' => __('Migration settings saved successfully.', 'bricks-etch-migration'),
            'settings' => $saved_settings,
            'scope' => $settings_manager->get_scope_summary()
        ));
    }
}
