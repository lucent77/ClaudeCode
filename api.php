<?php
/**
 * API Endpoints for Scanbody File Manager
 */

header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ========== SYSTEMS ==========
        case 'get_systems':
            $systems = $db->getAllSystems();
            echo json_encode(['success' => true, 'data' => $systems]);
            break;

        case 'create_system':
            $data = json_decode(file_get_contents('php://input'), true);
            $system_name = sanitize_input($data['system_name']);
            $size = sanitize_input($data['size']);
            $sku = sanitize_input($data['sku']);

            if ($db->createSystem($system_name, $size, $sku)) {
                echo json_encode(['success' => true, 'message' => 'System created successfully']);
            } else {
                throw new Exception('Failed to create system');
            }
            break;

        case 'update_system':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            $system_name = sanitize_input($data['system_name']);
            $size = sanitize_input($data['size']);
            $sku = sanitize_input($data['sku']);

            if ($db->updateSystem($id, $system_name, $size, $sku)) {
                echo json_encode(['success' => true, 'message' => 'System updated successfully']);
            } else {
                throw new Exception('Failed to update system');
            }
            break;

        case 'delete_system':
            $id = intval($_GET['id'] ?? 0);
            if ($db->deleteSystem($id)) {
                echo json_encode(['success' => true, 'message' => 'System deleted successfully']);
            } else {
                throw new Exception('Failed to delete system');
            }
            break;

        // ========== MANUFACTURERS ==========
        case 'get_manufacturers':
            $manufacturers = $db->getAllManufacturers();
            echo json_encode(['success' => true, 'data' => $manufacturers]);
            break;

        case 'create_manufacturer':
            $data = json_decode(file_get_contents('php://input'), true);
            $name = sanitize_input($data['name']);
            $type = sanitize_input($data['type']);

            if ($db->createManufacturer($name, $type)) {
                echo json_encode(['success' => true, 'message' => 'Manufacturer created successfully']);
            } else {
                throw new Exception('Failed to create manufacturer');
            }
            break;

        case 'update_manufacturer':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id']);
            $name = sanitize_input($data['name']);
            $type = sanitize_input($data['type']);

            if ($db->updateManufacturer($id, $name, $type)) {
                echo json_encode(['success' => true, 'message' => 'Manufacturer updated successfully']);
            } else {
                throw new Exception('Failed to update manufacturer');
            }
            break;

        case 'delete_manufacturer':
            $id = intval($_GET['id'] ?? 0);
            if ($db->deleteManufacturer($id)) {
                echo json_encode(['success' => true, 'message' => 'Manufacturer deleted successfully']);
            } else {
                throw new Exception('Failed to delete manufacturer');
            }
            break;

        // ========== SCANBODIES ==========
        case 'get_scanbodies':
            $scanbodies = $db->getAllScanbodies();
            echo json_encode(['success' => true, 'data' => $scanbodies]);
            break;

        case 'get_data_matrix':
            $matrix = $db->getDataMatrix();
            echo json_encode(['success' => true, 'data' => $matrix]);
            break;

        case 'update_scanbody_code':
            $data = json_decode(file_get_contents('php://input'), true);
            $system_id = intval($data['system_id']);
            $manufacturer_id = intval($data['manufacturer_id']);
            $id_code = sanitize_input($data['id_code']);

            // Check if scanbody exists
            $existing = $db->getScanbodyBySystemAndManufacturer($system_id, $manufacturer_id);

            if ($existing) {
                // Update existing
                if ($db->updateScanbody($existing['id'], $id_code)) {
                    echo json_encode(['success' => true, 'message' => 'ID Code updated successfully']);
                } else {
                    throw new Exception('Failed to update ID Code');
                }
            } else {
                // Create new
                if ($db->createScanbody($system_id, $manufacturer_id, $id_code)) {
                    echo json_encode(['success' => true, 'message' => 'ID Code saved successfully']);
                } else {
                    throw new Exception('Failed to save ID Code');
                }
            }
            break;

        case 'upload_stl':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error');
            }

            $file = $_FILES['file'];
            $system_id = intval($_POST['system_id']);
            $manufacturer_id = intval($_POST['manufacturer_id']);
            $id_code = sanitize_input($_POST['id_code'] ?? '');

            // Validate file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ALLOWED_EXTENSIONS)) {
                throw new Exception('Only STL files are allowed');
            }

            // Validate file size
            if ($file['size'] > MAX_FILE_SIZE) {
                throw new Exception('File size exceeds maximum allowed size');
            }

            // Generate unique filename
            $unique_filename = generate_unique_filename($file['name']);
            $upload_path = UPLOAD_DIR . $unique_filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Check if scanbody exists
            $existing = $db->getScanbodyBySystemAndManufacturer($system_id, $manufacturer_id);

            if ($existing) {
                // Delete old file if exists
                if ($existing['stl_file_path']) {
                    $old_file = UPLOAD_DIR . $existing['stl_file_path'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                // Update existing
                if ($db->updateScanbody($existing['id'], $id_code, $unique_filename, $file['name'], $file['size'])) {
                    echo json_encode(['success' => true, 'message' => 'STL file uploaded successfully', 'filename' => $unique_filename]);
                } else {
                    throw new Exception('Failed to update scanbody with file information');
                }
            } else {
                // Create new
                if ($db->createScanbody($system_id, $manufacturer_id, $id_code, $unique_filename, $file['name'], $file['size'])) {
                    echo json_encode(['success' => true, 'message' => 'STL file uploaded successfully', 'filename' => $unique_filename]);
                } else {
                    throw new Exception('Failed to create scanbody with file information');
                }
            }
            break;

        case 'delete_stl':
            $data = json_decode(file_get_contents('php://input'), true);
            $system_id = intval($data['system_id']);
            $manufacturer_id = intval($data['manufacturer_id']);

            $existing = $db->getScanbodyBySystemAndManufacturer($system_id, $manufacturer_id);

            if ($existing && $existing['stl_file_path']) {
                $file_path = UPLOAD_DIR . $existing['stl_file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // Update database to remove file reference
                if ($db->updateScanbody($existing['id'], $existing['id_code'], null, null, null)) {
                    echo json_encode(['success' => true, 'message' => 'STL file deleted successfully']);
                } else {
                    throw new Exception('Failed to update database');
                }
            } else {
                throw new Exception('No file found');
            }
            break;

        case 'search':
            $search_term = sanitize_input($_GET['q'] ?? '');
            if (empty($search_term)) {
                $results = $db->getAllScanbodies();
            } else {
                $results = $db->searchScanbodies($search_term);
            }
            echo json_encode(['success' => true, 'data' => $results]);
            break;

        // ========== CSV IMPORT ==========
        case 'import_csv':
            if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('CSV file upload error');
            }

            $file = $_FILES['csv'];
            $csv_data = array_map('str_getcsv', file($file['tmp_name']));

            if (empty($csv_data)) {
                throw new Exception('CSV file is empty');
            }

            // Get headers
            $headers = array_shift($csv_data);

            // Extract manufacturer columns (skip first 3 columns: System, Size, SKU)
            $manufacturer_columns = array_slice($headers, 3);

            $imported_systems = 0;
            $imported_manufacturers = 0;
            $imported_scanbodies = 0;

            // Import manufacturers first
            $manufacturer_map = [];
            foreach ($manufacturer_columns as $index => $column) {
                // Parse "ARGEN [3Shape]" format
                if (preg_match('/(.+?)\s*\[(.+?)\]/', $column, $matches)) {
                    $name = trim($matches[1]);
                    $type = trim($matches[2]);

                    // Check if manufacturer exists
                    $manufacturers = $db->getAllManufacturers();
                    $found = false;
                    foreach ($manufacturers as $m) {
                        if ($m['name'] === $name && $m['type'] === $type) {
                            $manufacturer_map[$index + 3] = $m['id'];
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        if ($db->createManufacturer($name, $type)) {
                            $manufacturers = $db->getAllManufacturers();
                            foreach ($manufacturers as $m) {
                                if ($m['name'] === $name && $m['type'] === $type) {
                                    $manufacturer_map[$index + 3] = $m['id'];
                                    $imported_manufacturers++;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // Import systems and scanbodies
            foreach ($csv_data as $row) {
                if (count($row) < 3) continue;

                $system_name = trim($row[0]);
                $size = trim($row[1]);
                $sku = trim($row[2]);

                if (empty($system_name) || empty($size) || empty($sku)) continue;

                // Check if system exists
                $systems = $db->getAllSystems();
                $system_id = null;
                foreach ($systems as $s) {
                    if ($s['sku'] === $sku) {
                        $system_id = $s['id'];
                        break;
                    }
                }

                // Create system if not exists
                if (!$system_id) {
                    if ($db->createSystem($system_name, $size, $sku)) {
                        $systems = $db->getAllSystems();
                        foreach ($systems as $s) {
                            if ($s['sku'] === $sku) {
                                $system_id = $s['id'];
                                $imported_systems++;
                                break;
                            }
                        }
                    }
                }

                // Import scanbody data
                if ($system_id) {
                    foreach ($manufacturer_map as $col_index => $manufacturer_id) {
                        if (isset($row[$col_index]) && !empty(trim($row[$col_index]))) {
                            $id_code = trim($row[$col_index]);

                            // Check if scanbody exists
                            $existing = $db->getScanbodyBySystemAndManufacturer($system_id, $manufacturer_id);

                            if (!$existing) {
                                if ($db->createScanbody($system_id, $manufacturer_id, $id_code)) {
                                    $imported_scanbodies++;
                                }
                            }
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Import completed successfully",
                'stats' => [
                    'systems' => $imported_systems,
                    'manufacturers' => $imported_manufacturers,
                    'scanbodies' => $imported_scanbodies
                ]
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
