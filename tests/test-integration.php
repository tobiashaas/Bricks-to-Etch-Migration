<?php
/**
 * Test Integration of Modular Converters
 * 
 * Tests if the new element converters work with the migration
 * 
 * Usage: docker exec b2e-bricks php /tmp/test-integration.php
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Testing Integration of Modular Converters ===\n\n";

// Load Gutenberg Generator
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/gutenberg_generator.php');

// Get Post 10 (Feature Section Sierra)
$post_id = 10;
$bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);

if (empty($bricks_content)) {
    echo "❌ FAIL: No Bricks content found for post {$post_id}\n";
    exit(1);
}

echo "✅ Bricks content loaded: " . count($bricks_content) . " elements\n\n";

// Create Gutenberg Generator
$generator = new B2E_Gutenberg_Generator();

// Generate Gutenberg blocks
echo "--- Generating Gutenberg blocks ---\n";
$gutenberg_html = $generator->generate_gutenberg_blocks($bricks_content);

if (empty($gutenberg_html)) {
    echo "❌ FAIL: No Gutenberg HTML generated\n";
    exit(1);
}

echo "✅ Gutenberg HTML generated: " . strlen($gutenberg_html) . " bytes\n\n";

// Check for v0.5.0 marker
if (strpos($gutenberg_html, 'v0.5.0: Modular Element Converters') !== false) {
    echo "✅ PASS: v0.5.0 marker found\n";
} else {
    echo "❌ FAIL: v0.5.0 marker NOT found\n";
}

// Check for ul tag
if (strpos($gutenberg_html, '"tagName":"ul"') !== false) {
    echo "✅ PASS: Found tagName:ul\n";
} else {
    echo "❌ FAIL: tagName:ul NOT found\n";
}

// Check for li tag
if (strpos($gutenberg_html, '"tagName":"li"') !== false) {
    echo "✅ PASS: Found tagName:li\n";
} else {
    echo "❌ FAIL: tagName:li NOT found\n";
}

// Check for block.tag ul
if (strpos($gutenberg_html, '"tag":"ul"') !== false) {
    echo "✅ PASS: Found block.tag:ul\n";
} else {
    echo "❌ FAIL: block.tag:ul NOT found\n";
}

// Check for block.tag li
if (strpos($gutenberg_html, '"tag":"li"') !== false) {
    echo "✅ PASS: Found block.tag:li\n";
} else {
    echo "❌ FAIL: block.tag:li NOT found\n";
}

// Check for section tag
if (strpos($gutenberg_html, '"tag":"section"') !== false) {
    echo "✅ PASS: Found block.tag:section\n";
} else {
    echo "❌ FAIL: block.tag:section NOT found\n";
}

// Check for CSS classes in attributes
if (strpos($gutenberg_html, '"class":"fr-feature-grid-sierra"') !== false) {
    echo "✅ PASS: Found CSS class in attributes\n";
} else {
    echo "❌ FAIL: CSS class NOT found in attributes\n";
}

echo "\n--- Sample Output ---\n";
echo substr($gutenberg_html, 0, 500) . "...\n";

echo "\n=== Test Complete ===\n";
