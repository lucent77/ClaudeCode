<?php
/**
 * Database Configuration
 *
 * Update these values with your Hostinger database credentials
 */

// Database configuration
define('DB_HOST', 'localhost');          // Usually 'localhost' for Hostinger
define('DB_NAME', 'your_database_name'); // Your database name from Hostinger panel
define('DB_USER', 'your_username');      // Your database username
define('DB_PASS', 'your_password');      // Your database password
define('DB_CHARSET', 'utf8mb4');

// Error reporting (set to 0 in production)
define('DEBUG_MODE', true); // Set to false in production

// Session configuration
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// File upload configuration
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LOG_DIR', __DIR__ . '/../logs/');

// Database connection function
function getDbConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact the administrator.");
            }
        }
    }

    return $pdo;
}

// Check if user is logged in
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Check if user is admin
function checkAdmin() {
    checkAuth();

    if ($_SESSION['role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied. Admin privileges required.');
    }
}

// Get current user info
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// JSON response helper
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}
?>
