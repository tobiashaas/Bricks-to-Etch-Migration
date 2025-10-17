<?php
// Load WordPress environments and sync API keys

echo "=== SYNCING API KEYS ===\n";

// Load Bricks WordPress
require_once 'wordpress-bricks/wp-load.php';

echo "Bricks Site API Key: ";
$bricks_key = get_option('b2e_api_key', 'NOT_SET');
echo $bricks_key . "\n";

// Load Etch WordPress
require_once 'wordpress-etch/wp-load.php';

echo "Etch Site API Key: ";
$etch_key = get_option('b2e_api_key', 'NOT_SET');
echo $etch_key . "\n";

// If they don't match, sync them
if ($bricks_key !== $etch_key) {
    echo "API Keys don't match! Syncing...\n";
    
    if ($bricks_key !== 'NOT_SET') {
        update_option('b2e_api_key', $bricks_key);
        echo "Updated Etch site with Bricks API key: " . $bricks_key . "\n";
    } else {
        // Generate a new key
        $new_key = wp_generate_password(64, false);
        update_option('b2e_api_key', $new_key);
        echo "Generated new API key: " . $new_key . "\n";
    }
} else {
    echo "API Keys are already synchronized.\n";
}

echo "\n=== DONE ===\n";
?>
