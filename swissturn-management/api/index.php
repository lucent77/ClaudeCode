<?php
/**
 * API 라우터
 * 모든 API 요청을 적절한 엔드포인트로 라우팅
 */

// CORS 헤더
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 요청 URI 파싱
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/', trim($request_uri, '/'));

// API 경로 확인
if (!isset($uri_parts[0]) || $uri_parts[0] !== 'api') {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'API endpoint not found'
    ]);
    exit();
}

// 엔드포인트 결정
$endpoint = isset($uri_parts[1]) ? $uri_parts[1] : '';

switch ($endpoint) {
    case 'machines':
        include_once 'endpoints/machines.php';
        break;

    case 'tools':
        include_once 'endpoints/tools.php';
        break;

    case 'production':
        include_once 'endpoints/production.php';
        break;

    case 'auth':
        include_once 'endpoints/auth.php';
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
        break;
}
?>
