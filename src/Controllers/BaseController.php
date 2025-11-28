<?php
/**
 * Base Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use PDO;

abstract class BaseController
{
    protected ?PDO $db;

    public function __construct(?PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Render a view with data
     */
    protected function render(string $view, array $data = []): void
    {
        // Extract data to variables
        extract($data);

        // Define view path
        $viewPath = BASE_PATH . '/src/Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Start output buffering for content
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Render with layout if not a partial
        if (!($data['_partial'] ?? false)) {
            $pageTitle = $data['title'] ?? 'Creodent Voice';
            require BASE_PATH . '/src/Views/layout.php';
        } else {
            echo $content;
        }
    }

    /**
     * Send JSON response
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Get POST data with optional default
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data with optional default
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Get CSRF token for forms
     */
    protected function csrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Get current user
     */
    protected function currentUser(): ?array
    {
        if (!$this->isAuthenticated() || !$this->db) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? AND status = ?');
        $stmt->execute([$_SESSION['user_id'], 'active']);
        return $stmt->fetch() ?: null;
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/auth/login');
        }
    }

    /**
     * Require specific role
     */
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        $user = $this->currentUser();

        if (!$user || !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            $this->render('errors/403', ['title' => 'Access Denied']);
            exit;
        }
    }

    /**
     * Get client IP address
     */
    protected function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        // Handle comma-separated IPs (from proxies)
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return $ip;
    }

    /**
     * Set flash message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get and clear flash messages
     */
    protected function getFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
}
