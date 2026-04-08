<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP is working</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Name: " . ($_SERVER["SERVER_NAME"] ?? 'not set') . "</p>";
echo "<p>Server Port: " . ($_SERVER["SERVER_PORT"] ?? 'not set') . "</p>";

// Test MySQL connection
echo "<h3>Database Check</h3>";
$host     = getenv('MYSQLHOST') ?: 'localhost';
$user     = getenv('MYSQLUSER') ?: 'daotaola6686_db';
$password = getenv('MYSQLPASSWORD') ?: 'localpass123';
$dbname   = getenv('MYSQLDATABASE') ?: 'daotaola6686_db';
$port     = getenv('MYSQLPORT') ?: 3306;

echo "<p>Host: $host, User: $user, DB: $dbname, Port: $port</p>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>DB CONNECTION OK</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema='$dbname'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Tables in DB: " . $row['cnt'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>DB ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check loaded PHP extensions
echo "<h3>Key PHP Extensions</h3>";
$exts = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'curl', 'json'];
foreach ($exts as $ext) {
    $loaded = extension_loaded($ext) ? 'YES' : 'NO';
    echo "<p>$ext: $loaded</p>";
}
