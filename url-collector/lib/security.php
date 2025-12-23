<?php
/**
 * URL Collector - Security Utilities
 *
 * API key verification, rate limiting, CSRF protection, input validation
 */

declare(strict_types=1);

class Security
{
    private static array $config = [];
    private static string $rateLimitDir = '';

    /**
     * Initialize security module
     */
    public static function init(array $config): void
    {
        self::$config = $config;
        self::$rateLimitDir = defined('APP_ROOT') ? APP_ROOT . '/logs/ratelimit' : sys_get_temp_dir() . '/ratelimit';

        if (!is_dir(self::$rateLimitDir)) {
            @mkdir(self::$rateLimitDir, 0755, true);
        }
    }

    /**
     * Verify API key from request headers
     */
    public static function verifyApiKey(): bool
    {
        $expectedKey = self::$config['app']['api_key'] ?? '';

        if (empty($expectedKey)) {
            return false;
        }

        // Check X-APP-KEY header
        $appKey = $_SERVER['HTTP_X_APP_KEY'] ?? '';
        if (!empty($appKey) && hash_equals($expectedKey, $appKey)) {
            return true;
        }

        // Check Authorization: Bearer header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            if (hash_equals($expectedKey, $matches[1])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check rate limit for an IP
     */
    public static function checkRateLimit(string $ip, int $limit = null): array
    {
        $limit = $limit ?? (self::$config['app']['rate_limit'] ?? 30);

        $hash = md5($ip);
        $file = self::$rateLimitDir . '/' . $hash . '.json';

        $now = time();
        $windowStart = $now - 60; // 1 minute window

        // Read existing data
        $data = [];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $data = json_decode($content, true) ?? [];
            }
        }

        // Clean old entries
        $data = array_filter($data, fn($timestamp) => $timestamp > $windowStart);

        // Check limit
        $count = count($data);
        if ($count >= $limit) {
            return [
                'allowed'   => false,
                'remaining' => 0,
                'reset'     => min($data) + 60,
            ];
        }

        // Add current request
        $data[] = $now;

        // Save
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return [
            'allowed'   => true,
            'remaining' => $limit - count($data),
            'reset'     => $now + 60,
        ];
    }

    /**
     * Get client IP address
     */
    public static function getClientIp(): string
    {
        // Check common proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For may contain multiple IPs
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session must be started before generating CSRF token');
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $storedToken = $_SESSION['csrf_token'] ?? '';
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;

        if (empty($storedToken) || empty($token)) {
            return false;
        }

        // Token expires after 1 hour
        if (time() - $tokenTime > 3600) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Start secure session
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionName = self::$config['app']['session_name'] ?? 'url_collector_session';
        $lifetime = self::$config['app']['session_lifetime'] ?? 7200;

        // Configure session before starting
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);

        session_name($sessionName);
        session_start();

        // Regenerate ID periodically (every 15 minutes)
        $lastRegen = $_SESSION['_last_regenerate'] ?? 0;
        if (time() - $lastRegen > 900) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = time();
        }
    }

    /**
     * Verify admin login
     */
    public static function verifyAdminLogin(string $username, string $password): bool
    {
        $adminUser = self::$config['admin']['username'] ?? '';
        $adminHash = self::$config['admin']['password_hash'] ?? '';

        if (empty($adminUser) || empty($adminHash)) {
            return false;
        }

        // Timing-safe comparison for username
        if (!hash_equals($adminUser, $username)) {
            // Still verify password to prevent timing attacks
            password_verify($password, '$2y$10$dummyhashtopreventtimingattacks');
            return false;
        }

        return password_verify($password, $adminHash);
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::startSession();
        }

        return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Set login session
     */
    public static function setLoggedIn(bool $status): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::startSession();
        }

        if ($status) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['login_time'] = time();
            session_regenerate_id(true);
        } else {
            unset($_SESSION['admin_logged_in'], $_SESSION['login_time']);
        }
    }

    /**
     * Require login (redirect if not logged in)
     */
    public static function requireLogin(string $loginUrl = 'login.php'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    /**
     * Validate URL format (basic validation, not SSRF)
     */
    public static function validateUrl(string $url): array
    {
        $url = trim($url);

        if (empty($url)) {
            return ['valid' => false, 'error' => 'URL is required'];
        }

        if (strlen($url) > 2048) {
            return ['valid' => false, 'error' => 'URL is too long (max 2048 characters)'];
        }

        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        // Check scheme
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['valid' => false, 'error' => 'Only HTTP and HTTPS URLs are allowed'];
        }

        return ['valid' => true, 'url' => $url];
    }

    /**
     * Sanitize string for output (XSS prevention)
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize integer input
     */
    public static function sanitizeInt($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);

        if ($int === false) {
            return null;
        }

        return max($min, min($max, $int));
    }

    /**
     * Validate and sanitize review status
     */
    public static function validateReviewStatus(string $status): ?string
    {
        $valid = ['inbox', 'keep', 'archive'];
        $status = strtolower(trim($status));

        return in_array($status, $valid, true) ? $status : null;
    }

    /**
     * Validate and sanitize category
     */
    public static function validateCategory(string $category, array $validCategories): ?string
    {
        $category = strtolower(trim($category));

        return in_array($category, $validCategories, true) ? $category : null;
    }

    /**
     * Generate URL hash for duplicate detection
     */
    public static function generateUrlHash(string $url): string
    {
        // Normalize URL before hashing
        $url = trim($url);
        $url = rtrim($url, '/');

        // Remove common tracking parameters
        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);

            // Remove tracking params
            $trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'];
            foreach ($trackingParams as $param) {
                unset($params[$param]);
            }

            // Rebuild query
            $parsed['query'] = http_build_query($params);
        }

        // Rebuild URL
        $normalized = ($parsed['scheme'] ?? 'https') . '://';
        $normalized .= $parsed['host'] ?? '';
        $normalized .= $parsed['path'] ?? '/';

        if (!empty($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }

        return hash('sha256', $normalized);
    }

    /**
     * Send JSON error response
     */
    public static function jsonError(string $message, int $httpCode = 400): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send JSON success response
     */
    public static function jsonSuccess(array $data = [], int $httpCode = 200): never
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
