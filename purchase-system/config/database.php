<?php
/**
 * Database Connection Class
 * Purchase Management System
 *
 * Handles MySQL database connections using PDO
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $conn;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }

    /**
     * Get singleton instance of Database
     *
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     *
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
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

    /**
     * Execute a prepared statement with parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get single row from query
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Get all rows from query
     *
     * @param string $sql SQL query
     * @param array $params Parameters to bind
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data and return last insert ID
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return string Last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->conn->lastInsertId();
    }

    /**
     * Update data in table
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        $setClause = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data from table
     *
     * @param string $table Table name
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
}

?>
