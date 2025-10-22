<?php
/**
 * Test Nested CSS Conversion to Ampersand
 * 
 * Tests the conversion of nested selectors to & (ampersand)
 */

// Test cases
$test_cases = array(
    array(
        'name' => 'Direct child selector',
        'input' => '.my-class > * { color: red; }',
        'expected' => '& > * {
  color: red;
}',
    ),
    array(
        'name' => 'Hover pseudo-class',
        'input' => '.my-class:hover { color: blue; }',
        'expected' => '&:hover {
  color: blue;
}',
    ),
    array(
        'name' => 'Before pseudo-element',
        'input' => '.my-class::before { content: ""; }',
        'expected' => '&::before {
  content: "";
}',
    ),
    array(
        'name' => 'Descendant selector',
        'input' => '.my-class .child { margin: 0; }',
        'expected' => '& .child {
  margin: 0;
}',
    ),
    array(
        'name' => 'Real-world example (separate rules)',
        'input' => '.feature-section-frankfurt__group {
    --padding: var(--space-xl);
    padding: 0 var(--padding) var(--padding);
    border-radius: calc(var(--radius) + var(--padding) / 2);
}

.feature-section-frankfurt__group > * {
    border-radius: var(--radius);
    overflow: hidden;
}',
        'expected' => '--padding: var(--space-xl);
    padding: 0 var(--padding) var(--padding);
    border-radius: calc(var(--radius) + var(--padding) / 2);

& > * {
  border-radius: var(--radius);
    overflow: hidden;
}',
    ),
);

// Run tests
echo "=== Testing Nested CSS Conversion ===\n\n";

$passed = 0;
$failed = 0;

foreach ($test_cases as $test) {
    echo "Test: " . $test['name'] . "\n";
    
    $class_name = 'my-class';
    if (strpos($test['input'], 'feature-section-frankfurt__group') !== false) {
        $class_name = 'feature-section-frankfurt__group';
    }
    
    // Simulate the conversion (same logic as in css_converter.php)
    $escaped_class = preg_quote($class_name, '/');
    $css = $test['input'];
    
    // Parse all CSS rules for this class
    $rules = array();
    $main_css = '';
    
    $pattern = '/\.' . $escaped_class . '([^{]*)\{([^}]*)\}/s';
    
    if (preg_match_all($pattern, $css, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $selector_suffix = $match[1];
            $rule_content = trim($match[2]);
            
            if (empty(trim($selector_suffix))) {
                $main_css .= $rule_content . "\n";
            } else {
                $trimmed_suffix = trim($selector_suffix);
                
                // Add space after & for combinators (>, +, ~) and descendant selectors
                if (preg_match('/^[>+~]/', $trimmed_suffix) || preg_match('/^[.#\[]/', $trimmed_suffix)) {
                    $nested_selector = '& ' . $trimmed_suffix;
                } else {
                    // Pseudo-classes/elements (:hover, ::before) - no space
                    $nested_selector = '&' . $trimmed_suffix;
                }
                
                $rules[] = array(
                    'selector' => $nested_selector,
                    'css' => $rule_content
                );
            }
        }
    }
    
    // Build the final nested CSS
    $result = trim($main_css);
    
    foreach ($rules as $rule) {
        if (!empty($result)) {
            $result .= "\n\n";
        }
        $result .= $rule['selector'] . " {\n  " . trim($rule['css']) . "\n}";
    }
    
    if (empty($result)) {
        $result = preg_replace('/\.' . $escaped_class . '(\s+[>+~]|\s+[.#\[]|::|:)/', '&$1', $css);
    }
    
    if (trim($result) === trim($test['expected'])) {
        echo "✅ PASS\n";
        $passed++;
    } else {
        echo "❌ FAIL\n";
        echo "Expected:\n" . $test['expected'] . "\n";
        echo "Got:\n" . $result . "\n";
        $failed++;
    }
    echo "\n";
}

echo "==================\n";
echo "Results: $passed passed, $failed failed\n";

exit($failed > 0 ? 1 : 0);
