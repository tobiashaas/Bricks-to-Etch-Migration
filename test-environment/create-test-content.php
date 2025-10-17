<?php
/**
 * Script to create test Bricks content for migration testing
 */

// WordPress bootstrap
require_once('/var/www/html/wp-config.php');
require_once('/var/www/html/wp-load.php');

echo "Creating test Bricks content...\n";

// Create a test page with Bricks content
$page_data = array(
    'post_title'   => 'Test Bricks Page',
    'post_content' => 'This is a test page with Bricks content that will be migrated to Etch.',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_author'  => 1
);

$page_id = wp_insert_post($page_data);

if ($page_id && !is_wp_error($page_id)) {
    echo "Created test page with ID: $page_id\n";
    
    // Add Bricks page content meta
    $bricks_content = array(
        'elements' => array(
            array(
                'id' => 'test-section-1',
                'name' => 'section',
                'settings' => array(
                    'backgroundColor' => '#f0f0f0',
                    'padding' => array('top' => '40px', 'bottom' => '40px')
                ),
                'children' => array(
                    array(
                        'id' => 'test-container-1',
                        'name' => 'container',
                        'settings' => array(
                            'maxWidth' => '1200px'
                        ),
                        'children' => array(
                            array(
                                'id' => 'test-text-1',
                                'name' => 'text',
                                'settings' => array(
                                    'text' => '<h2>Welcome to Our Test Page</h2><p>This is a test page created with Bricks Builder. The content will be migrated to Etch and converted to Gutenberg blocks.</p>'
                                )
                            ),
                            array(
                                'id' => 'test-button-1',
                                'name' => 'button',
                                'settings' => array(
                                    'text' => 'Click Me',
                                    'link' => array('url' => '#'),
                                    'backgroundColor' => '#0073aa'
                                )
                            )
                        )
                    )
                )
            ),
            array(
                'id' => 'test-section-2',
                'name' => 'section',
                'settings' => array(
                    'backgroundColor' => '#ffffff',
                    'padding' => array('top' => '60px', 'bottom' => '60px')
                ),
                'children' => array(
                    array(
                        'id' => 'test-container-2',
                        'name' => 'container',
                        'settings' => array(
                            'maxWidth' => '800px'
                        ),
                        'children' => array(
                            array(
                                'id' => 'test-heading-1',
                                'name' => 'heading',
                                'settings' => array(
                                    'text' => 'About This Migration',
                                    'tag' => 'h3'
                                )
                            ),
                            array(
                                'id' => 'test-text-2',
                                'name' => 'text',
                                'settings' => array(
                                    'text' => '<p>This content was created using Bricks Builder and will be automatically converted to Gutenberg blocks during the migration process. Etch will then render these blocks beautifully.</p>'
                                )
                            )
                        )
                    )
                )
            )
        )
    );
    
    // Save Bricks content
    update_post_meta($page_id, '_bricks_page_content', $bricks_content);
    update_post_meta($page_id, '_bricks_page_settings', array(
        'template' => 'bricks',
        'css' => '',
        'custom_css' => ''
    ));
    
    echo "Added Bricks content to page $page_id\n";
    echo "Bricks elements created: " . count($bricks_content['elements']) . "\n";
    
} else {
    echo "Failed to create test page\n";
}

// Create a test post with Bricks content
$post_data = array(
    'post_title'   => 'Test Bricks Post',
    'post_content' => 'This is a test post with Bricks content.',
    'post_status'  => 'publish',
    'post_type'    => 'post',
    'post_author'  => 1
);

$post_id = wp_insert_post($post_data);

if ($post_id && !is_wp_error($post_id)) {
    echo "Created test post with ID: $post_id\n";
    
    // Add simpler Bricks content for the post
    $post_bricks_content = array(
        'elements' => array(
            array(
                'id' => 'post-text-1',
                'name' => 'text',
                'settings' => array(
                    'text' => '<p>This is a test blog post created with Bricks Builder. It will be migrated and converted to Gutenberg blocks.</p>'
                )
            ),
            array(
                'id' => 'post-heading-1',
                'name' => 'heading',
                'settings' => array(
                    'text' => 'Post Content',
                    'tag' => 'h2'
                )
            )
        )
    );
    
    update_post_meta($post_id, '_bricks_page_content', $post_bricks_content);
    update_post_meta($post_id, '_bricks_page_settings', array(
        'template' => 'bricks'
    ));
    
    echo "Added Bricks content to post $post_id\n";
}

echo "Test content creation completed!\n";
echo "You can now test the migration with real Bricks content.\n";
?>
