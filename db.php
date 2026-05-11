<?php
// db.php - Database Connection with Enhanced Security
// Load environment variables from .env file
require_once __DIR__ . '/config.php';

// Get database credentials from environment variables
$host = env('DB_HOST', 'localhost');
$db   = env('DB_NAME', 'aqa_admission_db');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Additional security settings
    PDO::ATTR_STRINGIFY_FETCHES  => false,
    PDO::ATTR_PERSISTENT         => false, // Disable persistent connections for security
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set timezone for consistent timestamp handling
    $pdo->exec("SET time_zone = '+08:00'");
    
    // Set SQL mode for strict data validation
    $pdo->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    
} catch (\PDOException $e) {
    // Log error instead of displaying it
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Show generic error in production, detailed in development
    if (env('APP_DEBUG', false)) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    } else {
        throw new \PDOException("Database connection failed. Please try again later.", (int)$e->getCode());
    }
}
?>