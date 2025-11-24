<?php
/**
 * Database Configuration
 * Smart Delivery Route Optimizer
 *
 * Hostinger MySQL Connection Settings
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Database Configuration
define('DB_HOST', 'localhost');          // Hostinger: localhost
define('DB_NAME', 'your_database_name'); // 데이터베이스 이름
define('DB_USER', 'your_username');      // 데이터베이스 사용자
define('DB_PASS', 'your_password');      // 데이터베이스 비밀번호
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 *
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection failed. Please check configuration.");
        }
    }

    return $pdo;
}

/**
 * Test Database Connection
 *
 * @return array
 */
function testDBConnection(): array {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT 1");
        return [
            'success' => true,
            'message' => 'Database connection successful'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
