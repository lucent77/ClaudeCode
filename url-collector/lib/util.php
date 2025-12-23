<?php
/**
 * URL Collector - Utility Functions
 *
 * General helper functions for the application
 */

declare(strict_types=1);

class Util
{
    private static string $logFile = '';
    private static bool $loggingEnabled = true;

    /**
     * Initialize utilities with config
     */
    public static function init(array $config): void
    {
        self::$loggingEnabled = $config['logging']['enabled'] ?? true;
        self::$logFile = $config['logging']['file'] ?? (APP_ROOT . '/logs/app.log');

        // Set timezone
        $timezone = $config['timezone'] ?? 'Asia/Seoul';
        date_default_timezone_set($timezone);
    }

    /**
     * Log a message
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        if (!self::$loggingEnabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        // Try to write to log file
        if (!empty(self::$logFile)) {
            $logDir = dirname(self::$logFile);

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            $result = @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

            if ($result !== false) {
                return;
            }
        }

        // Fallback to error_log
        error_log($logEntry);
    }

    /**
     * Log info message
     */
    public static function logInfo(string $message): void
    {
        self::log($message, 'INFO');
    }

    /**
     * Log error message
     */
    public static function logError(string $message): void
    {
        self::log($message, 'ERROR');
    }

    /**
     * Log warning message
     */
    public static function logWarning(string $message): void
    {
        self::log($message, 'WARNING');
    }

    /**
     * Log debug message
     */
    public static function logDebug(string $message): void
    {
        self::log($message, 'DEBUG');
    }

    /**
     * Get current datetime in MySQL format
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Format datetime for display
     */
    public static function formatDate(?string $datetime, string $format = 'Y-m-d H:i'): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '-';
        }

        return date($format, $timestamp);
    }

    /**
     * Format relative time (e.g., "3 hours ago")
     */
    public static function timeAgo(?string $datetime): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '-';
        }

        $diff = time() - $timestamp;

        if ($diff < 0) {
            return 'just now';
        }

        $intervals = [
            ['year', 31536000],
            ['month', 2592000],
            ['week', 604800],
            ['day', 86400],
            ['hour', 3600],
            ['minute', 60],
            ['second', 1],
        ];

        foreach ($intervals as [$name, $seconds]) {
            $count = floor($diff / $seconds);
            if ($count > 0) {
                $suffix = $count > 1 ? 's' : '';
                return "{$count} {$name}{$suffix} ago";
            }
        }

        return 'just now';
    }

    /**
     * Truncate string with ellipsis
     */
    public static function truncate(string $string, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    /**
     * Convert bytes to human readable format
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Generate a random string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Get category badge color
     */
    public static function getCategoryColor(string $category): string
    {
        $colors = [
            'news'      => 'bg-red-100 text-red-800',
            'video'     => 'bg-purple-100 text-purple-800',
            'shopping'  => 'bg-green-100 text-green-800',
            'social'    => 'bg-blue-100 text-blue-800',
            'tech'      => 'bg-gray-100 text-gray-800',
            'business'  => 'bg-yellow-100 text-yellow-800',
            'design'    => 'bg-pink-100 text-pink-800',
            'music'     => 'bg-indigo-100 text-indigo-800',
            'dental'    => 'bg-teal-100 text-teal-800',
            'education' => 'bg-orange-100 text-orange-800',
            'other'     => 'bg-slate-100 text-slate-800',
        ];

        return $colors[$category] ?? $colors['other'];
    }

    /**
     * Get status badge color
     */
    public static function getStatusColor(string $status): string
    {
        $colors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'done'    => 'bg-green-100 text-green-800',
            'error'   => 'bg-red-100 text-red-800',
        ];

        return $colors[$status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get review status badge color
     */
    public static function getReviewStatusColor(string $reviewStatus): string
    {
        $colors = [
            'inbox'   => 'bg-blue-100 text-blue-800',
            'keep'    => 'bg-green-100 text-green-800',
            'archive' => 'bg-gray-100 text-gray-800',
        ];

        return $colors[$reviewStatus] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get score color based on value
     */
    public static function getScoreColor(?int $score): string
    {
        if ($score === null) {
            return 'text-gray-400';
        }

        if ($score >= 80) {
            return 'text-green-600';
        }
        if ($score >= 60) {
            return 'text-blue-600';
        }
        if ($score >= 40) {
            return 'text-yellow-600';
        }
        if ($score >= 20) {
            return 'text-orange-600';
        }

        return 'text-red-600';
    }

    /**
     * Parse JSON safely
     */
    public static function parseJson(string $json): ?array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Encode JSON with proper options
     */
    public static function toJson(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Build URL with query parameters
     */
    public static function buildUrl(string $baseUrl, array $params = []): string
    {
        if (empty($params)) {
            return $baseUrl;
        }

        $query = http_build_query($params);

        return str_contains($baseUrl, '?')
            ? $baseUrl . '&' . $query
            : $baseUrl . '?' . $query;
    }

    /**
     * Get pagination data
     */
    public static function paginate(int $total, int $page, int $perPage = 20): array
    {
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'total'       => $total,
            'per_page'    => $perPage,
            'current'     => $page,
            'total_pages' => $totalPages,
            'offset'      => $offset,
            'has_prev'    => $page > 1,
            'has_next'    => $page < $totalPages,
            'prev_page'   => max(1, $page - 1),
            'next_page'   => min($totalPages, $page + 1),
        ];
    }

    /**
     * Get domain favicon URL
     */
    public static function getFaviconUrl(string $domain): string
    {
        return 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=32';
    }

    /**
     * Safe redirect
     */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Flash message helpers
     */
    public static function setFlash(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    public static function getFlash(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return $flash;
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get request method
     */
    public static function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if POST request
     */
    public static function isPost(): bool
    {
        return self::getMethod() === 'POST';
    }
}
