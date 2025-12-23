<?php
/**
 * URL Collector - Database Connection (PDO)
 *
 * Singleton pattern for database connection
 * Uses prepared statements to prevent SQL injection
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Initialize with configuration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get PDO instance (singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                throw new RuntimeException('Database not initialized. Call Database::init() first.');
            }

            $cfg = self::$config;
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'] ?? 3306,
                $cfg['name'],
                $cfg['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ];

            try {
                self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Get PDO instance (alias)
     */
    public static function pdo(): PDO
    {
        return self::getInstance();
    }

    /**
     * Execute a query and return statement
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch single value
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    /**
     * Insert and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::quoteIdentifier($table),
            implode(', ', array_map([self::class, 'quoteIdentifier'], $columns)),
            implode(', ', $placeholders)
        );

        self::query($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Update rows
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = self::quoteIdentifier($column) . ' = ?';
            $values[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            self::quoteIdentifier($table),
            implode(', ', $setParts),
            $where
        );

        $stmt = self::query($sql, array_merge($values, $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Delete rows
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            self::quoteIdentifier($table),
            $where
        );

        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Check if a record exists
     */
    public static function exists(string $table, string $where, array $params = []): bool
    {
        $sql = sprintf(
            'SELECT 1 FROM %s WHERE %s LIMIT 1',
            self::quoteIdentifier($table),
            $where
        );

        return self::fetchValue($sql, $params) !== false;
    }

    /**
     * Count records
     */
    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s',
            self::quoteIdentifier($table),
            $where
        );

        return (int) self::fetchValue($sql, $params);
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::pdo()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::pdo()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::pdo()->rollBack();
    }

    /**
     * Quote identifier (table/column name)
     */
    public static function quoteIdentifier(string $identifier): string
    {
        // Only allow alphanumeric and underscore
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException('Invalid identifier: ' . $identifier);
        }
        return '`' . $identifier . '`';
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}
