<?php
/**
 * Debug Class Names
 * Shows what class names are extracted from Bricks
 */

require_once('/var/www/html/wp-load.php');

$post_id = 25; // Feature Section Frankfurt in Bricks

$bricks_content = get_post_meta($post_id, '_bricks_page_content_2', true);

if (empty($bricks_content)) {
    die("No Bricks content found!\n");
}

echo "Checking class names in Bricks post $post_id:\n\n";

$bricks_classes = get_option('bricks_global_classes', array());

foreach ($bricks_content as $element) {
    if (empty($element['settings']['_cssGlobalClasses'])) {
        continue;
    }
    
    echo "Element: " . ($element['label'] ?? $element['name']) . "\n";
    
    foreach ($element['settings']['_cssGlobalClasses'] as $bricks_id) {
        // Find class
        $found_class = null;
        foreach ($bricks_classes as $bricks_class) {
            if ($bricks_class['id'] === $bricks_id) {
                $found_class = $bricks_class;
                break;
            }
        }
        
        $class_name = $found_class && !empty($found_class['name']) ? $found_class['name'] : $bricks_id;
        
        // Remove ACSS prefix
        $class_name = preg_replace('/^acss_import_/', '', $class_name);
        
        echo "  - Bricks ID: $bricks_id\n";
        echo "    Class Name: $class_name\n";
        echo "    Hex: " . bin2hex($class_name) . "\n";
        
        // Check for special characters
        if (strpos($class_name, '--') !== false) {
            echo "    ✅ Contains '--' (double dash)\n";
        }
        if (strpos($class_name, 'u002d') !== false) {
            echo "    ❌ Contains 'u002d' (escaped!)\n";
        }
        
        echo "\n";
    }
    
    echo "---\n\n";
}
