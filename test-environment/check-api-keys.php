<?php
// Check API keys on both sites

echo "=== CHECKING API KEYS ===\n";

// Check Bricks site
echo "Bricks Site (localhost:8080):\n";
$bricks_config = file_get_contents('wordpress-bricks/wp-config.php');
if (preg_match('/DB_NAME.*?[\'"](\w+)[\'"]/', $bricks_config, $matches)) {
    $db_name = $matches[1];
    echo "DB Name: $db_name\n";
    
    // Try to connect to database and check option
    $pdo = new PDO("mysql:host=localhost:8080;dbname=$db_name", 'wordpress', 'wordpress');
    $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'b2e_api_key'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "API Key: " . ($result['option_value'] ?? 'NOT_SET') . "\n";
} else {
    echo "Could not find DB name\n";
}

echo "\n";

// Check Etch site
echo "Etch Site (localhost:8081):\n";
$etch_config = file_get_contents('wordpress-etch/wp-config.php');
if (preg_match('/DB_NAME.*?[\'"](\w+)[\'"]/', $etch_config, $matches)) {
    $db_name = $matches[1];
    echo "DB Name: $db_name\n";
    
    // Try to connect to database and check option
    $pdo = new PDO("mysql:host=localhost:8081;dbname=$db_name", 'wordpress', 'wordpress');
    $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'b2e_api_key'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "API Key: " . ($result['option_value'] ?? 'NOT_SET') . "\n";
} else {
    echo "Could not find DB name\n";
}

echo "\n=== DONE ===\n";
?>
