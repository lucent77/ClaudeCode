<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * Case Repository
 *
 * Handles all database operations for cases
 */
class CaseRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find case by ID
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM cases WHERE id = :id LIMIT 1';
        return $this->db->queryOne($sql, [':id' => $id]);
    }

    /**
     * Find case by external case number
     */
    public function findByExternalCaseNo(string $caseNo, ?string $source = null): ?array
    {
        if ($source) {
            $sql = 'SELECT * FROM cases WHERE external_case_no = :case_no AND source = :source LIMIT 1';
            return $this->db->queryOne($sql, [':case_no' => $caseNo, ':source' => $source]);
        } else {
            $sql = 'SELECT * FROM cases WHERE external_case_no = :case_no LIMIT 1';
            return $this->db->queryOne($sql, [':case_no' => $caseNo]);
        }
    }

    /**
     * Get all cases with pagination and filters
     */
    public function getAll(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        // Apply filters
        if (!empty($filters['search'])) {
            $where[] = '(external_case_no LIKE :search OR patient_name LIKE :search OR lab_name LIKE :search OR notes LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = 'priority = :priority';
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['source'])) {
            $where[] = 'source = :source';
            $params[':source'] = $filters['source'];
        }

        if (!empty($filters['location'])) {
            $where[] = 'location = :location';
            $params[':location'] = $filters['location'];
        }

        if (!empty($filters['due_date_from'])) {
            $where[] = 'due_date >= :due_date_from';
            $params[':due_date_from'] = $filters['due_date_from'];
        }

        if (!empty($filters['due_date_to'])) {
            $where[] = 'due_date <= :due_date_to';
            $params[':due_date_to'] = $filters['due_date_to'];
        }

        if (!empty($filters['lab_name'])) {
            $where[] = 'lab_name LIKE :lab_name';
            $params[':lab_name'] = '%' . $filters['lab_name'] . '%';
        }

        // Include or exclude archived
        if (!isset($filters['include_archived']) || !$filters['include_archived']) {
            $where[] = 'status != "archived"';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM cases WHERE {$whereClause}";
        $totalResult = $this->db->queryOne($countSql, $params);
        $total = (int) $totalResult['total'];

        // Get cases
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';

        $sql = "SELECT * FROM cases
                WHERE {$whereClause}
                ORDER BY {$orderBy} {$orderDir}
                LIMIT :limit OFFSET :offset";

        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;

        $cases = $this->db->query($sql, $params);

        return [
            'cases' => $cases,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Create new case
     */
    public function create(array $data): int
    {
        // Set defaults
        $data['version'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->insert('cases', $data);
    }

    /**
     * Update case with optimistic locking
     */
    public function update(int $id, array $data, int $expectedVersion): bool
    {
        // Get current case to check version
        $current = $this->findById($id);
        if (!$current) {
            throw new \Exception('Case not found');
        }

        if ((int) $current['version'] !== $expectedVersion) {
            throw new \Exception('Concurrent modification detected. Please refresh and try again.');
        }

        // Increment version
        $data['version'] = $expectedVersion + 1;
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Update with version check
        $sql = "UPDATE cases SET ";
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :set_{$key}";
            $params[":set_{$key}"] = $value;
        }

        $sql .= implode(', ', $setParts);
        $sql .= " WHERE id = :id AND version = :version";
        $params[':id'] = $id;
        $params[':version'] = $expectedVersion;

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);

        $rowsAffected = $stmt->rowCount();

        if ($rowsAffected === 0) {
            throw new \Exception('Concurrent modification detected. Please refresh and try again.');
        }

        return true;
    }

    /**
     * Delete case
     */
    public function delete(int $id): bool
    {
        $rowsAffected = $this->db->delete('cases', 'id = :id', [':id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Archive case (soft delete)
     */
    public function archive(int $id, int $expectedVersion): bool
    {
        return $this->update($id, ['status' => 'archived'], $expectedVersion);
    }

    /**
     * Get case with all related items
     */
    public function findWithItems(int $id): ?array
    {
        $case = $this->findById($id);
        if (!$case) {
            return null;
        }

        // Get case items
        $sql = 'SELECT ci.*, u.name as assigned_to_name
                FROM case_items ci
                LEFT JOIN users u ON ci.assigned_to_user_id = u.id
                WHERE ci.case_id = :case_id
                ORDER BY ci.created_at ASC';

        $case['items'] = $this->db->query($sql, [':case_id' => $id]);

        return $case;
    }

    /**
     * Get cases assigned to user
     */
    public function getAssignedToUser(int $userId, array $filters = []): array
    {
        $where = ['ci.assigned_to_user_id = :user_id'];
        $params = [':user_id' => $userId];

        if (!empty($filters['status'])) {
            $where[] = 'ci.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!isset($filters['include_done']) || !$filters['include_done']) {
            $where[] = 'ci.status != "done"';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT c.*, ci.work_type, ci.status as item_status, ci.id as item_id
                FROM cases c
                INNER JOIN case_items ci ON c.id = ci.case_id
                WHERE {$whereClause}
                AND c.status != 'archived'
                ORDER BY c.due_date ASC, c.created_at DESC";

        return $this->db->query($sql, $params);
    }

    /**
     * Get overdue cases
     */
    public function getOverdue(): array
    {
        $sql = 'SELECT * FROM cases
                WHERE due_date < CURDATE()
                AND status NOT IN ("done", "canceled", "archived")
                ORDER BY due_date ASC';

        return $this->db->query($sql);
    }

    /**
     * Get cases by status
     */
    public function getByStatus(string $status): array
    {
        $sql = 'SELECT * FROM cases
                WHERE status = :status
                ORDER BY due_date ASC, created_at DESC';

        return $this->db->query($sql, [':status' => $status]);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total by status
        $sql = 'SELECT status, COUNT(*) as count FROM cases WHERE status != "archived" GROUP BY status';
        $statusStats = $this->db->query($sql);
        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = (int) $stat['count'];
        }

        // Total by source
        $sql = 'SELECT source, COUNT(*) as count FROM cases WHERE status != "archived" GROUP BY source';
        $sourceStats = $this->db->query($sql);
        foreach ($sourceStats as $stat) {
            $stats['by_source'][$stat['source']] = (int) $stat['count'];
        }

        // Total by location
        $sql = 'SELECT location, COUNT(*) as count FROM cases WHERE status != "archived" AND location IS NOT NULL GROUP BY location';
        $locationStats = $this->db->query($sql);
        foreach ($locationStats as $stat) {
            $stats['by_location'][$stat['location']] = (int) $stat['count'];
        }

        // Overdue count
        $sql = 'SELECT COUNT(*) as count FROM cases WHERE due_date < CURDATE() AND status NOT IN ("done", "canceled", "archived")';
        $overdueResult = $this->db->queryOne($sql);
        $stats['overdue'] = (int) $overdueResult['count'];

        // This week
        $sql = 'SELECT COUNT(*) as count FROM cases WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != "archived"';
        $thisWeekResult = $this->db->queryOne($sql);
        $stats['this_week'] = (int) $thisWeekResult['count'];

        return $stats;
    }

    /**
     * Check if case number exists
     */
    public function caseNumberExists(string $caseNo, string $source, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM cases WHERE external_case_no = :case_no AND source = :source';
        $params = [':case_no' => $caseNo, ':source' => $source];

        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }

        $result = $this->db->queryOne($sql, $params);
        return (int) $result['count'] > 0;
    }

    /**
     * Update case status
     */
    public function updateStatus(int $id, string $status, int $expectedVersion): bool
    {
        return $this->update($id, ['status' => $status], $expectedVersion);
    }

    /**
     * Bulk update cases
     */
    public function bulkUpdateStatus(array $caseIds, string $status): int
    {
        if (empty($caseIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($caseIds), '?'));
        $sql = "UPDATE cases SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})";

        $params = array_merge([$status], $caseIds);

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
