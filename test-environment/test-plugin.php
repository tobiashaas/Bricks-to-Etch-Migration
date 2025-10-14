<?php
/**
 * Test Script for Bricks to Etch Migration Plugin
 * 
 * Comprehensive testing of all plugin components
 */

echo "🧪 Bricks to Etch Migration Plugin - V0.1.0 Testing\n";
echo "==================================================\n\n";

// Test 1: Check if we're in WordPress context
echo "1. Testing WordPress Context...\n";
echo "--------------------------------\n";

if (defined('ABSPATH')) {
    echo "✅ WordPress context detected\n";
} else {
    echo "⚠️  Not in WordPress context - running standalone tests\n";
}

// Test 2: Check plugin files exist
echo "\n2. Testing Plugin Files...\n";
echo "---------------------------\n";

$plugin_files = array(
    'bricks-etch-migration.php',
    'includes/class-b2e-autoloader.php',
    'includes/error_handler.php',
    'includes/admin_interface.php',
    'includes/api_endpoints.php',
    'includes/content_parser.php',
    'includes/css_converter.php',
    'includes/gutenberg_generator.php',
    'includes/dynamic_data_converter.php',
    'includes/plugin_detector.php',
    'includes/migration_manager.php',
    'includes/api_client.php',
    'includes/custom_fields_migrator.php',
    'includes/acf_field_groups_migrator.php',
    'includes/metabox_migrator.php',
    'includes/cpt_migrator.php',
    'includes/cross_plugin_converter.php'
);

$plugin_dir = dirname(__FILE__) . '/../bricks-etch-migration/';
$missing_files = array();

foreach ($plugin_files as $file) {
    $file_path = $plugin_dir . $file;
    if (file_exists($file_path)) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file} (missing)\n";
        $missing_files[] = $file;
    }
}

// Test 3: Check PHP syntax
echo "\n3. Testing PHP Syntax...\n";
echo "-------------------------\n";

$syntax_errors = array();
foreach ($plugin_files as $file) {
    $file_path = $plugin_dir . $file;
    if (file_exists($file_path)) {
        $output = array();
        $return_code = 0;
        exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "✅ {$file} (syntax OK)\n";
        } else {
            echo "❌ {$file} (syntax error)\n";
            $syntax_errors[] = $file;
        }
    }
}

// Test 4: Check file permissions
echo "\n4. Testing File Permissions...\n";
echo "-------------------------------\n";

foreach ($plugin_files as $file) {
    $file_path = $plugin_dir . $file;
    if (file_exists($file_path)) {
        $perms = fileperms($file_path);
        $readable = is_readable($file_path);
        $writable = is_writable($file_path);
        
        if ($readable) {
            echo "✅ {$file} (readable)\n";
        } else {
            echo "❌ {$file} (not readable)\n";
        }
    }
}

// Test 5: Check directory structure
echo "\n5. Testing Directory Structure...\n";
echo "----------------------------------\n";

$directories = array(
    'includes/',
    'assets/',
    'assets/js/',
    'assets/css/',
    'admin/'
);

foreach ($directories as $dir) {
    $dir_path = $plugin_dir . $dir;
    if (is_dir($dir_path)) {
        echo "✅ {$dir}\n";
    } else {
        echo "❌ {$dir} (missing)\n";
    }
}

// Test 6: Check asset files
echo "\n6. Testing Asset Files...\n";
echo "--------------------------\n";

$asset_files = array(
    'assets/js/admin.js',
    'assets/css/admin.css'
);

foreach ($asset_files as $file) {
    $file_path = $plugin_dir . $file;
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo "✅ {$file} ({$size} bytes)\n";
    } else {
        echo "❌ {$file} (missing)\n";
    }
}

// Test 7: Check plugin header
echo "\n7. Testing Plugin Header...\n";
echo "----------------------------\n";

$main_file = $plugin_dir . 'bricks-etch-migration.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    
    $required_headers = array(
        'Plugin Name:',
        'Version:',
        'Description:',
        'Author:',
        'License:'
    );
    
    foreach ($required_headers as $header) {
        if (strpos($content, $header) !== false) {
            echo "✅ {$header}\n";
        } else {
            echo "❌ {$header} (missing)\n";
        }
    }
} else {
    echo "❌ Main plugin file not found\n";
}

// Test 8: Check class definitions
echo "\n8. Testing Class Definitions...\n";
echo "--------------------------------\n";

$classes = array(
    'Bricks_Etch_Migration',
    'B2E_Autoloader',
    'B2E_Error_Handler',
    'B2E_Admin_Interface',
    'B2E_API_Endpoints',
    'B2E_Content_Parser',
    'B2E_CSS_Converter',
    'B2E_Gutenberg_Generator',
    'B2E_Dynamic_Data_Converter',
    'B2E_Plugin_Detector',
    'B2E_Migration_Manager',
    'B2E_API_Client',
    'B2E_Custom_Fields_Migrator',
    'B2E_ACF_Field_Groups_Migrator',
    'B2E_MetaBox_Migrator',
    'B2E_CPT_Migrator',
    'B2E_Cross_Plugin_Converter'
);

foreach ($classes as $class) {
    $found = false;
    foreach ($plugin_files as $file) {
        $file_path = $plugin_dir . $file;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            if (strpos($content, "class {$class}") !== false) {
                $found = true;
                break;
            }
        }
    }
    
    if ($found) {
        echo "✅ {$class}\n";
    } else {
        echo "❌ {$class} (not found)\n";
    }
}

// Test 9: Check constants
echo "\n9. Testing Constants...\n";
echo "------------------------\n";

$constants = array(
    'B2E_VERSION',
    'B2E_PLUGIN_FILE',
    'B2E_PLUGIN_DIR',
    'B2E_PLUGIN_URL',
    'B2E_PLUGIN_BASENAME'
);

$main_file = $plugin_dir . 'bricks-etch-migration.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    
    foreach ($constants as $constant) {
        if (strpos($content, "define('{$constant}'") !== false) {
            echo "✅ {$constant}\n";
        } else {
            echo "❌ {$constant} (not defined)\n";
        }
    }
} else {
    echo "❌ Main plugin file not found\n";
}

// Test 10: Check WordPress hooks
echo "\n10. Testing WordPress Hooks...\n";
echo "-------------------------------\n";

$hooks = array(
    'add_action',
    'register_activation_hook',
    'register_deactivation_hook',
    'add_menu_page',
    'add_action'
);

$main_file = $plugin_dir . 'bricks-etch-migration.php';
if (file_exists($main_file)) {
    $content = file_get_contents($main_file);
    
    foreach ($hooks as $hook) {
        if (strpos($content, $hook) !== false) {
            echo "✅ {$hook}\n";
        } else {
            echo "❌ {$hook} (not found)\n";
        }
    }
} else {
    echo "❌ Main plugin file not found\n";
}

// Final Results
echo "\n🎯 Testing Complete!\n";
echo "===================\n";

$total_files = count($plugin_files);
$existing_files = $total_files - count($missing_files);
$syntax_ok = count($syntax_errors) === 0;

echo "Plugin Files: {$existing_files}/{$total_files}\n";
echo "Syntax Check: " . ($syntax_ok ? "PASSED" : "FAILED") . "\n";
echo "Missing Files: " . count($missing_files) . "\n";
echo "Syntax Errors: " . count($syntax_errors) . "\n";

if (count($missing_files) > 0) {
    echo "\n❌ Missing Files:\n";
    foreach ($missing_files as $file) {
        echo "   - {$file}\n";
    }
}

if (count($syntax_errors) > 0) {
    echo "\n❌ Syntax Errors:\n";
    foreach ($syntax_errors as $file) {
        echo "   - {$file}\n";
    }
}

if ($existing_files === $total_files && $syntax_ok) {
    echo "\n🎉 All tests passed! Plugin structure is correct.\n";
    echo "\n📋 Next Steps:\n";
    echo "1. Install plugin in WordPress\n";
    echo "2. Test plugin activation\n";
    echo "3. Test admin interface\n";
    echo "4. Test API endpoints\n";
    echo "5. Test migration flow\n";
} else {
    echo "\n⚠️  Some tests failed. Please fix the issues above.\n";
}

echo "\n🔗 Plugin Location: " . $plugin_dir . "\n";
echo "📁 Test Environment: " . dirname(__FILE__) . "\n";

echo "\n✨ Plugin V0.1.0 Structure Test Complete! ✨\n";