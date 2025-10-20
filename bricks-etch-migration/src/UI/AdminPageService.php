<?php
/**
 * Admin Page Service
 * 
 * Minimal admin interface for migration
 */

namespace BricksEtchMigration\UI;

use BricksEtchMigration\Services\CSS\CSSConverterService;
use BricksEtchMigration\Services\Content\ContentMigrationService;
use BricksEtchMigration\Services\API\APIClientService;

class AdminPageService {
    public function __construct(
        private CSSConverterService $cssConverter,
        private ContentMigrationService $contentMigration,
        private APIClientService $apiClient
    ) {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // AJAX handlers
        add_action('wp_ajax_b2e_migrate_css', [$this, 'handleCSSMigration']);
        add_action('wp_ajax_b2e_migrate_content', [$this, 'handleContentMigration']);
    }
    
    /**
     * Add admin menu
     */
    public function addMenu(): void {
        add_menu_page(
            'Bricks to Etch Migration',
            'B2E Migration',
            'manage_options',
            'bricks-etch-migration',
            [$this, 'renderPage'],
            'dashicons-migrate',
            30
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueueAssets($hook): void {
        if ($hook !== 'toplevel_page_bricks-etch-migration') {
            return;
        }
        
        wp_enqueue_style(
            'b2e-admin',
            B2E_PLUGIN_URL . 'assets/css/admin.css',
            [],
            B2E_VERSION
        );
    }
    
    /**
     * Render admin page
     */
    public function renderPage(): void {
        ?>
        <div class="wrap">
            <h1>Bricks to Etch Migration</h1>
            
            <div class="b2e-dashboard">
                <div class="b2e-card">
                    <h2>CSS Migration</h2>
                    <p>Convert Bricks global classes to Etch styles</p>
                    <button id="b2e-migrate-css" class="button button-primary">
                        Migrate CSS
                    </button>
                    <div id="css-result"></div>
                </div>
                
                <div class="b2e-card">
                    <h2>Content Migration</h2>
                    <p>Convert Bricks content to Gutenberg blocks</p>
                    <input type="number" id="post-id" placeholder="Post ID" />
                    <button id="b2e-migrate-content" class="button button-primary">
                        Migrate Content
                    </button>
                    <div id="content-result"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // CSS Migration
            $('#b2e-migrate-css').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).text('Migrating...');
                
                $.post(ajaxurl, {
                    action: 'b2e_migrate_css',
                    nonce: '<?php echo wp_create_nonce('b2e_migrate'); ?>'
                }, function(response) {
                    $('#css-result').html(
                        response.success 
                            ? '<p class="success">✅ ' + response.data.message + '</p>'
                            : '<p class="error">❌ ' + response.data + '</p>'
                    );
                }).always(function() {
                    btn.prop('disabled', false).text('Migrate CSS');
                });
            });
            
            // Content Migration
            $('#b2e-migrate-content').on('click', function() {
                const btn = $(this);
                const postId = $('#post-id').val();
                
                if (!postId) {
                    alert('Please enter a Post ID');
                    return;
                }
                
                btn.prop('disabled', true).text('Migrating...');
                
                $.post(ajaxurl, {
                    action: 'b2e_migrate_content',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('b2e_migrate'); ?>'
                }, function(response) {
                    $('#content-result').html(
                        response.success 
                            ? '<p class="success">✅ ' + response.data.message + '</p>'
                            : '<p class="error">❌ ' + response.data + '</p>'
                    );
                }).always(function() {
                    btn.prop('disabled', false).text('Migrate Content');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle CSS migration AJAX
     */
    public function handleCSSMigration(): void {
        check_ajax_referer('b2e_migrate', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get Bricks classes
        $bricksClasses = get_option('bricks_global_classes', []);
        
        if (empty($bricksClasses)) {
            wp_send_json_error('No Bricks classes found');
            return;
        }
        
        // Convert
        $result = $this->cssConverter->execute(['classes' => $bricksClasses]);
        
        // Save styles
        update_option('etch_styles', $result['styles']);
        update_option('b2e_style_map', $result['style_map']);
        
        wp_send_json_success([
            'message' => sprintf(
                'Migrated %d styles successfully!',
                count($result['styles'])
            )
        ]);
    }
    
    /**
     * Handle content migration AJAX
     */
    public function handleContentMigration(): void {
        check_ajax_referer('b2e_migrate', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $postId = intval($_POST['post_id'] ?? 0);
        
        if (!$postId) {
            wp_send_json_error('Invalid post ID');
            return;
        }
        
        $result = $this->contentMigration->execute(['post_id' => $postId]);
        
        if ($result->success) {
            wp_send_json_success([
                'message' => 'Content migrated successfully!'
            ]);
        } else {
            wp_send_json_error(implode(', ', $result->errors));
        }
    }
}
