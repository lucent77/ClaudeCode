<?php

namespace SwissTurn\Core;

/**
 * HTTP Response helpers
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send success JSON response
     */
    public static function success(mixed $data = null, string $message = 'Success'): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send error JSON response
     */
    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Send 404 Not Found response
     */
    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Send 500 Server Error response
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 500);
    }

    /**
     * Redirect to another URL
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Render a view template
     */
    public static function view(string $template, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $template . '.php';

        if (!file_exists($viewPath)) {
            self::serverError('View not found: ' . $template);
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        echo $content;
    }

    /**
     * Render view with layout
     */
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        extract($data);

        // Render content
        $viewPath = __DIR__ . '/../../views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            self::serverError('View not found: ' . $template);
            return;
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Render layout with content
        $layoutPath = __DIR__ . '/../../views/' . $layout . '.php';
        if (!file_exists($layoutPath)) {
            echo $content;
            return;
        }

        require $layoutPath;
    }
}
