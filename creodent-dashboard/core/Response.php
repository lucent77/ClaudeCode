<?php
/**
 * Creodent Dashboard - Response Helper
 */

namespace Creodent\Core;

class Response
{
    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     */
    public static function success(mixed $data = null, array $meta = []): never
    {
        $response = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'generated_at' => date('c')
            ], $meta)
        ];

        self::json($response);
    }

    /**
     * Send error response
     */
    public static function error(string $message, string $code = 'ERROR', int $status = 400): never
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        self::json($response, $status);
    }

    /**
     * Send validation error
     */
    public static function validationError(array $errors): never
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $errors
            ]
        ];

        self::json($response, 422);
    }

    /**
     * Send unauthorized error
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 'UNAUTHORIZED', 401);
    }

    /**
     * Send forbidden error
     */
    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, 'FORBIDDEN', 403);
    }

    /**
     * Send not found error
     */
    public static function notFound(string $message = 'Not found'): never
    {
        self::error($message, 'NOT_FOUND', 404);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Set security headers
     */
    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Set cache headers
     */
    public static function cacheHeaders(int $seconds = 0): void
    {
        if ($seconds > 0) {
            header('Cache-Control: public, max-age=' . $seconds);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        } else {
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
}
