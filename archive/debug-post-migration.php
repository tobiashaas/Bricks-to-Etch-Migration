<?php
/**
 * Debug Post Migration
 */

require_once('/var/www/html/wp-load.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/content_parser.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/gutenberg_generator.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/dynamic_data_converter.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/error_handler.php');

echo "========================================\n";
echo "🔍 Debug Post Migration\n";
echo "========================================\n\n";

// Test Post 74 (Test Post 3 - no Bricks content)
$post_id = 74;
$post = get_post($post_id);

echo "Post: {$post->post_title} (ID: {$post_id})\n";
echo "post_content: \"{$post->post_content}\"\n";
echo "post_content length: " . strlen($post->post_content) . "\n\n";

// Check for Bricks content
$bricks_meta = get_post_meta($post_id, '_bricks_page_content', true);
echo "Bricks meta: " . (empty($bricks_meta) ? "EMPTY" : "HAS DATA") . "\n\n";

// Parse content
$parser = new B2E_Content_Parser();
$bricks_content = $parser->parse_bricks_content($post_id);

echo "Parsed result:\n";
if ($bricks_content && isset($bricks_content['elements'])) {
    echo "  - Has elements: " . count($bricks_content['elements']) . "\n";
} else {
    echo "  - No Bricks content (returned: " . gettype($bricks_content) . ")\n";
}
echo "\n";

// What would be sent?
if ($bricks_content && isset($bricks_content['elements'])) {
    $generator = new B2E_Gutenberg_Generator();
    $etch_content = $generator->generate_gutenberg_blocks($bricks_content['elements']);
    echo "Would send (Gutenberg):\n";
    echo $etch_content . "\n";
} else {
    $etch_content = !empty($post->post_content) ? $post->post_content : '<!-- wp:paragraph --><p>Empty content</p><!-- /wp:paragraph -->';
    echo "Would send (Original):\n";
    echo $etch_content . "\n";
}

echo "\n========================================\n";
echo "✅ Debug Complete\n";
echo "========================================\n";
