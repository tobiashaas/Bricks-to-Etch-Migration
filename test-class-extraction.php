<?php
/**
 * Test CSS Class Extraction
 */

require_once('/var/www/html/wp-load.php');

$settings = array(
    'text' => 'Feature heading',
    '_cssGlobalClasses' => array('bTyScnl9u5j'),
    'tag' => 'h3',
);

echo "Settings:\n";
print_r($settings);
echo "\n";

// Simulate extract_css_classes
$classes = array();

if (!empty($settings['_cssGlobalClasses']) && is_array($settings['_cssGlobalClasses'])) {
    $bricks_classes = get_option('bricks_global_classes', array());
    
    echo "Total Bricks classes: " . count($bricks_classes) . "\n\n";
    
    foreach ($settings['_cssGlobalClasses'] as $bricks_id) {
        echo "Looking for ID: $bricks_id\n";
        
        $found_class = null;
        foreach ($bricks_classes as $bricks_class) {
            if ($bricks_class['id'] === $bricks_id) {
                $found_class = $bricks_class;
                break;
            }
        }
        
        if ($found_class && !empty($found_class['name'])) {
            echo "  ✅ Found: {$found_class['name']}\n";
            $classes[] = $found_class['name'];
        } else {
            echo "  ❌ Not found, using ID\n";
            $classes[] = $bricks_id;
        }
    }
}

echo "\nFinal classes: " . implode(' ', $classes) . "\n";
