<?php
/**
 * Test CSS Migration
 */

require_once('/var/www/html/wp-load.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/css_converter.php');
require_once('/var/www/html/wp-content/plugins/bricks-etch-migration/includes/error_handler.php');

echo "========================================\n";
echo "🔍 Test CSS Migration\n";
echo "========================================\n\n";

$css_converter = new B2E_CSS_Converter();

echo "Converting Bricks classes to Etch...\n";
$etch_styles = $css_converter->convert_bricks_classes_to_etch();

echo "Total styles: " . count($etch_styles) . "\n\n";

if (empty($etch_styles)) {
    echo "❌ No styles generated!\n";
} else {
    echo "✅ Styles generated!\n\n";
    
    // Show first 5
    $count = 0;
    foreach ($etch_styles as $id => $style) {
        if ($count >= 5) break;
        echo "Style ID: $id\n";
        echo "  Type: " . ($style['type'] ?? 'unknown') . "\n";
        echo "  Selector: " . ($style['selector'] ?? 'unknown') . "\n";
        echo "  CSS (first 100 chars): " . substr($style['css'] ?? '', 0, 100) . "\n\n";
        $count++;
    }
}

echo "\n========================================\n";
echo "✅ Test Complete\n";
echo "========================================\n";
