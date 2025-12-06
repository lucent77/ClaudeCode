<?php
/**
 * Creodent Dashboard - Database Connection Manager
 *
 * Handles multi-branch database connections with connection pooling
 */

namespace Creodent\Core;

class Database
{
    private static array $connections = [];
    private static ?array $config = null;

    /**
     * Get database connection for specified branch
     */
    public static function getConnection(string $branch = 'nyc'): \PDO
    {
        $branch = strtolower($branch);

        if (!in_array($branch, ['nyc', 'hv'])) {
            throw new \InvalidArgumentException("Invalid branch: {$branch}");
        }

        if (!isset(self::$connections[$branch])) {
            self::$connections[$branch] = self::createConnection($branch);
        }

        return self::$connections[$branch];
    }

    /**
     * Create new database connection
     */
    private static function createConnection(string $branch): \PDO
    {
        $config = self::getConfig($branch);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        return new \PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    /**
     * Get configuration for branch
     */
    private static function getConfig(string $branch): array
    {
        if (self::$config === null) {
            self::$config = require dirname(__DIR__) . '/config/database.php';
        }

        if (!isset(self::$config[$branch])) {
            throw new \InvalidArgumentException("No configuration for branch: {$branch}");
        }

        return self::$config[$branch];
    }

    /**
     * Execute query on single branch
     */
    public static function query(string $sql, array $params = [], string $branch = 'nyc'): array
    {
        $pdo = self::getConnection($branch);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute query on both branches and merge results
     */
    public static function queryAllBranches(string $sql, array $params = []): array
    {
        $results = [];

        foreach (['nyc', 'hv'] as $branch) {
            $branchResults = self::query($sql, $params, $branch);
            foreach ($branchResults as &$row) {
                $row['_branch'] = strtoupper($branch);
            }
            $results = array_merge($results, $branchResults);
        }

        return $results;
    }

    /**
     * Execute insert/update/delete on single branch
     */
    public static function execute(string $sql, array $params = [], string $branch = 'nyc'): int
    {
        $pdo = self::getConnection($branch);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(string $branch = 'nyc'): string
    {
        return self::getConnection($branch)->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(string $branch = 'nyc'): bool
    {
        return self::getConnection($branch)->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(string $branch = 'nyc'): bool
    {
        return self::getConnection($branch)->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(string $branch = 'nyc'): bool
    {
        return self::getConnection($branch)->rollBack();
    }

    /**
     * Close all connections
     */
    public static function closeAll(): void
    {
        self::$connections = [];
    }
}
