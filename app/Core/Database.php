<?php
/**
 * Database Connection and Query Builder
 *
 * Singleton PDO wrapper with query builder functionality
 * Uses prepared statements for all queries
 */

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private array $config;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->config = $config['database'];

        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            $this->logError('Database connection failed', $e);
            throw new Exception('Database connection failed. Please check configuration.');
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a SELECT query
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return array Result rows
     */
    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('Query failed: ' . $sql, $e, $params);
            throw $e;
        }
    }

    /**
     * Execute a SELECT query and return single row
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    /**
     * Execute INSERT/UPDATE/DELETE query
     *
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('Execute failed: ' . $sql, $e, $params);
            throw $e;
        }
    }

    /**
     * Insert a record and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->execute($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update records with optimistic locking support
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where WHERE conditions
     * @param bool $useVersion Use version column for optimistic locking
     * @return int Number of affected rows
     * @throws Exception If version mismatch (concurrent modification)
     */
    public function update(string $table, array $data, array $where, bool $useVersion = true): int
    {
        // If using version, check if version column exists in data
        if ($useVersion && isset($data['version'])) {
            $expectedVersion = $data['version'];
            unset($data['version']); // Don't update version in SET clause
            $where['version'] = $expectedVersion;
            $data['version'] = $expectedVersion + 1; // Increment version
        }

        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }

        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );

        $affected = $this->execute($sql, $params);

        // If using version and no rows affected, throw concurrent modification error
        if ($useVersion && $affected === 0 && isset($expectedVersion)) {
            throw new Exception('Concurrent modification detected. Please refresh and try again.');
        }

        return $affected;
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = [];
        $params = [];

        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereParts)
        );

        return $this->execute($sql, $params);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Execute callback within transaction
     *
     * @param callable $callback
     * @return mixed Result of callback
     * @throws Exception
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Log database errors
     */
    private function logError(string $message, PDOException $e, array $params = []): void
    {
        error_log(sprintf(
            "[DB ERROR] %s\nError: %s\nParams: %s\nTrace: %s",
            $message,
            $e->getMessage(),
            json_encode($params),
            $e->getTraceAsString()
        ));
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
