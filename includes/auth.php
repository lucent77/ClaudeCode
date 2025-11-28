<?php
/**
 * Authentication Functions
 * LifeMandalart - Self Management Web Service
 */

// Prevent direct access
if (!defined('LIFE_MANDALART')) {
    die('Direct access not permitted');
}

/**
 * Authentication Class
 */
class Auth {
    /**
     * Check if user is logged in
     */
    public static function check(): bool {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user
     */
    public static function user(): ?array {
        if (!self::check()) return null;
        return User::getById(self::id());
    }

    /**
     * Attempt login
     */
    public static function attempt(string $email, string $password, bool $remember = false): bool {
        $user = User::getByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['language'] = $user['language'];
        $_SESSION['theme'] = $user['theme'];

        // Update last activity
        User::update($user['id'], ['last_active_date' => date('Y-m-d')]);

        // Remember me functionality
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            // Store token in database (you would need to add a remember_tokens table)
        }

        return true;
    }

    /**
     * Register new user
     */
    public static function register(array $data): array {
        $errors = [];

        // Validate username
        if (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (User::getByUsername($data['username'])) {
            $errors['username'] = 'Username already taken';
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        } elseif (User::getByEmail($data['email'])) {
            $errors['email'] = 'Email already registered';
        }

        // Validate password
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        }

        // Validate password confirmation
        if ($data['password'] !== ($data['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'Passwords do not match';
        }

        // Validate name
        if (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $userId = User::create($data);

            // Auto login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['language'] = $data['language'] ?? DEFAULT_LANGUAGE;
            $_SESSION['theme'] = DEFAULT_THEME;

            // Create default habits settings
            Database::insert(
                "INSERT INTO habits_settings (user_id) VALUES (?)",
                [$userId]
            );

            // Award first achievement
            Achievement::checkAndAward($userId);

            return ['success' => true, 'user_id' => $userId];

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Registration failed. Please try again.']];
        }
    }

    /**
     * Logout user
     */
    public static function logout(): void {
        // Clear session
        $_SESSION = [];

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Delete remember cookie
        setcookie('remember_token', '', time() - 42000, '/', '', true, true);

        // Destroy session
        session_destroy();
    }

    /**
     * Require authentication
     */
    public static function require(): void {
        if (!self::check()) {
            if (isAjax()) {
                jsonResponse(['error' => 'Unauthorized'], 401);
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect('login.php');
        }
    }

    /**
     * Require guest (not logged in)
     */
    public static function requireGuest(): void {
        if (self::check()) {
            redirect('index.php');
        }
    }

    /**
     * Change password
     */
    public static function changePassword(int $userId, string $currentPassword, string $newPassword): bool {
        $user = User::getById($userId);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return false;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return User::update($userId, ['password' => $hashedPassword]);
    }

    /**
     * Generate password reset token
     */
    public static function generatePasswordResetToken(string $email): ?string {
        $user = User::getByEmail($email);
        if (!$user) return null;

        $token = bin2hex(random_bytes(32));

        // Delete existing tokens
        Database::delete("DELETE FROM password_resets WHERE email = ?", [$email]);

        // Insert new token
        Database::insert(
            "INSERT INTO password_resets (email, token) VALUES (?, ?)",
            [$email, hash('sha256', $token)]
        );

        return $token;
    }

    /**
     * Reset password with token
     */
    public static function resetPassword(string $email, string $token, string $newPassword): bool {
        $reset = Database::fetch(
            "SELECT * FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$email]
        );

        if (!$reset || !hash_equals($reset['token'], hash('sha256', $token))) {
            return false;
        }

        $user = User::getByEmail($email);
        if (!$user) return false;

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        User::update($user['id'], ['password' => $hashedPassword]);

        // Delete used token
        Database::delete("DELETE FROM password_resets WHERE email = ?", [$email]);

        return true;
    }
}
