<?php
/**
 * Test Label Migration
 */

require_once('/var/www/html/wp-load.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/content_parser.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/error_handler.php');

echo "========================================\n";
echo "🔍 Test Label Migration\n";
echo "========================================\n\n";

// Get post 25 (Feature Section Frankfurt)
$post_id = 25;
$bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);

if (empty($bricks_content)) {
    echo "❌ No Bricks content found!\n";
    exit;
}

echo "Total elements: " . count($bricks_content) . "\n\n";

// Parse with content parser
$parser = new B2E_Content_Parser();
$parsed = $parser->parse_bricks_content($post_id);

echo "Parsed elements: " . count($parsed) . "\n\n";

// Check first 5 elements for label
foreach (array_slice($parsed, 0, 5) as $i => $elem) {
    echo "Element $i:\n";
    echo "  name: " . ($elem['name'] ?? 'N/A') . "\n";
    echo "  label: " . ($elem['label'] ?? 'NOT FOUND') . "\n";
    echo "  etch_type: " . ($elem['etch_type'] ?? 'N/A') . "\n";
    echo "\n";
}

echo "========================================\n";
echo "✅ Test Complete\n";
echo "========================================\n";
