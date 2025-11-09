<?php
$page_title = 'Customer Interactions';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();
$role = getCurrentUserRole();

// Handle new interaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_interaction'])) {
    try {
        $customerId = intval($_POST['customer_id']);
        $interactionType = sanitize($_POST['interaction_type']);
        $subject = sanitize($_POST['subject']);
        $description = sanitize($_POST['description'] ?? '');
        $outcome = sanitize($_POST['outcome'] ?? '');
        $followUpRequired = isset($_POST['follow_up_required']) ? 1 : 0;
        $followUpDate = $_POST['follow_up_date'] ?? null;
        $interactionDate = $_POST['interaction_date'] . ' ' . ($_POST['interaction_time'] ?? '00:00') . ':00';

        $stmt = $db->prepare("
            INSERT INTO interactions (customer_id, user_id, interaction_type, interaction_date, subject, description, outcome, follow_up_required, follow_up_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customerId, $userId, $interactionType, $interactionDate, $subject, $description, $outcome, $followUpRequired, $followUpDate]);

        logAction('log_interaction', 'interactions', $db->lastInsertId(), "Logged $interactionType with customer $customerId");
        redirect('/sales/interactions.php', 'Interaction logged successfully', 'success');
    } catch (PDOException $e) {
        error_log("Create Interaction Error: " . $e->getMessage());
        $_SESSION['flash_message'] = 'Error logging interaction';
        $_SESSION['flash_type'] = 'error';
    }
}

// Get filter parameters
$customerId = $_GET['customer_id'] ?? null;
$interactionType = $_GET['type'] ?? '';

// Build query
$where = [];
$params = [];

if ($role === 'sales_rep') {
    $where[] = "a.user_id = ? AND a.is_current = 1";
    $params[] = $userId;
}

if ($customerId) {
    $where[] = "i.customer_id = ?";
    $params[] = $customerId;
}

if ($interactionType) {
    $where[] = "i.interaction_type = ?";
    $params[] = $interactionType;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Get interactions
    $sql = "SELECT i.*, c.first_name, c.last_name, c.practice_name, u.first_name as rep_first, u.last_name as rep_last
            FROM interactions i
            INNER JOIN customers c ON i.customer_id = c.customer_id ";

    if ($role === 'sales_rep') {
        $sql .= "INNER JOIN assignments a ON c.customer_id = a.customer_id ";
    }

    $sql .= "LEFT JOIN users u ON i.user_id = u.user_id
            $whereClause
            ORDER BY i.interaction_date DESC
            LIMIT 50";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $interactions = $stmt->fetchAll();

    // Get my customers for dropdown
    if ($role === 'sales_rep') {
        $stmt = $db->prepare("
            SELECT c.customer_id, c.first_name, c.last_name, c.practice_name
            FROM customers c
            INNER JOIN assignments a ON c.customer_id = a.customer_id
            WHERE a.user_id = ? AND a.is_current = 1
            ORDER BY c.practice_name, c.last_name
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->query("
            SELECT customer_id, first_name, last_name, practice_name
            FROM customers
            ORDER BY practice_name, last_name
        ");
    }
    $customers = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Interactions Error: " . $e->getMessage());
    $interactions = [];
    $customers = [];
}

$showNewForm = isset($_GET['action']) && $_GET['action'] === 'new';
$preselectedCustomer = $_GET['customer_id'] ?? null;
$preselectedType = $_GET['type'] ?? 'call';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-journal-text"></i> Customer Interactions</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Interactions</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newInteractionModal">
                <i class="bi bi-plus-circle"></i> Log Interaction
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" name="customer_id">
                        <option value="">All Customers</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['customer_id']; ?>"
                                    <?php echo $customerId == $customer['customer_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['practice_name'] ?: ($customer['first_name'] . ' ' . $customer['last_name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="call" <?php echo $interactionType === 'call' ? 'selected' : ''; ?>>Call</option>
                        <option value="email" <?php echo $interactionType === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="meeting" <?php echo $interactionType === 'meeting' ? 'selected' : ''; ?>>Meeting</option>
                        <option value="visit" <?php echo $interactionType === 'visit' ? 'selected' : ''; ?>>Visit</option>
                        <option value="note" <?php echo $interactionType === 'note' ? 'selected' : ''; ?>>Note</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="/sales/interactions.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Interactions List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Interactions</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($interactions)): ?>
                        <div class="timeline">
                            <?php foreach ($interactions as $interaction): ?>
                            <div class="interaction-item <?php echo $interaction['interaction_type']; ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-<?php echo $interaction['interaction_type'] === 'call' ? 'info' : ($interaction['interaction_type'] === 'meeting' ? 'success' : 'secondary'); ?> me-2">
                                                <?php echo strtoupper($interaction['interaction_type']); ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($interaction['subject']); ?></strong>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($interaction['description'])); ?></p>
                                        <?php if ($interaction['outcome']): ?>
                                        <p class="mb-1"><em>Outcome: <?php echo htmlspecialchars($interaction['outcome']); ?></em></p>
                                        <?php endif; ?>
                                        <?php if ($interaction['follow_up_required']): ?>
                                        <p class="mb-0">
                                            <span class="badge bg-warning">
                                                <i class="bi bi-bell"></i> Follow-up required by <?php echo formatDate($interaction['follow_up_date']); ?>
                                            </span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <div><strong><?php echo htmlspecialchars($interaction['practice_name'] ?: ($interaction['first_name'] . ' ' . $interaction['last_name'])); ?></strong></div>
                                        <div class="text-muted"><?php echo formatDate($interaction['interaction_date'], DISPLAY_DATETIME_FORMAT); ?></div>
                                        <small class="text-muted">By: <?php echo htmlspecialchars($interaction['rep_first'] . ' ' . $interaction['rep_last']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No interactions found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Interaction Modal -->
<div class="modal fade" id="newInteractionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Log Interaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Customer</label>
                        <select class="form-select" name="customer_id" required>
                            <option value="">Select customer...</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['customer_id']; ?>"
                                        <?php echo $preselectedCustomer == $customer['customer_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['practice_name'] ?: ($customer['first_name'] . ' ' . $customer['last_name'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Interaction Type</label>
                                <select class="form-select" name="interaction_type" required>
                                    <option value="call" <?php echo $preselectedType === 'call' ? 'selected' : ''; ?>>Phone Call</option>
                                    <option value="email" <?php echo $preselectedType === 'email' ? 'selected' : ''; ?>>Email</option>
                                    <option value="meeting" <?php echo $preselectedType === 'meeting' ? 'selected' : ''; ?>>Meeting</option>
                                    <option value="visit" <?php echo $preselectedType === 'visit' ? 'selected' : ''; ?>>In-Person Visit</option>
                                    <option value="note" <?php echo $preselectedType === 'note' ? 'selected' : ''; ?>>Note</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label required">Date</label>
                                <input type="date" class="form-control" name="interaction_date" required
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Time</label>
                                <input type="time" class="form-control" name="interaction_time"
                                       value="<?php echo date('H:i'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Subject</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Outcome</label>
                        <input type="text" class="form-control" name="outcome" placeholder="What was the result?">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="followUpRequired" name="follow_up_required">
                            <label class="form-check-label" for="followUpRequired">
                                Follow-up required
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="followUpDateDiv" style="display:none;">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" class="form-control" name="follow_up_date" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_interaction" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Log Interaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('followUpRequired').addEventListener('change', function() {
    document.getElementById('followUpDateDiv').style.display = this.checked ? 'block' : 'none';
});

<?php if ($showNewForm): ?>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('newInteractionModal'));
    modal.show();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
