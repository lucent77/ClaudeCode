<?php
/**
 * Database Configuration
 * LifeMandalart - Self Management Web Service
 *
 * Update these settings according to your Hostinger database credentials
 */

// Prevent direct access
if (!defined('LIFE_MANDALART')) {
    die('Direct access not permitted');
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'life_mandalart');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// PDO options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

/**
 * Database Connection Class
 */
class Database {
    private static ?PDO $instance = null;

    /**
     * Get database connection instance (Singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);

            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please try again later.");
            }
        }

        return self::$instance;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Execute a query with parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get single row
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Get all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Insert and return last insert ID
     */
    public static function insert(string $sql, array $params = []): int {
        self::query($sql, $params);
        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update and return affected rows count
     */
    public static function update(string $sql, array $params = []): int {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Delete and return affected rows count
     */
    public static function delete(string $sql, array $params = []): int {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool {
        return self::getInstance()->rollBack();
    }
}
