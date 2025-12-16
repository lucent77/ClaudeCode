<?php
/**
 * Environment Variable Loader
 *
 * Loads environment variables from .env file
 */

class EnvLoader
{
    private static $loaded = false;

    /**
     * Load environment variables from .env file
     */
    public static function load($path = null)
    {
        if (self::$loaded) {
            return;
        }

        $path = $path ?? dirname(__DIR__) . '/.env';

        if (!file_exists($path)) {
            // Try to load from env.example.txt if .env doesn't exist
            $examplePath = dirname(__DIR__) . '/env.example.txt';
            if (file_exists($examplePath)) {
                error_log("Warning: .env file not found. Using env.example.txt for reference.");
            }
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Remove quotes from value
                if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                // Set environment variable
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable
     */
    public static function get($key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Check if environment variable exists
     */
    public static function has($key)
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }
}

// Auto-load environment variables
EnvLoader::load();

/**
 * Helper function to get environment variables
 */
function env($key, $default = null)
{
    return EnvLoader::get($key, $default);
}
