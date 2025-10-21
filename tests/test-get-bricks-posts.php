<?php
/**
 * Test script to check what ajax_get_bricks_posts returns
 * 
 * Usage: docker exec b2e-bricks wp eval-file /var/www/html/wp-content/plugins/bricks-etch-migration/../../../../../../tests/test-get-bricks-posts.php --allow-root
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Testing ajax_get_bricks_posts ===\n\n";

// Get content parser
$content_parser = new B2E_Content_Parser();

$bricks_posts = $content_parser->get_bricks_posts();
$gutenberg_posts = $content_parser->get_gutenberg_posts();
$media = $content_parser->get_media();

echo "Bricks Posts: " . count($bricks_posts) . "\n";
foreach ($bricks_posts as $post) {
    echo "  - ID: {$post->ID}, Title: {$post->post_title}\n";
}

echo "\nGutenberg Posts: " . count($gutenberg_posts) . "\n";
foreach ($gutenberg_posts as $post) {
    echo "  - ID: {$post->ID}, Title: {$post->post_title}\n";
}

echo "\nMedia: " . count($media) . "\n";

echo "\n=== Test Complete ===\n";
