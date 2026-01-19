<?php
// Test de connexion PDO utilisant les paramètres de .env
$host = '127.0.0.1';
$port = 3306;
$db   = 'larawaze';
$user = 'root';
$pass = '';
$dsn = "mysql:host={$host};port={$port};dbname={$db}";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "DB OK\n";
} catch (PDOException $e) {
    echo "DB ERR: " . $e->getMessage() . "\n";
}

echo 'ENV DB_PASSWORD: ' . (getenv('DB_PASSWORD') === false ? '[not set]' : (getenv('DB_PASSWORD') === '' ? '[empty]' : getenv('DB_PASSWORD'))) . PHP_EOL;
