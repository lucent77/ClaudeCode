<?php
/**
 * Client Controller
 *
 * Handles client management operations
 */

class ClientController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * List all clients
     * GET /api/clients
     */
    public function index()
    {
        $data = Router::getRequestData();

        $page = max(1, intval($data['page'] ?? 1));
        $limit = min(100, max(1, intval($data['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $search = $data['search'] ?? '';
        $type = $data['type'] ?? '';
        $location = $data['location'] ?? '';
        $isActive = $data['is_active'] ?? '';

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($type) && in_array($type, ['Clinical', 'Lab'])) {
            $where[] = "type = ?";
            $params[] = $type;
        }

        if (!empty($location)) {
            $where[] = "location = ?";
            $params[] = $location;
        }

        if ($isActive !== '') {
            $where[] = "is_active = ?";
            $params[] = intval($isActive);
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Get total count
        $countSql = "SELECT COUNT(*) FROM clients {$whereClause}";
        $total = $this->db->fetchColumn($countSql, $params);

        // Get clients
        $sql = "SELECT *
                FROM clients
                {$whereClause}
                ORDER BY name ASC
                LIMIT {$limit} OFFSET {$offset}";

        $clients = $this->db->fetchAll($sql, $params);

        Router::success([
            'clients' => $clients,
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Create a new client
     * POST /api/clients
     */
    public function store()
    {
        $data = Router::getRequestData();

        // Validate required fields
        $errors = $this->validateClient($data);
        if (!empty($errors)) {
            Router::error('Validation failed', 400, $errors);
            return;
        }

        // Check if email already exists
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM clients WHERE email = ?",
            [$data['email']]
        );

        if ($exists > 0) {
            Router::error('A client with this email already exists', 400);
            return;
        }

        // Insert client
        $clientId = $this->db->insert(
            "INSERT INTO clients (name, email, phone, type, location, notification_pref, notes, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['type'] ?? 'Clinical',
                $data['location'] ?? 'NYC',
                $data['notification_pref'] ?? 'Email_Only',
                $data['notes'] ?? null,
                $data['is_active'] ?? 1,
            ]
        );

        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$clientId]);

        Router::success(['client' => $client], 'Client created successfully', 201);
    }

    /**
     * Get a specific client
     * GET /api/clients/{id}
     */
    public function show($params)
    {
        $id = intval($params['id']);

        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$id]);

        if (!$client) {
            Router::error('Client not found', 404);
            return;
        }

        // Get associated cases count
        $casesCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE client_id = ?",
            [$id]
        );

        $client['cases_count'] = intval($casesCount);

        Router::success(['client' => $client]);
    }

    /**
     * Update a client
     * PUT /api/clients/{id}
     */
    public function update($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        // Check if client exists
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$id]);

        if (!$client) {
            Router::error('Client not found', 404);
            return;
        }

        // Build update query
        $updates = [];
        $updateParams = [];

        if (isset($data['name'])) {
            if (empty($data['name'])) {
                Router::error('Name cannot be empty', 400);
                return;
            }
            $updates[] = "name = ?";
            $updateParams[] = $data['name'];
        }

        if (isset($data['email'])) {
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                Router::error('Valid email is required', 400);
                return;
            }

            // Check if new email already exists for another client
            $emailExists = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM clients WHERE email = ? AND id != ?",
                [$data['email'], $id]
            );

            if ($emailExists > 0) {
                Router::error('A client with this email already exists', 400);
                return;
            }

            $updates[] = "email = ?";
            $updateParams[] = $data['email'];
        }

        if (isset($data['phone'])) {
            $updates[] = "phone = ?";
            $updateParams[] = $data['phone'] ?: null;
        }

        if (isset($data['type']) && in_array($data['type'], ['Clinical', 'Lab'])) {
            $updates[] = "type = ?";
            $updateParams[] = $data['type'];
        }

        if (isset($data['location'])) {
            $updates[] = "location = ?";
            $updateParams[] = $data['location'];
        }

        if (isset($data['notification_pref']) && in_array($data['notification_pref'], ['Email_Only', 'Text_Only', 'Both', 'None'])) {
            $updates[] = "notification_pref = ?";
            $updateParams[] = $data['notification_pref'];
        }

        if (isset($data['notes'])) {
            $updates[] = "notes = ?";
            $updateParams[] = $data['notes'] ?: null;
        }

        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $updateParams[] = intval($data['is_active']);
        }

        if (empty($updates)) {
            Router::error('No fields to update', 400);
            return;
        }

        $updateParams[] = $id;
        $sql = "UPDATE clients SET " . implode(", ", $updates) . " WHERE id = ?";
        $this->db->update($sql, $updateParams);

        $updatedClient = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$id]);

        Router::success(['client' => $updatedClient], 'Client updated successfully');
    }

    /**
     * Delete a client
     * DELETE /api/clients/{id}
     */
    public function destroy($params)
    {
        $id = intval($params['id']);

        // Check if client exists
        $client = $this->db->fetch("SELECT * FROM clients WHERE id = ?", [$id]);

        if (!$client) {
            Router::error('Client not found', 404);
            return;
        }

        // Check if client has associated cases
        $casesCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM cases WHERE client_id = ?",
            [$id]
        );

        if ($casesCount > 0) {
            // Soft delete - set is_active to 0
            $this->db->update("UPDATE clients SET is_active = 0 WHERE id = ?", [$id]);
            Router::success(null, 'Client deactivated (has associated cases)');
            return;
        }

        // Hard delete if no associated cases
        $this->db->delete("DELETE FROM clients WHERE id = ?", [$id]);

        Router::success(null, 'Client deleted successfully');
    }

    /**
     * Validate client data
     */
    private function validateClient($data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Name must be less than 100 characters';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!empty($data['phone'])) {
            // Basic phone validation
            $phone = preg_replace('/[^0-9+\-\(\)\s]/', '', $data['phone']);
            if (strlen($phone) < 10) {
                $errors['phone'] = 'Invalid phone number';
            }
        }

        if (!empty($data['type']) && !in_array($data['type'], ['Clinical', 'Lab'])) {
            $errors['type'] = 'Invalid type. Must be Clinical or Lab';
        }

        if (!empty($data['notification_pref']) && !in_array($data['notification_pref'], ['Email_Only', 'Text_Only', 'Both', 'None'])) {
            $errors['notification_pref'] = 'Invalid notification preference';
        }

        return $errors;
    }
}
