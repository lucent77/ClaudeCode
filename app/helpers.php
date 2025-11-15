<?php

/**
 * Helper functions for the application
 */

if (!function_exists('env')) {
    /**
     * Get environment variable value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert string booleans to actual booleans
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        static $config = [];

        if (empty($config)) {
            // Load all config files
            $configPath = __DIR__ . '/../config';
            $files = glob($configPath . '/*.php');

            foreach ($files as $file) {
                $name = basename($file, '.php');
                $config[$name] = require $file;
            }
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get storage path
     *
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        return __DIR__ . '/../storage' . ($path ? '/' . $path : '');
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL
     *
     * @param string $path
     * @return string
     */
    function url($path = '')
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host . '/' . ltrim($path, '/');
    }
}
