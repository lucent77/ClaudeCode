<?php
/**
 * Helper Functions
 * Purchase Management System
 */

/**
 * Sanitize input data
 *
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to another page
 *
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if user is logged in
 *
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Check if user is admin
 *
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login (redirect to login if not logged in)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/purchase-system/login.php');
    }
}

/**
 * Require admin (redirect to dashboard if not admin)
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/purchase-system/dashboard.php');
    }
}

/**
 * Format currency
 *
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 *
 * @param string $date Date to format
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Get status badge HTML
 *
 * @param string $status Status value
 * @return string HTML for status badge
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'approved' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Approved</span>',
        'ordered' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Ordered</span>',
        'delivered' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Delivered</span>',
        'completed' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completed</span>',
        'rejected' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>',
    ];
    return $badges[$status] ?? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">' . ucfirst($status) . '</span>';
}

/**
 * Handle file upload
 *
 * @param array $file $_FILES array element
 * @param string $uploadDir Upload directory
 * @return array ['success' => bool, 'message' => string, 'path' => string|null]
 */
function handleFileUpload($file, $uploadDir = UPLOAD_DIR) {
    // Check if file was uploaded
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'No file uploaded', 'path' => null];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred', 'path' => null];
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed size (5MB)', 'path' => null];
    }

    // Check file extension
    $filename = $file['name'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'File type not allowed', 'path' => null];
    }

    // Generate unique filename
    $newFilename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $newFilename;

    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'message' => 'File uploaded successfully', 'path' => 'uploads/' . $newFilename];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file', 'path' => null];
}

/**
 * Delete uploaded file
 *
 * @param string $filePath Relative file path
 * @return bool
 */
function deleteUploadedFile($filePath) {
    if (empty($filePath)) return false;

    $fullPath = __DIR__ . '/../' . $filePath;

    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }

    return false;
}

/**
 * Get pagination HTML
 *
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination links
 * @return string HTML for pagination
 */
function getPagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';

    $html = '<div class="flex justify-center items-center space-x-2 mt-6">';

    // Previous button
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="px-3 py-2 bg-white border rounded hover:bg-gray-50">Previous</a>';
    }

    // Page numbers
    $range = 2;
    for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
        $active = $i === $currentPage ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-50';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="px-3 py-2 border rounded ' . $active . '">' . $i . '</a>';
    }

    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="px-3 py-2 bg-white border rounded hover:bg-gray-50">Next</a>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Send email notification (if SMTP is configured)
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool
 */
function sendEmail($to, $subject, $message) {
    if (!SMTP_ENABLED) {
        return false;
    }

    // Basic email sending using PHP mail()
    // For production, consider using PHPMailer or similar library
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $message, $headers);
}

/**
 * Create notification for user
 *
 * @param int $userId User ID
 * @param string $message Notification message
 * @param int|null $purchaseRequestId Purchase request ID
 * @return bool
 */
function createNotification($userId, $message, $purchaseRequestId = null) {
    $db = Database::getInstance();
    try {
        $db->insert('notifications', [
            'user_id' => $userId,
            'purchase_request_id' => $purchaseRequestId,
            'message' => $message
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for user
 *
 * @param int $userId User ID
 * @return int
 */
function getUnreadNotificationCount($userId) {
    $db = Database::getInstance();
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
    return $result ? (int)$result['count'] : 0;
}

/**
 * Escape output for HTML
 *
 * @param string $string String to escape
 * @return string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if string contains value
 *
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool
 */
function str_contains_polyfill($haystack, $needle) {
    if (function_exists('str_contains')) {
        return str_contains($haystack, $needle);
    }
    return $needle !== '' && mb_strpos($haystack, $needle) !== false;
}

/**
 * Get flash message and clear it
 *
 * @param string $key Flash message key
 * @return string|null
 */
function getFlash($key = 'message') {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * Set flash message
 *
 * @param string $message Flash message
 * @param string $type Message type (success, error, info, warning)
 * @param string $key Flash message key
 */
function setFlash($message, $type = 'success', $key = 'message') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Display flash message HTML
 *
 * @return string
 */
function displayFlash() {
    $flash = getFlash();
    if (!$flash) return '';

    $colors = [
        'success' => 'bg-green-100 border-green-400 text-green-700',
        'error' => 'bg-red-100 border-red-400 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
        'info' => 'bg-blue-100 border-blue-400 text-blue-700',
    ];

    $color = $colors[$flash['type']] ?? $colors['info'];

    return '<div class="' . $color . ' border px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">' . e($flash['message']) . '</span>
    </div>';
}

?>
