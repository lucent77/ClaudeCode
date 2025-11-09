<?php
/**
 * Common Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '';
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate pagination
 */
function getPagination($total, $current_page, $per_page = RECORDS_PER_PAGE) {
    $total_pages = ceil($total / $per_page);
    $offset = ($current_page - 1) * $per_page;

    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

/**
 * Get all users (for assignments, etc.)
 */
function getAllUsers($role = null) {
    $db = getDB();

    try {
        $sql = "SELECT user_id, username, first_name, last_name, email, role
                FROM users
                WHERE is_active = 1";

        if ($role) {
            $sql .= " AND role = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$role]);
        } else {
            $stmt = $db->query($sql);
        }

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get Users Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    $db = getDB();

    try {
        $stmt = $db->prepare("
            SELECT user_id, username, first_name, last_name, email, role, phone, is_active
            FROM users
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get User Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get customer by ID
 */
function getCustomerById($customer_id) {
    $db = getDB();

    try {
        $stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Customer Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get assigned sales rep for customer
 */
function getAssignedSalesRep($customer_id) {
    $db = getDB();

    try {
        $stmt = $db->prepare("
            SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, a.assignment_date
            FROM assignments a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.customer_id = ? AND a.is_current = 1
            ORDER BY a.assignment_date DESC
            LIMIT 1
        ");
        $stmt->execute([$customer_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Assigned Rep Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if customer is assigned to current user
 */
function isCustomerAssignedToUser($customer_id, $user_id = null) {
    if ($user_id === null) {
        $user_id = getCurrentUserId();
    }

    // Admins and managers can see all customers
    if (hasAnyRole(['admin', 'manager'])) {
        return true;
    }

    $db = getDB();

    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM assignments
            WHERE customer_id = ? AND user_id = ? AND is_current = 1
        ");
        $stmt->execute([$customer_id, $user_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Check Assignment Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get customer status badge color
 */
function getStatusBadgeClass($status) {
    $classes = [
        'A' => 'badge-success',
        'I' => 'badge-secondary',
        'P' => 'badge-warning',
        'D' => 'badge-danger'
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Parse CSV line handling quoted fields with commas
 */
function parseCSVLine($line) {
    return str_getcsv($line);
}

/**
 * Upload file
 */
function uploadFile($file, $allowed_types = ALLOWED_EXTENSIONS) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error occurred'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

/**
 * Convert CSV date to MySQL format
 */
function convertCSVDate($date) {
    if (empty($date)) {
        return null;
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Convert boolean string to int
 */
function convertBoolean($value) {
    $value = strtoupper(trim($value));
    return ($value === 'TRUE' || $value === '1' || $value === 'YES') ? 1 : 0;
}
?>
