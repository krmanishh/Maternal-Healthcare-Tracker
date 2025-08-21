<?php
echo "<h2>Direct Database Connection Test</h2>";

// Test database connection without using the config file first
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=maternal_healthcare;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "<p style='color: green;'>✓ Direct database connection successful!</p>";
    
    // Test a query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "<p style='color: green;'>✓ Database query works!</p>";
    }
    
    // Check if database exists and has tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    if (count($tables) > 0) {
        echo "<h3>Tables found in database:</h3><ul>";
        foreach ($tables as $table) {
            echo "<li>" . $table[array_keys($table)[0]] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ Database exists but has no tables. You may need to run the setup script.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    
    // Try to create the database
    try {
        echo "<p>Attempting to create database...</p>";
        $db = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
        $db->exec("CREATE DATABASE IF NOT EXISTS maternal_healthcare");
        echo "<p style='color: green;'>✓ Database 'maternal_healthcare' created!</p>";
        echo "<p>Now you need to run the setup script to create tables.</p>";
    } catch (PDOException $e2) {
        echo "<p style='color: red;'>✗ Failed to create database: " . $e2->getMessage() . "</p>";
    }
}

echo "<h3>Now testing with config file:</h3>";

try {
    require_once 'config/database.php';
    $db = getDBConnection();
    echo "<p style='color: green;'>✓ Config file database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Config file connection failed: " . $e->getMessage() . "</p>";
}
?>
