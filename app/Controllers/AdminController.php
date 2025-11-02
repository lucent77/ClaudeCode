<?php
/**
 * Admin Controller
 *
 * Handles administrative functions (user management, departments, etc.)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use Exception;

class AdminController extends Controller
{
    private UserRepository $userRepo;

    public function __construct()
    {
        parent::__construct();
        $this->userRepo = new UserRepository();
    }

    /**
     * Show users management page
     */
    public function users(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        $this->view('admin.users', [
            'title' => 'User Management - CREODENT Work Manager',
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Get users list (API)
     */
    public function getUsers(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $filters = [
                'department_id' => $this->input('department_id'),
                'role' => $this->input('role'),
                'status' => $this->input('status') ?? 'active',
            ];

            $users = $this->userRepo->getAll($filters);

            // Remove sensitive data
            foreach ($users as &$user) {
                unset($user['password_hash']);
            }

            $this->success($users);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get single user
     */
    public function getUser(int $id): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $user = $this->userRepo->findById($id);

            if (!$user) {
                $this->error('User not found', null, 404);
                return;
            }

            unset($user['password_hash']);

            $this->success($user);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Create new user
     */
    public function createUser(): void
    {
        $this->requireAuth();
        $this->requireRole(['super_admin']);

        try {
            $data = $this->getAllInput();

            // Validate
            $errors = $this->validate($data, [
                'username' => 'required|min:3',
                'password' => 'required|min:8',
                'name' => 'required',
                'email' => 'email',
                'role' => 'required|in:super_admin,admin,manager,worker',
            ]);

            if (!empty($errors)) {
                $this->error('Validation failed', $errors, 422);
                return;
            }

            // Check if username exists
            if ($this->userRepo->findByUsername($data['username'])) {
                $this->error('Username already exists', null, 400);
                return;
            }

            // Check if email exists
            if (!empty($data['email']) && $this->userRepo->findByEmail($data['email'])) {
                $this->error('Email already exists', null, 400);
                return;
            }

            // Prepare user data
            $userData = [
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'role' => $data['role'],
                'status' => 'active',
            ];

            $userId = $this->userRepo->create($userData);

            // Log creation
            $this->logAudit(0, 'user_create', "User created: {$data['username']}", null, $userData);

            $user = $this->userRepo->findById($userId);
            unset($user['password_hash']);

            $this->success($user, 'User created successfully', 201);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(int $id): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        try {
            $currentUser = $this->userRepo->findById($id);

            if (!$currentUser) {
                $this->error('User not found', null, 404);
                return;
            }

            $data = $this->getAllInput();

            // Prepare update data
            $updateData = [];
            $allowedFields = ['name', 'email', 'department_id', 'role', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // If password provided, hash it
            if (!empty($data['password'])) {
                $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            // Only super_admin can change roles
            if (isset($updateData['role']) && !$this->getUser()['role'] === 'super_admin') {
                unset($updateData['role']);
            }

            $this->userRepo->update($id, $updateData);

            // Log update
            $this->logAudit(0, 'user_update', "User updated: {$currentUser['username']}", $currentUser, $updateData);

            $user = $this->userRepo->findById($id);
            unset($user['password_hash']);

            $this->success($user, 'User updated successfully');

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete (deactivate) user
     */
    public function deleteUser(int $id): void
    {
        $this->requireAuth();
        $this->requireRole(['super_admin']);

        try {
            $user = $this->userRepo->findById($id);

            if (!$user) {
                $this->error('User not found', null, 404);
                return;
            }

            // Don't allow deleting yourself
            if ($id === $this->getUserId()) {
                $this->error('Cannot delete your own account', null, 400);
                return;
            }

            // Soft delete - set status to inactive
            $this->userRepo->update($id, ['status' => 'inactive']);

            // Log deletion
            $this->logAudit(0, 'user_delete', "User deactivated: {$user['username']}");

            $this->success(null, 'User deactivated successfully');

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get all departments
     */
    public function getDepartments(): void
    {
        $this->requireAuth();

        try {
            $departments = $this->userRepo->getAllDepartments();
            $this->success($departments);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get user productivity stats
     */
    public function getUserProductivity(int $userId): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin', 'manager']);

        try {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            $stats = $this->userRepo->getProductivityStats($userId, $startDate, $endDate);

            $this->success($stats);

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }

    /**
     * Show departments management page
     */
    public function departments(): void
    {
        $this->requireAuth();
        $this->requireRole(['admin', 'super_admin']);

        $this->view('admin.departments', [
            'title' => 'Department Management - CREODENT Work Manager',
            'user' => $this->getUser(),
        ]);
    }
}
