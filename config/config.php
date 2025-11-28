<?php
/**
 * Main Configuration
 * LifeMandalart - Self Management Web Service
 */

// Application identifier
define('LIFE_MANDALART', true);

// Application settings
define('APP_NAME', 'LifeMandalart');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://your-domain.com'); // Update with your domain
define('APP_TIMEZONE', 'Asia/Seoul');

// Security settings
define('SESSION_LIFETIME', 86400 * 7); // 7 days
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Default language
define('DEFAULT_LANGUAGE', 'ko');
define('SUPPORTED_LANGUAGES', ['ko', 'en']);

// Default theme
define('DEFAULT_THEME', 'system');
define('SUPPORTED_THEMES', ['light', 'dark', 'system']);

// Points system
define('POINTS_TASK_COMPLETE', 10);
define('POINTS_PRIORITY_BONUS', 5);
define('POINTS_STREAK_BONUS', 2);
define('POINTS_PERFECT_DAY', 20);

// Execution score thresholds
define('SCORE_EXCELLENT', 90);
define('SCORE_GOOD', 70);
define('SCORE_AVERAGE', 50);
define('SCORE_POOR', 30);

// Streak requirements
define('STREAK_MIN_SCORE', 50);

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LANG_PATH', ROOT_PATH . '/lang');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('API_PATH', ROOT_PATH . '/api');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set(APP_TIMEZONE);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once CONFIG_PATH . '/database.php';

// Include language files
require_once INCLUDES_PATH . '/functions.php';

/**
 * Get current language
 */
function getCurrentLanguage(): string {
    if (isset($_SESSION['language']) && in_array($_SESSION['language'], SUPPORTED_LANGUAGES)) {
        return $_SESSION['language'];
    }

    // Check browser language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, SUPPORTED_LANGUAGES)) {
            return $browserLang;
        }
    }

    return DEFAULT_LANGUAGE;
}

/**
 * Get current theme
 */
function getCurrentTheme(): string {
    if (isset($_SESSION['theme']) && in_array($_SESSION['theme'], SUPPORTED_THEMES)) {
        return $_SESSION['theme'];
    }
    return DEFAULT_THEME;
}

// Load language strings
$currentLang = getCurrentLanguage();
$langFile = LANG_PATH . '/' . $currentLang . '.php';
if (file_exists($langFile)) {
    $GLOBALS['lang'] = require $langFile;
} else {
    $GLOBALS['lang'] = require LANG_PATH . '/ko.php';
}

/**
 * Translation function
 */
function __($key, $params = []): string {
    $text = $GLOBALS['lang'][$key] ?? $key;

    if (!empty($params)) {
        foreach ($params as $param => $value) {
            $text = str_replace(':' . $param, $value, $text);
        }
    }

    return $text;
}

/**
 * Escape output
 */
function e($string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'Y-m-d'): string {
    return date($format, strtotime($date));
}

/**
 * Get user's locale-aware date format
 */
function localDate(string $date): string {
    $lang = getCurrentLanguage();
    $timestamp = strtotime($date);

    if ($lang === 'ko') {
        return date('Y년 m월 d일', $timestamp);
    }
    return date('F j, Y', $timestamp);
}
