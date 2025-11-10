<?php
/**
 * Tools API 엔드포인트
 * GET /api/tools - 모든 공구 조회
 * GET /api/tools/used - 사용 완료 공구 조회
 * GET /api/tools/{id} - 특정 공구 조회
 * POST /api/tools - 공구 생성
 * PUT /api/tools/{id} - 공구 업데이트
 * DELETE /api/tools/{id} - 공구 삭제
 * POST /api/tools/{id}/replace - 공구 교체
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../models/Tool.php';

$database = new Database();
$db = $database->getConnection();

$tool = new Tool($db);

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// URL에서 ID와 액션 추출
$uri_parts = explode('/', trim($request_uri, '/'));
$tool_id = isset($uri_parts[2]) && $uri_parts[2] !== 'used' ? $uri_parts[2] : null;
$action = isset($uri_parts[3]) ? $uri_parts[3] : null;

switch ($method) {
    case 'GET':
        if ($uri_parts[2] === 'used') {
            // 사용 완료 공구 조회
            $stmt = $tool->getUsedTools();
            $used_tools = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $used_tool = [
                    'id' => $row['id'],
                    'toolId' => $row['tool_id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'finalUsage' => floatval($row['final_usage']),
                    'lifespanLimit' => floatval($row['lifespan_limit']),
                    'machineId' => $row['machine_id'],
                    'replacedAt' => $row['replaced_at'],
                    'reason' => $row['reason']
                ];
                array_push($used_tools, $used_tool);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $used_tools
            ]);

        } elseif ($tool_id) {
            // 특정 공구 조회
            $tool->id = $tool_id;

            if ($tool->readOne()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $tool->id,
                        'code' => $tool->code,
                        'name' => $tool->name,
                        'category' => $tool->category,
                        'size' => $tool->size,
                        'supplier' => $tool->supplier,
                        'supplierModel' => $tool->supplier_model,
                        'currentStock' => intval($tool->current_stock),
                        'minStock' => intval($tool->min_stock),
                        'lifespanType' => $tool->lifespan_type,
                        'lifespanLimit' => floatval($tool->lifespan_limit),
                        'currentUsage' => floatval($tool->current_usage),
                        'status' => $tool->status,
                        'machineId' => $tool->machine_id,
                        'description' => $tool->description
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tool not found'
                ]);
            }

        } else {
            // 모든 공구 조회
            $stmt = $tool->readAll();
            $tools = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tool_item = [
                    'id' => $row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'size' => $row['size'],
                    'supplier' => $row['supplier'],
                    'supplierModel' => $row['supplier_model'],
                    'currentStock' => intval($row['current_stock']),
                    'minStock' => intval($row['min_stock']),
                    'lifespanType' => $row['lifespan_type'],
                    'lifespanLimit' => floatval($row['lifespan_limit']),
                    'currentUsage' => floatval($row['current_usage']),
                    'status' => $row['status'],
                    'machineId' => $row['machine_id'],
                    'description' => $row['description']
                ];
                array_push($tools, $tool_item);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $tools
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        if ($tool_id && $action === 'replace') {
            // 공구 교체
            $tool->id = $tool_id;

            if ($tool->readOne()) {
                $new_tool_data = [
                    'code' => $data->code ?? $tool->code,
                    'name' => $data->name ?? $tool->name,
                    'category' => $tool->category,
                    'size' => $tool->size,
                    'supplier' => $tool->supplier,
                    'supplier_model' => $tool->supplier_model,
                    'current_stock' => $tool->current_stock,
                    'min_stock' => $tool->min_stock,
                    'lifespan_type' => $tool->lifespan_type,
                    'lifespan_limit' => $data->lifespanLimit ?? $tool->lifespan_limit,
                    'description' => $tool->description
                ];

                $new_tool_id = $tool->replace($new_tool_data);

                if ($new_tool_id) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tool replaced successfully',
                        'data' => ['newToolId' => $new_tool_id]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to replace tool'
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tool not found'
                ]);
            }

        } else {
            // 공구 생성
            if (!empty($data->code) && !empty($data->name)) {
                $tool->code = $data->code;
                $tool->name = $data->name;
                $tool->category = $data->category ?? '';
                $tool->size = $data->size ?? '';
                $tool->supplier = $data->supplier ?? '';
                $tool->supplier_model = $data->supplierModel ?? '';
                $tool->current_stock = $data->currentStock ?? 0;
                $tool->min_stock = $data->minStock ?? 0;
                $tool->lifespan_type = $data->lifespanType ?? 'time';
                $tool->lifespan_limit = $data->lifespanLimit ?? 200;
                $tool->current_usage = 0;
                $tool->status = 'available';
                $tool->machine_id = null;
                $tool->description = $data->description ?? '';

                if ($tool->create()) {
                    http_response_code(201);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tool created successfully',
                        'data' => ['id' => $tool->id]
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to create tool'
                    ]);
                }
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Code and name are required'
                ]);
            }
        }
        break;

    case 'PUT':
        if ($tool_id) {
            $data = json_decode(file_get_contents("php://input"));

            $tool->id = $tool_id;

            if ($tool->readOne()) {
                $tool->code = $data->code ?? $tool->code;
                $tool->name = $data->name ?? $tool->name;
                $tool->category = $data->category ?? $tool->category;
                $tool->size = $data->size ?? $tool->size;
                $tool->supplier = $data->supplier ?? $tool->supplier;
                $tool->supplier_model = $data->supplierModel ?? $tool->supplier_model;
                $tool->current_stock = $data->currentStock ?? $tool->current_stock;
                $tool->min_stock = $data->minStock ?? $tool->min_stock;
                $tool->lifespan_type = $data->lifespanType ?? $tool->lifespan_type;
                $tool->lifespan_limit = $data->lifespanLimit ?? $tool->lifespan_limit;
                $tool->current_usage = $data->currentUsage ?? $tool->current_usage;
                $tool->status = $data->status ?? $tool->status;
                $tool->machine_id = $data->machineId ?? $tool->machine_id;
                $tool->description = $data->description ?? $tool->description;

                if ($tool->update()) {
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Tool updated successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update tool'
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tool not found'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tool ID required'
            ]);
        }
        break;

    case 'DELETE':
        if ($tool_id) {
            $tool->id = $tool_id;

            if ($tool->delete()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Tool deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete tool'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tool ID required'
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
