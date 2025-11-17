<?php
/**
 * 데이터베이스 설정 파일
 * Hostinger 데이터베이스 정보를 입력하세요
 */

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CORS 설정 (필요시)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 데이터베이스 연결 정보 (Hostinger 정보로 변경하세요)
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

/**
 * 데이터베이스 연결 함수
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => '데이터베이스 연결에 실패했습니다.'
        ]);
        exit;
    }
}

/**
 * JSON 응답 함수
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 이메일 유효성 검사
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 클라이언트 IP 주소 가져오기
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * SQL 인젝션 방지를 위한 입력 정리
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
