<?php
/**
 * Database Configuration
 */

declare(strict_types=1);

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$name = $_ENV['DB_NAME'] ?? 'creodent_voice';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    return $pdo;
} catch (PDOException $e) {
    // In production, log error and show generic message
    if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
        die('Database connection failed: ' . $e->getMessage());
    }

    // Return null - routes will handle database unavailability
    return null;
}
