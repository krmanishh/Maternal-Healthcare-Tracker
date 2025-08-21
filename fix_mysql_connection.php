<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>MySQL Connection Troubleshooter</h2>";

$configs = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'desc' => 'Root with no password (localhost)'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'desc' => 'Root with no password (127.0.0.1)'],
    ['host' => 'localhost:3306', 'user' => 'root', 'pass' => '', 'desc' => 'Root via localhost:3306'],
    ['host' => '127.0.0.1:3306', 'user' => 'root', 'pass' => '', 'desc' => 'Root via 127.0.0.1:3306'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root', 'desc' => 'Root with password "root"'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'mysql', 'desc' => 'Root with password "mysql"'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'admin', 'desc' => 'Root with password "admin"'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'options' => [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false], 'desc' => 'Root no password with SSL disabled'],
];

$working_config = null;

foreach ($configs as $config) {
    echo "<h3>Testing: " . $config['desc'] . "</h3>";
    
    try {
        $dsn = "mysql:host=" . $config['host'] . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Test a simple query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        
        echo "<p style='color: green;'>✓ SUCCESS! MySQL Version: " . $result['version'] . "</p>";
        
        $working_config = $config;
        break; // Stop testing once we find a working config
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

if ($working_config) {
    echo "<h2 style='color: green;'>✓ Found Working Configuration!</h2>";
    echo "<p><strong>Host:</strong> " . $working_config['host'] . "</p>";
    echo "<p><strong>Username:</strong> " . $working_config['user'] . "</p>";
    echo "<p><strong>Password:</strong> " . (empty($working_config['pass']) ? "(no password)" : "'" . $working_config['pass'] . "'") . "</p>";
    
    // Update the database configuration file
    echo "<h3>Updating Configuration File</h3>";
    
    $new_config = "<?php
// Database configuration - Auto-updated by connection fixer
define('DB_HOST', '{$working_config['host']}');
define('DB_USERNAME', '{$working_config['user']}');
define('DB_PASSWORD', '{$working_config['pass']}');
define('DB_NAME', 'maternal_healthcare');

class Database {
    private static \$instance = null;
    private \$connection;
    
    private function __construct() {
        try {
            \$this->connection = new PDO(
                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
                DB_USERNAME,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException \$e) {
            die(\"Database connection failed: \" . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance == null) {
            self::\$instance = new Database();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->connection;
    }
}

// Function to get database connection
function getDBConnection() {
    return Database::getInstance()->getConnection();
}
?>";

    $config_file = __DIR__ . '/config/database.php';
    if (file_put_contents($config_file, $new_config)) {
        echo "<p style='color: green;'>✓ Configuration file updated successfully!</p>";
        
        // Now try the database setup
        echo "<h3>Testing Database Setup</h3>";
        echo "<p><a href='setup_database.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Database Setup Now</a></p>";
        
    } else {
        echo "<p style='color: red;'>✗ Failed to update configuration file. Please update manually.</p>";
    }
    
} else {
    echo "<h2 style='color: red;'>✗ No Working Configuration Found</h2>";
    echo "<p>Please try the following solutions:</p>";
    echo "<ol>";
    echo "<li><strong>Reset MySQL password via XAMPP:</strong>";
    echo "<ul>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Shell' button</li>";
    echo "<li>Run: <code>mysqladmin -u root password \"\"</code></li>";
    echo "</ul></li>";
    echo "<li><strong>Restart XAMPP MySQL service</strong></li>";
    echo "<li><strong>Check if another MySQL service is running</strong></li>";
    echo "</ol>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
hr { margin: 20px 0; border: 1px solid #ddd; }
</style>
