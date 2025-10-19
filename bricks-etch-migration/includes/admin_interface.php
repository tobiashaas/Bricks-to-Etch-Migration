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
        add_action('wp_ajax_b2e_migrate_batch', array($this, 'ajax_migrate_batch'));
        add_action('wp_ajax_b2e_get_bricks_posts', array($this, 'ajax_get_bricks_posts'));
        add_action('wp_ajax_b2e_migrate_css', array($this, 'ajax_migrate_css'));
        add_action('wp_ajax_b2e_migrate_media', array($this, 'ajax_migrate_media'));
        add_action('wp_ajax_b2e_migrate_cpts', array($this, 'ajax_migrate_cpts'));
        add_action('wp_ajax_b2e_get_bricks_cpts', array($this, 'ajax_get_bricks_cpts'));
        add_action('wp_ajax_b2e_get_etch_cpts', array($this, 'ajax_get_etch_cpts'));
        add_action('wp_ajax_b2e_cleanup_etch', array($this, 'ajax_cleanup_etch'));
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
                            
                            // Load CPT mapping options
                            loadCPTMappingOptions(domain, data.data.api_key);
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
         * Start the migration process (BATCH VERSION)
         */
        async function startMigrationProcess(domain, token, expires) {
            console.log('🚀 Starting BATCH migration process...', { domain, token, expires });
            
            // Get API key from sessionStorage (set during token validation)
            const apiKey = sessionStorage.getItem('b2e_api_key');
            
            if (!apiKey) {
                showToast('API key not found. Please validate the migration key first.', 'error');
                console.error('API key not found in sessionStorage');
                return;
            }
            
            console.log('🔑 Using API key from sessionStorage:', apiKey.substring(0, 20) + '...');
            
            // Note: Localhost conversion is handled server-side in convert_localhost_for_docker()
            // No need to convert here - just use the domain as-is
            let apiDomain = domain;
            
            // Show progress section
            const progressSection = document.getElementById('migration-progress');
            if (progressSection) {
                progressSection.style.display = 'block';
            }
            
            // Update progress
            updateProgress(0, 'Getting list of posts...', []);
            
            // Step 1: Get list of all Bricks posts
            updateProgress(0, '🔍 Scanning for Bricks content...', ['Starting migration process...']);
            const posts = await getBricksPosts();
            
            if (!posts || posts.length === 0) {
                showToast('No Bricks posts found to migrate', 'error');
                updateProgress(0, '❌ No content found', ['No Bricks posts or pages found']);
                return;
            }
            
            console.log(`📋 Found ${posts.length} posts to migrate`);
            const initialSteps = [`Found ${posts.length} items to migrate (${posts.filter(p => p.type === 'page').length} pages, ${posts.filter(p => p.type === 'post').length} posts)`];
            updateProgress(3, `📋 Found ${posts.length} items to migrate`, initialSteps);
            
            // Step 2: Migrate Custom Post Types first (so bricks_template exists in Etch)
            updateProgress(4, '📦 Migrating Custom Post Types...', [...initialSteps, 'Registering Bricks templates and other CPTs...']);
            let cptSteps = [...initialSteps];
            try {
                await migrateCPTs(apiDomain, apiKey);
                cptSteps.push('✅ Custom Post Types registered successfully');
                updateProgress(5, '✅ CPT migration complete', cptSteps);
            } catch (error) {
                console.error('❌ CPT migration error:', error);
                cptSteps.push('⚠️ CPT migration failed: ' + error.message);
                updateProgress(5, '⚠️ CPT migration failed (continuing...)', cptSteps);
            }
            
            // Step 3: Migrate CSS
            updateProgress(6, '🎨 Migrating CSS styles...', [...cptSteps, 'Converting Bricks classes to Etch styles...']);
            let cssSteps = [...cptSteps];
            try {
                await migrateCSSStyles(apiDomain, apiKey);
                cssSteps.push('✅ CSS styles migrated successfully');
                updateProgress(8, '✅ CSS migration complete', cssSteps);
            } catch (error) {
                console.error('❌ CSS migration error:', error);
                cssSteps.push('⚠️ CSS migration failed: ' + error.message);
                updateProgress(8, '⚠️ CSS migration failed (continuing...)', cssSteps);
            }
            
            // Step 2.5: Migrate Media
            updateProgress(8, '📸 Migrating media files...', [...cssSteps, 'Transferring images and attachments...']);
            try {
                await migrateMedia(apiDomain, apiKey);
                cssSteps.push('✅ Media files migrated successfully');
                updateProgress(10, '✅ Media migration complete', cssSteps);
            } catch (error) {
                cssSteps.push('⚠️ Media migration had errors: ' + error.message);
                updateProgress(10, '⚠️ Media migration completed with errors', cssSteps);
            }
            
            // Step 3: Migrate posts one by one
            const completedSteps = [...cssSteps];
            let successCount = 0;
            let errorCount = 0;
            
            for (let i = 0; i < posts.length; i++) {
                const post = posts[i];
                const progress = 10 + ((i / posts.length) * 85); // 10-95%
                const postTypeIcon = post.type === 'page' ? '📄' : '📝';
                
                updateProgress(progress, `${postTypeIcon} Migrating: ${post.title} (${i + 1}/${posts.length})...`, completedSteps);
                
                try {
                    const startTime = Date.now();
                    await migratePost(post.id, apiDomain, apiKey);
                    const duration = ((Date.now() - startTime) / 1000).toFixed(2);
                    
                    successCount++;
                    completedSteps.push(`✅ ${post.title} (${post.type}) - ${duration}s`);
                    console.log(`✅ Migrated: ${post.title} in ${duration}s`);
                } catch (error) {
                    errorCount++;
                    completedSteps.push(`❌ ${post.title} (${post.type}): ${error.message}`);
                    console.error(`❌ Failed: ${post.title}`, error);
                }
                
                // Update progress with completed step
                const newProgress = 10 + (((i + 1) / posts.length) * 85);
                const statusText = `${successCount} successful, ${errorCount} failed`;
                updateProgress(newProgress, `📊 Progress: ${i + 1}/${posts.length} (${statusText})`, completedSteps);
                
                // Small delay to show progress
                await new Promise(resolve => setTimeout(resolve, 300));
            }
            
            // Step 4: Complete
            const finalMessage = errorCount === 0 
                ? `🎉 Migration complete! All ${successCount} items migrated successfully!`
                : `⚠️ Migration complete with ${errorCount} error(s). ${successCount} items migrated successfully.`;
            
            completedSteps.push('');
            completedSteps.push(`📊 Final Summary:`);
            completedSteps.push(`   ✅ Success: ${successCount}`);
            completedSteps.push(`   ❌ Failed: ${errorCount}`);
            completedSteps.push(`   📦 Total: ${posts.length}`);
            
            updateProgress(100, finalMessage, completedSteps);
            showToast(finalMessage, errorCount === 0 ? 'success' : 'warning');
            
            // Generate report
            setTimeout(() => {
                generateMigrationReport();
            }, 2000);
        }
        
        /**
         * Get list of Bricks posts
         */
        async function getBricksPosts() {
            const formData = new FormData();
            formData.append('action', 'b2e_get_bricks_posts');
            formData.append('nonce', b2e_ajax.nonce);
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data.data.posts;
            } else {
                throw new Error(data.data || 'Failed to get posts');
            }
        }
        
        /**
         * Migrate CSS styles
         */
        async function migrateCSSStyles(apiDomain, apiKey) {
            const formData = new FormData();
            formData.append('action', 'b2e_migrate_css');
            formData.append('nonce', b2e_ajax.nonce);
            formData.append('target_url', apiDomain);
            formData.append('api_key', apiKey);
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Failed to migrate CSS');
            }
        }
        
        /**
         * Migrate media files
         */
        async function migrateMedia(apiDomain, apiKey) {
            const formData = new FormData();
            formData.append('action', 'b2e_migrate_media');
            formData.append('nonce', b2e_ajax.nonce);
            formData.append('target_url', apiDomain);
            formData.append('api_key', apiKey);
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Failed to migrate media');
            }
            
            return data.data;
        }
        
        /**
         * Migrate Custom Post Types
         */
        async function migrateCPTs(apiDomain, apiKey) {
            const formData = new FormData();
            formData.append('action', 'b2e_migrate_cpts');
            formData.append('nonce', b2e_ajax.nonce);
            formData.append('target_url', apiDomain);
            formData.append('api_key', apiKey);
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Failed to migrate CPTs');
            }
            
            return data.data;
        }
        
        /**
         * Load CPT mapping options
         */
        async function loadCPTMappingOptions(domain, apiKey) {
            try {
                // Get Bricks CPTs
                const bricksCPTs = await getBricksCPTs();
                
                // Get Etch CPTs
                const etchCPTs = await getEtchCPTs(domain, apiKey);
                
                // Build mapping UI
                const mappingList = document.getElementById('cpt-mapping-list');
                if (!mappingList) return;
                
                if (bricksCPTs.length === 0) {
                    mappingList.innerHTML = '<p style="color: var(--e-base-light);">No custom post types found in Bricks.</p>';
                    return;
                }
                
                let html = '<table style="width: 100%; border-collapse: collapse;">';
                html += '<thead><tr style="background: var(--e-base-dark); border-bottom: 2px solid var(--e-border-color);">';
                html += '<th style="padding: 10px; text-align: left;">Bricks Post Type</th>';
                html += '<th style="padding: 10px; text-align: left;">→ Map to Etch Post Type</th>';
                html += '<th style="padding: 10px; text-align: left;">Count</th>';
                html += '</tr></thead><tbody>';
                
                bricksCPTs.forEach(cpt => {
                    html += '<tr style="border-bottom: 1px solid var(--e-border-color);">';
                    html += `<td style="padding: 10px;"><strong>${cpt.label}</strong><br><code style="font-size: 0.9em; color: var(--e-base-light);">${cpt.name}</code></td>`;
                    html += '<td style="padding: 10px;">';
                    html += `<select name="cpt_mapping[${cpt.name}]" id="cpt_mapping_${cpt.name}" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid var(--e-border-color);">`;
                    
                    // Default option: map to page
                    html += `<option value="page" ${cpt.name === 'bricks_template' ? 'selected' : ''}>Page</option>`;
                    html += `<option value="post">Post</option>`;
                    
                    // Add Etch CPTs as options
                    etchCPTs.forEach(etchCpt => {
                        const selected = cpt.name === etchCpt.name ? 'selected' : '';
                        html += `<option value="${etchCpt.name}" ${selected}>${etchCpt.label}</option>`;
                    });
                    
                    html += '</select>';
                    html += '</td>';
                    html += `<td style="padding: 10px; color: var(--e-base-light);">${cpt.count} items</td>`;
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                mappingList.innerHTML = html;
                
            } catch (error) {
                console.error('Failed to load CPT mapping options:', error);
                const mappingList = document.getElementById('cpt-mapping-list');
                if (mappingList) {
                    mappingList.innerHTML = '<p style="color: var(--e-warning);">⚠️ Failed to load post type options</p>';
                }
            }
        }
        
        /**
         * Get Bricks CPTs
         */
        async function getBricksCPTs() {
            const formData = new FormData();
            formData.append('action', 'b2e_get_bricks_cpts');
            formData.append('nonce', b2e_ajax.nonce);
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.data : [];
        }
        
        /**
         * Get Etch CPTs
         */
        async function getEtchCPTs(domain, apiKey) {
            const formData = new FormData();
            formData.append('action', 'b2e_get_etch_cpts');
            formData.append('nonce', b2e_ajax.nonce);
            formData.append('target_url', domain);
            formData.append('api_key', apiKey);
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.data : [];
        }
        
        /**
         * Get CPT mapping from UI
         */
        function getCPTMapping() {
            const mapping = {};
            const selects = document.querySelectorAll('[name^="cpt_mapping["]');
            
            selects.forEach(select => {
                const match = select.name.match(/cpt_mapping\[([^\]]+)\]/);
                if (match) {
                    mapping[match[1]] = select.value;
                }
            });
            
            return mapping;
        }
        
        /**
         * Migrate single post
         */
        async function migratePost(postId, apiDomain, apiKey) {
            const formData = new FormData();
            formData.append('action', 'b2e_migrate_batch');
            formData.append('nonce', b2e_ajax.nonce);
            formData.append('post_id', postId);
            formData.append('target_url', apiDomain);
            formData.append('api_key', apiKey);
            
            // Add CPT mapping
            const cptMapping = getCPTMapping();
            formData.append('cpt_mapping', JSON.stringify(cptMapping));
            
            const response = await fetch(b2e_ajax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Failed to migrate post');
            }
            
            return data.data;
        }

        /**
         * Update migration progress (IMPROVED VERSION)
         */
        function updateProgress(percentage, currentStep, steps) {
            const progressBar = document.getElementById('progress-bar');
            const progressPercentage = document.getElementById('progress-percentage');
            const progressText = document.getElementById('progress-text');
            const progressSteps = document.getElementById('progress-steps');
            
            console.log('📊 Updating progress:', { percentage, currentStep, steps });
            
            // Update progress bar width with smooth transition
            if (progressBar) {
                progressBar.style.width = percentage + '%';
                progressBar.style.transition = 'width 0.3s ease-in-out';
                
                // Color coding based on progress
                if (percentage < 30) {
                    progressBar.style.background = 'var(--e-danger)'; // Orange - Starting
                } else if (percentage < 70) {
                    progressBar.style.background = 'cyan'; // Blue - In Progress
                } else if (percentage < 100) {
                    progressBar.style.background = 'var(--e-primary)'; // Green - Almost Done
                } else {
                    progressBar.style.background = 'lightgreen'; // Dark Green - Complete
                }
            }
            
            // Update percentage text inside bar
            if (progressPercentage) {
                progressPercentage.textContent = Math.round(percentage) + '%';
            }
            
            // Update current step text with animation
            if (progressText) {
                progressText.style.transition = 'opacity 0.2s';
                progressText.style.opacity = '0';
                
                setTimeout(() => {
                    progressText.textContent = currentStep || 'Processing...';
                    progressText.style.opacity = '1';
                }, 100);
            }
            
            // Update steps list with better formatting
            if (progressSteps && Array.isArray(steps) && steps.length > 0) {
                let stepsHTML = '<div style="max-height: 300px; overflow-y: auto; padding: var(--e-space-m); border-radius: var(--e-border-radius); margin-top: var(--e-space-m);">';
                stepsHTML += '<ul style="list-style: none; padding: 0; margin: 0; font-family: monospace; font-size: 13px;">';
                
                steps.forEach((step, index) => {
                    const isSuccess = step.includes('✅');
                    const isError = step.includes('❌');
                    const isInfo = !isSuccess && !isError;
                    
                    let color = '#6b7280'; // Gray for info
                    let icon = '•';
                    
                    if (isSuccess) {
                        color = 'var(--e-primary)'; // Green
                        icon = '✅';
                        step = step.replace('✅', '');
                    } else if (isError) {
                        color = 'var(--e-danger)'; // Red
                        icon = '❌';
                        step = step.replace('❌', '');
                    }
                    
                    stepsHTML += `
                        <li style="
                            padding: var(--e-space-s) var(--e-space-m); 
                            margin: var(--e-space-xs) 0; 
                            color: ${color};
                            border-left: 3px solid ${color};
                            border-radius: var(--e-border-radius);
                            animation: slideIn 0.3s ease-out;
                        ">
                            <span style="margin-right: var(--e-space-s);">${icon}</span>
                            <span>${step.trim()}</span>
                        </li>
                    `;
                });
                
                stepsHTML += '</ul></div>';
                
                // Add CSS animation
                if (!document.getElementById('progress-animation-style')) {
                    const style = document.createElement('style');
                    style.id = 'progress-animation-style';
                    style.textContent = `
                        @keyframes slideIn {
                            from {
                                opacity: 0;
                                transform: translateX(-10px);
                            }
                            to {
                                opacity: 1;
                                transform: translateX(0);
                            }
                        }
                    `;
                    document.head.appendChild(style);
                }
                
                progressSteps.innerHTML = stepsHTML;
                
                // Auto-scroll to bottom
                setTimeout(() => {
                    const container = progressSteps.querySelector('div');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                }, 100);
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
                    <div class="b2e-report-modal" style="background: var(--e-base); padding: var(--e-space-l); border-radius: var(--e-border-radius); max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 1px solid var(--e-border-color);">
                        <h2 style="margin-top: 0; color: var(--e-light);">📊 Migration Report</h2>
                        <div style="margin-bottom: var(--e-space-l);">
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
                        ${reportData.details ? `<div style="margin-bottom: var(--e-space-l);"><strong>Recent Activity:</strong><br><pre style="background: var(--e-base-dark); padding: var(--e-space-m); border-radius: var(--e-border-radius); overflow-x: auto; white-space: pre-wrap; border: 1px solid var(--e-border-color);">${reportData.details}</pre></div>` : ''}
                        <button onclick="this.closest('.b2e-report-overlay').remove()" style="background: var(--e-primary); color: var(--e-base-dark); border: none; padding: var(--e-space-m) var(--e-space-l); border-radius: var(--e-border-radius); cursor: pointer;">Close Report</button>
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
                bottom: 16px;
                right: 16px;
                background: #252525;
                color: #e3e3e3;
                padding: 16px;
                border-radius: 6px;
                z-index: 9999;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                font-family: Arial, sans-serif;
                box-shadow: 0 8px 24px rgba(0,0,0,0.3);
                border: 1px solid #383838;
            `;
            
            if (type === 'success') {
                toast.style.background = 'var(--e-primary)';
                toast.style.color = 'var(--e-base-dark)';
            } else if (type === 'error') {
                toast.style.background = 'var(--e-danger)';
                toast.style.color = 'white';
            } else if (type === 'warning') {
                toast.style.background = 'var(--e-warning)';
                toast.style.color = 'var(--e-base-dark)';
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
                            <div style="display: flex; gap: var(--e-space-m);">
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
                
                <!-- CPT Mapping Section -->
                <div id="cpt-mapping-section" style="margin-top: 30px; padding: 20px; background: var(--e-highlight-light); border-radius: var(--e-border-radius); border-left: 4px solid var(--e-primary);">
                    <h3 style="margin-top: 0;">🔄 <?php _e('Custom Post Type Mapping', 'bricks-etch-migration'); ?></h3>
                    <p><?php _e('Map your Bricks post types to Etch post types. Bricks templates will be converted to pages by default.', 'bricks-etch-migration'); ?></p>
                    
                    <div id="cpt-mapping-list">
                        <p style="color: var(--e-base-light); font-style: italic;">
                            <?php _e('Validate the migration key first to see available post types...', 'bricks-etch-migration'); ?>
                        </p>
                    </div>
                </div>
                
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
                <div id="migration-progress" style="margin-top: var(--e-space-l); display: none;">
                    <h3>📊 <?php _e('Migration Progress', 'bricks-etch-migration'); ?></h3>
                    <div style="background: var(--e-base-dark); border-radius: var(--e-border-radius); padding: var(--e-space-l); margin-top: var(--e-space-m); border: 1px solid var(--e-border-color);">
                        <div style="margin-bottom: var(--e-space-m);">
                            <strong id="progress-text"><?php _e('Initializing...', 'bricks-etch-migration'); ?></strong>
                        </div>
                        <div style="background: var(--e-base-ultra-light); border-radius: var(--e-border-radius); height: 30px; overflow: hidden; border: 1px solid var(--e-border-color);">
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
            <div style="background: var(--e-highlight-light); border-radius: var(--e-border-radius); border-left: 10px solid var(--e-warning); padding: var(--e-space-l); margin: var(--e-space-l) 0;">
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
        
        // Convert localhost URLs for Docker internal communication (only if both are localhost)
        $target_url = $this->convert_localhost_for_docker($target_url);
        
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
        
        // Convert localhost URLs for Docker internal communication (only if both are localhost)
        $target_url = $this->convert_localhost_for_docker($target_url);
        
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
                bottom: 16px;
                right: 16px;
                background: #252525;
                color: #e3e3e3;
                padding: 16px;
                border-radius: 6px;
                z-index: 9999;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                font-family: Arial, sans-serif;
                box-shadow: 0 8px 24px rgba(0,0,0,0.3);
                border: 1px solid #383838;
            `;
            
            if (type === 'success') {
                toast.style.background = 'var(--e-primary)';
                toast.style.color = 'var(--e-base-dark)';
            } else if (type === 'error') {
                toast.style.background = 'var(--e-danger)';
                toast.style.color = 'white';
            } else if (type === 'warning') {
                toast.style.background = 'var(--e-warning)';
                toast.style.color = 'var(--e-base-dark)';
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
        
        // Increase timeout for large migrations
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', '300');
        
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
    
    /**
     * AJAX handler for batch migration (one post at a time)
     */
    public function ajax_migrate_batch() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $post_id = intval($_POST['post_id'] ?? 0);
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $cpt_mapping_json = $_POST['cpt_mapping'] ?? '{}';
        
        if (empty($post_id) || empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Parse CPT mapping
        $cpt_mapping = json_decode($cpt_mapping_json, true);
        if (!is_array($cpt_mapping)) {
            $cpt_mapping = array();
        }
        
        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
            return;
        }
        
        // Apply CPT mapping if needed
        if (isset($cpt_mapping[$post->post_type])) {
            $original_type = $post->post_type;
            $post->post_type = $cpt_mapping[$original_type];
            // Store original type for logging
            $post->_original_post_type = $original_type;
        }
        
        // Check if post has Bricks content
        $bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);
        if (empty($bricks_content)) {
            wp_send_json_success(array(
                'message' => 'Post skipped (no Bricks content)',
                'skipped' => true
            ));
            return;
        }
        
        // Migrate this single post
        try {
            // Convert localhost URLs for Docker internal communication (only if both are localhost)
            $internal_url = $this->convert_localhost_for_docker($target_url);
            
            // Save settings temporarily with internal URL
            update_option('b2e_settings', array(
                'target_url' => $internal_url,
                'api_key' => $api_key
            ), false);
            
            $migration_manager = new B2E_Migration_Manager();
            $result = $migration_manager->migrate_single_post($post);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(array(
                    'message' => 'Post migrated successfully',
                    'post_title' => $post->post_title
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to get list of Bricks posts
     */
    public function ajax_get_bricks_posts() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get all posts AND pages with Bricks content
        $all_posts = array();
        
        // Get pages
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_bricks_page_content_2',
        ));
        
        // Get posts
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_bricks_page_content_2',
        ));
        
        // Merge and verify they actually have Bricks content
        $all_posts = array_merge($pages, $posts);
        $posts_data = array();
        
        foreach ($all_posts as $post) {
            // Double-check that it has Bricks content
            $bricks_content = get_post_meta($post->ID, '_bricks_page_content_2', true);
            if (!empty($bricks_content) && is_array($bricks_content)) {
                $posts_data[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                );
            }
        }
        
        wp_send_json_success(array(
            'posts' => $posts_data,
            'count' => count($posts_data)
        ));
    }
    
    /**
     * AJAX handler to migrate CSS
     */
    public function ajax_migrate_css() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Convert localhost URLs for Docker internal communication (only if both are localhost)
        $internal_url = $this->convert_localhost_for_docker($target_url);
        
        // Save settings temporarily with internal URL
        update_option('b2e_settings', array(
            'target_url' => $internal_url,
            'api_key' => $api_key
        ), false);
        
        // Migrate CSS
        try {
            // Step 1: Convert Bricks classes to Etch styles
            $css_converter = new B2E_CSS_Converter();
            $etch_styles = $css_converter->convert_bricks_classes_to_etch();
            
            if (is_wp_error($etch_styles)) {
                wp_send_json_error($etch_styles->get_error_message());
                return;
            }
            
            // Step 2: Send styles to Etch via API
            $api_client = new B2E_API_Client();
            $result = $api_client->send_css_styles($internal_url, $api_key, $etch_styles);
            
            if (is_wp_error($result)) {
                wp_send_json_error('Failed to send styles to Etch: ' . $result->get_error_message());
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'CSS migrated successfully',
                'styles_count' => count($etch_styles)
            ));
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to migrate Custom Post Types
     */
    public function ajax_migrate_cpts() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Convert localhost URLs for Docker internal communication (only if both are localhost)
        $internal_url = $this->convert_localhost_for_docker($target_url);
        
        // Migrate CPTs
        try {
            $cpt_migrator = new B2E_CPT_Migrator();
            
            // Export CPTs from Bricks
            $cpts_data = $cpt_migrator->export_custom_post_types();
            
            if (empty($cpts_data)) {
                wp_send_json_success(array(
                    'message' => 'No custom post types to migrate',
                    'cpts_count' => 0
                ));
                return;
            }
            
            // Send to Etch via API
            $api_service = B2E_API_Service::get_instance();
            $api_service->init($internal_url, $api_key);
            $result = $api_service->send_cpts($cpts_data);
            
            if (is_wp_error($result)) {
                wp_send_json_error('Failed to send CPTs to Etch: ' . $result->get_error_message());
                return;
            }
            
            wp_send_json_success(array(
                'message' => 'Custom Post Types migrated successfully',
                'cpts_count' => count($cpts_data)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to migrate media files
     */
    public function ajax_migrate_media() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Convert localhost URLs for Docker internal communication (only if both are localhost)
        $internal_url = $this->convert_localhost_for_docker($target_url);
        
        // Save settings temporarily with internal URL
        update_option('b2e_settings', array(
            'target_url' => $internal_url,
            'api_key' => $api_key
        ), false);
        
        // Migrate media
        try {
            $media_migrator = new B2E_Media_Migrator();
            $result = $media_migrator->migrate_media($internal_url, $api_key);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(array(
                    'message' => 'Media migrated successfully',
                    'migrated' => $result['migrated'] ?? 0,
                    'failed' => $result['failed'] ?? 0,
                    'skipped' => $result['skipped'] ?? 0,
                    'total' => $result['total'] ?? 0
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to cleanup Etch (delete all posts, pages, styles)
     */
    public function ajax_cleanup_etch() {
        // Verify nonce
        if (!check_ajax_referer('b2e_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get parameters
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        try {
            // Build cleanup commands
            $commands = array(
                // Delete all posts and pages
                'wp post delete $(wp post list --post_type=post,page,attachment --format=ids) --force',
                // Delete etch_styles
                'wp option delete etch_styles',
                // Clear cache
                'wp cache flush',
                // Clear transients
                'wp transient delete --all'
            );
            
            $results = array();
            
            foreach ($commands as $command) {
                // Send command via API (you'll need to implement this endpoint on Etch side)
                // For now, we'll return the commands
                $results[] = $command;
            }
            
            wp_send_json_success(array(
                'message' => 'Cleanup commands prepared',
                'commands' => $results,
                'note' => 'Run these commands on Etch server via WP-CLI'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler to get Bricks CPTs
     */
    public function ajax_get_bricks_cpts() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get all custom post types
        $cpts = get_post_types(array('_builtin' => false), 'objects');
        $cpt_list = array();
        
        // Exclude Bricks-specific CPTs that shouldn't be migrated
        $excluded_cpts = array(
            'bricks_fonts'       // Bricks custom fonts (Etch has own font system)
        );
        
        foreach ($cpts as $cpt) {
            // Skip excluded CPTs
            if (in_array($cpt->name, $excluded_cpts)) {
                continue;
            }
            
            $count = wp_count_posts($cpt->name);
            $cpt_list[] = array(
                'name' => $cpt->name,
                'label' => $cpt->label,
                'count' => $count->publish
            );
        }
        
        wp_send_json_success($cpt_list);
    }
    
    /**
     * AJAX handler to get Etch CPTs
     */
    public function ajax_get_etch_cpts() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $target_url = sanitize_url($_POST['target_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($target_url) || empty($api_key)) {
            wp_send_json_error('Missing required parameters');
            return;
        }
        
        // Convert localhost URLs for Docker
        $internal_url = $this->convert_localhost_for_docker($target_url);
        
        // Get CPTs from Etch via API
        $api_service = B2E_API_Service::get_instance();
        $api_service->init($internal_url, $api_key);
        $result = $api_service->get_cpts();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Convert localhost URLs for Docker internal communication
     * Only converts if BOTH source and target are localhost (Docker environment)
     */
    private function convert_localhost_for_docker($target_url) {
        // Check if we're in a Docker/localhost environment
        $current_host = $_SERVER['HTTP_HOST'] ?? '';
        $is_source_localhost = (strpos($current_host, 'localhost') !== false || strpos($current_host, '127.0.0.1') !== false);
        $is_target_localhost = (strpos($target_url, 'localhost') !== false || strpos($target_url, '127.0.0.1') !== false);
        
        // Only convert if BOTH are localhost (Docker environment)
        if ($is_source_localhost && $is_target_localhost) {
            // Convert localhost:8081 to b2e-etch for Docker internal communication
            if (strpos($target_url, 'localhost:8081') !== false) {
                $target_url = str_replace('localhost:8081', 'b2e-etch', $target_url);
            }
        }
        
        return $target_url;
    }
}
