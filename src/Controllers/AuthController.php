<?php
/**
 * Authentication Controller
 */

declare(strict_types=1);

namespace App\Controllers;

class AuthController extends BaseController
{
    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/admin');
        }

        $this->render('auth/login', [
            'title' => 'Staff Login - Creodent Voice',
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/auth/login');
        }

        $email = trim($this->input('email', ''));
        $password = $this->input('password', '');

        // Validate inputs
        if (empty($email) || empty($password)) {
            $this->flash('error', 'Please enter both email and password.');
            $this->redirect('/auth/login');
        }

        if (!$this->db) {
            $this->flash('error', 'Database unavailable. Please try again later.');
            $this->redirect('/auth/login');
        }

        // Find user
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? AND status = ?');
        $stmt->execute([$email, 'active']);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->flash('error', 'Invalid email or password.');
            $this->redirect('/auth/login');
        }

        // Update last login
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Regenerate session ID for security
        session_regenerate_id(true);

        $this->flash('success', 'Welcome back, ' . $user['name'] . '!');
        $this->redirect('/admin');
    }

    public function logout(): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('/');
        }

        // Clear session
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();

        $this->redirect('/');
    }
}
