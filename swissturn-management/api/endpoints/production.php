<?php
/**
 * Production API 엔드포인트
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/Production.php';

$database = new Database();
$db = $database->getConnection();
$production = new Production($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        $stmt = $production->readAll($limit, $offset);
        $records = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($records, $row);
        }

        echo json_encode([
            'success' => true,
            'data' => $records
        ]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if (isset($data->bulk) && is_array($data->records)) {
            // CSV 일괄 삽입
            if ($production->bulkInsert($data->records)) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Records inserted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to insert records'
                ]);
            }
        } else {
            // 단일 생산 기록 생성
            foreach ($data as $key => $value) {
                if (property_exists($production, $key)) {
                    $production->$key = $value;
                }
            }

            if ($production->create()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'data' => ['id' => $production->id]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create record'
                ]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
