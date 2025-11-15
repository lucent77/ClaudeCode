<?php

namespace App\Http\Controllers;

use PDO;
use App\Models\CaseActivityLog;

class CaseController
{
    private $db;

    public function __construct()
    {
        $config = include(__DIR__ . '/../../../config/database.php');
        $dbConfig = $config['connections']['mysql'];

        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
        $this->db = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get all cases
     */
    public function index()
    {
        header('Content-Type: application/json');

        try {
            $status = $_GET['status'] ?? null;
            $assignee = $_GET['assignee'] ?? null;
            $search = $_GET['search'] ?? null;

            $query = "SELECT c.*, u.name as assignee_name_full FROM cases c
                      LEFT JOIN users u ON c.assignee_user_id = u.id
                      WHERE c.deleted_at IS NULL";

            $params = [];

            if ($status) {
                $query .= " AND c.status = ?";
                $params[] = $status;
            }

            if ($assignee) {
                $query .= " AND c.assignee_user_id = ?";
                $params[] = $assignee;
            }

            if ($search) {
                $query .= " AND (c.patient_name LIKE ? OR c.notes LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $query .= " ORDER BY c.surgery_date DESC, c.created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'cases' => $cases,
                'count' => count($cases),
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch cases: ' . $e->getMessage()]);
        }
    }

    /**
     * Get single case
     */
    public function show($id)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("SELECT c.*, u.name as assignee_name_full FROM cases c
                                        LEFT JOIN users u ON c.assignee_user_id = u.id
                                        WHERE c.id = ? AND c.deleted_at IS NULL");
            $stmt->execute([$id]);
            $case = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$case) {
                http_response_code(404);
                echo json_encode(['error' => 'Case not found']);
                return;
            }

            // Get attachments
            $stmt = $this->db->prepare("SELECT * FROM case_attachments WHERE case_id = ? ORDER BY created_at DESC");
            $stmt->execute([$id]);
            $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get steps
            $stmt = $this->db->prepare("SELECT s.*, u.name as assigned_to_name FROM case_steps s
                                        LEFT JOIN users u ON s.assigned_to = u.id
                                        WHERE s.case_id = ? ORDER BY s.step_order");
            $stmt->execute([$id]);
            $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get activity logs
            $stmt = $this->db->prepare("SELECT l.*, u.name as user_name FROM case_activity_logs l
                                        LEFT JOIN users u ON l.user_id = u.id
                                        WHERE l.case_id = ? ORDER BY l.created_at DESC LIMIT 50");
            $stmt->execute([$id]);
            $activityLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $case['attachments'] = $attachments;
            $case['steps'] = $steps;
            $case['activity_logs'] = $activityLogs;

            echo json_encode([
                'success' => true,
                'case' => $case,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch case: ' . $e->getMessage()]);
        }
    }

    /**
     * Create new case
     */
    public function store()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['patient_name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Patient name is required']);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO cases
                (patient_name, assignee_name, assignee_user_id, surgery_date, surgery_time,
                 status, priority, notes, arch, existing_implants, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

            $stmt->execute([
                $input['patient_name'],
                $input['assignee_name'] ?? null,
                $input['assignee_user_id'] ?? null,
                $input['surgery_date'] ?? null,
                $input['surgery_time'] ?? null,
                $input['status'] ?? 'open',
                $input['priority'] ?? 'normal',
                $input['notes'] ?? null,
                $input['arch'] ?? null,
                $input['existing_implants'] ?? null,
            ]);

            $caseId = $this->db->lastInsertId();

            // Log activity
            $this->logActivity($caseId, 'created', 'Case created');

            echo json_encode([
                'success' => true,
                'case_id' => $caseId,
                'message' => 'Case created successfully',
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create case: ' . $e->getMessage()]);
        }
    }

    /**
     * Update case
     */
    public function update($id)
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        try {
            // Get current case data for logging
            $stmt = $this->db->prepare("SELECT * FROM cases WHERE id = ?");
            $stmt->execute([$id]);
            $oldCase = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldCase) {
                http_response_code(404);
                echo json_encode(['error' => 'Case not found']);
                return;
            }

            $updateFields = [];
            $params = [];

            $allowedFields = ['patient_name', 'assignee_name', 'assignee_user_id', 'surgery_date',
                              'surgery_time', 'status', 'priority', 'notes', 'arch', 'existing_implants',
                              'due_date', 'preop_scan_date', 'completed', 'ready_for_surgery'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $input[$field];
                }
            }

            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                return;
            }

            $updateFields[] = "updated_at = NOW()";
            $params[] = $id;

            $sql = "UPDATE cases SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // Log activity
            $changes = [];
            foreach ($allowedFields as $field) {
                if (isset($input[$field]) && $oldCase[$field] != $input[$field]) {
                    $changes[$field] = [
                        'old' => $oldCase[$field],
                        'new' => $input[$field],
                    ];
                }
            }

            if (!empty($changes)) {
                $this->logActivity($id, 'updated', 'Case updated', $changes);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Case updated successfully',
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update case: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete case (soft delete)
     */
    public function destroy($id)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("UPDATE cases SET deleted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Case not found']);
                return;
            }

            // Log activity
            $this->logActivity($id, 'deleted', 'Case deleted');

            echo json_encode([
                'success' => true,
                'message' => 'Case deleted successfully',
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete case: ' . $e->getMessage()]);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        header('Content-Type: application/json');

        try {
            $stats = [];

            // Total cases
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM cases WHERE deleted_at IS NULL");
            $stats['total_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Cases by status
            $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM cases
                                     WHERE deleted_at IS NULL GROUP BY status");
            $statusCounts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusCounts[$row['status']] = $row['count'];
            }
            $stats['by_status'] = $statusCounts;

            // Upcoming surgeries (next 30 days)
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM cases
                                     WHERE deleted_at IS NULL
                                     AND surgery_date >= CURDATE()
                                     AND surgery_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
            $stats['upcoming_surgeries'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Overdue cases
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM cases
                                     WHERE deleted_at IS NULL
                                     AND due_date < CURDATE()
                                     AND status != 'completed'");
            $stats['overdue_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            echo json_encode([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch statistics: ' . $e->getMessage()]);
        }
    }

    /**
     * Log case activity
     */
    private function logActivity($caseId, $action, $description, $changes = null)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO case_activity_logs
                (case_id, action, description, changes, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())");

            $stmt->execute([
                $caseId,
                $action,
                $description,
                $changes ? json_encode($changes) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
