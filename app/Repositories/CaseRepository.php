<?php
/**
 * Case Repository
 *
 * Data access layer for cases table
 */

namespace App\Repositories;

use App\Core\Database;

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
        return $this->db->queryOne(
            "SELECT c.*,
                    u1.name as created_by_name,
                    u2.name as updated_by_name,
                    d.name as department_name
             FROM cases c
             LEFT JOIN users u1 ON c.created_by = u1.id
             LEFT JOIN users u2 ON c.updated_by = u2.id
             LEFT JOIN departments d ON u1.department_id = d.id
             WHERE c.id = ?",
            [$id]
        );
    }

    /**
     * Find case by external case number
     */
    public function findByExternalCaseNo(string $caseNo): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM cases WHERE external_case_no = ?",
            [$caseNo]
        );
    }

    /**
     * Get cases with filters and pagination
     */
    public function getWithFilters(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $where = [];
        $params = [];

        // Build WHERE clause
        if (!empty($filters['search'])) {
            $where[] = "(external_case_no LIKE ? OR patient_name LIKE ? OR lab_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['location'])) {
            $where[] = "location = ?";
            $params[] = $filters['location'];
        }

        if (!empty($filters['due_date_from'])) {
            $where[] = "due_date >= ?";
            $params[] = $filters['due_date_from'];
        }

        if (!empty($filters['due_date_to'])) {
            $where[] = "due_date <= ?";
            $params[] = $filters['due_date_to'];
        }

        if (!empty($filters['source'])) {
            $where[] = "source = ?";
            $params[] = $filters['source'];
        }

        // Exclude archived by default
        if (!isset($filters['include_archived']) || !$filters['include_archived']) {
            $where[] = "status != 'archived'";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM cases {$whereClause}";
        $total = $this->db->queryOne($countSql, $params)['total'] ?? 0;

        // Calculate pagination
        $offset = ($page - 1) * $perPage;
        $totalPages = ceil($total / $perPage);

        // Get paginated results
        $sql = "SELECT c.*,
                       u1.name as created_by_name,
                       u2.name as updated_by_name,
                       (SELECT COUNT(*) FROM case_items WHERE case_id = c.id) as items_count,
                       (SELECT COUNT(*) FROM case_items WHERE case_id = c.id AND status = 'done') as items_done
                FROM cases c
                LEFT JOIN users u1 ON c.created_by = u1.id
                LEFT JOIN users u2 ON c.updated_by = u2.id
                {$whereClause}
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $perPage;
        $params[] = $offset;

        $cases = $this->db->query($sql, $params);

        return [
            'data' => $cases,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    /**
     * Get cases by department
     */
    public function getByDepartment(int $departmentId, string $status = null, int $limit = 50): array
    {
        $statusFilter = $status ? "AND ci.status = ?" : "";
        $params = [$departmentId];

        if ($status) {
            $params[] = $status;
        }

        $params[] = $limit;

        return $this->db->query(
            "SELECT DISTINCT c.*, ci.status as item_status
             FROM cases c
             INNER JOIN case_items ci ON c.id = ci.case_id
             WHERE ci.department_id = ? {$statusFilter}
             ORDER BY c.due_date ASC, c.created_at DESC
             LIMIT ?",
            $params
        );
    }

    /**
     * Get overdue cases
     */
    public function getOverdue(): array
    {
        return $this->db->query(
            "SELECT c.*, u.name as created_by_name
             FROM cases c
             LEFT JOIN users u ON c.created_by = u.id
             WHERE c.due_date < CURDATE()
               AND c.status NOT IN ('done', 'archived', 'canceled')
             ORDER BY c.due_date ASC"
        );
    }

    /**
     * Get recent cases
     */
    public function getRecent(int $limit = 10): array
    {
        return $this->db->query(
            "SELECT c.*, u.name as created_by_name
             FROM cases c
             LEFT JOIN users u ON c.created_by = u.id
             ORDER BY c.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get case statistics
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total cases by status
        $stats['by_status'] = $this->db->query(
            "SELECT status, COUNT(*) as count
             FROM cases
             WHERE status != 'archived'
             GROUP BY status"
        );

        // Cases by source
        $stats['by_source'] = $this->db->query(
            "SELECT source, COUNT(*) as count
             FROM cases
             GROUP BY source"
        );

        // Cases by location
        $stats['by_location'] = $this->db->query(
            "SELECT location, COUNT(*) as count
             FROM cases
             WHERE location IS NOT NULL
             GROUP BY location"
        );

        // Cases created per day (last 30 days)
        $stats['created_per_day'] = $this->db->query(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM cases
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );

        return $stats;
    }

    /**
     * Search cases (full-text search)
     */
    public function search(string $query, int $limit = 20): array
    {
        $searchTerm = '%' . $query . '%';

        return $this->db->query(
            "SELECT c.*, u.name as created_by_name,
                    MATCH(patient_name, lab_name, lab_number, patient_number) AGAINST(? IN BOOLEAN MODE) as relevance
             FROM cases c
             LEFT JOIN users u ON c.created_by = u.id
             WHERE MATCH(patient_name, lab_name, lab_number, patient_number) AGAINST(? IN BOOLEAN MODE)
                OR external_case_no LIKE ?
                OR patient_name LIKE ?
                OR lab_name LIKE ?
             ORDER BY relevance DESC, created_at DESC
             LIMIT ?",
            [$query, $query, $searchTerm, $searchTerm, $searchTerm, $limit]
        );
    }
}
