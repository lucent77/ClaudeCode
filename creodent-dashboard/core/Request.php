<?php
/**
 * Creodent Dashboard - Request Handler
 */

namespace Creodent\Core;

class Request
{
    private static ?array $jsonBody = null;

    /**
     * Get request method
     */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if POST request
     */
    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /**
     * Check if GET request
     */
    public static function isGet(): bool
    {
        return self::method() === 'GET';
    }

    /**
     * Check if AJAX request
     */
    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get GET parameter
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get input from any source
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        // Check POST first
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        // Check GET
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        // Check JSON body
        $json = self::json();
        if (isset($json[$key])) {
            return $json[$key];
        }

        return $default;
    }

    /**
     * Get all input
     */
    public static function all(): array
    {
        return array_merge($_GET, $_POST, self::json() ?? []);
    }

    /**
     * Get JSON body
     */
    public static function json(): ?array
    {
        if (self::$jsonBody === null) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (stripos($contentType, 'application/json') !== false) {
                $body = file_get_contents('php://input');
                self::$jsonBody = json_decode($body, true) ?? [];
            } else {
                self::$jsonBody = [];
            }
        }

        return self::$jsonBody;
    }

    /**
     * Get validated date parameter
     */
    public static function date(string $key, ?string $default = null): ?string
    {
        $value = self::input($key, $default);

        if (!$value) {
            return $default;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return $default;
        }

        return $value;
    }

    /**
     * Get validated integer parameter
     */
    public static function int(string $key, ?int $default = null): ?int
    {
        $value = self::input($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : $default;
    }

    /**
     * Get validated branch parameter
     */
    public static function branch(?string $default = 'ALL'): string
    {
        $branch = strtoupper(self::input('branch', $default));
        return in_array($branch, ['ALL', 'NYC', 'HV']) ? $branch : $default;
    }

    /**
     * Get date range parameters
     */
    public static function dateRange(): array
    {
        $type = self::input('date_type', 'year');
        $year = self::int('year', (int) date('Y'));
        $month = self::int('month', (int) date('n'));

        switch ($type) {
            case 'month':
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate = date('Y-m-t', strtotime($startDate));
                break;

            case 'custom':
                $startDate = self::date('start_date', date('Y-01-01'));
                $endDate = self::date('end_date', date('Y-m-d'));
                break;

            case 'year':
            default:
                $startDate = sprintf('%04d-01-01', $year);
                $endDate = sprintf('%04d-12-31', $year);
                break;
        }

        return [
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    /**
     * Get pagination parameters
     */
    public static function pagination(): array
    {
        $page = max(1, self::int('page', 1));
        $perPage = min(MAX_PAGE_SIZE, max(1, self::int('per_page', DEFAULT_PAGE_SIZE)));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset
        ];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrf(): bool
    {
        $token = self::input('_csrf');
        return $token && Auth::verifyCsrf($token);
    }
}
