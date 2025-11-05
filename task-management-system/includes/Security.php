<?php
/**
 * Security Utilities for CSRF, XSS protection, and input sanitization
 */

class Security {
    private static $config;

    public static function init() {
        self::$config = require __DIR__ . '/../config/app.php';
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION[self::$config['security']['csrf_token_name']])) {
            $_SESSION[self::$config['security']['csrf_token_name']] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$config['security']['csrf_token_name']];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        if (!isset($_SESSION[self::$config['security']['csrf_token_name']])) {
            return false;
        }
        return hash_equals($_SESSION[self::$config['security']['csrf_token_name']], $token);
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        self::init();
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => self::$config['security']['password_cost']
        ]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Escape output for HTML
     */
    public static function escape($value) {
        if (is_null($value)) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = []) {
        self::init();

        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'message' => 'Invalid file upload'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
        }

        if ($file['size'] > self::$config['upload']['max_size']) {
            return ['success' => false, 'message' => 'File size exceeds maximum allowed'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = !empty($allowedTypes) ? $allowedTypes : self::$config['upload']['allowed_types'];

        if (!in_array($extension, $allowed)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }

        return ['success' => true, 'extension' => $extension];
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return trim($filename, '._-');
    }
}

// Initialize security config
Security::init();
