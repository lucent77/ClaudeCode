<?php
/**
 * Offline Prize Drawing System - Configuration
 * JSON File-based Data Storage
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Data directory path
define('DATA_DIR', __DIR__ . '/../data/');

// JSON file names
define('PRIZES_FILE', 'prizes.json');
define('SPECIAL_PRIZE_SETTINGS_FILE', 'special_prize_settings.json');

/**
 * Read JSON file
 * @param string $filename
 * @return array|null
 */
function readJSONFile($filename) {
    $filepath = DATA_DIR . $filename;

    if (!file_exists($filepath)) {
        // Create initial file structure
        if ($filename === PRIZES_FILE) {
            return ['prizes' => [], 'last_id' => 0];
        } elseif ($filename === SPECIAL_PRIZE_SETTINGS_FILE) {
            return [
                'id' => 1,
                'special_prize_id' => null,
                'milestone_count' => 100,
                'current_count' => 0,
                'is_active' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        return null;
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        error_log("Failed to read file: $filepath");
        return null;
    }

    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . " in file: $filepath");
        return null;
    }

    return $data;
}

/**
 * Write JSON file with backup
 * @param string $filename
 * @param array $data
 * @return bool
 */
function writeJSONFile($filename, $data) {
    $filepath = DATA_DIR . $filename;
    $backupPath = $filepath . '.backup';

    // Create backup if file exists
    if (file_exists($filepath)) {
        copy($filepath, $backupPath);
    }

    // Encode data
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($jsonContent === false) {
        error_log("JSON encode error: " . json_last_error_msg());
        return false;
    }

    // Write with file locking
    $fp = fopen($filepath, 'w');
    if (!$fp) {
        error_log("Failed to open file for writing: $filepath");
        return false;
    }

    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $jsonContent);
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        error_log("Failed to lock file: $filepath");
        fclose($fp);
        return false;
    }

    fclose($fp);
    return true;
}

/**
 * Send JSON response
 * @param array $data
 * @param int $statusCode
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get POST input as JSON
 * @return array|null
 */
function getJSONInput() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return $_POST;
    }
    return json_decode($input, true);
}

/**
 * Validate probability sum (should equal 100)
 * @param array $prizes
 * @return bool
 */
function validateProbabilities($prizes) {
    $total = 0;
    foreach ($prizes as $prize) {
        if ($prize['is_active'] == 1) {
            $total += floatval($prize['probability']);
        }
    }
    return abs($total - 100.0) < 0.01; // Allow small floating point errors
}
