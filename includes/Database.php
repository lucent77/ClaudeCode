<?php
/**
 * Database Connection Manager with PDO
 * Implements Singleton pattern and connection pooling
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private int $transactionLevel = 0;

    private function __construct()
    {
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_PERSISTENT => false
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }

    /**
     * Execute a query and return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single column value
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }

    /**
     * Insert a record and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        $this->query($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update records with optimistic locking support
     */
    public function update(string $table, array $data, array $where, ?int $expectedVersion = null): int
    {
        $setClauses = [];
        $params = [];

        // Handle version increment for optimistic locking
        if ($expectedVersion !== null) {
            $data['version'] = $expectedVersion + 1;
            $where['version'] = $expectedVersion;
        }

        foreach ($data as $column => $value) {
            $setClauses[] = "`$column` = ?";
            $params[] = $value;
        }

        $whereClauses = [];
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $whereClauses[] = "`$column` IN ($placeholders)";
                $params = array_merge($params, $value);
            } else {
                $whereClauses[] = "`$column` = ?";
                $params[] = $value;
            }
        }

        $sql = "UPDATE `$table` SET " . implode(', ', $setClauses) .
               " WHERE " . implode(' AND ', $whereClauses);

        $stmt = $this->query($sql, $params);
        $affectedRows = $stmt->rowCount();

        // If version was specified but no rows were affected, throw concurrency exception
        if ($expectedVersion !== null && $affectedRows === 0) {
            throw new ConcurrencyException('The record was modified by another user. Please refresh and try again.');
        }

        return $affectedRows;
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $where): int
    {
        $whereClauses = [];
        $params = [];

        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $whereClauses[] = "`$column` IN ($placeholders)";
                $params = array_merge($params, $value);
            } else {
                $whereClauses[] = "`$column` = ?";
                $params[] = $value;
            }
        }

        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $whereClauses);
        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Begin a transaction (supports nested transactions)
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionLevel === 0) {
            $result = $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec("SAVEPOINT trans_{$this->transactionLevel}");
            $result = true;
        }
        $this->transactionLevel++;
        return $result;
    }

    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        $this->transactionLevel--;
        if ($this->transactionLevel === 0) {
            return $this->pdo->commit();
        }
        return true;
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        $this->transactionLevel--;
        if ($this->transactionLevel === 0) {
            return $this->pdo->rollBack();
        }
        $this->pdo->exec("ROLLBACK TO SAVEPOINT trans_{$this->transactionLevel}");
        return true;
    }

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(): bool
    {
        return $this->transactionLevel > 0;
    }

    /**
     * Execute a callback within a transaction
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
     * Get the last inserted ID
     */
    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Quote a value for use in a query
     */
    public function quote($value): string
    {
        return $this->pdo->quote($value);
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
        throw new Exception('Cannot unserialize singleton');
    }
}

/**
 * Custom exception for concurrency conflicts
 */
class ConcurrencyException extends Exception {}
