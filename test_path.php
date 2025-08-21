<?php
// Test path resolution from different locations

echo "<h2>Path Resolution Test</h2>";

// Test from root directory
echo "<h3>From root directory (/maternal_healthcare_tracker/):</h3>";
$root_path = __DIR__ . '/config/database.php';
echo "<p>Path: " . $root_path . "</p>";
echo "<p>Exists: " . (file_exists($root_path) ? "✓ Yes" : "✗ No") . "</p>";

// Test from backend/auth directory (simulating the auth.php location)
echo "<h3>From backend/auth directory:</h3>";
$auth_path = __DIR__ . '/backend/auth/';
$config_from_auth = $auth_path . '../../config/database.php';
$real_path = realpath($config_from_auth);
echo "<p>Relative path from auth: ../../config/database.php</p>";
echo "<p>Full constructed path: " . $config_from_auth . "</p>";
echo "<p>Real path: " . ($real_path ?: 'Cannot resolve') . "</p>";
echo "<p>Exists: " . (file_exists($config_from_auth) ? "✓ Yes" : "✗ No") . "</p>";

// List contents of config directory
echo "<h3>Contents of config directory:</h3>";
$config_dir = __DIR__ . '/config/';
if (is_dir($config_dir)) {
    $files = scandir($config_dir);
    echo "<ul>";
    foreach($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>Config directory not found!</p>";
}

?>
