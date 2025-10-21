<?php
/**
 * Test CSS Variables Extraction
 */

require_once('/var/www/html/wp-load.php');

echo "========================================\n";
echo "🔍 Test CSS Variables Extraction\n";
echo "========================================\n\n";

$bricks_classes = get_option('bricks_global_classes', array());

echo "Total Bricks classes: " . count($bricks_classes) . "\n\n";

$css_variables = array();
$classes_with_vars = 0;

foreach ($bricks_classes as $class) {
    if (!empty($class['settings']['_cssCustom'])) {
        $custom_css = $class['settings']['_cssCustom'];
        
        // Extract CSS variables (--variable-name: value;)
        if (preg_match_all('/--([a-zA-Z0-9_-]+):\s*([^;]+);/', $custom_css, $matches, PREG_SET_ORDER)) {
            $classes_with_vars++;
            
            if ($classes_with_vars <= 3) {
                echo "Class: " . $class['name'] . "\n";
                echo "Variables found: " . count($matches) . "\n";
                
                foreach ($matches as $i => $match) {
                    if ($i >= 3) break;
                    echo "  --{$match[1]}: {$match[2]}\n";
                    $css_variables['--' . $match[1]] = trim($match[2]);
                }
                echo "\n";
            }
        }
    }
}

echo "Total classes with CSS variables: $classes_with_vars\n";
echo "Total unique variables: " . count($css_variables) . "\n\n";

if (!empty($css_variables)) {
    echo "Sample variables:\n";
    $count = 0;
    foreach ($css_variables as $var => $val) {
        if ($count >= 10) break;
        echo "  $var: $val\n";
        $count++;
    }
}

echo "\n========================================\n";
echo "✅ Test Complete\n";
echo "========================================\n";
