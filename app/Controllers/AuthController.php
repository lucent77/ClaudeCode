<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Repositories\UserRepository;

/**
 * Authentication Controller
 *
 * Handles user login, logout, and authentication
 */
class AuthController extends Controller
{
    private UserRepository $userRepo;

    public function __construct()
    {
        // Don't call parent constructor to skip auth check
        $this->db = \App\Core\Database::getInstance();
        $this->config = require BASE_PATH . '/config/config.php';
        $this->userRepo = new UserRepository();
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // If already logged in, redirect to dashboard
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login', ['noLayout' => false]);
    }

    /**
     * Handle login request
     */
    public function login(): void
    {
        // Get input
        $username = $this->input('username');
        $password = $this->input('password');
        $remember = $this->input('remember') === 'on';

        // Validate input
        if (empty($username) || empty($password)) {
            Session::flash('error', 'Username and password are required');
            $this->redirect('/login');
            return;
        }

        // Find user
        $user = $this->userRepo->findByUsername($username);

        if (!$user) {
            Session::flash('error', 'Invalid username or password');
            $this->redirect('/login');
            return;
        }

        // Check if account is locked
        if ($this->userRepo->isLocked($user['id'])) {
            Session::flash('error', 'Account is temporarily locked due to too many failed login attempts. Please try again later.');
            $this->redirect('/login');
            return;
        }

        // Check if account is active
        if ($user['status'] !== 'active') {
            Session::flash('error', 'Account is inactive. Please contact administrator.');
            $this->redirect('/login');
            return;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Increment login attempts
            $this->userRepo->incrementLoginAttempts($user['id']);

            // Check if we should lock the account
            $maxAttempts = $this->config['security']['max_login_attempts'] ?? 5;
            $attempts = ($user['login_attempts'] ?? 0) + 1;

            if ($attempts >= $maxAttempts) {
                $lockoutDuration = ($this->config['security']['lockout_duration'] ?? 900) / 60;
                $this->userRepo->lockAccount($user['id'], $lockoutDuration);
                Session::flash('error', "Account locked due to {$maxAttempts} failed login attempts. Please try again in {$lockoutDuration} minutes.");
            } else {
                $remaining = $maxAttempts - $attempts;
                Session::flash('error', "Invalid username or password. {$remaining} attempts remaining.");
            }

            $this->redirect('/login');
            return;
        }

        // Login successful
        // Update last login and reset attempts
        $this->userRepo->updateLastLogin($user['id']);

        // Log activity
        $this->userRepo->logActivity($user['id'], 'login', 'User logged in successfully');

        // Remove sensitive data
        unset($user['password_hash']);
        unset($user['login_attempts']);
        unset($user['locked_until']);

        // Set session
        Session::setUser($user);

        // Set remember me cookie if requested
        if ($remember) {
            // TODO: Implement remember me functionality
        }

        // Redirect to return URL or dashboard
        $returnUrl = $_GET['return'] ?? '/dashboard';
        $this->redirect($returnUrl);
    }

    /**
     * Handle logout request
     */
    public function logout(): void
    {
        // Log activity before logout
        $userId = Session::getUserId();
        if ($userId) {
            $this->userRepo->logActivity($userId, 'logout', 'User logged out');
        }

        // Clear session
        Session::logout();

        // Redirect to login
        Session::flash('success', 'You have been logged out successfully');
        $this->redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password', ['noLayout' => false]);
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(): void
    {
        $email = $this->input('email');

        if (empty($email)) {
            Session::flash('error', 'Email is required');
            $this->redirect('/forgot-password');
            return;
        }

        $user = $this->userRepo->findByEmail($email);

        // Always show success message (security: don't reveal if email exists)
        Session::flash('success', 'If an account exists with that email, password reset instructions have been sent.');
        $this->redirect('/login');

        // TODO: Implement password reset email functionality
        if ($user) {
            // Send password reset email
            $this->log("Password reset requested for user: {$user['username']}");
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            Session::flash('error', 'Invalid reset token');
            $this->redirect('/login');
            return;
        }

        $this->view('auth/reset-password', [
            'noLayout' => false,
            'token' => $token
        ]);
    }

    /**
     * Handle reset password request
     */
    public function resetPassword(): void
    {
        // TODO: Implement password reset functionality
        Session::flash('error', 'Password reset functionality is not yet implemented');
        $this->redirect('/login');
    }
}
