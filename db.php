<?php
// db.php
$host = '127.0.0.1';
$db   = 'restaurant';
$user = 'root';     // XAMPP default
$pass = '';         // XAMPP default
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    die('DB connection failed: ' . $e->getMessage());
}
