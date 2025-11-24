<?php
/**
 * Update Prize API (Admin)
 * Handles create, update, and delete operations
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

// Read prizes data
$prizesData = readJSONFile(PRIZES_FILE);
if (!$prizesData) {
    sendJSON(['success' => false, 'error' => 'Failed to load prizes data'], 500);
}

// Get input
$input = getJSONInput();

// Handle different operations
switch ($method) {
    case 'POST': // Create new prize
        // Validate input
        if (!isset($input['name']) || empty($input['name'])) {
            sendJSON(['success' => false, 'error' => 'Prize name is required'], 400);
        }

        if (!isset($input['probability']) || !is_numeric($input['probability'])) {
            sendJSON(['success' => false, 'error' => 'Valid probability is required'], 400);
        }

        if (!isset($input['stock']) || !is_numeric($input['stock'])) {
            sendJSON(['success' => false, 'error' => 'Valid stock is required'], 400);
        }

        // Create new prize
        $newPrize = [
            'id' => $prizesData['last_id'] + 1,
            'name' => trim($input['name']),
            'probability' => floatval($input['probability']),
            'color' => isset($input['color']) ? $input['color'] : '#' . substr(md5(rand()), 0, 6),
            'stock' => intval($input['stock']),
            'is_active' => isset($input['is_active']) ? intval($input['is_active']) : 1,
            'is_special' => isset($input['is_special']) ? intval($input['is_special']) : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $prizesData['prizes'][] = $newPrize;
        $prizesData['last_id']++;

        if (writeJSONFile(PRIZES_FILE, $prizesData)) {
            sendJSON(['success' => true, 'prize' => $newPrize]);
        } else {
            sendJSON(['success' => false, 'error' => 'Failed to save prize'], 500);
        }
        break;

    case 'PUT': // Update existing prize
        if (!isset($input['id'])) {
            sendJSON(['success' => false, 'error' => 'Prize ID is required'], 400);
        }

        $prizeId = intval($input['id']);
        $prizeFound = false;

        foreach ($prizesData['prizes'] as &$prize) {
            if ($prize['id'] == $prizeId) {
                // Update fields
                if (isset($input['name'])) {
                    $prize['name'] = trim($input['name']);
                }
                if (isset($input['probability'])) {
                    $prize['probability'] = floatval($input['probability']);
                }
                if (isset($input['color'])) {
                    $prize['color'] = $input['color'];
                }
                if (isset($input['stock'])) {
                    $prize['stock'] = intval($input['stock']);
                }
                if (isset($input['is_active'])) {
                    $prize['is_active'] = intval($input['is_active']);
                }
                if (isset($input['is_special'])) {
                    $prize['is_special'] = intval($input['is_special']);
                }

                $prizeFound = true;
                break;
            }
        }

        if (!$prizeFound) {
            sendJSON(['success' => false, 'error' => 'Prize not found'], 404);
        }

        if (writeJSONFile(PRIZES_FILE, $prizesData)) {
            sendJSON(['success' => true, 'message' => 'Prize updated successfully']);
        } else {
            sendJSON(['success' => false, 'error' => 'Failed to update prize'], 500);
        }
        break;

    case 'DELETE': // Delete prize
        if (!isset($input['id'])) {
            sendJSON(['success' => false, 'error' => 'Prize ID is required'], 400);
        }

        $prizeId = intval($input['id']);
        $originalCount = count($prizesData['prizes']);

        $prizesData['prizes'] = array_filter($prizesData['prizes'], function($prize) use ($prizeId) {
            return $prize['id'] != $prizeId;
        });

        // Re-index array
        $prizesData['prizes'] = array_values($prizesData['prizes']);

        if (count($prizesData['prizes']) == $originalCount) {
            sendJSON(['success' => false, 'error' => 'Prize not found'], 404);
        }

        if (writeJSONFile(PRIZES_FILE, $prizesData)) {
            sendJSON(['success' => true, 'message' => 'Prize deleted successfully']);
        } else {
            sendJSON(['success' => false, 'error' => 'Failed to delete prize'], 500);
        }
        break;

    default:
        sendJSON(['success' => false, 'error' => 'Invalid request method'], 405);
}
