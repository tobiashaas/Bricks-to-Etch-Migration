<?php
/**
 * Test clean_custom_css function
 */

$custom_css = '.fr-feature-card-sierra {
  max-inline-size: 100%;
}

@media (min-width: 768px) {
  
	/* Grid Alternating */  
  .fr-feature-card-sierra:nth-child(odd) > *:last-child {
	  order: -1;
    justify-content: flex-end;
  }
	.fr-feature-card-sierra:nth-child(even) > *:last-child {
	  order: 0;
    justify-content: flex-start;
  }  
  
  .fr-feature-card-sierra > *:nth-child(odd) {
    grid-column: span var(--features-span);
  }
  
  .fr-feature-card-sierra > *:nth-child(even) {
    grid-column: span calc(12 - var(--features-span));
  }

  .fr-feature-card-sierra > *:last-child * {
    min-width: calc((50vw - (var(--features-span) - 6) * (var(--content-width) / 12)) - (var(--grid-card-gap) / 4))    
  }
  
}';

$class_name = 'fr-feature-card-sierra';

echo "Original CSS:\n";
echo $custom_css;
echo "\n\n";
echo "========================================\n\n";

// Simulate clean_custom_css
$pattern = '/\.' . preg_quote($class_name, '/') . '\s*\{\s*([^{}]*(?:\{[^}]*\}[^{}]*)*)\s*\}/s';

$cleaned_parts = array();

if (preg_match_all($pattern, $custom_css, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $content = trim($match[1]);
        
        // Check if content contains media queries or other nested rules
        if (preg_match('/@media|@supports|@container/', $content)) {
            // Keep media queries as-is
            $cleaned_parts[] = $content;
        } else if (preg_match('/^\s*\.' . preg_quote($class_name, '/') . '\s/', $content)) {
            // Skip if it's another nested .class-name (redundant)
            continue;
        } else {
            // Regular CSS properties - add them
            $cleaned_parts[] = $content;
        }
    }
}

echo "Cleaned CSS:\n";
echo implode("\n", $cleaned_parts);
echo "\n";
