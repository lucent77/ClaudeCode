<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * User Repository
 *
 * Handles all database operations for users
 */
class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM users WHERE id = :id LIMIT 1';
        return $this->db->queryOne($sql, [':id' => $id]);
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT * FROM users WHERE username = :username LIMIT 1';
        return $this->db->queryOne($sql, [':username' => $username]);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
        return $this->db->queryOne($sql, [':email' => $email]);
    }

    /**
     * Get all users with pagination
     */
    public function getAll(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        // Apply filters
        if (!empty($filters['role'])) {
            $where[] = 'role = :role';
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['department_id'])) {
            $where[] = 'department_id = :department_id';
            $params[':department_id'] = $filters['department_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(username LIKE :search OR name LIKE :search OR email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users WHERE {$whereClause}";
        $totalResult = $this->db->queryOne($countSql, $params);
        $total = (int) $totalResult['total'];

        // Get users
        $sql = "SELECT u.*, d.name as department_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE {$whereClause}
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset";

        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;

        $users = $this->db->query($sql, $params);

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create new user
     */
    public function create(array $data): int
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->db->insert('users', $data);
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        $rowsAffected = $this->db->update('users', $data, 'id = :id', [':id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Delete user
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('users', 'id = :id', [':id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM users WHERE username = :username';
        $params = [':username' => $username];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return (int) $result['count'] > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM users WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return (int) $result['count'] > 0;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $id): void
    {
        $sql = 'UPDATE users SET last_login_at = NOW(), login_attempts = 0, locked_until = NULL WHERE id = :id';
        $this->db->query($sql, [':id' => $id]);
    }

    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(int $id): void
    {
        $sql = 'UPDATE users SET login_attempts = login_attempts + 1 WHERE id = :id';
        $this->db->query($sql, [':id' => $id]);
    }

    /**
     * Lock user account
     */
    public function lockAccount(int $id, int $minutes = 15): void
    {
        $sql = 'UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL :minutes MINUTE) WHERE id = :id';
        $this->db->query($sql, [':id' => $id, ':minutes' => $minutes]);
    }

    /**
     * Check if account is locked
     */
    public function isLocked(int $id): bool
    {
        $sql = 'SELECT locked_until FROM users WHERE id = :id';
        $user = $this->db->queryOne($sql, [':id' => $id]);

        if (!$user || $user['locked_until'] === null) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total users by role
        $sql = 'SELECT role, COUNT(*) as count FROM users WHERE status = "active" GROUP BY role';
        $roleStats = $this->db->query($sql);

        foreach ($roleStats as $stat) {
            $stats['by_role'][$stat['role']] = (int) $stat['count'];
        }

        // Total users by department
        $sql = 'SELECT d.name, COUNT(u.id) as count
                FROM departments d
                LEFT JOIN users u ON d.id = u.department_id AND u.status = "active"
                GROUP BY d.id, d.name';
        $deptStats = $this->db->query($sql);

        foreach ($deptStats as $stat) {
            $stats['by_department'][$stat['name']] = (int) $stat['count'];
        }

        // Total active vs inactive
        $sql = 'SELECT status, COUNT(*) as count FROM users GROUP BY status';
        $statusStats = $this->db->query($sql);

        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = (int) $stat['count'];
        }

        return $stats;
    }

    /**
     * Log user activity
     */
    public function logActivity(int $userId, string $action, ?string $details = null): void
    {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];

        $this->db->insert('user_audit_logs', $data);
    }
}
