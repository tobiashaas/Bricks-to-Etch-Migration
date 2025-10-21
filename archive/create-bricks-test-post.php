<?php
/**
 * Create a test post with Bricks content
 */

require_once('/var/www/html/wp-load.php');

// Create a simple Bricks structure
$bricks_content = array(
    array(
        'id' => 'section1',
        'name' => 'section',
        'parent' => 0,
        'children' => array('container1'),
        'settings' => array(
            '_cssClasses' => 'test-section',
            'tag' => 'section'
        )
    ),
    array(
        'id' => 'container1',
        'name' => 'container',
        'parent' => 'section1',
        'children' => array('heading1', 'text1'),
        'settings' => array(
            '_cssClasses' => 'test-container'
        )
    ),
    array(
        'id' => 'heading1',
        'name' => 'heading',
        'parent' => 'container1',
        'children' => array(),
        'settings' => array(
            'tag' => 'h2',
            'text' => 'Test Heading from Bricks'
        )
    ),
    array(
        'id' => 'text1',
        'name' => 'text',
        'parent' => 'container1',
        'children' => array(),
        'settings' => array(
            'text' => 'This is test content from Bricks Builder. It should be converted to Gutenberg blocks.'
        )
    )
);

// Create post
$post_id = wp_insert_post(array(
    'post_title' => 'Bricks Test Post for Conversion',
    'post_content' => '', // Bricks leaves this empty!
    'post_status' => 'publish',
    'post_type' => 'post'
));

if ($post_id) {
    // Add Bricks meta
    update_post_meta($post_id, '_bricks_page_content', $bricks_content);
    update_post_meta($post_id, '_bricks_editor_mode', 'bricks');
    
    echo "✅ Created test post ID: $post_id\n";
    echo "   Title: Bricks Test Post for Conversion\n";
    echo "   Bricks Elements: " . count($bricks_content) . "\n";
    echo "   Structure: section > container > (heading + text)\n";
} else {
    echo "❌ Failed to create post\n";
}
