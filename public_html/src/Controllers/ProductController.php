<?php
/**
 * Product Controller
 *
 * Handles product management operations
 */

class ProductController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * List all products
     * GET /api/products
     */
    public function index()
    {
        $data = Router::getRequestData();

        $page = max(1, intval($data['page'] ?? 1));
        $limit = min(100, max(1, intval($data['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $search = $data['search'] ?? '';
        $isActive = $data['is_active'] ?? '';

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(name LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($isActive !== '') {
            $where[] = "is_active = ?";
            $params[] = intval($isActive);
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Get total count
        $total = $this->db->fetchColumn("SELECT COUNT(*) FROM products {$whereClause}", $params);

        // Get products
        $sql = "SELECT *
                FROM products
                {$whereClause}
                ORDER BY name ASC
                LIMIT {$limit} OFFSET {$offset}";

        $products = $this->db->fetchAll($sql, $params);

        Router::success([
            'products' => $products,
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Create a new product
     * POST /api/products
     */
    public function store()
    {
        $data = Router::getRequestData();

        // Validate required fields
        if (empty($data['name'])) {
            Router::error('Product name is required', 400);
            return;
        }

        // Insert product
        $productId = $this->db->insert(
            "INSERT INTO products (name, description, google_sheet_id, case_col, patient_col, pan_col, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['description'] ?? null,
                $data['google_sheet_id'] ?? null,
                $data['case_col'] ?? 'A',
                $data['patient_col'] ?? 'H',
                $data['pan_col'] ?? 'I',
                $data['is_active'] ?? 1,
            ]
        );

        $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);

        Router::success(['product' => $product], 'Product created successfully', 201);
    }

    /**
     * Get a specific product
     * GET /api/products/{id}
     */
    public function show($params)
    {
        $id = intval($params['id']);

        $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);

        if (!$product) {
            Router::error('Product not found', 404);
            return;
        }

        // Get usage count
        $usageCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM case_products WHERE product_id = ?",
            [$id]
        );

        $product['usage_count'] = intval($usageCount);

        Router::success(['product' => $product]);
    }

    /**
     * Update a product
     * PUT /api/products/{id}
     */
    public function update($params)
    {
        $id = intval($params['id']);
        $data = Router::getRequestData();

        // Check if product exists
        $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);

        if (!$product) {
            Router::error('Product not found', 404);
            return;
        }

        // Build update query
        $updates = [];
        $updateParams = [];

        $allowedFields = ['name', 'description', 'google_sheet_id', 'case_col', 'patient_col', 'pan_col', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'name' && empty($data[$field])) {
                    Router::error('Product name cannot be empty', 400);
                    return;
                }

                $updates[] = "{$field} = ?";
                $updateParams[] = $data[$field] === '' ? null : $data[$field];
            }
        }

        if (empty($updates)) {
            Router::error('No fields to update', 400);
            return;
        }

        $updateParams[] = $id;
        $sql = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = ?";
        $this->db->update($sql, $updateParams);

        $updatedProduct = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);

        Router::success(['product' => $updatedProduct], 'Product updated successfully');
    }

    /**
     * Delete a product
     * DELETE /api/products/{id}
     */
    public function destroy($params)
    {
        $id = intval($params['id']);

        // Check if product exists
        $product = $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);

        if (!$product) {
            Router::error('Product not found', 404);
            return;
        }

        // Check if product is in use
        $usageCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM case_products WHERE product_id = ?",
            [$id]
        );

        if ($usageCount > 0) {
            // Soft delete
            $this->db->update("UPDATE products SET is_active = 0 WHERE id = ?", [$id]);
            Router::success(null, 'Product deactivated (in use by cases)');
            return;
        }

        // Hard delete
        $this->db->delete("DELETE FROM products WHERE id = ?", [$id]);

        Router::success(null, 'Product deleted successfully');
    }
}
