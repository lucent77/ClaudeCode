<?php
/**
 * Cases API Endpoint
 * Creodent AoX Elevate Dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/CaseModel.php';

$caseModel = new CaseModel();
$method = $_SERVER['REQUEST_METHOD'];

/**
 * GET /api/cases.php - Get all cases or single case
 * GET /api/cases.php?id=123 - Get case by ID
 * GET /api/cases.php?search=patient - Search cases
 */
if ($method === 'GET') {
    try {
        if (isset($_GET['id'])) {
            // Get single case
            $case = $caseModel->getCaseById($_GET['id']);

            if ($case) {
                // Get attachments grouped by type
                $attachments = $caseModel->getCaseAttachments($_GET['id']);
                $case['attachments'] = $attachments;

                // Group attachments by type
                $case['attachments_by_type'] = [];
                foreach ($attachments as $attachment) {
                    $type = $attachment['file_type'];
                    if (!isset($case['attachments_by_type'][$type])) {
                        $case['attachments_by_type'][$type] = [];
                    }
                    $case['attachments_by_type'][$type][] = $attachment;
                }

                // Get activity logs
                $case['activity_logs'] = $caseModel->getCaseActivityLogs($_GET['id'], 20);

                echo json_encode([
                    'success' => true,
                    'data' => $case
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Case not found'
                ]);
            }
        } else {
            // Get all cases with filters
            $filters = [
                'search' => $_GET['search'] ?? null,
                'status' => $_GET['status'] ?? null,
                'surgeon' => $_GET['surgeon'] ?? null,
                'limit' => isset($_GET['limit']) ? intval($_GET['limit']) : null,
                'offset' => isset($_GET['offset']) ? intval($_GET['offset']) : 0,
                'order_by' => $_GET['order_by'] ?? 'created_at',
                'order_dir' => $_GET['order_dir'] ?? 'DESC'
            ];

            $cases = $caseModel->getAllCases($filters);

            echo json_encode([
                'success' => true,
                'data' => $cases,
                'count' => count($cases)
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
        ]);
    }
}

/**
 * POST /api/cases.php - Create new case
 */
elseif ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['case_code']) || !isset($input['patient_name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'case_code and patient_name are required'
            ]);
            exit;
        }

        $caseId = $caseModel->createCase($input);

        if ($caseId) {
            $case = $caseModel->getCaseById($caseId);
            echo json_encode([
                'success' => true,
                'data' => $case,
                'message' => 'Case created successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create case'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
        ]);
    }
}

/**
 * PUT /api/cases.php?id=123 - Update case
 */
elseif ($method === 'PUT') {
    try {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Case ID is required'
            ]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $success = $caseModel->updateCase($_GET['id'], $input);

        if ($success) {
            $case = $caseModel->getCaseById($_GET['id']);
            echo json_encode([
                'success' => true,
                'data' => $case,
                'message' => 'Case updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update case'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
        ]);
    }
}

/**
 * DELETE /api/cases.php?id=123 - Delete case
 */
elseif ($method === 'DELETE') {
    try {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Case ID is required'
            ]);
            exit;
        }

        $success = $caseModel->deleteCase($_GET['id']);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Case deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to delete case'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
        ]);
    }
}

else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}

?>
