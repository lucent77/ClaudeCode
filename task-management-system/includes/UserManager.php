<?php
/**
 * User Management Operations
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class UserManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all users
     */
    public function getAllUsers($filters = []) {
        $sql = "SELECT u.*, r.name as role_name, d.name as department_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE 1=1";

        $params = [];

        if (isset($filters['role_id'])) {
            $sql .= " AND u.role_id = ?";
            $params[] = $filters['role_id'];
        }

        if (isset($filters['department_id'])) {
            $sql .= " AND u.department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND u.is_active = ?";
            $params[] = $filters['is_active'];
        }

        $sql .= " ORDER BY u.name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $sql = "SELECT u.*, r.name as role_name, d.name as department_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.id = ?
                LIMIT 1";

        return $this->db->queryOne($sql, [$id]);
    }

    /**
     * Create user
     */
    public function createUser($data) {
        $name = Security::sanitizeString($data['name']);
        $email = Security::sanitizeEmail($data['email']);

        if (!Security::validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        // Check if email already exists
        $existing = $this->db->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $passwordHash = Security::hashPassword($data['password']);

        $sql = "INSERT INTO users (name, email, password_hash, role_id, department_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?)";

        $params = [
            $name,
            $email,
            $passwordHash,
            $data['role_id'],
            $data['department_id'] ?? null,
            $data['is_active'] ?? 1
        ];

        try {
            $this->db->execute($sql, $params);
            $userId = $this->db->lastInsertId();
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }

    /**
     * Update user
     */
    public function updateUser($id, $data) {
        $updates = [];
        $params = [];

        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = Security::sanitizeString($data['name']);
        }

        if (isset($data['email'])) {
            $email = Security::sanitizeEmail($data['email']);
            if (!Security::validateEmail($email)) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }

            // Check if email already exists for another user
            $existing = $this->db->queryOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            $updates[] = "email = ?";
            $params[] = $email;
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $updates[] = "password_hash = ?";
            $params[] = Security::hashPassword($data['password']);
        }

        if (isset($data['role_id'])) {
            $updates[] = "role_id = ?";
            $params[] = $data['role_id'];
        }

        if (isset($data['department_id'])) {
            $updates[] = "department_id = ?";
            $params[] = $data['department_id'];
        }

        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = $data['is_active'];
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $this->db->execute($sql, $params);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update user'];
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        try {
            $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete user. User may have associated tasks.'];
        }
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        return $this->db->query("SELECT * FROM roles ORDER BY name ASC");
    }

    /**
     * Get all departments
     */
    public function getAllDepartments() {
        return $this->db->query("SELECT * FROM departments ORDER BY name ASC");
    }

    /**
     * Deactivate user
     */
    public function deactivateUser($id) {
        try {
            $this->db->execute("UPDATE users SET is_active = 0 WHERE id = ?", [$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to deactivate user'];
        }
    }

    /**
     * Activate user
     */
    public function activateUser($id) {
        try {
            $this->db->execute("UPDATE users SET is_active = 1 WHERE id = ?", [$id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to activate user'];
        }
    }
}
