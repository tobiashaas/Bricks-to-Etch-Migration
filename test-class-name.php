<?php
/**
 * Test Class Name Encoding
 */

require_once('/var/www/html/wp-load.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/css_converter.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/error_handler.php');

$css_converter = new B2E_CSS_Converter();
$etch_styles = $css_converter->convert_bricks_classes_to_etch();

// Find content--feature-max
foreach ($etch_styles as $id => $style) {
    if (strpos($style['selector'], 'feature-max') !== false) {
        echo "Style ID: $id\n";
        echo "Selector: {$style['selector']}\n";
        echo "Selector (repr): " . var_export($style['selector'], true) . "\n";
        echo "Selector (bytes): " . bin2hex($style['selector']) . "\n";
        echo "\n";
        
        // Check for unicode escapes
        if (strpos($style['selector'], 'u002d') !== false) {
            echo "❌ Contains unicode escape!\n";
        } else {
            echo "✅ No unicode escape\n";
        }
        break;
    }
}
