<?php
/**
 * Machines API 엔드포인트
 * GET /api/machines - 모든 장비 조회
 * GET /api/machines/{id} - 특정 장비 조회
 * PUT /api/machines/{id} - 장비 업데이트
 * GET /api/machines/{id}/tools - 장비의 공구 목록
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/Machine.php';

$database = new Database();
$db = $database->getConnection();

$machine = new Machine($db);

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// URL에서 ID 추출
$uri_parts = explode('/', trim($request_uri, '/'));
$machine_id = isset($uri_parts[2]) ? $uri_parts[2] : null;
$action = isset($uri_parts[3]) ? $uri_parts[3] : null;

switch ($method) {
    case 'GET':
        if ($machine_id && $action === 'tools') {
            // 장비의 공구 목록 조회
            $machine->id = $machine_id;
            $stmt = $machine->getTools();
            $tools = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tool_item = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'size' => $row['size'],
                    'currentUsage' => floatval($row['current_usage']),
                    'lifespanLimit' => floatval($row['lifespan_limit']),
                    'status' => $row['status']
                ];
                array_push($tools, $tool_item);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $tools
            ]);

        } elseif ($machine_id) {
            // 특정 장비 조회
            $machine->id = $machine_id;

            if ($machine->readOne()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $machine->id,
                        'name' => $machine->name,
                        'status' => $machine->status,
                        'currentJob' => $machine->current_job,
                        'runtime' => floatval($machine->runtime),
                        'downtime' => floatval($machine->downtime),
                        'oee' => floatval($machine->oee)
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Machine not found'
                ]);
            }

        } else {
            // 모든 장비 조회
            $stmt = $machine->readAll();
            $machines = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $machine_item = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'status' => $row['status'],
                    'currentJob' => $row['current_job'],
                    'runtime' => floatval($row['runtime']),
                    'downtime' => floatval($row['downtime']),
                    'oee' => floatval($row['oee']),
                    'tools' => []
                ];
                array_push($machines, $machine_item);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $machines
            ]);
        }
        break;

    case 'PUT':
        if ($machine_id) {
            // 장비 업데이트
            $data = json_decode(file_get_contents("php://input"));

            if (!empty($data)) {
                $machine->id = $machine_id;
                $machine->status = $data->status ?? 'idle';
                $machine->current_job = $data->currentJob ?? null;
                $machine->runtime = $data->runtime ?? 0;
                $machine->downtime = $data->downtime ?? 0;

                // OEE 계산
                $machine->oee = $machine->calculateOEE();

                if ($machine->update()) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Machine updated successfully',
                        'data' => [
                            'id' => $machine->id,
                            'oee' => $machine->oee
                        ]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update machine'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid data'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Machine ID required'
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>
