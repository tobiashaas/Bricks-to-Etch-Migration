<?php
/**
 * Test Content Conversion
 */

require_once('/var/www/html/wp-load.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/content_parser.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/gutenberg_generator.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/dynamic_data_converter.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/error_handler.php');

echo "========================================\n";
echo "🔄 Content Conversion Test\n";
echo "========================================\n\n";

// Get test post
$post_id = 77;
$post = get_post($post_id);

if (!$post) {
    echo "❌ Post not found!\n";
    exit(1);
}

echo "Post: {$post->post_title} (ID: {$post_id})\n\n";

// Get Bricks content
$bricks_content = get_post_meta($post_id, '_bricks_page_content', true);

if (empty($bricks_content)) {
    echo "❌ No Bricks content found!\n";
    exit(1);
}

echo "✅ Found Bricks content: " . count($bricks_content) . " elements\n\n";

// Show Bricks structure
echo "Bricks Structure:\n";
echo "=================\n";
foreach ($bricks_content as $element) {
    $indent = $element['parent'] === 0 ? '' : '  ';
    if (isset($element['settings']['text'])) {
        $text = substr($element['settings']['text'], 0, 50);
        echo "{$indent}- {$element['name']} (ID: {$element['id']}): {$text}...\n";
    } else {
        echo "{$indent}- {$element['name']} (ID: {$element['id']})\n";
    }
}

echo "\n";

// Parse content
$parser = new B2E_Content_Parser();
$parsed = $parser->parse_bricks_content($post_id);

if (!$parsed) {
    echo "❌ Failed to parse Bricks content!\n";
    exit(1);
}

echo "✅ Parsed content: " . count($parsed['elements']) . " elements\n\n";

// Generate Gutenberg blocks
$generator = new B2E_Gutenberg_Generator();
$gutenberg = $generator->generate_gutenberg_blocks($parsed['elements']);

if (empty($gutenberg)) {
    echo "❌ Failed to generate Gutenberg blocks!\n";
    echo "Parsed elements:\n";
    print_r($parsed['elements']);
    exit(1);
}

echo "✅ Generated Gutenberg blocks\n\n";

echo "Gutenberg Output:\n";
echo "=================\n";
echo $gutenberg;
echo "\n\n";

echo "========================================\n";
echo "✅ Content Conversion Test Complete!\n";
echo "========================================\n";
