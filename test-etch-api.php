<?php
/**
 * Test Etch REST API
 * Understand how to use the API for migration
 */

require_once('/var/www/html/wp-load.php');

// Set current user to admin for API permissions
wp_set_current_user(1);

echo "================================================\n";
echo "Etch REST API Test\n";
echo "================================================\n\n";
echo "Running as user: " . wp_get_current_user()->user_login . "\n\n";

// Test 1: Get current styles
echo "1. GET /wp-json/etch-api/styles\n";
echo "-----------------------------------\n";

$response = wp_remote_get('http://localhost/wp-json/etch-api/styles');

if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n\n";
} else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (is_array($data)) {
        echo "✅ Success! Got " . count($data) . " styles\n";
        
        // Show first style as example
        if (!empty($data)) {
            $first_key = array_key_first($data);
            $first_style = $data[$first_key];
            echo "\nExample style:\n";
            echo "  Key: $first_key\n";
            echo "  Type: " . ($first_style['type'] ?? 'N/A') . "\n";
            echo "  Selector: " . ($first_style['selector'] ?? 'N/A') . "\n";
            echo "  CSS: " . substr($first_style['css'] ?? '', 0, 50) . "...\n";
        }
    } else {
        echo "⚠️  Unexpected response format\n";
    }
}

echo "\n\n";

// Test 2: Create a test style via API
echo "2. PUT /wp-json/etch-api/styles (Create test style)\n";
echo "-----------------------------------\n";

$test_styles = array(
    'test-api-class' => array(
        'type' => 'class',
        'selector' => '.test-api-class',
        'collection' => 'default',
        'css' => 'color: blue; padding: 10px;',
        'readonly' => false,
    )
);

$response = wp_remote_post('http://localhost/wp-json/etch-api/styles?_method=PUT', array(
    'headers' => array('Content-Type' => 'application/json'),
    'body' => json_encode($test_styles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'method' => 'POST',
));

if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
} else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "Response Code: $code\n";
    echo "Response Body: $body\n";
    
    if ($code == 200) {
        echo "✅ Style created successfully!\n";
        
        // Verify it was saved
        $saved_styles = get_option('etch_styles', array());
        if (isset($saved_styles['test-api-class'])) {
            echo "✅ Verified: Style is in database!\n";
            echo "   Selector: " . $saved_styles['test-api-class']['selector'] . "\n";
            echo "   CSS: " . $saved_styles['test-api-class']['css'] . "\n";
        } else {
            echo "⚠️  Style not found in database\n";
        }
    } else {
        echo "❌ Failed to create style\n";
    }
}

echo "\n\n";

// Test 3: Test with special characters (Unicode test)
echo "3. PUT /wp-json/etch-api/styles (Unicode test)\n";
echo "-----------------------------------\n";

$unicode_test = array(
    'test-unicode' => array(
        'type' => 'class',
        'selector' => '.content--feature-max',  // Double dash!
        'collection' => 'default',
        'css' => 'color: red;',
        'readonly' => false,
    )
);

$response = wp_remote_post('http://localhost/wp-json/etch-api/styles?_method=PUT', array(
    'headers' => array('Content-Type' => 'application/json'),
    'body' => json_encode($unicode_test, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'method' => 'POST',
));

if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
    $saved_styles = get_option('etch_styles', array());
    if (isset($saved_styles['test-unicode'])) {
        $selector = $saved_styles['test-unicode']['selector'];
        echo "✅ Unicode test passed!\n";
        echo "   Selector: $selector\n";
        echo "   Hex: " . bin2hex($selector) . "\n";
        
        if (strpos($selector, '--') !== false) {
            echo "   ✅ Contains '--' (correct!)\n";
        }
        if (strpos($selector, 'u002d') !== false) {
            echo "   ❌ Contains 'u002d' (escaped!)\n";
        }
    }
}

echo "\n\n";

// Test 4: Check post blocks API
echo "4. POST /wp-json/etch-api/post/{id}/blocks\n";
echo "-----------------------------------\n";

// Get a test post
$posts = get_posts(array('post_type' => 'page', 'numberposts' => 1));

if (!empty($posts)) {
    $post_id = $posts[0]->ID;
    echo "Testing with post ID: $post_id\n";
    
    // Try to get current blocks
    $response = wp_remote_get("http://localhost/wp-json/etch-api/post/$post_id/blocks");
    
    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        echo "GET Response Code: $code\n";
        
        if ($code == 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            echo "✅ Blocks API is accessible\n";
            echo "   Response type: " . gettype($data) . "\n";
        }
    }
} else {
    echo "⚠️  No posts found for testing\n";
}

echo "\n\n";
echo "================================================\n";
echo "Test Complete!\n";
echo "================================================\n";
