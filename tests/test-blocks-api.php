<?php
/**
 * Test Blocks API
 */

require_once('/var/www/html/wp-load.php');

echo "Testing Blocks API...\n\n";

// Get a test post
$post = get_post(25); // Feature Section Frankfurt

if (!$post) {
    die("Post not found!\n");
}

echo "Post ID: {$post->ID}\n";
echo "Post Title: {$post->post_title}\n\n";

// Get Bricks content
$bricks_content = get_post_meta($post->ID, '_bricks_page_content_2', true);

if (empty($bricks_content)) {
    die("No Bricks content!\n");
}

echo "Bricks elements: " . count($bricks_content) . "\n\n";

// Try to parse and convert
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/content_parser.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/gutenberg_generator.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/error_handler.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/dynamic_data_converter.php');

$error_handler = new B2E_Error_Handler();
$dynamic_converter = new B2E_Dynamic_Data_Converter();
$content_parser = new B2E_Content_Parser($error_handler);
$gutenberg_generator = new B2E_Gutenberg_Generator($error_handler, $dynamic_converter);

try {
    echo "Converting to Gutenberg...\n";
    $result = $gutenberg_generator->convert_bricks_to_gutenberg($post);
    
    if ($result) {
        echo "✅ Success!\n";
    } else {
        echo "❌ Failed!\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
