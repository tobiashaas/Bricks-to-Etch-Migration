<?php
/**
 * Test Batch Migration - One post at a time
 */

require_once('/var/www/html/wp-load.php');

echo "Testing Batch Migration...\n\n";

// Get all Bricks pages
$pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1,
    'meta_key' => '_bricks_page_content_2',
));

echo "Found " . count($pages) . " Bricks pages\n\n";

// Set target URL (use localhost:8081 from inside container)
update_option('b2e_settings', array(
    'target_url' => 'http://localhost:8081',
    'api_key' => 'test'
), false);

// Migrate each page
$migration_manager = new B2E_Migration_Manager();

foreach ($pages as $index => $post) {
    echo "[$index/" . count($pages) . "] Migrating: {$post->post_title} (ID: {$post->ID})\n";
    
    $start_time = microtime(true);
    $result = $migration_manager->migrate_single_post($post);
    $duration = round(microtime(true) - $start_time, 2);
    
    if (is_wp_error($result)) {
        echo "   ❌ Failed: " . $result->get_error_message() . "\n";
    } else {
        echo "   ✅ Success! ({$duration}s)\n";
    }
}

echo "\n✅ Batch migration complete!\n";
