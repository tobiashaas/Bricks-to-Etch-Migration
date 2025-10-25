<?php
/**
 * Test Element Converters
 * 
 * Tests the new modular element converter structure
 * 
 * Usage: docker exec b2e-bricks php /tmp/test-element-converters.php
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=== Testing Element Converters ===\n\n";

// Load the new converters
require_once('/var/www/html/wp-content/plugins/etch-fusion-suite/includes/converters/class-element-factory.php');

// Get style map
$style_map = get_option('b2e_style_map', array());
echo "Style map loaded: " . count($style_map) . " entries\n\n";

// Create factory
$factory = new B2E_Element_Factory($style_map);

// Test 1: Container with ul tag
echo "--- Test 1: Container with ul tag ---\n";
$container_element = array(
    'id' => '1bf80b',
    'name' => 'container',
    'label' => 'Feature Grid Sierra (CSS Tab) <ul>',
    'settings' => array(
        '_cssGlobalClasses' => array('bTySculwtsp'),
        'tag' => 'ul'
    )
);

$result = $factory->convert_element($container_element, array());
if ($result) {
    // Check if tagName is set
    if (strpos($result, '"tagName":"ul"') !== false) {
        echo "✅ PASS: tagName is 'ul'\n";
    } else {
        echo "❌ FAIL: tagName is NOT 'ul'\n";
        echo "Result: " . substr($result, 0, 200) . "...\n";
    }
    
    // Check if tag in block is ul
    if (strpos($result, '"tag":"ul"') !== false) {
        echo "✅ PASS: block.tag is 'ul'\n";
    } else {
        echo "❌ FAIL: block.tag is NOT 'ul'\n";
    }
} else {
    echo "❌ FAIL: No result returned\n";
}

echo "\n--- Test 2: Div with li tag ---\n";
$div_element = array(
    'id' => 'b08ea2',
    'name' => 'div',
    'label' => 'Feature Card Sierra <li>',
    'settings' => array(
        '_cssGlobalClasses' => array('bTySctnmzzp'),
        'tag' => 'li'
    )
);

$result = $factory->convert_element($div_element, array());
if ($result) {
    // Check if tagName is set
    if (strpos($result, '"tagName":"li"') !== false) {
        echo "✅ PASS: tagName is 'li'\n";
    } else {
        echo "❌ FAIL: tagName is NOT 'li'\n";
    }
    
    // Check if tag in block is li
    if (strpos($result, '"tag":"li"') !== false) {
        echo "✅ PASS: block.tag is 'li'\n";
    } else {
        echo "❌ FAIL: block.tag is NOT 'li'\n";
    }
} else {
    echo "❌ FAIL: No result returned\n";
}

echo "\n--- Test 3: Heading (h2) ---\n";
$heading_element = array(
    'id' => '84b973',
    'name' => 'heading',
    'settings' => array(
        'text' => 'Your heading',
        'tag' => 'h2',
        '_cssGlobalClasses' => array('bTySctkbujj')
    )
);

$result = $factory->convert_element($heading_element, array());
if ($result) {
    if (strpos($result, 'wp:heading') !== false) {
        echo "✅ PASS: Is heading block\n";
    } else {
        echo "❌ FAIL: Not a heading block\n";
    }
    
    if (strpos($result, '"level":2') !== false) {
        echo "✅ PASS: Level is 2\n";
    } else {
        echo "❌ FAIL: Level is NOT 2\n";
    }
    
    if (strpos($result, 'Your heading') !== false) {
        echo "✅ PASS: Text content is correct\n";
    } else {
        echo "❌ FAIL: Text content is missing\n";
    }
} else {
    echo "❌ FAIL: No result returned\n";
}

echo "\n--- Test 4: Image (figure tag) ---\n";
$image_element = array(
    'id' => 'eeb349',
    'name' => 'image',
    'label' => 'Feature Card Image',
    'settings' => array(
        'image' => array(
            'id' => 123,
            'url' => 'http://example.com/image.jpg'
        ),
        'alt' => 'Test Image',
        '_cssGlobalClasses' => array('bTyScimage')
    )
);

$result = $factory->convert_element($image_element, array());
if ($result) {
    if (strpos($result, '"tag":"figure"') !== false) {
        echo "✅ PASS: tag is 'figure' (not 'img'!)\n";
    } else {
        echo "❌ FAIL: tag is NOT 'figure'\n";
    }
    
    if (strpos($result, 'wp:image') !== false) {
        echo "✅ PASS: Is image block\n";
    } else {
        echo "❌ FAIL: Not an image block\n";
    }
} else {
    echo "❌ FAIL: No result returned\n";
}

echo "\n--- Test 5: Section ---\n";
$section_element = array(
    'id' => 'e38b5e',
    'name' => 'section',
    'label' => 'Feature Section Sierra',
    'settings' => array(
        '_cssGlobalClasses' => array('bTySccocilw'),
        'tag' => 'section'
    )
);

$result = $factory->convert_element($section_element, array());
if ($result) {
    if (strpos($result, '"tag":"section"') !== false) {
        echo "✅ PASS: tag is 'section'\n";
    } else {
        echo "❌ FAIL: tag is NOT 'section'\n";
    }
    
    if (strpos($result, 'data-etch-element":"section"') !== false) {
        echo "✅ PASS: data-etch-element is 'section'\n";
    } else {
        echo "❌ FAIL: data-etch-element is NOT 'section'\n";
    }
    
    if (strpos($result, 'etch-section-style') !== false) {
        echo "✅ PASS: Has etch-section-style\n";
    } else {
        echo "❌ FAIL: Missing etch-section-style\n";
    }
} else {
    echo "❌ FAIL: No result returned\n";
}

echo "\n=== Test Complete ===\n";
