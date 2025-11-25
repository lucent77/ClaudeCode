<?php
/**
 * Magic Rx Scanner - Database Class
 *
 * PDO-based database connection and query handling with security best practices
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $inTransaction = false;

    /**
     * Private constructor for Singleton pattern
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }

    /**
     * Get Database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a SELECT query
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array Query results
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError("SELECT query failed: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database query failed.");
        }
    }

    /**
     * Execute a SELECT query and return a single row
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return array|null Single row or null
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            $this->logError("SELECT ONE query failed: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database query failed.");
        }
    }

    /**
     * Execute an INSERT query
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Last insert ID
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->logError("INSERT query failed: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database insert failed.");
        }
    }

    /**
     * Execute an UPDATE query
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function update($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("UPDATE query failed: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database update failed.");
        }
    }

    /**
     * Execute a DELETE query
     *
     * @param string $query SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return int Number of affected rows
     */
    public function delete($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError("DELETE query failed: " . $e->getMessage() . " | Query: " . $query);
            throw new Exception("Database delete failed.");
        }
    }

    /**
     * Begin a database transaction
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->connection->beginTransaction();
            $this->inTransaction = true;
        }
    }

    /**
     * Commit the current transaction
     */
    public function commit() {
        if ($this->inTransaction) {
            $this->connection->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * Rollback the current transaction
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->connection->rollBack();
            $this->inTransaction = false;
        }
    }

    /**
     * Log errors to file
     *
     * @param string $message Error message
     */
    private function logError($message) {
        if (ENABLE_LOGGING) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] ERROR: {$message}" . PHP_EOL;
            error_log($logMessage, 3, LOG_FILE);
        }
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
