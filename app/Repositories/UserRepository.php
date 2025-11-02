<?php
/**
 * User Repository
 *
 * Data access layer for users table
 */

namespace App\Repositories;

use App\Core\Database;

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
        return $this->db->queryOne(
            "SELECT u.*, d.name as department_name, d.code as department_code
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = ?",
            [$id]
        );
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->queryOne(
            "SELECT u.*, d.name as department_name, d.code as department_code
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.username = ?",
            [$username]
        );
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->queryOne(
            "SELECT u.*, d.name as department_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.email = ?",
            [$email]
        );
    }

    /**
     * Get all users with optional filters
     */
    public function getAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['department_id'])) {
            $where[] = "u.department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (!empty($filters['role'])) {
            $where[] = "u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = "u.status = ?";
            $params[] = $filters['status'];
        } else {
            // Default: only active users
            $where[] = "u.status = 'active'";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        return $this->db->query(
            "SELECT u.*, d.name as department_name, d.code as department_code
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             {$whereClause}
             ORDER BY u.name ASC",
            $params
        );
    }

    /**
     * Get users by department
     */
    public function getByDepartment(int $departmentId): array
    {
        return $this->db->query(
            "SELECT * FROM users
             WHERE department_id = ? AND status = 'active'
             ORDER BY name ASC",
            [$departmentId]
        );
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): array
    {
        return $this->db->query(
            "SELECT u.*, d.name as department_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.role = ? AND u.status = 'active'
             ORDER BY u.name ASC",
            [$role]
        );
    }

    /**
     * Create new user
     */
    public function create(array $data): int
    {
        return $this->db->insert('users', $data);
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        return $this->db->update('users', $data, ['id' => $id], false) > 0;
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $userId): void
    {
        $this->db->execute(
            "UPDATE users SET last_login_at = ?, login_attempts = 0, locked_until = NULL WHERE id = ?",
            [date('Y-m-d H:i:s'), $userId]
        );
    }

    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(string $username): void
    {
        $config = require __DIR__ . '/../../config/config.php';
        $maxAttempts = $config['security']['max_login_attempts'];
        $lockoutDuration = $config['security']['lockout_duration'];

        $this->db->execute(
            "UPDATE users
             SET login_attempts = login_attempts + 1,
                 locked_until = IF(login_attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NULL)
             WHERE username = ?",
            [$maxAttempts, $lockoutDuration, $username]
        );
    }

    /**
     * Check if user is locked
     */
    public function isLocked(string $username): bool
    {
        $user = $this->findByUsername($username);

        if (!$user || !$user['locked_until']) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * Verify user password
     */
    public function verifyPassword(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);

        if (!$user) {
            return null;
        }

        // Check if account is locked
        if ($this->isLocked($username)) {
            throw new \Exception('Account is locked due to too many failed login attempts. Please try again later.');
        }

        // Check if account is active
        if ($user['status'] !== 'active') {
            throw new \Exception('Account is not active.');
        }

        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }

        // Increment failed attempts
        $this->incrementLoginAttempts($username);
        return null;
    }

    /**
     * Get user productivity stats
     */
    public function getProductivityStats(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        return [
            'assigned' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM case_items
                 WHERE assigned_to = ? AND assigned_at BETWEEN ? AND ?",
                [$userId, $startDate, $endDate]
            )['count'] ?? 0,

            'completed' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM case_items
                 WHERE assigned_to = ? AND status = 'done'
                   AND completed_at BETWEEN ? AND ?",
                [$userId, $startDate, $endDate]
            )['count'] ?? 0,

            'in_progress' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM case_items
                 WHERE assigned_to = ? AND status IN ('assigned', 'working')",
                [$userId]
            )['count'] ?? 0,

            'total_hours' => $this->db->queryOne(
                "SELECT SUM(actual_hours) as hours FROM case_items
                 WHERE assigned_to = ? AND completed_at BETWEEN ? AND ?",
                [$userId, $startDate, $endDate]
            )['hours'] ?? 0,
        ];
    }

    /**
     * Get all departments
     */
    public function getAllDepartments(): array
    {
        return $this->db->query(
            "SELECT * FROM departments WHERE status = 'active' ORDER BY name ASC"
        );
    }
}
