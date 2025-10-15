<?php
/**
 * WPvivid-Style Migration Interface for Bricks to Etch Migration Plugin
 * 
 * Provides elegant migration URL generation similar to WPvivid's system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class B2E_WPvivid_Migration_Interface {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Token manager instance
     */
    private $token_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new B2E_Error_Handler();
        $this->token_manager = new B2E_Migration_Token_Manager();
        
        // Register AJAX handlers
        add_action('wp_ajax_b2e_generate_migration_url', array($this, 'ajax_generate_migration_url'));
        add_action('wp_ajax_b2e_validate_migration_url', array($this, 'ajax_validate_migration_url'));
        add_action('wp_ajax_b2e_start_wpvivid_migration', array($this, 'ajax_start_wpvivid_migration'));
    }
    
    /**
     * Render WPvivid-style migration interface
     */
    public function render_wpvivid_interface() {
        $current_settings = get_option('b2e_settings', array());
        ?>
        
        <div class="wrap">
            <h1>🚀 Bricks to Etch Migration - WPvivid Style</h1>
            
            <!-- Migration URL Generator -->
            <div class="b2e-wpvivid-section" style="background: #f0f8ff; border: 2px dashed #0073aa; border-radius: 12px; padding: 30px; margin: 30px 0;">
                <h2 style="margin-top: 0; color: #0073aa; display: flex; align-items: center; gap: 10px;">
                    🔗 <?php _e('WPvivid-Style Migration URL', 'bricks-etch-migration'); ?>
                </h2>
                
                <p style="font-size: 16px; margin-bottom: 25px; color: #555;">
                    <?php _e('Generate a secure migration URL with embedded authentication - just like WPvivid! This creates a one-click migration link that contains all necessary authentication.', 'bricks-etch-migration'); ?>
                </p>
                
                <!-- Target Domain Input -->
                <div style="margin-bottom: 20px;">
                    <label for="target-domain" style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;">
                        <?php _e('Target Domain (where to migrate TO):', 'bricks-etch-migration'); ?>
                    </label>
                    <input type="url" id="target-domain" 
                           placeholder="https://your-target-site.com"
                           value="<?php echo esc_attr($current_settings['target_url'] ?? ''); ?>"
                           style="width: 100%; max-width: 500px; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                    <p style="font-size: 12px; color: #666; margin-top: 5px;">
                        <?php _e('Enter the full URL of your target WordPress site (where Etch is installed)', 'bricks-etch-migration'); ?>
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; margin-bottom: 25px;">
                    <button type="button" id="generate-migration-url" class="button button-primary" 
                            style="background: #0073aa; border-color: #0073aa; padding: 12px 24px; font-size: 16px; border-radius: 8px;">
                        🔗 <?php _e('Generate Migration URL', 'bricks-etch-migration'); ?>
                    </button>
                    
                    <button type="button" id="copy-migration-url" class="button" style="display: none; padding: 12px 24px; font-size: 16px; border-radius: 8px;">
                        📋 <?php _e('Copy URL', 'bricks-etch-migration'); ?>
                    </button>
                    
                    <button type="button" id="generate-qr-code" class="button" style="display: none; padding: 12px 24px; font-size: 16px; border-radius: 8px;">
                        📱 <?php _e('Show QR Code', 'bricks-etch-migration'); ?>
                    </button>
                </div>
                
                <!-- Migration URL Result -->
                <div id="migration-url-result" style="display: none; background: #fff; border: 1px solid #0073aa; border-radius: 8px; padding: 20px;">
                    <h3 style="margin-top: 0; color: #0073aa;">
                        ✅ <?php _e('Migration URL Generated Successfully!', 'bricks-etch-migration'); ?>
                    </h3>
                    
                    <label for="migration-url-display" style="display: block; font-weight: bold; margin-bottom: 8px; color: #333;">
                        <?php _e('Your Migration URL:', 'bricks-etch-migration'); ?>
                    </label>
                    <input type="text" id="migration-url-display" readonly 
                           style="width: 100%; padding: 12px; font-family: 'Courier New', monospace; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; word-break: break-all;">
                    
                    <div id="migration-url-info" style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 6px; border-left: 4px solid #0073aa;">
                        <h4 style="margin-top: 0; color: #0073aa;">
                            ℹ️ <?php _e('Migration Information', 'bricks-etch-migration'); ?>
                        </h4>
                        <div id="token-info-content"></div>
                    </div>
                    
                    <!-- Instructions -->
                    <div style="margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
                        <h4 style="margin-top: 0; color: #856404;">
                            📋 <?php _e('How to Use This URL', 'bricks-etch-migration'); ?>
                        </h4>
                        <ol style="margin-bottom: 0; color: #856404;">
                            <li><?php _e('Copy the migration URL above', 'bricks-etch-migration'); ?></li>
                            <li><?php _e('Go to your TARGET site (where Etch is installed)', 'bricks-etch-migration'); ?></li>
                            <li><?php _e('Open the migration URL in your browser', 'bricks-etch-migration'); ?></li>
                            <li><?php _e('The migration will start automatically!', 'bricks-etch-migration'); ?></li>
                        </ol>
                    </div>
                </div>
                
                <!-- QR Code Display -->
                <div id="qr-code-display" style="margin-top: 20px; display: none; text-align: center;">
                    <div style="background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 30px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #0073aa;">
                            📱 <?php _e('Mobile Migration QR Code', 'bricks-etch-migration'); ?>
                        </h3>
                        <div id="qr-code-container" style="margin: 20px 0;"></div>
                        <p style="margin-bottom: 0; font-size: 14px; color: #666; max-width: 300px;">
                            <?php _e('Scan this QR code with your mobile device to open the migration URL directly on your target site', 'bricks-etch-migration'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Traditional Migration (Fallback) -->
            <div class="b2e-traditional-section" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 12px; padding: 30px; margin: 30px 0;">
                <h2 style="margin-top: 0; color: #666;">
                    🔧 <?php _e('Traditional Migration (Fallback)', 'bricks-etch-migration'); ?>
                </h2>
                <p style="color: #666; margin-bottom: 20px;">
                    <?php _e('If the WPvivid-style migration doesn\'t work, you can use the traditional API key method.', 'bricks-etch-migration'); ?>
                </p>
                <a href="<?php echo admin_url('admin.php?page=bricks-etch-migration'); ?>" class="button">
                    <?php _e('Go to Traditional Migration', 'bricks-etch-migration'); ?>
                </a>
            </div>
        </div>
        
        <!-- JavaScript for WPvivid Migration -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const generateBtn = document.getElementById('generate-migration-url');
            const copyBtn = document.getElementById('copy-migration-url');
            const qrBtn = document.getElementById('generate-qr-code');
            const resultDiv = document.getElementById('migration-url-result');
            const qrDiv = document.getElementById('qr-code-display');
            const urlDisplay = document.getElementById('migration-url-display');
            const targetDomainInput = document.getElementById('target-domain');
            
            // Generate Migration URL
            generateBtn.addEventListener('click', function() {
                const targetDomain = targetDomainInput.value.trim();
                
                if (!targetDomain) {
                    alert('Please enter a target domain');
                    return;
                }
                
                if (!targetDomain.startsWith('http://') && !targetDomain.startsWith('https://')) {
                    alert('Please enter a valid URL starting with http:// or https://');
                    return;
                }
                
                generateBtn.disabled = true;
                generateBtn.textContent = '🔄 Generating...';
                
                // AJAX call to generate migration URL
                fetch(ajaxurl, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'b2e_generate_migration_url',
                        nonce: '<?php echo wp_create_nonce('b2e_nonce'); ?>',
                        target_domain: targetDomain
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        urlDisplay.value = data.data.url;
                        resultDiv.style.display = 'block';
                        copyBtn.style.display = 'inline-block';
                        qrBtn.style.display = 'inline-block';
                        
                        // Update token info
                        document.getElementById('token-info-content').innerHTML = `
                            <p><strong>Expires:</strong> ${data.data.expires_at}</p>
                            <p><strong>Valid for:</strong> ${Math.floor(data.data.expires_in / 3600)} hours</p>
                            <p><strong>Token Type:</strong> RSA Public Key (Base64)</p>
                        `;
                        
                        showToast('Migration URL generated successfully!', 'success');
                    } else {
                        showToast('Failed to generate migration URL: ' + (data.data || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to generate migration URL: ' + error.message, 'error');
                })
                .finally(() => {
                    generateBtn.disabled = false;
                    generateBtn.textContent = '🔗 Generate Migration URL';
                });
            });
            
            // Copy URL
            copyBtn.addEventListener('click', function() {
                urlDisplay.select();
                document.execCommand('copy');
                showToast('Migration URL copied to clipboard!', 'success');
            });
            
            // Generate QR Code
            qrBtn.addEventListener('click', function() {
                if (qrDiv.style.display === 'none') {
                    qrDiv.style.display = 'block';
                    
                    // Simple QR code generation (you can use a proper QR library)
                    const qrContainer = document.getElementById('qr-code-container');
                    qrContainer.innerHTML = `
                        <div style="background: #000; color: #fff; padding: 20px; font-family: monospace; font-size: 10px; line-height: 1; word-break: break-all; max-width: 200px; margin: 0 auto;">
                            QR Code for:<br>
                            ${urlDisplay.value.substring(0, 50)}...
                        </div>
                        <p style="margin-top: 10px; font-size: 12px; color: #666;">
                            <?php _e('(For a real QR code, integrate a QR code library)', 'bricks-etch-migration'); ?>
                        </p>
                    `;
                    
                    showToast('QR code displayed!', 'info');
                } else {
                    qrDiv.style.display = 'none';
                }
            });
        });
        
        // Toast notification function
        function showToast(message, type = 'info', duration = 4000) {
            // Create toast element
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
            
            // Set background color based on type
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            toast.style.background = colors[type] || colors.info;
            
            // Add content
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 18px;">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span>
                    <span style="flex: 1;">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; font-size: 18px; cursor: pointer; opacity: 0.7;">×</button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 100);
            
            // Auto remove
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
     * AJAX handler for generating migration URL
     */
    public function ajax_generate_migration_url() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
            return;
        }
        
        $target_domain = sanitize_url($_POST['target_domain']);
        
        if (empty($target_domain)) {
            wp_send_json_error(__('Target domain is required.', 'bricks-etch-migration'));
            return;
        }
        
        try {
            $migration_data = $this->token_manager->generate_qr_data($target_domain);
            
            wp_send_json_success($migration_data);
        } catch (Exception $e) {
            wp_send_json_error('Failed to generate migration URL: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for validating migration URL
     */
    public function ajax_validate_migration_url() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
            return;
        }
        
        $migration_url = sanitize_url($_POST['migration_url']);
        
        if (empty($migration_url)) {
            wp_send_json_error(__('Migration URL is required.', 'bricks-etch-migration'));
            return;
        }
        
        try {
            $parsed = $this->token_manager->parse_migration_url($migration_url);
            
            if (empty($parsed['token']) || empty($parsed['expires'])) {
                wp_send_json_error(__('Invalid migration URL format.', 'bricks-etch-migration'));
                return;
            }
            
            $validation = $this->token_manager->validate_migration_token(
                $parsed['token'],
                $parsed['domain'],
                $parsed['expires']
            );
            
            if (is_wp_error($validation)) {
                wp_send_json_error($validation->get_error_message());
                return;
            }
            
            wp_send_json_success(__('Migration URL is valid and ready to use.', 'bricks-etch-migration'));
        } catch (Exception $e) {
            wp_send_json_error('Failed to validate migration URL: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for starting WPvivid-style migration
     */
    public function ajax_start_wpvivid_migration() {
        check_ajax_referer('b2e_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bricks-etch-migration'));
            return;
        }
        
        $migration_url = sanitize_url($_POST['migration_url']);
        
        if (empty($migration_url)) {
            wp_send_json_error(__('Migration URL is required.', 'bricks-etch-migration'));
            return;
        }
        
        try {
            $parsed = $this->token_manager->parse_migration_url($migration_url);
            
            // Validate token
            $validation = $this->token_manager->validate_migration_token(
                $parsed['token'],
                $parsed['domain'],
                $parsed['expires']
            );
            
            if (is_wp_error($validation)) {
                wp_send_json_error($validation->get_error_message());
                return;
            }
            
            // Start migration using the validated token
            $migration_manager = new B2E_Migration_Manager();
            $result = $migration_manager->start_migration($parsed['domain'], $parsed['token']);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(__('WPvivid-style migration started successfully!', 'bricks-etch-migration'));
            }
        } catch (Exception $e) {
            wp_send_json_error('Failed to start migration: ' . $e->getMessage());
        }
    }
}
