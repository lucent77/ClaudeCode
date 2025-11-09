<?php
$page_title = 'My Customers';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();
$role = getCurrentUserRole();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$accountClass = $_GET['account_class'] ?? '';
$state = $_GET['state'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = RECORDS_PER_PAGE;

// Build WHERE clause
$where = [];
$params = [];

// Role-based filtering
if ($role === 'sales_rep') {
    $where[] = "a.user_id = ? AND a.is_current = 1";
    $params[] = $userId;
}

// Search filter
if (!empty($search)) {
    $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.practice_name LIKE ? OR c.email_address LIKE ? OR c.account_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Status filter
if (!empty($status)) {
    $where[] = "c.customer_status = ?";
    $params[] = $status;
}

// Account class filter
if (!empty($accountClass)) {
    $where[] = "c.account_class = ?";
    $params[] = $accountClass;
}

// State filter
if (!empty($state)) {
    $where[] = "c.state_code = ?";
    $params[] = $state;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Get total count
    $countSql = "SELECT COUNT(DISTINCT c.customer_id) as total
                 FROM customers c ";

    if ($role === 'sales_rep') {
        $countSql .= "INNER JOIN assignments a ON c.customer_id = a.customer_id ";
    } else {
        $countSql .= "LEFT JOIN assignments a ON c.customer_id = a.customer_id AND a.is_current = 1 ";
    }

    $countSql .= $whereClause;

    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $pagination = getPagination($total, $page, $perPage);

    // Get customers
    $sql = "SELECT c.*,
                   u.first_name as rep_first_name,
                   u.last_name as rep_last_name,
                   u.user_id as assigned_user_id
            FROM customers c ";

    if ($role === 'sales_rep') {
        $sql .= "INNER JOIN assignments a ON c.customer_id = a.customer_id ";
    } else {
        $sql .= "LEFT JOIN assignments a ON c.customer_id = a.customer_id AND a.is_current = 1 ";
    }

    $sql .= "LEFT JOIN users u ON a.user_id = u.user_id
            $whereClause
            GROUP BY c.customer_id
            ORDER BY c.practice_name, c.last_name, c.first_name
            LIMIT {$pagination['offset']}, {$perPage}";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

    // Get filter options
    $states = $db->query("SELECT DISTINCT state_code FROM customers WHERE state_code IS NOT NULL AND state_code != '' ORDER BY state_code")->fetchAll(PDO::FETCH_COLUMN);
    $accountClasses = $db->query("SELECT DISTINCT account_class FROM customers WHERE account_class IS NOT NULL AND account_class != '' ORDER BY account_class")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Customers Error: " . $e->getMessage());
    $customers = [];
    $total = 0;
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-people"></i> My Customers</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Customers</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <?php if (hasAnyRole(['admin', 'manager'])): ?>
            <a href="/admin/import.php" class="btn btn-primary">
                <i class="bi bi-upload"></i> Import Customers
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" name="search" placeholder="Search customers..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="A" <?php echo $status === 'A' ? 'selected' : ''; ?>>Active</option>
                        <option value="I" <?php echo $status === 'I' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="P" <?php echo $status === 'P' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="account_class">
                        <option value="">All Types</option>
                        <?php foreach ($accountClasses as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>"
                                    <?php echo $accountClass === $class ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="state">
                        <option value="">All States</option>
                        <?php foreach ($states as $st): ?>
                            <option value="<?php echo htmlspecialchars($st); ?>"
                                    <?php echo $state === $st ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="/sales/customers.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results summary -->
    <div class="mb-3">
        <p class="text-muted">
            Showing <?php echo number_format(min($total, $pagination['offset'] + 1)); ?>
            to <?php echo number_format(min($total, $pagination['offset'] + count($customers))); ?>
            of <?php echo number_format($total); ?> customers
        </p>
    </div>

    <!-- Customers table -->
    <div class="table-responsive customer-table">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Account #</th>
                    <th>Customer</th>
                    <th>Practice Name</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <?php if ($role !== 'sales_rep'): ?>
                    <th>Assigned To</th>
                    <?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td>
                            <code><?php echo htmlspecialchars($customer['account_number']); ?></code>
                        </td>
                        <td>
                            <strong>
                                <?php if ($customer['title']): ?>
                                    <?php echo htmlspecialchars($customer['title']); ?>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                            </strong>
                        </td>
                        <td><?php echo htmlspecialchars($customer['practice_name']); ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo htmlspecialchars($customer['account_class'] ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($customer['city']); ?>,
                            <?php echo htmlspecialchars($customer['state_code']); ?>
                        </td>
                        <td>
                            <?php if ($customer['phone_number']): ?>
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($customer['phone_number']); ?><br>
                            <?php endif; ?>
                            <?php if ($customer['email_address']): ?>
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($customer['email_address']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($customer['customer_status']); ?>">
                                <?php echo htmlspecialchars($customer['customer_status']); ?>
                            </span>
                            <?php if ($customer['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($role !== 'sales_rep'): ?>
                        <td>
                            <?php if ($customer['assigned_user_id']): ?>
                                <?php echo htmlspecialchars($customer['rep_first_name'] . ' ' . $customer['rep_last_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <a href="/sales/customer_detail.php?id=<?php echo $customer['customer_id']; ?>"
                               class="btn btn-sm btn-outline-primary btn-action">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $role !== 'sales_rep' ? '9' : '8'; ?>" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i><br>
                            No customers found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($pagination['has_prev']): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&account_class=<?php echo urlencode($accountClass); ?>&state=<?php echo urlencode($state); ?>">
                    Previous
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&account_class=<?php echo urlencode($accountClass); ?>&state=<?php echo urlencode($state); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($pagination['has_next']): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&account_class=<?php echo urlencode($accountClass); ?>&state=<?php echo urlencode($state); ?>">
                    Next
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
