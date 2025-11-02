<?php
/**
 * Authentication Controller
 *
 * Handles login, logout, and authentication
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Repositories\UserRepository;
use Exception;

class AuthController extends Controller
{
    private UserRepository $userRepo;

    public function __construct()
    {
        parent::__construct();
        $this->userRepo = new UserRepository();
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // Redirect if already logged in
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login', [
            'title' => 'Login - CREODENT Work Manager',
            'error' => Session::flash('error'),
        ]);
    }

    /**
     * Process login
     */
    public function login(): void
    {
        try {
            $username = $this->input('username');
            $password = $this->input('password');

            // Validate input
            $errors = $this->validate([
                'username' => $username,
                'password' => $password,
            ], [
                'username' => 'required',
                'password' => 'required',
            ]);

            if (!empty($errors)) {
                if ($this->isAjaxRequest()) {
                    $this->error('Validation failed', $errors, 422);
                } else {
                    Session::flash('error', 'Username and password are required');
                    $this->redirect('/login');
                }
                return;
            }

            // Attempt login
            $user = $this->userRepo->verifyPassword($username, $password);

            if (!$user) {
                if ($this->isAjaxRequest()) {
                    $this->error('Invalid credentials', null, 401);
                } else {
                    Session::flash('error', 'Invalid username or password');
                    $this->redirect('/login');
                }
                return;
            }

            // Set session
            Session::setUser($user);
            Session::regenerate();

            // Log login
            $this->db->insert('case_audit_logs', [
                'user_id' => $user['id'],
                'action' => 'login',
                'description' => 'User logged in',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            // Response
            if ($this->isAjaxRequest()) {
                $this->success([
                    'redirect' => '/dashboard',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                    ]
                ], 'Login successful');
            } else {
                $this->redirect('/dashboard');
            }

        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                $this->error($e->getMessage(), null, 400);
            } else {
                Session::flash('error', $e->getMessage());
                $this->redirect('/login');
            }
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $userId = Session::getUserId();

        // Log logout
        if ($userId) {
            $this->db->insert('case_audit_logs', [
                'user_id' => $userId,
                'action' => 'logout',
                'description' => 'User logged out',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        }

        Session::destroy();

        if ($this->isAjaxRequest()) {
            $this->success(null, 'Logged out successfully');
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * Get current user info (API endpoint)
     */
    public function me(): void
    {
        $this->requireAuth();

        $user = Session::getUser();

        if (!$user) {
            $this->error('User not found', null, 404);
            return;
        }

        // Remove sensitive data
        unset($user['password_hash']);

        $this->success($user);
    }

    /**
     * Change password
     */
    public function changePassword(): void
    {
        $this->requireAuth();

        try {
            $currentPassword = $this->input('current_password');
            $newPassword = $this->input('new_password');
            $confirmPassword = $this->input('confirm_password');

            // Validate
            $errors = $this->validate([
                'current_password' => $currentPassword,
                'new_password' => $newPassword,
                'confirm_password' => $confirmPassword,
            ], [
                'current_password' => 'required',
                'new_password' => 'required|min:8',
                'confirm_password' => 'required',
            ]);

            if (!empty($errors)) {
                $this->error('Validation failed', $errors, 422);
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->error('New password and confirmation do not match', null, 400);
                return;
            }

            // Verify current password
            $user = $this->getUser();
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $this->error('Current password is incorrect', null, 400);
                return;
            }

            // Update password
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $this->userRepo->update($user['id'], [
                'password_hash' => $newPasswordHash,
            ]);

            // Log password change
            $this->db->insert('case_audit_logs', [
                'user_id' => $user['id'],
                'action' => 'password_change',
                'description' => 'Password changed',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $this->success(null, 'Password changed successfully');

        } catch (Exception $e) {
            $this->error($e->getMessage(), null, 500);
        }
    }
}
