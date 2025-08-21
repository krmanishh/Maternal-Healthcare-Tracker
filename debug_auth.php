<?php
echo "<h2>Debugging Auth.php Include</h2>";

echo "<h3>Current Directory Info:</h3>";
echo "<p>__FILE__: " . __FILE__ . "</p>";
echo "<p>__DIR__: " . __DIR__ . "</p>";
echo "<p>getcwd(): " . getcwd() . "</p>";

echo "<h3>Testing Path Resolution:</h3>";

// Test the exact path used in auth.php
$auth_dir = __DIR__ . '/backend/auth/';
$config_path_from_auth = $auth_dir . '../../config/database.php';
$real_config_path = realpath($config_path_from_auth);

echo "<p>Auth directory: " . $auth_dir . "</p>";
echo "<p>Config path from auth: " . $config_path_from_auth . "</p>";
echo "<p>Real config path: " . ($real_config_path ?: 'Cannot resolve') . "</p>";
echo "<p>Config file exists: " . (file_exists($config_path_from_auth) ? "✓ Yes" : "✗ No") . "</p>";

echo "<h3>Testing Auth.php Include:</h3>";

try {
    // Set working directory to simulate index.php context
    echo "<p>Before include - Working directory: " . getcwd() . "</p>";
    
    // Try to include auth.php like index.php does
    require_once 'backend/auth/auth.php';
    echo "<p style='color: green;'>✓ Auth.php included successfully!</p>";
    
    // Test Auth class instantiation
    $auth = new Auth();
    echo "<p style='color: green;'>✓ Auth class instantiated successfully!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
