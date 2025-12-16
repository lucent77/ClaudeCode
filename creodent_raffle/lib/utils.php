<?php
/**
 * CREODENT HV Raffle 2025 - Utility Functions
 */

/**
 * Escape HTML to prevent XSS
 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Get query parameter with default value
 */
function getParam($key, $default = null) {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * Get POST parameter with default value
 */
function postParam($key, $default = null) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Format date for display
 */
function formatDate($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Generate CSV from array
 */
function arrayToCSV($data, $headers = null) {
    $output = fopen('php://temp', 'r+');

    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($headers) {
        fputcsv($output, $headers);
    }

    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv;
}

/**
 * Shuffle array with seed for reproducibility (optional)
 */
function shuffleArray($array, $seed = null) {
    if ($seed !== null) {
        mt_srand($seed);
    }

    $keys = array_keys($array);
    for ($i = count($keys) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $temp = $keys[$i];
        $keys[$i] = $keys[$j];
        $keys[$j] = $temp;
    }

    $shuffled = [];
    foreach ($keys as $key) {
        $shuffled[$key] = $array[$key];
    }

    return $shuffled;
}

/**
 * Get random element from array
 */
function randomElement($array) {
    if (empty($array)) {
        return null;
    }
    return $array[array_rand($array)];
}

/**
 * Format number with ordinal suffix
 */
function ordinal($number) {
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number . 'th';
    }
    return $number . $ends[$number % 10];
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
 * Debug log (only in development)
 */
function debugLog($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $log = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $log .= ' - ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        error_log($log);
    }
}
