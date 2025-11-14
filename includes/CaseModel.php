<?php
/**
 * Case Model
 * Handles case management operations
 * Creodent AoX Elevate Dashboard
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/FileClassifier.php';

class CaseModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all cases with file counts
     *
     * @param array $filters
     * @return array
     */
    public function getAllCases($filters = []) {
        $sql = "SELECT * FROM v_case_summary WHERE 1=1";
        $params = [];

        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (patient_name LIKE :search OR case_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['surgeon'])) {
            $sql .= " AND surgeon = :surgeon";
            $params['surgeon'] = $filters['surgeon'];
        }

        // Ordering
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $sql .= " ORDER BY " . $orderBy . " " . $orderDir;

        // Pagination
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            if (isset($filters['offset'])) {
                $sql .= " OFFSET :offset";
            }
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get case by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getCaseById($id) {
        $sql = "SELECT * FROM v_case_summary WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Get case by case code
     *
     * @param string $caseCode
     * @return array|null
     */
    public function getCaseByCaseCode($caseCode) {
        $sql = "SELECT * FROM cases WHERE case_code = :case_code";
        return $this->db->fetchOne($sql, ['case_code' => $caseCode]);
    }

    /**
     * Create new case
     *
     * @param array $data
     * @return int|false Case ID or false
     */
    public function createCase($data) {
        $sql = "INSERT INTO cases (
            case_code, patient_name, surgery_date, arch, surgeon, clinic, status, notes
        ) VALUES (
            :case_code, :patient_name, :surgery_date, :arch, :surgeon, :clinic, :status, :notes
        )";

        try {
            $this->db->execute($sql, [
                'case_code' => $data['case_code'],
                'patient_name' => $data['patient_name'],
                'surgery_date' => $data['surgery_date'] ?? null,
                'arch' => $data['arch'] ?? null,
                'surgeon' => $data['surgeon'] ?? null,
                'clinic' => $data['clinic'] ?? null,
                'status' => $data['status'] ?? 'open',
                'notes' => $data['notes'] ?? null
            ]);

            $caseId = $this->db->lastInsertId();

            // Log activity
            $this->logActivity($caseId, 'case_created', 'Case created: ' . $data['case_code'], 'system');

            return $caseId;

        } catch (PDOException $e) {
            error_log("Failed to create case: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update case
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateCase($id, $data) {
        $allowedFields = ['patient_name', 'surgery_date', 'arch', 'surgeon', 'clinic', 'status', 'notes'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE cases SET " . implode(', ', $updates) . " WHERE id = :id";

        try {
            $this->db->execute($sql, $params);
            $this->logActivity($id, 'case_updated', 'Case updated', 'system');
            return true;
        } catch (PDOException $e) {
            error_log("Failed to update case: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete case
     *
     * @param int $id
     * @return bool
     */
    public function deleteCase($id) {
        $sql = "DELETE FROM cases WHERE id = :id";
        try {
            $this->db->execute($sql, ['id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to delete case: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get case attachments
     *
     * @param int $caseId
     * @param string|null $fileType
     * @return array
     */
    public function getCaseAttachments($caseId, $fileType = null) {
        $sql = "SELECT * FROM case_attachments WHERE case_id = :case_id";
        $params = ['case_id' => $caseId];

        if ($fileType) {
            $sql .= " AND file_type = :file_type";
            $params['file_type'] = $fileType;
        }

        $sql .= " ORDER BY uploaded_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Add attachment to case
     *
     * @param int $caseId
     * @param array $fileData
     * @return int|false Attachment ID or false
     */
    public function addAttachment($caseId, $fileData) {
        // Check if file already exists (by slack_file_id)
        $existing = $this->db->fetchOne(
            "SELECT id FROM case_attachments WHERE slack_file_id = :slack_file_id",
            ['slack_file_id' => $fileData['slack_file_id']]
        );

        if ($existing) {
            // Update existing
            return $this->updateAttachment($existing['id'], $fileData);
        }

        // Insert new
        $sql = "INSERT INTO case_attachments (
            case_id, slack_file_id, file_name, file_type, file_url, preview_url,
            permalink, mimetype, size, uploaded_by, uploaded_at
        ) VALUES (
            :case_id, :slack_file_id, :file_name, :file_type, :file_url, :preview_url,
            :permalink, :mimetype, :size, :uploaded_by, :uploaded_at
        )";

        try {
            $this->db->execute($sql, [
                'case_id' => $caseId,
                'slack_file_id' => $fileData['slack_file_id'],
                'file_name' => $fileData['file_name'],
                'file_type' => $fileData['file_type'],
                'file_url' => $fileData['file_url'],
                'preview_url' => $fileData['preview_url'] ?? null,
                'permalink' => $fileData['permalink'] ?? null,
                'mimetype' => $fileData['mimetype'] ?? null,
                'size' => $fileData['size'] ?? 0,
                'uploaded_by' => $fileData['uploaded_by'] ?? null,
                'uploaded_at' => $fileData['uploaded_at'] ?? date('Y-m-d H:i:s')
            ]);

            $attachmentId = $this->db->lastInsertId();
            $this->logActivity($caseId, 'file_added', 'File added: ' . $fileData['file_name'], 'system');

            return $attachmentId;

        } catch (PDOException $e) {
            error_log("Failed to add attachment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update attachment
     *
     * @param int $id
     * @param array $fileData
     * @return bool
     */
    private function updateAttachment($id, $fileData) {
        $sql = "UPDATE case_attachments SET
            file_name = :file_name,
            file_type = :file_type,
            file_url = :file_url,
            preview_url = :preview_url,
            mimetype = :mimetype,
            size = :size
            WHERE id = :id";

        try {
            $this->db->execute($sql, [
                'id' => $id,
                'file_name' => $fileData['file_name'],
                'file_type' => $fileData['file_type'],
                'file_url' => $fileData['file_url'],
                'preview_url' => $fileData['preview_url'] ?? null,
                'mimetype' => $fileData['mimetype'] ?? null,
                'size' => $fileData['size'] ?? 0
            ]);

            return $id;

        } catch (PDOException $e) {
            error_log("Failed to update attachment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get case activity logs
     *
     * @param int $caseId
     * @param int $limit
     * @return array
     */
    public function getCaseActivityLogs($caseId, $limit = 50) {
        $sql = "SELECT * FROM case_activity_logs
                WHERE case_id = :case_id
                ORDER BY created_at DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            'case_id' => $caseId,
            'limit' => $limit
        ]);
    }

    /**
     * Log case activity
     *
     * @param int $caseId
     * @param string $action
     * @param string $message
     * @param string $user
     * @return bool
     */
    public function logActivity($caseId, $action, $message, $user = 'system') {
        $sql = "INSERT INTO case_activity_logs (case_id, action, message, user)
                VALUES (:case_id, :action, :message, :user)";

        try {
            $this->db->execute($sql, [
                'case_id' => $caseId,
                'action' => $action,
                'message' => $message,
                'user' => $user
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get case statistics
     *
     * @return array
     */
    public function getStatistics() {
        $stats = [
            'total_cases' => 0,
            'open_cases' => 0,
            'in_progress_cases' => 0,
            'completed_cases' => 0,
            'total_files' => 0,
            'recent_cases' => []
        ];

        // Total cases
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM cases");
        $stats['total_cases'] = $result['count'] ?? 0;

        // Cases by status
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM cases WHERE status = 'open'");
        $stats['open_cases'] = $result['count'] ?? 0;

        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM cases WHERE status = 'in_progress'");
        $stats['in_progress_cases'] = $result['count'] ?? 0;

        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM cases WHERE status = 'completed'");
        $stats['completed_cases'] = $result['count'] ?? 0;

        // Total files
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM case_attachments");
        $stats['total_files'] = $result['count'] ?? 0;

        // Recent cases
        $stats['recent_cases'] = $this->getAllCases(['limit' => 5, 'order_by' => 'created_at', 'order_dir' => 'DESC']);

        return $stats;
    }
}

?>
