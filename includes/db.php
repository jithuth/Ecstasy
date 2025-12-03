<?php
$host = 'localhost';
$db = 'pirnav_clone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If connection fails, we might be in a setup where the DB doesn't exist yet.
    // For this demo, we'll just die with a message.
    die("Database connection failed: " . $e->getMessage() . ". <br>Please ensure you have created the database 'pirnav_clone' and imported 'database.sql'.");
}

// Helper function to get settings
function get_setting($pdo, $key)
{
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : '';
}
?>