<?php
/**
 * Template Controller
 *
 * Handles email template management operations
 */

class TemplateController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * List all templates
     * GET /api/templates
     */
    public function index()
    {
        $data = Router::getRequestData();

        $search = $data['search'] ?? '';
        $isActive = $data['is_active'] ?? '';

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(name LIKE ? OR subject LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($isActive !== '') {
            $where[] = "is_active = ?";
            $params[] = intval($isActive);
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT * FROM email_templates {$whereClause} ORDER BY name ASC";

        $templates = $this->db->fetchAll($sql, $params);

        // Parse JSON variables
        foreach ($templates as &$template) {
            if (!empty($template['variables'])) {
                $template['variables'] = json_decode($template['variables'], true);
            }
        }

        Router::success(['templates' => $templates]);
    }

    /**
     * Create a new template
     * POST /api/templates
     */
    public function store()
    {
        $data = Router::getRequestData();

        // Validate required fields
        $errors = $this->validateTemplate($data);
        if (!empty($errors)) {
            Router::error('Validation failed', 400, $errors);
            return;
        }

        // Check if name already exists
        $exists = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM email_templates WHERE name = ?",
            [$data['name']]
        );

        if ($exists > 0) {
            Router::error('A template with this name already exists', 400);
            return;
        }

        // Prepare variables JSON
        $variables = null;
        if (!empty($data['variables'])) {
            $variables = is_array($data['variables'])
                ? json_encode($data['variables'])
                : $data['variables'];
        }

        // Insert template
        $templateId = $this->db->insert(
            "INSERT INTO email_templates (name, subject, body, variables, is_active)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['subject'],
                $data['body'],
                $variables,
                $data['is_active'] ?? 1,
            ]
        );

        $template = $this->db->fetch("SELECT * FROM email_templates WHERE id = ?", [$templateId]);

        if (!empty($template['variables'])) {
            $template['variables'] = json_decode($template['variables'], true);
        }

        Router::success(['template' => $template], 'Template created successfully', 201);
    }

    /**
     * Get a specific template
     * GET /api/templates/{id}
     */
    public function show($params)
    {
        $id = intval($params['id']);

        $template = $this->db->fetch("SELECT * FROM email_templates WHERE id = ?", [$id]);

        if (!$template) {
            Router::error('Template not found', 404);
            return;
        }

        if (!empty($template['variables'])) {
            $template['variables'] = json_decode($template['variables'], true);
        }

        Router::success(['template' => $template]);
    }

    /**
     * Update a template
     * PUT /api/templates/{id}
     */
    public function update($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        // Check if template exists
        $template = $this->db->fetch("SELECT * FROM email_templates WHERE id = ?", [$id]);

        if (!$template) {
            Router::error('Template not found', 404);
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

            // Check if name already exists for another template
            $exists = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM email_templates WHERE name = ? AND id != ?",
                [$data['name'], $id]
            );

            if ($exists > 0) {
                Router::error('A template with this name already exists', 400);
                return;
            }

            $updates[] = "name = ?";
            $updateParams[] = $data['name'];
        }

        if (isset($data['subject'])) {
            if (empty($data['subject'])) {
                Router::error('Subject cannot be empty', 400);
                return;
            }
            $updates[] = "subject = ?";
            $updateParams[] = $data['subject'];
        }

        if (isset($data['body'])) {
            if (empty($data['body'])) {
                Router::error('Body cannot be empty', 400);
                return;
            }
            $updates[] = "body = ?";
            $updateParams[] = $data['body'];
        }

        if (isset($data['variables'])) {
            $variables = is_array($data['variables'])
                ? json_encode($data['variables'])
                : $data['variables'];
            $updates[] = "variables = ?";
            $updateParams[] = $variables;
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
        $sql = "UPDATE email_templates SET " . implode(", ", $updates) . " WHERE id = ?";
        $this->db->update($sql, $updateParams);

        $updatedTemplate = $this->db->fetch("SELECT * FROM email_templates WHERE id = ?", [$id]);

        if (!empty($updatedTemplate['variables'])) {
            $updatedTemplate['variables'] = json_decode($updatedTemplate['variables'], true);
        }

        Router::success(['template' => $updatedTemplate], 'Template updated successfully');
    }

    /**
     * Delete a template
     * DELETE /api/templates/{id}
     */
    public function destroy($params)
    {
        $id = intval($params['id']);

        // Check if template exists
        $template = $this->db->fetch("SELECT * FROM email_templates WHERE id = ?", [$id]);

        if (!$template) {
            Router::error('Template not found', 404);
            return;
        }

        // Delete template
        $this->db->delete("DELETE FROM email_templates WHERE id = ?", [$id]);

        Router::success(null, 'Template deleted successfully');
    }

    /**
     * Validate template data
     */
    private function validateTemplate($data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Name must be less than 100 characters';
        }

        if (empty($data['subject'])) {
            $errors['subject'] = 'Subject is required';
        } elseif (strlen($data['subject']) > 255) {
            $errors['subject'] = 'Subject must be less than 255 characters';
        }

        if (empty($data['body'])) {
            $errors['body'] = 'Body is required';
        }

        return $errors;
    }

    /**
     * Render template with variables
     */
    public static function render($template, $variables = [])
    {
        $subject = $template['subject'];
        $body = $template['body'];

        foreach ($variables as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
            $body = str_replace("{{$key}}", $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
