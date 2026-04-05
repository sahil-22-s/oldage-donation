#!/usr/bin/env php
<?php
/**
 * ElderCare Database Setup Script
 * Run this script to initialize the PostgreSQL database
 * 
 * Usage: php setup.php
 */

echo "===================================\n";
echo "ElderCare Database Setup\n";
echo "===================================\n\n";

// Database configuration
$db_host = 'localhost';
$db_port = '5432';
$db_user = 'postgres';
$db_password = '';

echo "PostgreSQL Database Setup\n";
echo "Enter your PostgreSQL details below:\n\n";

// Get input
$db_password = readline("PostgreSQL password (default 'postgres'): ");
if (empty($db_password)) {
    $db_password = 'postgres';
}

$db_name = readline("Database name (default 'eldercare'): ");
if (empty($db_name)) {
    $db_name = 'eldercare';
}

echo "\nConnecting to PostgreSQL...\n";

try {
    // Connect to default postgres database to create new database
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=postgres";
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to PostgreSQL\n";
    
    // Check if database exists
    $result = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$db_name'");
    if ($result->fetch()) {
        echo "✓ Database '$db_name' already exists\n";
        $drop = readline("Drop and recreate? (y/n): ");
        if (strtolower($drop) === 'y') {
            $pdo->exec("DROP DATABASE IF EXISTS $db_name");
            echo "✓ Database dropped\n";
        }
    }
    
    // Create database
    if (!$result->fetch() || strtolower($drop) === 'y') {
        $pdo->exec("CREATE DATABASE $db_name");
        echo "✓ Database '$db_name' created\n";
    }
    
    // Connect to new database and run schema
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute database.sql
    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        die("Error: database.sql not found in " . __DIR__ . "\n");
    }
    
    $sql = file_get_contents($sql_file);
    $pdo->exec($sql);
    
    echo "✓ Database schema created\n";
    
    // Update config.php
    $config_file = __DIR__ . '/config.php';
    $config_content = file_get_contents($config_file);
    
    // Update password in config
    $config_content = preg_replace(
        "/define\('DB_PASSWORD', '.*?'\)/",
        "define('DB_PASSWORD', '$db_password')",
        $config_content
    );
    
    $config_content = preg_replace(
        "/define\('DB_NAME', '.*?'\)/",
        "define('DB_NAME', '$db_name')",
        $config_content
    );
    
    file_put_contents($config_file, $config_content);
    
    echo "✓ Configuration updated\n";
    
    echo "\n===================================\n";
    echo "✓ Setup Complete!\n";
    echo "===================================\n";
    echo "\nYour database is ready. You can now:\n";
    echo "1. Start PHP server: php -S localhost:8000\n";
    echo "2. Test API: http://localhost:8000/api.php?action=inventory\n";
    echo "3. Open index.html in your browser\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. PostgreSQL is running\n";
    echo "2. Password is correct\n";
    echo "3. Port 5432 is accessible\n";
    exit(1);
}
?>
