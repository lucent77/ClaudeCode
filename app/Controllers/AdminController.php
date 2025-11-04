<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Repositories\UserRepository;

/**
 * Admin Controller
 *
 * Handles user management and administrative functions
 */
class AdminController extends Controller
{
    private UserRepository $userRepo;

    public function __construct()
    {
        parent::__construct();
        $this->userRepo = new UserRepository();
    }

    /**
     * Display users list
     */
    public function users(): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);

        // Get filters
        $filters = [
            'search' => $_GET['search'] ?? '',
            'role' => $_GET['role'] ?? '',
            'status' => $_GET['status'] ?? '',
            'department_id' => $_GET['department_id'] ?? ''
        ];

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 50;

        $result = $this->userRepo->getAll($page, $perPage, $filters);

        // Get departments for filter
        $departments = $this->db->query('SELECT * FROM departments WHERE status = "active" ORDER BY name');

        $this->view('admin/users', [
            'title' => 'User Management - CREODENT Work Manager',
            'users' => $result['users'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'filters' => $filters,
            'departments' => $departments
        ]);
    }

    /**
     * Show create user form
     */
    public function createUser(): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);

        $departments = $this->db->query('SELECT * FROM departments WHERE status = "active" ORDER BY name');

        $this->view('admin/user_create', [
            'title' => 'Create User - CREODENT Work Manager',
            'departments' => $departments
        ]);
    }

    /**
     * Store new user
     */
    public function storeUser(): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);
        $this->validateCsrf();

        try {
            // Validate input
            $data = $this->validate([
                'username' => 'required|min:3|max:100',
                'name' => 'required|min:2|max:255',
                'password' => 'required|min:8',
                'email' => 'email',
                'role' => 'required'
            ]);

            // Check username uniqueness
            if ($this->userRepo->usernameExists($data['username'])) {
                throw new \Exception('Username already exists');
            }

            // Check email uniqueness
            if (!empty($data['email']) && $this->userRepo->emailExists($data['email'])) {
                throw new \Exception('Email already exists');
            }

            // Validate password strength
            $this->validatePassword($data['password']);

            // Only super_admin can create super_admin
            if ($data['role'] === 'super_admin' && !Session::hasRole('super_admin')) {
                throw new \Exception('Only super administrators can create super admin accounts');
            }

            // Set default status
            $data['status'] = $data['status'] ?? 'active';
            $data['department_id'] = !empty($data['department_id']) ? (int) $data['department_id'] : null;

            // Create user
            $userId = $this->userRepo->create($data);

            // Log activity
            $this->userRepo->logActivity(Session::getUserId(), 'user_created', "Created user: {$data['username']}");

            if ($this->isApiRequest()) {
                $user = $this->userRepo->findById($userId);
                unset($user['password_hash']);
                $this->success($user, 'User created successfully');
            }

            Session::flash('success', 'User created successfully');
            $this->redirect('/admin/users');

        } catch (\Exception $e) {
            $this->log('Error creating user: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/users/create');
        }
    }

    /**
     * Show edit user form
     */
    public function editUser(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);

        $userId = (int) $id;
        $user = $this->userRepo->findById($userId);

        if (!$user) {
            Session::flash('error', 'User not found');
            $this->redirect('/admin/users');
        }

        // Only super_admin can edit super_admin
        if ($user['role'] === 'super_admin' && !Session::hasRole('super_admin')) {
            $this->forbidden('You cannot edit super administrator accounts');
        }

        $departments = $this->db->query('SELECT * FROM departments WHERE status = "active" ORDER BY name');

        $this->view('admin/user_edit', [
            'title' => 'Edit User - CREODENT Work Manager',
            'user' => $user,
            'departments' => $departments
        ]);
    }

    /**
     * Update user
     */
    public function updateUser(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);
        $this->validateCsrf();

        try {
            $userId = (int) $id;
            $user = $this->userRepo->findById($userId);

            if (!$user) {
                throw new \Exception('User not found');
            }

            // Only super_admin can edit super_admin
            if ($user['role'] === 'super_admin' && !Session::hasRole('super_admin')) {
                throw new \Exception('You cannot edit super administrator accounts');
            }

            $data = $this->input();

            // Remove fields that shouldn't be updated
            unset($data['id'], $data['created_at'], $data['password_hash']);

            // Check username uniqueness
            if (isset($data['username']) && $this->userRepo->usernameExists($data['username'], $userId)) {
                throw new \Exception('Username already exists');
            }

            // Check email uniqueness
            if (isset($data['email']) && !empty($data['email']) && $this->userRepo->emailExists($data['email'], $userId)) {
                throw new \Exception('Email already exists');
            }

            // Handle password change
            if (!empty($data['password'])) {
                $this->validatePassword($data['password']);
                // Password will be hashed in repository
            } else {
                unset($data['password']);
            }

            // Only super_admin can change role to super_admin
            if (isset($data['role']) && $data['role'] === 'super_admin' && !Session::hasRole('super_admin')) {
                throw new \Exception('Only super administrators can assign super admin role');
            }

            // Convert department_id to int or null
            if (isset($data['department_id'])) {
                $data['department_id'] = !empty($data['department_id']) ? (int) $data['department_id'] : null;
            }

            // Update user
            $this->userRepo->update($userId, $data);

            // Log activity
            $this->userRepo->logActivity(Session::getUserId(), 'user_updated', "Updated user: {$user['username']}");

            if ($this->isApiRequest()) {
                $updatedUser = $this->userRepo->findById($userId);
                unset($updatedUser['password_hash']);
                $this->success($updatedUser, 'User updated successfully');
            }

            Session::flash('success', 'User updated successfully');
            $this->redirect('/admin/users');

        } catch (\Exception $e) {
            $this->log('Error updating user: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/users/' . $id . '/edit');
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(string $id): void
    {
        $this->requireRole('super_admin'); // Only super admin can delete
        $this->validateCsrf();

        try {
            $userId = (int) $id;
            $user = $this->userRepo->findById($userId);

            if (!$user) {
                throw new \Exception('User not found');
            }

            // Cannot delete self
            if ($userId === Session::getUserId()) {
                throw new \Exception('You cannot delete your own account');
            }

            // Cannot delete super_admin
            if ($user['role'] === 'super_admin') {
                throw new \Exception('Cannot delete super administrator accounts');
            }

            // Log before deletion
            $this->userRepo->logActivity(Session::getUserId(), 'user_deleted', "Deleted user: {$user['username']}");

            $this->userRepo->delete($userId);

            if ($this->isApiRequest()) {
                $this->success(null, 'User deleted successfully');
            }

            Session::flash('success', 'User deleted successfully');
            $this->redirect('/admin/users');

        } catch (\Exception $e) {
            $this->log('Error deleting user: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/users');
        }
    }

    /**
     * Toggle user status (active/inactive)
     */
    public function toggleUserStatus(string $id): void
    {
        $this->requireAnyRole(['super_admin', 'admin']);
        $this->validateCsrf();

        try {
            $userId = (int) $id;
            $user = $this->userRepo->findById($userId);

            if (!$user) {
                throw new \Exception('User not found');
            }

            // Cannot deactivate self
            if ($userId === Session::getUserId()) {
                throw new \Exception('You cannot deactivate your own account');
            }

            // Only super_admin can deactivate super_admin
            if ($user['role'] === 'super_admin' && !Session::hasRole('super_admin')) {
                throw new \Exception('You cannot modify super administrator accounts');
            }

            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

            $this->userRepo->update($userId, ['status' => $newStatus]);

            // Log activity
            $this->userRepo->logActivity(Session::getUserId(), 'user_status_changed', "Changed user {$user['username']} status to {$newStatus}");

            if ($this->isApiRequest()) {
                $this->success(['status' => $newStatus], 'User status updated successfully');
            }

            Session::flash('success', 'User status updated successfully');
            $this->redirect('/admin/users');

        } catch (\Exception $e) {
            $this->log('Error toggling user status: ' . $e->getMessage(), 'error');

            if ($this->isApiRequest()) {
                $this->error($e->getMessage(), 400);
            }

            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/users');
        }
    }

    /**
     * Validate password strength
     */
    private function validatePassword(string $password): void
    {
        $minLength = $this->config['security']['password_min_length'] ?? 8;

        if (strlen($password) < $minLength) {
            throw new \Exception("Password must be at least {$minLength} characters");
        }

        if ($this->config['security']['password_require_uppercase'] ?? true) {
            if (!preg_match('/[A-Z]/', $password)) {
                throw new \Exception('Password must contain at least one uppercase letter');
            }
        }

        if ($this->config['security']['password_require_lowercase'] ?? true) {
            if (!preg_match('/[a-z]/', $password)) {
                throw new \Exception('Password must contain at least one lowercase letter');
            }
        }

        if ($this->config['security']['password_require_numbers'] ?? true) {
            if (!preg_match('/[0-9]/', $password)) {
                throw new \Exception('Password must contain at least one number');
            }
        }

        if ($this->config['security']['password_require_special'] ?? false) {
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                throw new \Exception('Password must contain at least one special character');
            }
        }
    }
}
