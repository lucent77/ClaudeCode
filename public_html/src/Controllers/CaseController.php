<?php
/**
 * Case Controller
 *
 * Handles case management operations
 */

class CaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * List all cases
     * GET /api/cases
     */
    public function index()
    {
        $data = Router::getRequestData();

        $page = max(1, intval($data['page'] ?? 1));
        $limit = min(100, max(1, intval($data['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $search = $data['search'] ?? '';
        $type = $data['type'] ?? 'All';
        $filter = $data['filter'] ?? 'All';
        $sort = $data['sort'] ?? 'received_desc';
        $status = $data['status'] ?? '';
        $clientId = $data['client_id'] ?? '';
        $priority = $data['priority'] ?? '';

        $where = ["1=1"];
        $params = [];

        // Search filter
        if (!empty($search)) {
            $where[] = "(c.case_number LIKE ? OR c.pan_number LIKE ? OR c.patient_name LIKE ? OR cl.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        // Type filter (Clinical/Lab)
        if ($type !== 'All' && in_array($type, ['Clinical', 'Lab'])) {
            $where[] = "cl.type = ?";
            $params[] = $type;
        }

        // Response filter
        if ($filter === 'responded') {
            $where[] = "c.last_email_received_at IS NOT NULL";
        } elseif ($filter === 'open') {
            $where[] = "c.status NOT IN ('Confirmed', 'Resolved')";
        }

        // Status filter
        if (!empty($status) && in_array($status, ['Pending', 'Confirmed', 'Action Needed', 'Confirmed with Action Needed', 'Resolved'])) {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        // Client filter
        if (!empty($clientId)) {
            $where[] = "c.client_id = ?";
            $params[] = intval($clientId);
        }

        // Priority filter
        if (!empty($priority) && in_array($priority, ['Low', 'Normal', 'High', 'Urgent'])) {
            $where[] = "c.priority = ?";
            $params[] = $priority;
        }

        $whereClause = "WHERE " . implode(" AND ", $where);

        // Sorting
        $orderBy = "ORDER BY ";
        switch ($sort) {
            case 'client':
                $orderBy .= "cl.name ASC";
                break;
            case 'user':
                $orderBy .= "u.username ASC";
                break;
            case 'received_asc':
                $orderBy .= "c.created_at ASC";
                break;
            case 'received_desc':
            default:
                $orderBy .= "c.created_at DESC";
                break;
        }

        // Get total count
        $countSql = "SELECT COUNT(*)
                     FROM cases c
                     LEFT JOIN clients cl ON c.client_id = cl.id
                     {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get cases with related data
        $sql = "SELECT
                    c.*,
                    cl.name as client_name,
                    cl.email as client_email,
                    cl.type as client_type,
                    cl.location as client_location,
                    cl.notification_pref as client_notification_pref,
                    u.username as created_by_username,
                    au.username as assigned_to_username
                FROM cases c
                LEFT JOIN clients cl ON c.client_id = cl.id
                LEFT JOIN users u ON c.created_by_user_id = u.id
                LEFT JOIN users au ON c.assigned_to_user_id = au.id
                {$whereClause}
                {$orderBy}
                LIMIT {$limit} OFFSET {$offset}";

        $cases = $this->db->fetchAll($sql, $params);

        // Get products for each case
        foreach ($cases as &$case) {
            $case['products'] = $this->db->fetchAll(
                "SELECT p.id, p.name, cp.quantity, cp.notes
                 FROM case_products cp
                 JOIN products p ON cp.product_id = p.id
                 WHERE cp.case_id = ?",
                [$case['id']]
            );
        }

        Router::success([
            'cases' => $cases,
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Create a new case
     * POST /api/cases
     */
    public function store()
    {
        $data = Router::getRequestData();

        // Validate required fields
        $errors = $this->validateCase($data);
        if (!empty($errors)) {
            Router::error('Validation failed', 400, $errors);
            return;
        }

        // Check if case number already exists
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE case_number = ?",
            [$data['case_number']]
        );

        if ($exists > 0) {
            Router::error('A case with this case number already exists', 400);
            return;
        }

        // Check if client exists
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [intval($data['client_id'])]);
        if (!$client) {
            Router::error('Client not found', 400);
            return;
        }

        $this->db->beginTransaction();

        try {
            // Insert case
            $caseId = $this->db->insert(
                "INSERT INTO cases (case_number, pan_number, patient_name, status, client_id,
                 created_by_user_id, assigned_to_user_id, priority, notes, design_link, due_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['case_number'],
                    $data['pan_number'] ?? null,
                    $data['patient_name'],
                    $data['status'] ?? 'Pending',
                    intval($data['client_id']),
                    AuthController::getCurrentUserId(),
                    $data['assigned_to_user_id'] ?? null,
                    $data['priority'] ?? 'Normal',
                    $data['notes'] ?? null,
                    $data['design_link'] ?? null,
                    $data['due_date'] ?? null,
                ]
            );

            // Add products if provided
            if (!empty($data['products']) && is_array($data['products'])) {
                foreach ($data['products'] as $product) {
                    $productId = intval($product['id'] ?? $product['product_id'] ?? 0);
                    if ($productId > 0) {
                        $this->db->insert(
                            "INSERT INTO case_products (case_id, product_id, quantity, notes)
                             VALUES (?, ?, ?, ?)",
                            [
                                $caseId,
                                $productId,
                                intval($product['quantity'] ?? 1),
                                $product['notes'] ?? null,
                            ]
                        );
                    }
                }
            }

            $this->db->commit();

            // Fetch complete case data
            $case = $this->getFullCase($caseId);

            Router::success(['case' => $case], 'Case created successfully', 201);

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get a specific case
     * GET /api/cases/{id}
     */
    public function show($params)
    {
        $id = intval($params['id']);

        $case = $this->getFullCase($id);

        if (!$case) {
            Router::error('Case not found', 404);
            return;
        }

        Router::success(['case' => $case]);
    }

    /**
     * Update a case
     * PUT /api/cases/{id}
     */
    public function update($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        // Check if case exists
        $case = $this->db->fetch("SELECT * FROM cases WHERE id = ?", [$id]);

        if (!$case) {
            Router::error('Case not found', 404);
            return;
        }

        // Build update query
        $updates = [];
        $updateParams = [];

        $allowedFields = [
            'case_number', 'pan_number', 'patient_name', 'status', 'client_id',
            'assigned_to_user_id', 'priority', 'notes', 'design_link', 'due_date'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Special handling for case_number uniqueness
                if ($field === 'case_number' && $data[$field] !== $case['case_number']) {
                    $exists = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM cases WHERE case_number = ? AND id != ?",
                        [$data[$field], $id]
                    );

                    if ($exists > 0) {
                        Router::error('A case with this case number already exists', 400);
                        return;
                    }
                }

                // Special handling for status
                if ($field === 'status') {
                    $validStatuses = ['Pending', 'Confirmed', 'Action Needed', 'Confirmed with Action Needed', 'Resolved'];
                    if (!in_array($data[$field], $validStatuses)) {
                        Router::error('Invalid status', 400);
                        return;
                    }

                    // Set resolved_at if status is being changed to Resolved
                    if ($data[$field] === 'Resolved' && $case['status'] !== 'Resolved') {
                        $updates[] = "resolved_at = NOW()";
                    }
                }

                // Special handling for client_id
                if ($field === 'client_id') {
                    $clientExists = $this->db->fetchColumn(
                        "SELECT COUNT(*) FROM clients WHERE id = ?",
                        [intval($data[$field])]
                    );

                    if ($clientExists == 0) {
                        Router::error('Client not found', 400);
                        return;
                    }
                }

                $updates[] = "{$field} = ?";
                $updateParams[] = $data[$field] === '' ? null : $data[$field];
            }
        }

        if (empty($updates)) {
            Router::error('No fields to update', 400);
            return;
        }

        $this->db->beginTransaction();

        try {
            $updateParams[] = $id;
            $sql = "UPDATE cases SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->update($sql, $updateParams);

            // Update products if provided
            if (isset($data['products']) && is_array($data['products'])) {
                // Remove existing products
                $this->db->delete("DELETE FROM case_products WHERE case_id = ?", [$id]);

                // Add new products
                foreach ($data['products'] as $product) {
                    $productId = intval($product['id'] ?? $product['product_id'] ?? 0);
                    if ($productId > 0) {
                        $this->db->insert(
                            "INSERT INTO case_products (case_id, product_id, quantity, notes)
                             VALUES (?, ?, ?, ?)",
                            [
                                $id,
                                $productId,
                                intval($product['quantity'] ?? 1),
                                $product['notes'] ?? null,
                            ]
                        );
                    }
                }
            }

            $this->db->commit();

            $updatedCase = $this->getFullCase($id);

            Router::success(['case' => $updatedCase], 'Case updated successfully');

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete a case
     * DELETE /api/cases/{id}
     */
    public function destroy($params)
    {
        $id = intval($params['id']);

        // Check if case exists
        $case = $this->db->fetch("SELECT * FROM cases WHERE id = ?", [$id]);

        if (!$case) {
            Router::error('Case not found', 404);
            return;
        }

        $this->db->beginTransaction();

        try {
            // Delete case products
            $this->db->delete("DELETE FROM case_products WHERE case_id = ?", [$id]);

            // Delete email logs associated with the case
            $this->db->delete("DELETE FROM email_logs WHERE case_id = ?", [$id]);

            // Delete case
            $this->db->delete("DELETE FROM cases WHERE id = ?", [$id]);

            $this->db->commit();

            Router::success(null, 'Case deleted successfully');

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get all products
     * GET /api/cases/products
     */
    public function products()
    {
        $products = $this->db->fetchAll(
            "SELECT id, name, description FROM products WHERE is_active = 1 ORDER BY name ASC"
        );

        Router::success(['products' => $products]);
    }

    /**
     * Get case statistics
     * GET /api/cases/stats
     */
    public function stats()
    {
        $stats = [];

        // Total cases
        $stats['total'] = intval($this->db->fetchColumn("SELECT COUNT(*) FROM cases"));

        // Cases by status
        $statusCounts = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM cases GROUP BY status"
        );
        $stats['by_status'] = [];
        foreach ($statusCounts as $row) {
            $stats['by_status'][$row['status']] = intval($row['count']);
        }

        // Cases by priority
        $priorityCounts = $this->db->fetchAll(
            "SELECT priority, COUNT(*) as count FROM cases GROUP BY priority"
        );
        $stats['by_priority'] = [];
        foreach ($priorityCounts as $row) {
            $stats['by_priority'][$row['priority']] = intval($row['count']);
        }

        // Cases created today
        $stats['created_today'] = intval($this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE DATE(created_at) = CURDATE()"
        ));

        // Cases created this week
        $stats['created_this_week'] = intval($this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        ));

        // Open cases (not confirmed/resolved)
        $stats['open'] = intval($this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE status NOT IN ('Confirmed', 'Resolved')"
        ));

        // Overdue cases
        $stats['overdue'] = intval($this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE due_date < CURDATE() AND status NOT IN ('Confirmed', 'Resolved')"
        ));

        Router::success(['stats' => $stats]);
    }

    /**
     * Update case status
     * POST /api/cases/{id}/status
     */
    public function updateStatus($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        if (empty($data['status'])) {
            Router::error('Status is required', 400);
            return;
        }

        $validStatuses = ['Pending', 'Confirmed', 'Action Needed', 'Confirmed with Action Needed', 'Resolved'];
        if (!in_array($data['status'], $validStatuses)) {
            Router::error('Invalid status', 400);
            return;
        }

        $case = $this->db->fetch("SELECT * FROM cases WHERE id = ?", [$id]);

        if (!$case) {
            Router::error('Case not found', 404);
            return;
        }

        $updates = ["status = ?"];
        $updateParams = [$data['status']];

        // Set resolved_at if status is being changed to Resolved
        if ($data['status'] === 'Resolved' && $case['status'] !== 'Resolved') {
            $updates[] = "resolved_at = NOW()";
        }

        $updateParams[] = $id;
        $sql = "UPDATE cases SET " . implode(", ", $updates) . " WHERE id = ?";
        $this->db->update($sql, $updateParams);

        $updatedCase = $this->getFullCase($id);

        Router::success(['case' => $updatedCase], 'Case status updated successfully');
    }

    /**
     * Send email for a case
     * POST /api/cases/{id}/send-email
     */
    public function sendEmail($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        $case = $this->getFullCase($id);

        if (!$case) {
            Router::error('Case not found', 404);
            return;
        }

        // Validate email data
        if (empty($data['subject']) || empty($data['body'])) {
            Router::error('Subject and body are required', 400);
            return;
        }

        try {
            // Load email service
            require_once dirname(__DIR__) . '/Services/EmailService.php';
            $emailService = new EmailService();

            // Send email
            $result = $emailService->send(
                $case['client_email'],
                $data['subject'],
                $data['body'],
                $data['attachments'] ?? []
            );

            if ($result) {
                // Update last_email_sent_at
                $this->db->update(
                    "UPDATE cases SET last_email_sent_at = NOW() WHERE id = ?",
                    [$id]
                );

                // Log email
                $this->db->insert(
                    "INSERT INTO email_logs (case_id, client_id, direction, from_email, to_email, subject, body, status)
                     VALUES (?, ?, 'Outbound', ?, ?, ?, ?, 'Sent')",
                    [
                        $id,
                        $case['client_id'],
                        env('SMTP_FROM_EMAIL'),
                        $case['client_email'],
                        $data['subject'],
                        $data['body'],
                    ]
                );

                Router::success(null, 'Email sent successfully');
            } else {
                Router::error('Failed to send email', 500);
            }

        } catch (Exception $e) {
            Router::error('Failed to send email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get full case data with related information
     */
    private function getFullCase($id)
    {
        $case = $this->db->fetch(
            "SELECT
                c.*,
                cl.name as client_name,
                cl.email as client_email,
                cl.phone as client_phone,
                cl.type as client_type,
                cl.location as client_location,
                cl.notification_pref as client_notification_pref,
                u.username as created_by_username,
                au.username as assigned_to_username
             FROM cases c
             LEFT JOIN clients cl ON c.client_id = cl.id
             LEFT JOIN users u ON c.created_by_user_id = u.id
             LEFT JOIN users au ON c.assigned_to_user_id = au.id
             WHERE c.id = ?",
            [$id]
        );

        if ($case) {
            $case['products'] = $this->db->fetchAll(
                "SELECT p.id, p.name, p.description, cp.quantity, cp.notes
                 FROM case_products cp
                 JOIN products p ON cp.product_id = p.id
                 WHERE cp.case_id = ?",
                [$id]
            );
        }

        return $case;
    }

    /**
     * Validate case data
     */
    private function validateCase($data)
    {
        $errors = [];

        if (empty($data['case_number'])) {
            $errors['case_number'] = 'Case number is required';
        } elseif (strlen($data['case_number']) > 50) {
            $errors['case_number'] = 'Case number must be less than 50 characters';
        }

        if (empty($data['patient_name'])) {
            $errors['patient_name'] = 'Patient name is required';
        } elseif (strlen($data['patient_name']) > 100) {
            $errors['patient_name'] = 'Patient name must be less than 100 characters';
        }

        if (empty($data['client_id'])) {
            $errors['client_id'] = 'Client is required';
        }

        if (!empty($data['status'])) {
            $validStatuses = ['Pending', 'Confirmed', 'Action Needed', 'Confirmed with Action Needed', 'Resolved'];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Invalid status';
            }
        }

        if (!empty($data['priority'])) {
            $validPriorities = ['Low', 'Normal', 'High', 'Urgent'];
            if (!in_array($data['priority'], $validPriorities)) {
                $errors['priority'] = 'Invalid priority';
            }
        }

        return $errors;
    }
}
