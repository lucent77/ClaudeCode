<?php
/**
 * Application Bootstrap
 * Initializes all required components
 */

// Set error handling first
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    error_log($exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());

    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre>' . htmlspecialchars($exception) . '</pre>';
    } else {
        http_response_code(500);
        include APP_ROOT . '/views/errors/500.php';
    }
    exit;
});

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Load configuration
require_once APP_ROOT . '/config/config.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        APP_ROOT . '/includes/',
        APP_ROOT . '/models/',
        APP_ROOT . '/controllers/'
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

// Load helper functions
require_once APP_ROOT . '/includes/helpers.php';

// Initialize core services
$db = Database::getInstance();
$auth = Auth::getInstance();

/**
 * Global helper to get Auth instance
 */
function auth(): Auth
{
    return Auth::getInstance();
}

/**
 * Global helper to get Database instance
 */
function db(): Database
{
    return Database::getInstance();
}

/**
 * Global helper to get WorkflowEngine instance
 */
function workflow(): WorkflowEngine
{
    return WorkflowEngine::getInstance();
}

/**
 * Global helper to get CaseManager instance
 */
function caseManager(): CaseManager
{
    return CaseManager::getInstance();
}

/**
 * Global helper to get GoogleDriveService instance
 */
function drive(): GoogleDriveService
{
    return GoogleDriveService::getInstance();
}

/**
 * Global helper to get GmailService instance
 */
function gmail(): GmailService
{
    return GmailService::getInstance();
}
