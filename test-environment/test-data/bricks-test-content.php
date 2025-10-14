<?php
/**
 * Test Data for Bricks to Etch Migration Plugin
 * 
 * Sample Bricks content structure for testing
 */

// Sample Bricks page content (serialized array)
$bricks_test_content = array(
    array(
        'id' => '953e49',
        'name' => 'section',
        'parent' => 0,
        'children' => array('a1b2c3'),
        'settings' => array(
            '_cssClasses' => 'hero-section',
            '_cssGlobalClasses' => array('container', 'spacing-large'),
            'background' => array(
                'color' => '#f8f9fa',
                'image' => array(
                    'url' => 'https://example.com/hero-bg.jpg',
                    'size' => 'cover',
                    'position' => 'center center',
                    'repeat' => 'no-repeat'
                )
            ),
            'spacing' => array(
                'padding' => '60px 20px',
                'margin' => '0'
            )
        ),
        'content' => ''
    ),
    array(
        'id' => 'a1b2c3',
        'name' => 'container',
        'parent' => '953e49',
        'children' => array('d4e5f6', 'g7h8i9'),
        'settings' => array(
            '_cssClasses' => 'hero-container',
            '_cssGlobalClasses' => array('max-width-1200'),
            'spacing' => array(
                'padding' => '40px 20px'
            )
        ),
        'content' => ''
    ),
    array(
        'id' => 'd4e5f6',
        'name' => 'heading',
        'parent' => 'a1b2c3',
        'children' => array(),
        'settings' => array(
            'tag' => 'h1',
            '_cssClasses' => 'hero-title',
            '_cssGlobalClasses' => array('text-center', 'font-weight-bold'),
            'typography' => array(
                'fontSize' => '48px',
                'fontWeight' => '700',
                'color' => '#333333',
                'textAlign' => 'center',
                'lineHeight' => '1.2'
            )
        ),
        'content' => 'Welcome to Our Amazing Website'
    ),
    array(
        'id' => 'g7h8i9',
        'name' => 'text',
        'parent' => 'a1b2c3',
        'children' => array(),
        'settings' => array(
            '_cssClasses' => 'hero-description',
            '_cssGlobalClasses' => array('text-center', 'text-muted'),
            'typography' => array(
                'fontSize' => '18px',
                'color' => '#666666',
                'textAlign' => 'center',
                'lineHeight' => '1.6'
            )
        ),
        'content' => 'This is a sample description with {post_title} and {acf:hero_subtitle} dynamic data.'
    )
);

// Sample Bricks global classes
$bricks_global_classes = array(
    array(
        'id' => 'container',
        'settings' => array(
            'background' => array(
                'color' => '#ffffff'
            ),
            'spacing' => array(
                'padding' => '20px',
                'margin' => '0 auto'
            ),
            'border' => array(
                'radius' => '8px',
                'width' => '1px',
                'style' => 'solid',
                'color' => '#e0e0e0'
            )
        )
    ),
    array(
        'id' => 'spacing-large',
        'settings' => array(
            'spacing' => array(
                'padding' => '60px 0',
                'margin' => '0'
            )
        )
    )
);

// Export test data
return array(
    'bricks_content' => $bricks_test_content,
    'bricks_global_classes' => $bricks_global_classes
);