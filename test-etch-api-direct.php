<?php
/**
 * Test Etch API - Direct Method Calls
 * Call API methods directly without HTTP
 */

require_once('/var/www/html/wp-load.php');

echo "================================================\n";
echo "Etch API Direct Test\n";
echo "================================================\n\n";

// Test 1: Direct update_option (current method)
echo "1. Direct update_option (current method)\n";
echo "-----------------------------------\n";

$test_styles = array(
    'test-direct' => array(
        'type' => 'class',
        'selector' => '.test-direct',
        'collection' => 'default',
        'css' => 'color: green;',
        'readonly' => false,
    )
);

update_option('etch_styles', $test_styles);
$saved = get_option('etch_styles', array());

if (isset($saved['test-direct'])) {
    echo "✅ Direct method works\n";
    echo "   Selector: " . $saved['test-direct']['selector'] . "\n";
} else {
    echo "❌ Direct method failed\n";
}

echo "\n\n";

// Test 2: Use Etch's StylesRoutes class directly
echo "2. Using Etch StylesRoutes class directly\n";
echo "-----------------------------------\n";

// Check if class exists
if (class_exists('Etch\RestApi\Routes\StylesRoutes')) {
    echo "✅ StylesRoutes class found!\n";
    
    // Try to instantiate and call update_styles
    try {
        $routes = new Etch\RestApi\Routes\StylesRoutes();
        
        // Create a mock request
        $request = new WP_REST_Request('POST', '/etch-api/styles');
        $request->set_body(json_encode($test_styles, JSON_UNESCAPED_UNICODE));
        
        $response = $routes->update_styles($request);
        
        if (is_wp_error($response)) {
            echo "❌ Error: " . $response->get_error_message() . "\n";
        } else {
            echo "✅ API method called successfully!\n";
            echo "   Response: " . print_r($response->get_data(), true) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  StylesRoutes class not found\n";
    echo "   Available classes:\n";
    
    // List available Etch classes
    $classes = get_declared_classes();
    $etch_classes = array_filter($classes, function($class) {
        return strpos($class, 'Etch') === 0;
    });
    
    foreach (array_slice($etch_classes, 0, 10) as $class) {
        echo "   - $class\n";
    }
}

echo "\n\n";

// Test 3: Check what happens with Unicode
echo "3. Unicode preservation test\n";
echo "-----------------------------------\n";

$unicode_test = array(
    'test-unicode-direct' => array(
        'type' => 'class',
        'selector' => '.content--feature-max',
        'collection' => 'default',
        'css' => 'color: blue;',
        'readonly' => false,
    )
);

update_option('etch_styles', $unicode_test);
$saved = get_option('etch_styles', array());

if (isset($saved['test-unicode-direct'])) {
    $selector = $saved['test-unicode-direct']['selector'];
    echo "Selector: $selector\n";
    echo "Hex: " . bin2hex($selector) . "\n";
    
    if (strpos($selector, '--') !== false) {
        echo "✅ Contains '--' (correct!)\n";
    }
    if (strpos($selector, 'u002d') !== false) {
        echo "❌ Contains 'u002d' (escaped!)\n";
    }
}

echo "\n\n";
echo "================================================\n";
echo "Test Complete!\n";
echo "================================================\n";
