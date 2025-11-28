<?php
/**
 * Admin User Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class UserController extends BaseController
{
    public function index(): void
    {
        $this->requireRole(['moderator']);

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin');
        }

        $stmt = $this->db->query("
            SELECT u.*,
                   (SELECT COUNT(*) FROM posts WHERE owner_id = u.id) as assigned_count,
                   (SELECT COUNT(*) FROM moderation_audit WHERE moderator_id = u.id) as audit_count
            FROM users u
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();

        $this->render('admin/users', [
            'title' => 'Manage Users - Creodent Voice',
            'users' => $users,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['moderator']);

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/admin/users');
        }

        if (!$this->db) {
            $this->flash('error', 'Database unavailable.');
            $this->redirect('/admin/users');
        }

        $name = trim($this->input('name', ''));
        $email = trim($this->input('email', ''));
        $password = $this->input('password', '');
        $role = $this->input('role', 'viewer');

        // Validate
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }

        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        $validRoles = ['moderator', 'owner', 'exec', 'viewer'];
        if (!in_array($role, $validRoles, true)) {
            $errors[] = 'Invalid role selected.';
        }

        // Check for duplicate email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'A user with this email already exists.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/admin/users');
        }

        // Create user
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, role, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
        ]);

        $this->flash('success', 'User created successfully.');
        $this->redirect('/admin/users');
    }
}
