<?php
$page_title = 'Customer Assignments';
require_once __DIR__ . '/../includes/header.php';
requireAnyRole(['admin', 'manager']);

$db = getDB();
$message = '';
$messageType = '';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_customers'])) {
    $customerIds = $_POST['customer_ids'] ?? [];
    $salesRepId = $_POST['sales_rep_id'] ?? 0;

    if (empty($customerIds) || empty($salesRepId)) {
        $message = 'Please select customers and a sales representative';
        $messageType = 'warning';
    } else {
        try {
            $db->beginTransaction();

            foreach ($customerIds as $customerId) {
                // Mark previous assignments as not current
                $stmt = $db->prepare("UPDATE assignments SET is_current = 0 WHERE customer_id = ?");
                $stmt->execute([$customerId]);

                // Create new assignment
                $stmt = $db->prepare("
                    INSERT INTO assignments (customer_id, user_id, assigned_by)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$customerId, $salesRepId, getCurrentUserId()]);
            }

            $db->commit();

            logAction('assign_customers', 'assignments', null, count($customerIds) . " customers assigned to user $salesRepId");

            $message = count($customerIds) . ' customer(s) successfully assigned';
            $messageType = 'success';
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Assignment Error: " . $e->getMessage());
            $message = 'Error assigning customers';
            $messageType = 'danger';
        }
    }
}

// Get filter parameters
$state = $_GET['state'] ?? '';
$accountClass = $_GET['account_class'] ?? '';
$status = $_GET['status'] ?? '';
$assignmentStatus = $_GET['assignment_status'] ?? 'unassigned'; // unassigned, assigned, all

$where = [];
$params = [];

if (!empty($state)) {
    $where[] = "c.state_code = ?";
    $params[] = $state;
}

if (!empty($accountClass)) {
    $where[] = "c.account_class = ?";
    $params[] = $accountClass;
}

if (!empty($status)) {
    $where[] = "c.customer_status = ?";
    $params[] = $status;
}

if ($assignmentStatus === 'unassigned') {
    $where[] = "a.assignment_id IS NULL";
} elseif ($assignmentStatus === 'assigned') {
    $where[] = "a.assignment_id IS NOT NULL";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Get unassigned/assigned customers
    $sql = "SELECT c.*, u.first_name as rep_first, u.last_name as rep_last
            FROM customers c
            LEFT JOIN assignments a ON c.customer_id = a.customer_id AND a.is_current = 1
            LEFT JOIN users u ON a.user_id = u.user_id
            $whereClause
            ORDER BY c.practice_name, c.last_name
            LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

    // Get sales reps
    $salesReps = getAllUsers('sales_rep');

    // Get filter options
    $states = $db->query("SELECT DISTINCT state_code FROM customers WHERE state_code IS NOT NULL AND state_code != '' ORDER BY state_code")->fetchAll(PDO::FETCH_COLUMN);
    $accountClasses = $db->query("SELECT DISTINCT account_class FROM customers WHERE account_class IS NOT NULL AND account_class != '' ORDER BY account_class")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Assignments Error: " . $e->getMessage());
    $customers = [];
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-person-plus"></i> Customer Assignments</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Assignments</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-2">
                    <select class="form-select" name="assignment_status">
                        <option value="unassigned" <?php echo $assignmentStatus === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                        <option value="assigned" <?php echo $assignmentStatus === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="all" <?php echo $assignmentStatus === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="A" <?php echo $status === 'A' ? 'selected' : ''; ?>>Active</option>
                        <option value="I" <?php echo $status === 'I' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="account_class">
                        <option value="">All Types</option>
                        <?php foreach ($accountClasses as $class): ?>
                            <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $accountClass === $class ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="state">
                        <option value="">All States</option>
                        <?php foreach ($states as $st): ?>
                            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $state === $st ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="/admin/assignments.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Assignment Form -->
    <form method="POST" action="" id="assignmentForm">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Select Sales Representative</label>
                <select class="form-select" name="sales_rep_id" required>
                    <option value="">Choose sales rep...</option>
                    <?php foreach ($salesReps as $rep): ?>
                        <option value="<?php echo $rep['user_id']; ?>">
                            <?php echo htmlspecialchars($rep['first_name'] . ' ' . $rep['last_name']); ?>
                            (<?php echo htmlspecialchars($rep['username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="assign_customers" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle"></i> Assign Selected
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-primary w-100" onclick="selectAll()">
                    <i class="bi bi-check2-all"></i> Select All
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="deselectAll()">
                    <i class="bi bi-x-square"></i> Deselect All
                </button>
            </div>
        </div>

        <!-- Customers table -->
        <div class="table-responsive customer-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllCheckbox" onclick="toggleAll(this)">
                        </th>
                        <th>Account #</th>
                        <th>Customer</th>
                        <th>Practice Name</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Current Assignment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="customer_ids[]" value="<?php echo $customer['customer_id']; ?>" class="customer-checkbox">
                            </td>
                            <td><code><?php echo htmlspecialchars($customer['account_number']); ?></code></td>
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
                                <span class="badge bg-info"><?php echo htmlspecialchars($customer['account_class'] ?: 'N/A'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($customer['city'] . ', ' . $customer['state_code']); ?></td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($customer['customer_status']); ?>">
                                    <?php echo htmlspecialchars($customer['customer_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($customer['rep_first']): ?>
                                    <span class="badge bg-success">
                                        <?php echo htmlspecialchars($customer['rep_first'] . ' ' . $customer['rep_last']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No customers found with current filters
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAllCheckbox').checked = false;
}

// Form validation
document.getElementById('assignmentForm').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one customer to assign');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
