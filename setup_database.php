<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Setup for Maternal Healthcare Tracker</h2>";

try {
    // Connect to MySQL server without specifying database
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<p style='color: green;'>✓ Connected to MySQL server</p>";

    // Read and execute the schema file
    $schema_file = __DIR__ . '/database/schema.sql';
    
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: " . $schema_file);
    }

    $sql_content = file_get_contents($schema_file);
    
    // Split the SQL content into individual statements
    $statements = explode(';', $sql_content);
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'DELIMITER') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Some statements might fail if they already exist, that's ok
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
                $errors++;
            }
        }
    }

    echo "<p style='color: green;'>✓ Database setup completed!</p>";
    echo "<p>Executed: $executed statements</p>";
    if ($errors > 0) {
        echo "<p style='color: orange;'>Warnings: $errors</p>";
    }

    // Test the connection with our config
    echo "<h3>Testing Configuration</h3>";
    
    require_once 'config/database.php';
    $test_db = getDBConnection();
    
    // Test query
    $stmt = $test_db->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    
    echo "<p style='color: green;'>✓ Config file connection works!</p>";
    echo "<p>Users in database: " . $result['user_count'] . "</p>";

    // Show default login credentials
    echo "<h3>Default Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / admin123</li>";
    echo "<li><strong>Doctor:</strong> dr_sharma / doctor123</li>";
    echo "<li><strong>Patients:</strong> Register through the registration page</li>";
    echo "</ul>";

    echo "<p style='color: green; font-weight: bold;'>✓ Setup completed successfully!</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go to Application</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL is running</li>";
    echo "<li>MySQL is accessible without password for root user</li>";
    echo "<li>The schema.sql file exists</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
.btn:hover { background: #0056b3; }
</style>
