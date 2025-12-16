<?php
/**
 * User Controller
 *
 * Handles user management operations
 */

class UserController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * List all users
     * GET /api/users
     */
    public function index()
    {
        $data = Router::getRequestData();

        $page = max(1, intval($data['page'] ?? 1));
        $limit = min(100, max(1, intval($data['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $search = $data['search'] ?? '';
        $role = $data['role'] ?? '';
        $isActive = $data['is_active'] ?? '';

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(username LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($role) && in_array($role, ['Admin', 'User'])) {
            $where[] = "role = ?";
            $params[] = $role;
        }

        if ($isActive !== '') {
            $where[] = "is_active = ?";
            $params[] = intval($isActive);
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Get total count
        $countSql = "SELECT COUNT(*) FROM users {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get users
        $sql = "SELECT id, username, email, role, slack_member_id, is_active, last_login_at, created_at
                FROM users
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $users = $this->db->fetchAll($sql, $params);

        Router::success([
            'users' => $users,
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Create a new user
     * POST /api/users
     */
    public function store()
    {
        $data = Router::getRequestData();

        // Validate required fields
        $errors = $this->validateUser($data);
        if (!empty($errors)) {
            Router::error('Validation failed', 400, $errors);
            return;
        }

        // Check if username already exists
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE username = ?",
            [$data['username']]
        );

        if ($exists > 0) {
            Router::error('Username already exists', 400);
            return;
        }

        // Check if email already exists (if provided)
        if (!empty($data['email'])) {
            $emailExists = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE email = ?",
                [$data['email']]
            );

            if ($emailExists > 0) {
                Router::error('Email already exists', 400);
                return;
            }
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user
        $userId = $this->db->insert(
            "INSERT INTO users (username, email, password_hash, role, slack_member_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['username'],
                $data['email'] ?? null,
                $passwordHash,
                $data['role'] ?? 'User',
                $data['slack_member_id'] ?? null,
                $data['is_active'] ?? 1,
            ]
        );

        $user = $this->db->fetch(
            "SELECT id, username, email, role, slack_member_id, is_active, created_at
             FROM users WHERE id = ?",
            [$userId]
        );

        Router::success(['user' => $user], 'User created successfully', 201);
    }

    /**
     * Get a specific user
     * GET /api/users/{id}
     */
    public function show($params)
    {
        $id = intval($params['id']);

        $user = $this->db->fetch(
            "SELECT id, username, email, role, slack_member_id, is_active, last_login_at, created_at
             FROM users WHERE id = ?",
            [$id]
        );

        if (!$user) {
            Router::error('User not found', 404);
            return;
        }

        Router::success(['user' => $user]);
    }

    /**
     * Update a user
     * PUT /api/users/{id}
     */
    public function update($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        // Check if user exists
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Router::error('User not found', 404);
            return;
        }

        // Build update query
        $updates = [];
        $updateParams = [];

        if (isset($data['username'])) {
            // Check if new username already exists
            $exists = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE username = ? AND id != ?",
                [$data['username'], $id]
            );

            if ($exists > 0) {
                Router::error('Username already exists', 400);
                return;
            }

            $updates[] = "username = ?";
            $updateParams[] = $data['username'];
        }

        if (isset($data['email'])) {
            // Check if new email already exists
            if (!empty($data['email'])) {
                $emailExists = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?",
                    [$data['email'], $id]
                );

                if ($emailExists > 0) {
                    Router::error('Email already exists', 400);
                    return;
                }
            }

            $updates[] = "email = ?";
            $updateParams[] = $data['email'] ?: null;
        }

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                Router::error('Password must be at least 6 characters', 400);
                return;
            }

            $updates[] = "password_hash = ?";
            $updateParams[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['role']) && in_array($data['role'], ['Admin', 'User'])) {
            $updates[] = "role = ?";
            $updateParams[] = $data['role'];
        }

        if (isset($data['slack_member_id'])) {
            $updates[] = "slack_member_id = ?";
            $updateParams[] = $data['slack_member_id'] ?: null;
        }

        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $updateParams[] = intval($data['is_active']);
        }

        if (empty($updates)) {
            Router::error('No fields to update', 400);
            return;
        }

        $updateParams[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $this->db->update($sql, $updateParams);

        $updatedUser = $this->db->fetch(
            "SELECT id, username, email, role, slack_member_id, is_active, created_at
             FROM users WHERE id = ?",
            [$id]
        );

        Router::success(['user' => $updatedUser], 'User updated successfully');
    }

    /**
     * Delete a user
     * DELETE /api/users/{id}
     */
    public function destroy($params)
    {
        $id = intval($params['id']);

        // Check if user exists
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Router::error('User not found', 404);
            return;
        }

        // Prevent deleting own account
        if (AuthController::getCurrentUserId() === $id) {
            Router::error('Cannot delete your own account', 400);
            return;
        }

        // Soft delete - set is_active to 0
        $this->db->update("UPDATE users SET is_active = 0 WHERE id = ?", [$id]);

        Router::success(null, 'User deleted successfully');
    }

    /**
     * Validate user data
     */
    private function validateUser($data)
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            $errors['username'] = 'Username must be between 3 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!empty($data['role']) && !in_array($data['role'], ['Admin', 'User'])) {
            $errors['role'] = 'Invalid role. Must be Admin or User';
        }

        return $errors;
    }
}
