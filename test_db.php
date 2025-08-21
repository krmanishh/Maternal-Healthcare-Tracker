<?php
// Test database connection
require_once 'config/database.php';

try {
    echo "<h2>Testing Database Connection</h2>";
    
    $db = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result['test'] == 1) {
        echo "<p style='color: green;'>✓ Database query test successful!</p>";
    }
    
    // Check if tables exist
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    echo "<h3>Available Tables:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table[array_keys($table)[0]] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}
?>
