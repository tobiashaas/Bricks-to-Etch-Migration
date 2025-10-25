<?php
/**
 * CSS Converter Test Script
 * Tests the CSS converter with real Bricks data
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Load CSS Converter
require_once('/var/www/html/wp-content/plugins/etch-fusion-suite/includes/css_converter.php');
require_once('/var/www/html/wp-content/plugins/etch-fusion-suite/includes/error_handler.php');

echo "========================================\n";
echo "🎨 CSS Converter Test\n";
echo "========================================\n\n";

// Get Bricks classes
$bricks_classes = get_option('bricks_global_classes', array());

if (empty($bricks_classes)) {
    echo "❌ No Bricks classes found!\n";
    exit(1);
}

echo "✅ Found " . count($bricks_classes) . " Bricks classes\n\n";

// Initialize converter
$converter = new B2E_CSS_Converter();

// Test conversion
echo "Converting Bricks classes to Etch format...\n";
$etch_styles = $converter->convert_bricks_classes_to_etch();

if (empty($etch_styles)) {
    echo "❌ Conversion failed - no styles generated!\n";
    exit(1);
}

echo "✅ Generated " . count($etch_styles) . " Etch styles\n\n";

// Show sample conversions
echo "Sample Conversions:\n";
echo "==================\n\n";

$sample_count = 0;
foreach ($etch_styles as $style_id => $style_data) {
    if ($sample_count >= 5) break;
    
    // Skip element styles
    if (strpos($style_id, 'etch-') === 0) {
        continue;
    }
    
    echo "Style ID: $style_id\n";
    echo "Type: " . $style_data['type'] . "\n";
    echo "Selector: " . $style_data['selector'] . "\n";
    echo "CSS: " . substr($style_data['css'], 0, 200) . (strlen($style_data['css']) > 200 ? '...' : '') . "\n";
    echo "---\n\n";
    
    $sample_count++;
}

// Statistics
echo "Statistics:\n";
echo "===========\n\n";

$stats = array(
    'element' => 0,
    'custom' => 0,
    'class' => 0,
);

foreach ($etch_styles as $style) {
    if (isset($style['type'])) {
        $stats[$style['type']]++;
    }
}

echo "Element styles: " . $stats['element'] . "\n";
echo "Custom styles (CSS variables): " . $stats['custom'] . "\n";
echo "Class styles: " . $stats['class'] . "\n";
echo "\n";

// Test specific properties
echo "Testing Property Conversion:\n";
echo "============================\n\n";

$test_class = null;
foreach ($bricks_classes as $class) {
    if (!empty($class['settings'])) {
        $test_class = $class;
        break;
    }
}

if ($test_class) {
    echo "Test Class: " . $test_class['name'] . "\n";
    echo "Settings: " . json_encode($test_class['settings'], JSON_PRETTY_PRINT) . "\n\n";
    
    $converted = $converter->convert_bricks_class_to_etch($test_class);
    if ($converted) {
        echo "Converted CSS:\n";
        echo $converted['css'] . "\n\n";
    }
}

echo "========================================\n";
echo "✅ CSS Converter Test Complete!\n";
echo "========================================\n";
