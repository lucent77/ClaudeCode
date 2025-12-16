<?php
/**
 * Utility Functions
 */

/**
 * Safely escape HTML output
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get request parameter
 */
function getParam($key, $default = null) {
    return $_REQUEST[$key] ?? $default;
}

/**
 * Get POST parameter
 */
function postParam($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Validate employee ID format
 */
function isValidEmployeeId($id) {
    return preg_match('/^E\d{3}$/', $id);
}

/**
 * Validate prize ID format
 */
function isValidPrizeId($id) {
    return preg_match('/^P\d{3}$/', $id);
}

/**
 * Sort prizes by number
 */
function sortPrizesByNum($prizes) {
    usort($prizes, fn($a, $b) => $a['num'] - $b['num']);
    return $prizes;
}

/**
 * Sort employees by name
 */
function sortEmployeesByName($employees) {
    usort($employees, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    return $employees;
}

/**
 * Pick random winner from list
 */
function pickRandomWinner($candidates) {
    if (empty($candidates)) {
        return null;
    }
    $index = random_int(0, count($candidates) - 1);
    return $candidates[$index];
}

/**
 * Format date for display
 */
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M j, Y g:i A');
}

/**
 * Get tier display name
 */
function getTierDisplay($tier) {
    switch ($tier) {
        case 'jackpot':
            return '🎰 JACKPOT';
        default:
            return 'Regular';
    }
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status, $type = 'employee') {
    if ($type === 'employee') {
        if ($status === 'done') {
            return '<span class="badge badge-good">Won</span>';
        }
        return '<span class="badge badge-pending">Pending</span>';
    } else {
        if ($status === 'won') {
            return '<span class="badge badge-good">Won</span>';
        }
        return '<span class="badge badge-available">Available</span>';
    }
}

/**
 * Log message to file (for debugging)
 */
function logMessage($message, $level = 'INFO') {
    $logFile = DATA_DIR . '/raffle.log';
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}
