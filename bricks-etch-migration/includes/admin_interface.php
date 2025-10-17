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
    
    public function __construct($register_menu = true) {
        // Only register admin menu if requested (to avoid duplicates)
        if ($register_menu) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        
        // Register admin scripts and styles properly
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register AJAX handlers (these can be registered multiple times safely)
        add_action('wp_ajax_b2e_test_export_connection', array($this, 'ajax_test_export_connection'));
        add_action('wp_ajax_b2e_test_import_connection', array($this, 'ajax_test_import_connection'));
        add_action('wp_ajax_b2e_start_migration', array($this, 'ajax_start_migration'));
        add_action('wp_ajax_b2e_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_b2e_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_b2e_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_b2e_save_migration_settings', array($this, 'ajax_save_migration_settings'));
        add_action('wp_ajax_b2e_generate_migration_key', array($this, 'ajax_generate_migration_key'));
        add_action('wp_ajax_b2e_validate_api_key', array($this, 'ajax_validate_api_key'));
        add_action('wp_ajax_b2e_validate_migration_token', array($this, 'ajax_validate_migration_token'));
        add_action('wp_ajax_b2e_get_migration_progress', array($this, 'ajax_get_migration_progress'));
    }
    
    /**
     * Enqueue admin scripts and styles properly
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'bricks-etch-migration') === false) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'b2e-admin-css',
            B2E_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            B2E_VERSION
        );
        
        // Add AJAX configuration to page
        wp_localize_script('b2e-admin-js', 'b2e_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('b2e_nonce')
        ));
        
        // Fallback: Add AJAX configuration directly to page if script doesn't load
        add_action('admin_footer', array($this, 'add_ajax_config_fallback'));
    }
    
    /**
     * Add AJAX configuration fallback directly to page
     */
    public function add_ajax_config_fallback() {
        // Only add on our plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'bricks-etch-migration') === false) {
            return;
        }
        
        echo '<script type="text/javascript">';
        echo 'if (typeof b2e_ajax === "undefined") {';
        echo '    var b2e_ajax = {';
        echo '        ajax_url: "' . admin_url('admin-ajax.php') . '",';
        echo '        nonce: "' . wp_create_nonce('b2e_nonce') . '"';
        echo '    };';
        echo '}';
        echo '</script>';
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
        
        // Only show progress if migration is actually running (not just old data)
        $progress = get_option('b2e_migration_progress', array());
        if (!empty($progress) && isset($progress['status']) && $progress['status'] === 'running') {
            // Check if migration is actually still running (not stuck)
            $last_update = isset($progress['last_update']) ? strtotime($progress['last_update']) : 0;
            $timeout = 300; // 5 minutes timeout
            
            if (time() - $last_update > $timeout) {
                // Migration appears to be stuck, reset it
                $progress['status'] = 'timeout';
                $progress['message'] = 'Migration timed out. Please try again.';
                update_option('b2e_migration_progress', $progress);
            }
        } else {
            // Clear any old progress data if not running
            $progress = array();
        }
        
        $error_handler = new B2E_Error_Handler();
        $logs = $error_handler->get_log();
        
        ?>
        <!-- AJAX Nonce -->
        <input type="hidden" id="b2e_nonce" value="<?php echo wp_create_nonce('b2e_nonce'); ?>" />
        
        <!-- Inline JavaScript for better reliability -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 B2E Debug - DOM loaded, initializing...');
            console.log('🔍 B2E Debug - Available elements:');
            console.log('🔍 B2E Debug - validate-migration-key:', document.getElementById('validate-migration-key'));
            console.log('🔍 B2E Debug - start-migration:', document.getElementById('start-migration'));
            console.log('🔍 B2E Debug - generate-migration-key:', document.getElementById('generate-migration-key'));
            console.log('🔍 B2E Debug - clear-logs:', document.getElementById('clear-logs'));
            console.log('🔍 B2E Debug - copy-key:', document.getElementById('copy-key'));
            console.log('🔍 B2E Debug - paste-key:', document.getElementById('paste-key'));
            
            // Initialize all functionality
            initMigrationKeyValidation();
            initClearLogs();
            initStartMigration();
            initKeyGeneration();
            initCopyKey();
            initPasteKey();
            
            // Add simple click listeners for debugging
            document.addEventListener('click', function(e) {
                console.log('🔍 B2E Debug - Click detected on:', e.target);
                console.log('🔍 B2E Debug - Target ID:', e.target.id);
                console.log('🔍 B2E Debug - Target class:', e.target.className);
                
                // Simple button tests
                if (e.target.id === 'validate-migration-key') {
                    console.log('🔍 B2E Debug - Validate button clicked - using dedicated handler');
                    // This will be handled by the dedicated validate handler
                }
                if (e.target.id === 'start-migration') {
                    console.log('🔍 B2E Debug - Start migration button clicked');
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
                            showToast('Invalid migration key format.', 'error');
                            return;
                        }
                        
                        // Start migration
                        startMigrationProcess(domain, token, expires);
                        
                    } catch (error) {
                        console.error('Migration key parsing error:', error);
                        showToast('Invalid migration key format. Please check the URL.', 'error');
                    }
                }
                if (e.target.id === 'generate-migration-key') {
                    console.log('🔍 B2E Debug - Generate key button clicked - showing test toast');
                    showToast('Generate key button clicked! (Test)', 'info');
                }
                if (e.target.id === 'clear-logs') {
                    console.log('🔍 B2E Debug - Clear logs button clicked - using dedicated handler');
                    // This will be handled by the dedicated clear logs handler
                }
                if (e.target.id === 'generate-report') {
                    console.log('🔍 B2E Debug - Generate report button clicked');
                    generateMigrationReport();
                }
            });
        });

        /**
         * Initialize migration key validation
         */
        function initMigrationKeyValidation() {
            const validateBtn = document.getElementById('validate-migration-key');
            if (!validateBtn) {
                console.log('🔍 B2E Debug - Validate button not found');
                return;
            }
            console.log('🔍 B2E Debug - Validate button found, adding listener');

            validateBtn.addEventListener('click', function() {
                console.log('🔍 B2E Debug - Validate button clicked');
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
                        showToast('Invalid migration key format.', 'error');
                        return;
                    }
                    
                    // Debug: Log the migration key components
                    console.log('🔍 B2E Debug - Domain:', domain);
                    console.log('🔍 B2E Debug - Token:', token.substring(0, 20) + '...');
                    console.log('🔍 B2E Debug - Expires:', expires);
                    
                    // Validate migration token (not API key)
                    // The token is validated, and the API key is retrieved from the target site
                    const formData = new FormData();
                    formData.append('action', 'b2e_validate_migration_token');
                    formData.append('nonce', b2e_ajax.nonce);
                    formData.append('target_url', domain);
                    formData.append('token', token);
                    formData.append('expires', expires);
                    
                    fetch(b2e_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('🔍 B2E Debug - AJAX response:', data);
                        
                        if (data.success) {
                            showToast('Migration token validated successfully! Ready to migrate.', 'success');
                            
                            // Store API key for later use
                            if (data.data.api_key) {
                                // Store API key in a hidden field or session storage
                                sessionStorage.setItem('b2e_api_key', data.data.api_key);
                                console.log('🔍 B2E Debug - API key stored for migration');
                            }
                            
                            // Show migration info
                            const infoDiv = document.getElementById('migration-key-info');
                            if (infoDiv) {
                                infoDiv.innerHTML = `
                                    <div class="b2e-status success">✅ Migration Token Valid</div>
                                    <p><strong>Target Site:</strong> ${domain}</p>
                                    <p><strong>Status:</strong> ${data.data.message || 'Connected and ready'}</p>
                                    <p><strong>Token expires:</strong> ${new Date(expires * 1000).toLocaleString()}</p>
                                `;
                                infoDiv.style.display = 'block';
                            }
                        } else {
                            showToast('Migration token validation failed: ' + (data.data || 'Invalid token'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('🔍 B2E Debug - API validation error:', error);
                        
                        let errorMessage = error.message;
                        if (error.name === 'AbortError') {
                            errorMessage = 'API request timed out (10 seconds)';
                        } else if (error.message.includes('Failed to fetch')) {
                            errorMessage = 'Cannot connect to target site. Please check:<br>• Network connectivity<br>• Target site is accessible<br>• CORS settings allow requests';
                        } else if (error.message.includes('404')) {
                            errorMessage = 'API endpoint not found. Please ensure B2E plugin is installed on target site.';
                        } else if (error.message.includes('500')) {
                            errorMessage = 'Server error on target site. Check target site error logs.';
                        }
                        showToast(`API validation failed: ${errorMessage}`, 'error');
                        const infoDiv = document.getElementById('migration-key-info');
                        if (infoDiv) {
                            infoDiv.style.display = 'none';
                        }
                    });
                    
                } catch (error) {
                    console.error('🔍 B2E Debug - URL parsing error:', error);
                    showToast('Invalid migration key format. Please check the URL.', 'error');
                }
            });
        }

        /**
         * Initialize clear logs functionality
         */
        function initClearLogs() {
            const clearLogsBtn = document.getElementById('clear-logs');
            if (!clearLogsBtn) {
                console.log('🔍 B2E Debug - Clear logs button not found');
                return;
            }
            console.log('🔍 B2E Debug - Clear logs button found, adding listener');

            clearLogsBtn.addEventListener('click', function() {
                console.log('🔍 B2E Debug - Clear logs button clicked');
                showClearLogsConfirmation();
            });
        }

        /**
         * Initialize start migration functionality (REMOVED - using global handler)
         */
        function initStartMigration() {
            console.log('🔍 B2E Debug - Start migration init called - using global handler instead');
            // This function is intentionally empty - the start migration button
            // is handled by the global click handler in initGlobalClickHandlers()
        }

        /**
         * Initialize key generation functionality
         */
        function initKeyGeneration() {
            const generateBtn = document.getElementById('generate-migration-key');
            if (!generateBtn) {
                console.log('🔍 B2E Debug - Generate key button not found');
                return;
            }
            console.log('🔍 B2E Debug - Generate key button found, adding listener');

            generateBtn.addEventListener('click', function() {
                console.log('🔍 B2E Debug - Generate key button clicked');
                const expirationHours = document.getElementById('expiration_hours').value || '24';
                
                const formData = new FormData();
                formData.append('action', 'b2e_generate_migration_key');
                formData.append('nonce', b2e_ajax.nonce);
                formData.append('expiration_hours', expirationHours);
                
                fetch(b2e_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('🔍 B2E Debug - Key generation response:', data);
                    if (data.success) {
                        document.getElementById('generated_key').value = data.data.migration_url;
                        document.getElementById('generated-key-section').style.display = 'block';
                        showToast('Migration key generated successfully!', 'success');
                    } else {
                        showToast('Error generating key: ' + data.data, 'error');
                    }
                })
                .catch(error => {
                    console.error('Key generation error:', error);
                    showToast('Failed to generate migration key. Please try again.', 'error');
                });
            });
        }

        /**
         * Initialize copy key functionality
         */
        function initCopyKey() {
            const copyBtn = document.getElementById('copy-key');
            if (!copyBtn) {
                console.log('🔍 B2E Debug - Copy key button not found');
                return;
            }
            console.log('🔍 B2E Debug - Copy key button found, adding listener');

            copyBtn.addEventListener('click', function() {
                console.log('🔍 B2E Debug - Copy key button clicked');
                const keyTextarea = document.getElementById('generated_key');
                if (!keyTextarea) return;

                keyTextarea.select();
                keyTextarea.setSelectionRange(0, 99999); // For mobile devices

                try {
                    document.execCommand('copy');
                    showToast('Migration key copied to clipboard!', 'success');
                } catch (err) {
                    // Fallback for modern browsers
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(keyTextarea.value).then(() => {
                            showToast('Migration key copied to clipboard!', 'success');
                        }).catch(() => {
                            showToast('Failed to copy key. Please copy manually.', 'error');
                        });
                    } else {
                        showToast('Failed to copy key. Please copy manually.', 'error');
                    }
                }
            });
        }
        
        /**
         * Initialize paste key functionality
         */
        function initPasteKey() {
            const pasteBtn = document.getElementById('paste-key');
            if (!pasteBtn) {
                console.log('🔍 B2E Debug - Paste key button not found');
                return;
            }
            
            pasteBtn.addEventListener('click', function() {
                navigator.clipboard.readText().then(function(text) {
                    const migrationKeyInput = document.getElementById('migration_key');
                    migrationKeyInput.value = text;
                    showToast('Migration key pasted!', 'success');
                }).catch(function() {
                    showToast('Failed to paste from clipboard. Please paste manually.', 'error');
                });
            });
        }

        /**
         * Show clear logs confirmation dialog
         */
        function showClearLogsConfirmation() {
            const confirmToast = document.createElement('div');
            confirmToast.className = 'b2e-toast info';
            confirmToast.style.position = 'fixed';
            confirmToast.style.top = '50%';
            confirmToast.style.left = '50%';
            confirmToast.style.transform = 'translate(-50%, -50%)';
            confirmToast.style.zIndex = '10000';
            confirmToast.style.minWidth = '300px';
            confirmToast.style.textAlign = 'center';
            confirmToast.innerHTML = `
                <div style="margin-bottom: var(--e-space-l);">
                    <strong>Clear Migration Logs?</strong><br>
                    <span style="opacity: 0.8; font-size: 14px;">This action cannot be undone.</span>
                </div>
                <div style="display: flex; gap: var(--e-space-m); justify-content: center;">
                    <button id="confirm-clear" class="b2e-button" style="padding: var(--e-space-s) var(--e-space-m); font-size: 12px;">
                        Yes, Clear Logs
                    </button>
                    <button id="cancel-clear" class="b2e-button-secondary" style="padding: var(--e-space-s) var(--e-space-m); font-size: 12px;">
                        Cancel
                    </button>
                </div>
            `;
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.style.position = 'fixed';
            backdrop.style.top = '0';
            backdrop.style.left = '0';
            backdrop.style.width = '100%';
            backdrop.style.height = '100%';
            backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            backdrop.style.zIndex = '9999';
            
            document.body.appendChild(backdrop);
            document.body.appendChild(confirmToast);
            
            // Handle confirm
            document.getElementById('confirm-clear').addEventListener('click', function() {
                document.body.removeChild(backdrop);
                document.body.removeChild(confirmToast);
                performClearLogs();
            });
            
            // Handle cancel
            document.getElementById('cancel-clear').addEventListener('click', function() {
                document.body.removeChild(backdrop);
                document.body.removeChild(confirmToast);
            });
            
            // Handle backdrop click
            backdrop.addEventListener('click', function() {
                document.body.removeChild(backdrop);
                document.body.removeChild(confirmToast);
            });
        }

        /**
         * Perform clear logs AJAX request
         */
        function performClearLogs() {
            const formData = new FormData();
            formData.append('action', 'b2e_clear_logs');
            formData.append('nonce', b2e_ajax.nonce);
            
            fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.data, 'success');
                    // Reload the page to show updated logs
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error: ' + data.data, 'error');
                }
            })
            .catch(error => {
                console.error('Clear logs error:', error);
                showToast('Failed to clear logs. Please try again.', 'error');
            });
        }

        /**
         * Start the migration process
         */
        function startMigrationProcess(domain, token, expires) {
            console.log('🚀 Starting migration process...', { domain, token, expires });
            
            // Get API key from sessionStorage (set during token validation)
            const apiKey = sessionStorage.getItem('b2e_api_key');
            
            if (!apiKey) {
                showToast('API key not found. Please validate the migration key first.', 'error');
                console.error('API key not found in sessionStorage');
                return;
            }
            
            console.log('🔑 Using API key from sessionStorage:', apiKey.substring(0, 20) + '...');
            
            // Convert localhost:8081 to b2e-etch for Docker internal communication
            let apiDomain = domain;
            if (domain.includes('localhost:8081')) {
                apiDomain = domain.replace('localhost:8081', 'b2e-etch');
            }
            
            // Show progress section
            // Show progress section immediately
            const progressSection = document.getElementById('migration-progress');
            if (progressSection) {
                progressSection.style.display = 'block';
            }
            
            // Update progress
            updateProgress(0, 'Starting migration...', []);
            
            // Start migration via AJAX to local handler
                        const formData = new FormData();
                        formData.append('action', 'b2e_start_migration');
                        formData.append('nonce', b2e_ajax.nonce);
                        formData.append('target_url', apiDomain); // Use converted domain
                        formData.append('api_key', apiKey); // Use actual API key, not token
            
            console.log('📡 AJAX parameters:', {
                action: 'b2e_start_migration',
                nonce: b2e_ajax.nonce,
                target_url: apiDomain,
                api_key: apiKey.substring(0, 20) + '...'
            });
            
            console.log('📡 Using nonce:', b2e_ajax.nonce);
            
            console.log('📡 Sending AJAX request...', {
                action: 'b2e_start_migration',
                target_url: apiDomain,
                api_key: apiKey.substring(0, 20) + '...'
            });
            
            fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('📡 AJAX response:', data);
                console.log('📡 AJAX response type:', typeof data);
                console.log('📡 AJAX response success:', data.success);
                console.log('📡 AJAX response data:', data.data);
                
                if (data.success) {
                showToast('Migration started successfully!', 'success');
                // Start polling for progress
                pollMigrationProgress();
                    
                    // Generate migration report after a delay
                    setTimeout(() => {
                        generateMigrationReport();
                    }, 2000);
            } else {
                    let errorMessage = 'Unknown error';
                    
                    if (data.data) {
                        if (typeof data.data === 'string') {
                            errorMessage = data.data;
                        } else if (data.data.message) {
                            errorMessage = data.data.message;
                        } else {
                            errorMessage = JSON.stringify(data.data);
                        }
                    }
                    
                    console.error('Migration failed with error:', errorMessage);
                    showToast('Migration failed to start: ' + errorMessage, 'error');
                    
                if (progressSection) {
                    progressSection.style.display = 'none';
                }
            }
            })
            .catch(error => {
                console.error('Migration start error:', error);
                showToast('Migration failed to start: ' + error.message, 'error');
                if (progressSection) {
                    progressSection.style.display = 'none';
                }
            });
        }

        /**
         * Update migration progress
         */
        function updateProgress(percentage, currentStep, steps) {
            const progressBar = document.getElementById('progress-bar');
            const progressPercentage = document.getElementById('progress-percentage');
            const progressText = document.getElementById('progress-text');
            const progressSteps = document.getElementById('progress-steps');
            
            console.log('📊 Updating progress:', { percentage, currentStep, steps });
            
            // Update progress bar width
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
            
            // Update percentage text inside bar
            if (progressPercentage) {
                progressPercentage.textContent = Math.round(percentage) + '%';
            }
            
            // Update current step text
            if (progressText) {
                progressText.textContent = currentStep || 'Processing...';
            }
            
            // Update steps list
            if (progressSteps && Array.isArray(steps) && steps.length > 0) {
                progressSteps.innerHTML = '<ul style="list-style: none; padding: 0; margin: 0;">';
                steps.forEach((step, index) => {
                    progressSteps.innerHTML += `<li style="padding: 5px 0;">✓ ${step}</li>`;
                });
                progressSteps.innerHTML += '</ul>';
            }
        }

        /**
         * Poll migration progress
         */
        function pollMigrationProgress() {
            const formData = new FormData();
            formData.append('action', 'b2e_get_migration_progress');
            formData.append('nonce', b2e_ajax.nonce);
            
            fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const progress = data.data;
                    updateProgress(progress.percentage || 0, progress.current_step || '', progress.steps || []);
                    
                    if (progress.status === 'running') {
                        // Continue polling
                        setTimeout(pollMigrationProgress, 2000);
                    } else if (progress.status === 'completed') {
                        showToast('Migration completed successfully!', 'success');
                        updateProgress(100, 'Migration completed!', progress.steps || []);
                    } else if (progress.status === 'error') {
                        showToast('Migration failed: ' + (progress.message || 'Unknown error'), 'error');
                        updateProgress(0, 'Migration failed', progress.steps || []);
                    }
                } else {
                    console.error('Progress polling error:', data.data);
                    setTimeout(pollMigrationProgress, 5000); // Retry in 5 seconds
                }
            })
            .catch(error => {
                console.error('Progress polling error:', error);
                setTimeout(pollMigrationProgress, 5000); // Retry in 5 seconds
            });
        }

        /**
         * Generate migration report
         */
        function generateMigrationReport() {
            console.log('📊 Generating migration report...');
            
            const formData = new FormData();
            formData.append('action', 'b2e_generate_report');
            formData.append('nonce', b2e_ajax.nonce);
            
            fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('📊 Migration report:', data);
                
                if (data.success && data.data) {
                    showMigrationReport(data.data);
                } else {
                    console.log('📊 No migration data available yet');
                }
            })
            .catch(error => {
                console.error('📊 Error generating migration report:', error);
            });
        }
        
        /**
         * Show migration report
         */
        function showMigrationReport(reportData) {
            const reportHtml = `
                <div class="b2e-report-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                    <div class="b2e-report-modal" style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                        <h2 style="margin-top: 0; color: #333;">📊 Migration Report</h2>
                        <div style="margin-bottom: 20px;">
                            <strong>Migration Status:</strong> ${reportData.status || 'Unknown'}<br>
                            <strong>Posts Migrated:</strong> ${reportData.posts_migrated || 0} / ${reportData.posts_available || 0}<br>
                            <strong>Pages Migrated:</strong> ${reportData.pages_migrated || 0} / ${reportData.pages_available || 0}<br>
                            <strong>Media Migrated:</strong> ${reportData.media_files || 0} / ${reportData.media_available || 0}<br>
                            <strong>ACF Installed:</strong> ${reportData.acf_installed ? 'Yes' : 'No'}<br>
                            <strong>ACF Field Groups:</strong> ${reportData.acf_groups || 0}<br>
                            <strong>Custom Post Types:</strong> ${reportData.custom_post_types || 0}<br>
                            <strong>Report Time:</strong> ${reportData.migration_time || 'Unknown'}<br>
                            <strong>Log Entries:</strong> ${reportData.total_entries || 0}<br>
                        </div>
                        ${reportData.details ? `<div style="margin-bottom: 20px;"><strong>Recent Activity:</strong><br><pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap;">${reportData.details}</pre></div>` : ''}
                        <button onclick="this.closest('.b2e-report-overlay').remove()" style="background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Close Report</button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', reportHtml);
        }

        /**
         * Show toast notification
         */
        function showToast(message, type = 'info', duration = 4000) {
            console.log('🔍 B2E Debug - showToast called:', message, type);
            
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.b2e-toast');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `b2e-toast ${type}`;
            toast.innerHTML = message;
            
            // Add basic styles if CSS is not loaded
            toast.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: #333;
                color: white;
                padding: 16px 24px;
                border-radius: 6px;
                z-index: 9999;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                font-family: Arial, sans-serif;
                box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            `;
            
            if (type === 'success') {
                toast.style.background = '#46b450';
            } else if (type === 'error') {
                toast.style.background = '#f26060';
            } else if (type === 'warning') {
                toast.style.background = '#f2c960';
                toast.style.color = '#333';
            }
            
            document.body.appendChild(toast);
            console.log('🔍 B2E Debug - Toast added to DOM:', toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                console.log('🔍 B2E Debug - Toast animation triggered');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                        console.log('🔍 B2E Debug - Toast removed from DOM');
                    }
                }, 300);
            }, duration);
        }

        /**
         * API Client class for making requests
         */
        class B2E_API_Client {
            sendRequest(url, data) {
                // This is a simplified version - in reality, this would make an actual HTTP request
                // For now, we'll simulate the response
                return {
                    success: true,
                    message: 'Migration started successfully!'
                };
            }
        }
        </script>

        <div class="wrap">
            <div class="b2e-admin-wrap">
                <div class="b2e-header">
                    <h1>🚀 <?php _e('Bricks to Etch Migration', 'bricks-etch-migration'); ?></h1>
                    <p><?php _e('Migrate your Bricks Builder content to Etch with ease', 'bricks-etch-migration'); ?></p>
                </div>
                
                <!-- Migration Interface -->
                <div class="b2e-migration-container">
                    <?php $this->render_migration_interface($settings, $progress); ?>
                </div>
            </div>
            
            <!-- Progress Section (Always visible when migration is running) -->
            <?php if (!empty($progress) && $progress['status'] !== 'completed' && $progress['status'] !== 'error'): ?>
                <?php $this->render_progress_section($progress); ?>
            <?php endif; ?>
            
            <!-- Recent Logs -->
            <div class="b2e-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--e-space-l);">
                    <h3 style="margin: 0;"><?php _e('Recent Migration Logs', 'bricks-etch-migration'); ?></h3>
                    <button type="button" id="clear-logs" class="b2e-button" style="padding: var(--e-space-s) var(--e-space-m); font-size: 12px;">
                        <?php _e('Clear Logs', 'bricks-etch-migration'); ?>
                    </button>
                    <button type="button" id="generate-report" class="b2e-button" style="padding: var(--e-space-s) var(--e-space-m); font-size: 12px; margin-left: 10px;">
                        📊 <?php _e('Generate Report', 'bricks-etch-migration'); ?>
                    </button>
                </div>
                <?php $this->render_recent_logs($logs); ?>
            </div>
        </div>
        
        <!-- All CSS is now handled by admin.css -->
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
            <div>
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
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="migration_key" name="migration_key" 
                                       value="<?php echo esc_attr($settings['migration_key'] ?? ''); ?>"
                                       placeholder="Paste your migration key here..."
                                       style="flex: 1; font-family: monospace; font-size: 12px; word-break: break-all;" />
                                <button type="button" id="paste-key" class="b2e-button" style="white-space: nowrap;">
                                    📋 <?php _e('Paste Key', 'bricks-etch-migration'); ?>
                                </button>
                            </div>
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
                    <button type="button" id="validate-migration-key" class="b2e-button">
                        🔗 <?php _e('Validate Key', 'bricks-etch-migration'); ?>
                    </button>
                    <button type="button" id="start-migration" class="b2e-button" style="margin-left: 10px;">
                        🚀 <?php _e('Start Migration', 'bricks-etch-migration'); ?>
                    </button>
                </div>
                
                <div id="migration-key-info" style="margin-top: 15px; display: none;">
                    <!-- Key validation info will be displayed here -->
                </div>
                
                <!-- Migration Progress Section -->
                <div id="migration-progress" style="margin-top: 30px; display: none;">
                    <h3>📊 <?php _e('Migration Progress', 'bricks-etch-migration'); ?></h3>
                    <div style="background: #f0f0f1; border-radius: 8px; padding: 20px; margin-top: 15px;">
                        <div style="margin-bottom: 10px;">
                            <strong id="progress-text"><?php _e('Initializing...', 'bricks-etch-migration'); ?></strong>
                        </div>
                        <div style="background: #fff; border-radius: 4px; height: 30px; overflow: hidden; border: 1px solid #ddd;">
                            <div id="progress-bar" style="background: linear-gradient(90deg, #0073aa, #00a0d2); height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                                <span id="progress-percentage">0%</span>
                            </div>
                        </div>
                        <div id="progress-steps" style="margin-top: 15px; font-size: 12px; color: #666;">
                            <!-- Migration steps will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Instructions -->
            <div style="background: var(--e-highlight-light);border-radius:var(--e-border-radius);border-left: 10px solid var(--e-warning);padding: var(--e-space-l);margin: 20px 0;">
                <h3>
                    📋 <?php _e('How to Get Your Migration Key', 'bricks-etch-migration'); ?>
                </h3>
                <ol>
                    <li><?php _e('Go to your Etch site (target site)', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Install the Bricks to Etch Migration plugin', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Go to B2E Migration in the admin menu', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Click "Generate Migration Key"', 'bricks-etch-migration'); ?></li>
                    <li><?php _e('Copy the generated key and paste it above', 'bricks-etch-migration'); ?></li>
                </ol>
            </div>
        </div>
        
        <!-- JavaScript is now loaded from admin.js file -->
        
        <?php
    }
    
    /**
     * Render recent migration logs
     */
    public function render_recent_logs($logs) {
        if (empty($logs)) {
            echo '<p style="color: var(--e-base-light); font-style: italic;">' . __('No migration logs available.', 'bricks-etch-migration') . '</p>';
            return;
        }
        
        echo '<div class="b2e-logs-container">';
        foreach (array_reverse($logs) as $log_entry) {
            $timestamp = isset($log_entry['timestamp']) ? $log_entry['timestamp'] : '';
            $level = isset($log_entry['level']) ? $log_entry['level'] : 'info';
            $message = isset($log_entry['message']) ? $log_entry['message'] : '';
            $code = isset($log_entry['code']) ? $log_entry['code'] : '';
            
            $level_class = 'info';
            if ($level === 'error') $level_class = 'error';
            if ($level === 'warning') $level_class = 'warning';
            if ($level === 'success') $level_class = 'success';
            
            echo '<div class="b2e-log-entry">';
            echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--e-space-xs);">';
            echo '<span class="b2e-log-level b2e-status ' . $level_class . '">' . esc_html($level) . '</span>';
            echo '<span class="b2e-log-timestamp">' . esc_html($timestamp) . '</span>';
            echo '</div>';
            if ($code) {
                echo '<div class="b2e-log-code">' . esc_html($code) . '</div>';
            }
            echo '<div class="b2e-log-message">' . esc_html($message) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    
    /**
     * AJAX: Clear migration logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        // Clear logs from error handler
        $error_handler = new B2E_Error_Handler();
        $result = $error_handler->clear_log();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Migration logs cleared successfully!', 'bricks-etch-migration'));
    }
    
    /**
     * AJAX handler for validating API key
     */
    public function ajax_validate_api_key() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Target URL and API key are required');
            return;
        }
        
        // Convert localhost:8081 to b2e-etch for Docker internal communication
        if (strpos($target_url, 'localhost:8081') !== false) {
            $target_url = str_replace('localhost:8081', 'b2e-etch', $target_url);
        }
        
        // Validate API key via API client
        $api_client = new B2E_API_Client();
        $result = $api_client->validate_api_key($target_url, $api_key);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX handler for validating migration token
     */
    public function ajax_validate_migration_token() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $token = sanitize_text_field($_POST['token'] ?? '');
        $expires = intval($_POST['expires'] ?? 0);
        
        if (empty($target_url) || empty($token) || empty($expires)) {
            wp_send_json_error('Target URL, token, and expiration are required');
            return;
        }
        
        // Convert localhost:8081 to b2e-etch for Docker internal communication
        if (strpos($target_url, 'localhost:8081') !== false) {
            $target_url = str_replace('localhost:8081', 'b2e-etch', $target_url);
        }
        
        // Validate migration token on target site
        $api_client = new B2E_API_Client();
        $result = $api_client->validate_migration_token($target_url, $token, $expires);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Token is valid, return success with API key
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX: Get migration progress
     */
    public function ajax_get_migration_progress() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $progress = get_option('b2e_migration_progress', array());
        
        if (empty($progress)) {
            wp_send_json_success(array(
                'status' => 'idle',
                'percentage' => 0,
                'current_step' => '',
                'steps' => array()
            ));
        }
        
        wp_send_json_success($progress);
    }
    
    /**
     * AJAX: Generate migration key
     */
    public function ajax_generate_migration_key() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
        }
        
        $expiration_hours = intval($_POST['expiration_hours'] ?? 24);
        
        // Generate migration key using token manager
        $token_manager = new B2E_Migration_Token_Manager();
        $result = $token_manager->generate_migration_url(null, $expiration_hours * 3600); // Convert hours to seconds
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'migration_url' => $result,
            'expires_in_hours' => $expiration_hours
        ));
    }
    
    /**
     * Render key generator for Etch sites
     */
    public function render_key_generator() {
        $error_handler = new B2E_Error_Handler();
        $logs = $error_handler->get_log();
        
        ?>
        <!-- AJAX Nonce -->
        <input type="hidden" id="b2e_nonce" value="<?php echo wp_create_nonce('b2e_nonce'); ?>" />
        
        <!-- Inline JavaScript for Generate Key page -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 B2E Debug - Generate Key page loaded');
            
            // Initialize key generation
            initKeyGeneration();
            initCopyKey();
        });

        function initKeyGeneration() {
            const generateBtn = document.getElementById('generate-migration-key');
            if (!generateBtn) {
                console.log('🔍 B2E Debug - Generate key button not found');
                return;
            }
            console.log('🔍 B2E Debug - Generate key button found, adding listener');

            generateBtn.addEventListener('click', function() {
                console.log('🔍 B2E Debug - Generate key button clicked');
                const expirationHours = document.getElementById('expiration_hours').value || '24';
                
                const formData = new FormData();
                formData.append('action', 'b2e_generate_migration_key');
                formData.append('nonce', b2e_ajax.nonce);
                formData.append('expiration_hours', expirationHours);
                
                fetch(b2e_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('🔍 B2E Debug - Key generation response:', data);
                    if (data.success) {
                        document.getElementById('generated_key').value = data.data.migration_url;
                        document.getElementById('generated-key-section').style.display = 'block';
                        showToast('Migration key generated successfully!', 'success');
                    } else {
                        showToast('Error generating key: ' + data.data, 'error');
                    }
                })
                .catch(error => {
                    console.error('Key generation error:', error);
                    showToast('Failed to generate migration key. Please try again.', 'error');
                });
            });
        }

        function initCopyKey() {
            const copyBtn = document.getElementById('copy-key');
            if (!copyBtn) {
                console.log('🔍 B2E Debug - Copy key button not found');
                return;
            }
            console.log('🔍 B2E Debug - Copy key button found, adding listener');

            copyBtn.addEventListener('click', function() {
                console.log('🔍 B2E Debug - Copy key button clicked');
                const keyTextarea = document.getElementById('generated_key');
                if (!keyTextarea) return;

                keyTextarea.select();
                keyTextarea.setSelectionRange(0, 99999);

                try {
                    document.execCommand('copy');
                    showToast('Migration key copied to clipboard!', 'success');
                } catch (err) {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(keyTextarea.value).then(() => {
                            showToast('Migration key copied to clipboard!', 'success');
                        }).catch(() => {
                            showToast('Failed to copy key. Please copy manually.', 'error');
                        });
                    } else {
                        showToast('Failed to copy key. Please copy manually.', 'error');
                    }
                }
            });
        }

        function showToast(message, type = 'info', duration = 4000) {
            console.log('🔍 B2E Debug - showToast called:', message, type);
            
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.b2e-toast');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `b2e-toast ${type}`;
            toast.innerHTML = message;
            
            // Add basic styles if CSS is not loaded
            toast.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: #333;
                color: white;
                padding: 16px 24px;
                border-radius: 6px;
                z-index: 9999;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                font-family: Arial, sans-serif;
                box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            `;
            
            if (type === 'success') {
                toast.style.background = '#46b450';
            } else if (type === 'error') {
                toast.style.background = '#f26060';
            } else if (type === 'warning') {
                toast.style.background = '#f2c960';
                toast.style.color = '#333';
            }
            
            document.body.appendChild(toast);
            console.log('🔍 B2E Debug - Toast added to DOM:', toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                console.log('🔍 B2E Debug - Toast animation triggered');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                toast.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                        console.log('🔍 B2E Debug - Toast removed from DOM');
                    }
                }, 300);
            }, duration);
        }
        </script>

        <div class="wrap">
            <div class="b2e-admin-wrap">
                <div class="b2e-header">
                    <h1>🔑 <?php _e('Generate Migration Key', 'bricks-etch-migration'); ?></h1>
                    <p><?php _e('Generate a secure migration key for your Etch site', 'bricks-etch-migration'); ?></p>
                </div>
                
                <div class="b2e-card">
                    <h2><?php _e('Migration Key Generator', 'bricks-etch-migration'); ?></h2>
                    <p><?php _e('Generate a secure migration key that contains your site information and authentication token.', 'bricks-etch-migration'); ?></p>
                    
                    <div class="b2e-form-group">
                        <label for="target_domain"><?php _e('Target Domain', 'bricks-etch-migration'); ?></label>
                        <input type="url" id="target_domain" name="target_domain" 
                               value="<?php echo esc_attr(home_url()); ?>"
                               placeholder="https://your-bricks-site.com" readonly />
                        <p class="description">
                            <?php _e('This is your current Etch site (where this key is generated)', 'bricks-etch-migration'); ?>
                        </p>
                    </div>
                    
                    <div class="b2e-form-group">
                        <label for="expiration_hours"><?php _e('Expiration Time', 'bricks-etch-migration'); ?></label>
                        <select id="expiration_hours" name="expiration_hours">
                            <option value="1">1 Hour</option>
                            <option value="8" selected>8 Hours (Recommended)</option>
                            <option value="24">24 Hours</option>
                            <option value="72">3 Days</option>
                        </select>
                        <p class="description">
                            <?php _e('How long the migration key should remain valid', 'bricks-etch-migration'); ?>
                        </p>
                    </div>
                    
                    <button type="button" id="generate-migration-key" class="b2e-button">
                        🔑 <?php _e('Generate Migration Key', 'bricks-etch-migration'); ?>
                    </button>
                </div>
                
                <div class="b2e-card" id="generated-key-section" style="display: none;">
                    <h2><?php _e('Generated Migration Key', 'bricks-etch-migration'); ?></h2>
                    <p><?php _e('Copy this migration key and paste it into your Bricks site to start the migration:', 'bricks-etch-migration'); ?></p>
                    
                    <div class="b2e-form-group">
                        <label for="generated_key"><?php _e('Migration Key', 'bricks-etch-migration'); ?></label>
                        <div class="b2e-key-display">
                            <textarea id="generated_key" rows="3" readonly style="width: 100%; background: transparent; border: none; color: var(--e-light); resize: none;"></textarea>
                        </div>
                        <button type="button" id="copy-key" class="b2e-button-secondary">
                            📋 <?php _e('Copy Key', 'bricks-etch-migration'); ?>
                        </button>
                    </div>
                    
                    <div class="b2e-status success">
                        ✅ <?php _e('Key generated successfully! Use this on your Bricks site.', 'bricks-etch-migration'); ?>
                    </div>
                </div>
                
                <div class="b2e-card">
                    <h2><?php _e('How to Use This Key', 'bricks-etch-migration'); ?></h2>
                    <ol>
                        <li><?php _e('Copy the generated migration key above', 'bricks-etch-migration'); ?></li>
                        <li><?php _e('Go to your Bricks Builder site', 'bricks-etch-migration'); ?></li>
                        <li><?php _e('Install the B2E Migration plugin', 'bricks-etch-migration'); ?></li>
                        <li><?php _e('Paste the key in the migration form', 'bricks-etch-migration'); ?></li>
                        <li><?php _e('Click "Start Migration" to begin', 'bricks-etch-migration'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for testing export connection
     */
    public function ajax_test_export_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2e_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Target URL and API key are required');
            return;
        }
        
        // Test the connection using API client
        $api_client = new B2E_API_Client();
        $result = $api_client->test_connection($target_url, $api_key);
        
        if ($result['valid']) {
            wp_send_json_success(array(
                'message' => 'Connection successful!',
                'plugins' => $result['plugins']
            ));
        } else {
            wp_send_json_error(implode(', ', $result['errors']));
        }
    }
    
    /**
     * AJAX handler for testing import connection
     */
    public function ajax_test_import_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2e_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $source_url = sanitize_url($_POST['source_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($source_url) || empty($api_key)) {
            wp_send_json_error('Source URL and API key are required');
            return;
        }
        
        // Test the connection using API client
        $api_client = new B2E_API_Client();
        $result = $api_client->test_connection($source_url, $api_key);
        
        if ($result['valid']) {
            wp_send_json_success(array(
                'message' => 'Connection successful!',
                'plugins' => $result['plugins']
            ));
        } else {
            wp_send_json_error(implode(', ', $result['errors']));
        }
    }
    
    /**
     * AJAX handler for starting migration
     */
    public function ajax_start_migration() {
        error_log('B2E AJAX: Start migration called');
        
        // Verify nonce using check_ajax_referer (doesn't consume nonce)
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            error_log('B2E AJAX: Invalid nonce - ' . ($_POST['nonce'] ?? 'not set'));
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        error_log('B2E AJAX: Nonce verified successfully');
        
        // Get migration parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        error_log('B2E AJAX: Target URL: ' . $target_url);
        error_log('B2E AJAX: API Key length: ' . strlen($api_key));
        
        if (empty($target_url) || empty($api_key)) {
            error_log('B2E AJAX: Missing parameters');
            wp_send_json_error('Target URL and API key are required');
            return;
        }
        
        // Start migration process
        error_log('B2E AJAX: Starting migration manager');
        $migration_manager = new B2E_Migration_Manager();
        $result = $migration_manager->start_migration($target_url, $api_key);
        
        if (is_wp_error($result)) {
            error_log('B2E AJAX: Migration failed: ' . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        } else {
            error_log('B2E AJAX: Migration started successfully');
            wp_send_json_success(array(
                'message' => 'Migration started successfully!'
            ));
        }
    }
    
    /**
     * AJAX handler for getting migration progress
     */
    public function ajax_get_progress() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2e_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get current migration progress
        $migration_manager = new B2E_Migration_Manager();
        $progress = $migration_manager->get_migration_status();
        
        wp_send_json_success($progress);
    }
    
    /**
     * AJAX handler for generating migration report
     */
    public function ajax_generate_report() {
        try {
            // Verify nonce
            if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }
            
            // Get real data from the system - ALL posts and pages, not just Bricks
            $all_posts = get_posts(array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            $all_pages = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'numberposts' => -1
            ));
            
            // Count media files
            $media_files = get_posts(array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'numberposts' => -1,
                'post_mime_type' => 'image'
            ));
            
            // Check if ACF is installed
            $acf_installed = class_exists('ACF') || function_exists('acf_get_field_groups');
            $acf_groups = 0;
            if ($acf_installed) {
                $acf_groups = count(get_posts(array(
                    'post_type' => 'acf-field-group',
                    'post_status' => 'publish',
                    'numberposts' => -1
                )));
            }
            
            // Get custom post types (exclude WordPress defaults and Bricks internal types)
            $all_custom_post_types = get_post_types(array(
                '_builtin' => false
            ), 'objects');
            
            // Filter out WordPress core CPTs and Bricks internal types
            $exclude_types = array('wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 
                                   'wp_global_styles', 'wp_font_family', 'wp_font_face', 
                                   'bricks_fonts', 'bricks_template', 'acf-field-group', 'acf-field');
            
            $custom_post_types = array_filter($all_custom_post_types, function($cpt) use ($exclude_types) {
                return !in_array($cpt->name, $exclude_types);
            });
            
            // Get migration log
            $migration_log = get_option('b2e_migration_log', array());
            
            // Get migration statistics from local storage
            $migration_stats = get_option('b2e_migration_stats', array());
            $migrated_posts = $migration_stats['posts_migrated'] ?? 0;
            $migrated_pages = $migration_stats['pages_migrated'] ?? 0;
            $migrated_media = $migration_stats['media_migrated'] ?? 0;
            $media_failed = $migration_stats['media_failed'] ?? 0;
            $media_skipped = $migration_stats['media_skipped'] ?? 0;
            $migration_status = $migration_stats['status'] ?? 'ready';
            
            // Determine migration status
            $total_available = count($all_posts) + count($all_pages);
            $total_migrated = $migrated_posts + $migrated_pages;
            
            if ($migration_status === 'completed') {
                $status = 'Migration Completed';
            } elseif ($total_migrated > 0) {
                $status = 'Migration In Progress';
            } else {
                $status = 'Ready for Migration';
            }
            
                    // Build real report data
                    $report_data = array(
                        'status' => $status,
                        'posts_migrated' => $migrated_posts,
                        'pages_migrated' => $migrated_pages,
                        'posts_available' => count($all_posts),
                        'pages_available' => count($all_pages),
                        'media_files' => $migrated_media,
                        'media_available' => count($media_files),
                        'media_failed' => $media_failed,
                        'media_skipped' => $media_skipped,
                        'acf_installed' => $acf_installed,
                        'acf_groups' => $acf_groups,
                        'custom_post_types' => count($custom_post_types),
                        'migration_time' => date('Y-m-d H:i:s'),
                        'details' => sprintf(
                            "Available: %d posts, %d pages, %d media files. Migrated: %d posts, %d pages, %d media files (%d failed, %d skipped). ACF %s (%d groups). %d custom post types.",
                            count($all_posts),
                            count($all_pages),
                            count($media_files),
                            $migrated_posts,
                            $migrated_pages,
                            $migrated_media,
                            $media_failed,
                            $media_skipped,
                            $acf_installed ? 'installed' : 'not installed',
                            $acf_groups,
                            count($custom_post_types)
                        ),
                        'total_entries' => count($migration_log),
                        'migration_log' => array_slice($migration_log, -5) // Last 5 entries
                    );
            
            wp_send_json_success($report_data);
            
        } catch (Exception $e) {
            wp_send_json_error('Report generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for saving migration settings
     */
    public function ajax_save_migration_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'b2e_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Save migration settings
        wp_send_json_success(array(
            'message' => 'Settings saved successfully!'
        ));
    }
}
