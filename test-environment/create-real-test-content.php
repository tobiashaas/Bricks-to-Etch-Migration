<?php
// Load WordPress environment
require_once __DIR__ . '/wordpress-bricks/wp-load.php';

echo "Creating real test Bricks content...\n";

// Create a test page with Bricks content
$page_title = 'Test Bricks Page';
$page_content = 'This is a test page created with Bricks.';
$page_id = wp_insert_post(array(
    'post_title'    => $page_title,
    'post_content'  => $page_content,
    'post_status'   => 'publish',
    'post_type'     => 'page',
));

if ($page_id) {
    echo "Created test page with ID: " . $page_id . "\n";
    
    // Add Bricks content meta
    $bricks_data = array(
        'elements' => array(
            array(
                'id' => 'brx-text-1',
                'name' => 'text',
                'settings' => array(
                    'text' => '<h2>Hello from Bricks Page!</h2><p>This is some paragraph text.</p>'
                )
            ),
            array(
                'id' => 'brx-button-1',
                'name' => 'button',
                'settings' => array(
                    'text' => 'Click Me',
                    'link' => array('url' => '#')
                )
            )
        )
    );
    
    $result = update_post_meta($page_id, '_bricks_page_content', $bricks_data);
    echo "Added Bricks content to page " . $page_id . " (result: " . ($result ? 'success' : 'failed') . ")\n";
    echo "Bricks elements created: " . count($bricks_data['elements']) . "\n";
    
    // Verify the meta was saved
    $saved_content = get_post_meta($page_id, '_bricks_page_content', true);
    if ($saved_content) {
        echo "Verified: Bricks content saved successfully\n";
    } else {
        echo "ERROR: Bricks content was not saved\n";
    }
} else {
    echo "Failed to create test page.\n";
}

// Create a test post with Bricks content
$post_title = 'Test Bricks Post';
$post_content = 'This is a test post created with Bricks.';
$post_id = wp_insert_post(array(
    'post_title'    => $post_title,
    'post_content'  => $post_content,
    'post_status'   => 'publish',
    'post_type'     => 'post',
));

if ($post_id) {
    echo "Created test post with ID: " . $post_id . "\n";
    
    // Add Bricks content meta
    $bricks_data = array(
        'elements' => array(
            array(
                'id' => 'brx-section-1',
                'name' => 'section',
                'settings' => array(
                    'background' => array('color' => 'lightgray'),
                    'padding' => array('top' => '50px', 'bottom' => '50px')
                ),
                'elements' => array(
                    array(
                        'id' => 'brx-heading-1',
                        'name' => 'heading',
                        'settings' => array(
                            'text' => '<h1>Welcome to Bricks Post!</h1>',
                            'tag' => 'h1'
                        )
                    ),
                    array(
                        'id' => 'brx-text-2',
                        'name' => 'text',
                        'settings' => array(
                            'text' => '<p>This is a section with a heading and some text.</p>'
                        )
                    )
                )
            )
        )
    );
    
    $result = update_post_meta($post_id, '_bricks_page_content', $bricks_data);
    echo "Added Bricks content to post " . $post_id . " (result: " . ($result ? 'success' : 'failed') . ")\n";
    
    // Verify the meta was saved
    $saved_content = get_post_meta($post_id, '_bricks_page_content', true);
    if ($saved_content) {
        echo "Verified: Bricks content saved successfully\n";
    } else {
        echo "ERROR: Bricks content was not saved\n";
    }
} else {
    echo "Failed to create test post.\n";
}

// Check how many posts/pages have Bricks content
$posts_with_bricks = get_posts(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'numberposts' => -1,
    'meta_query' => array(
        array(
            'key' => '_bricks_page_content',
            'compare' => 'EXISTS'
        )
    )
));

$pages_with_bricks = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1,
    'meta_query' => array(
        array(
            'key' => '_bricks_page_content',
            'compare' => 'EXISTS'
        )
    )
));

echo "\n=== SUMMARY ===\n";
echo "Posts with Bricks content: " . count($posts_with_bricks) . "\n";
echo "Pages with Bricks content: " . count($pages_with_bricks) . "\n";

if (count($posts_with_bricks) > 0 || count($pages_with_bricks) > 0) {
    echo "✅ Test content created successfully!\n";
    echo "You can now test the migration.\n";
} else {
    echo "❌ No Bricks content found!\n";
    echo "Migration will have nothing to migrate.\n";
}

echo "\nTest content creation completed!\n";
?>
