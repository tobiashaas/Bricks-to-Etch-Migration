<?php
/**
 * Test Class Encoding
 */

require_once('/var/www/html/wp-load.php');

// Test string
$class_name = 'content--feature-max';

echo "Original: $class_name\n";
echo "Hex: " . bin2hex($class_name) . "\n\n";

// Test json_encode without flags
$json1 = json_encode(array('className' => $class_name));
echo "json_encode (no flags):\n";
echo "$json1\n\n";

// Test json_encode with flags
$json2 = json_encode(array('className' => $class_name), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo "json_encode (with flags):\n";
echo "$json2\n\n";

// Test esc_attr
$escaped = esc_attr($class_name);
echo "esc_attr: $escaped\n";
echo "Hex: " . bin2hex($escaped) . "\n\n";

// Test esc_html
$escaped_html = esc_html($class_name);
echo "esc_html: $escaped_html\n";
echo "Hex: " . bin2hex($escaped_html) . "\n\n";

// Test sanitize_html_class
$sanitized = sanitize_html_class($class_name);
echo "sanitize_html_class: $sanitized\n";
echo "Hex: " . bin2hex($sanitized) . "\n";
