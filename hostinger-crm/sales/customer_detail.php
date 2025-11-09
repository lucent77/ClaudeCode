<?php
$page_title = 'Customer Details';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$customerId = $_GET['id'] ?? 0;
$db = getDB();
$userId = getCurrentUserId();

// Get customer details
try {
    $customer = getCustomerById($customerId);

    if (!$customer) {
        redirect('/sales/customers.php', 'Customer not found', 'error');
    }

    // Check if user has access to this customer
    if (!isCustomerAssignedToUser($customerId)) {
        redirect('/sales/customers.php', 'Access denied', 'error');
    }

    // Get assigned sales rep
    $assignedRep = getAssignedSalesRep($customerId);

    // Get recent interactions
    $stmt = $db->prepare("
        SELECT i.*, u.first_name, u.last_name
        FROM interactions i
        LEFT JOIN users u ON i.user_id = u.user_id
        WHERE i.customer_id = ?
        ORDER BY i.interaction_date DESC
        LIMIT 10
    ");
    $stmt->execute([$customerId]);
    $interactions = $stmt->fetchAll();

    // Get upcoming meetings
    $stmt = $db->prepare("
        SELECT m.*, u.first_name, u.last_name
        FROM meetings m
        LEFT JOIN users u ON m.user_id = u.user_id
        WHERE m.customer_id = ?
        AND m.meeting_date >= NOW()
        AND m.status = 'scheduled'
        ORDER BY m.meeting_date ASC
        LIMIT 5
    ");
    $stmt->execute([$customerId]);
    $upcomingMeetings = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Customer Detail Error: " . $e->getMessage());
    redirect('/sales/customers.php', 'Error loading customer', 'error');
}

// Handle quick note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_note'])) {
    try {
        $note = sanitize($_POST['note_text']);

        $stmt = $db->prepare("
            INSERT INTO interactions (customer_id, user_id, interaction_type, interaction_date, subject, description)
            VALUES (?, ?, 'note', NOW(), 'Quick Note', ?)
        ");
        $stmt->execute([$customerId, $userId, $note]);

        logAction('add_note', 'interactions', $db->lastInsertId(), "Added note for customer $customerId");
        redirect("/sales/customer_detail.php?id=$customerId", 'Note added successfully', 'success');
    } catch (PDOException $e) {
        error_log("Add Note Error: " . $e->getMessage());
        $_SESSION['flash_message'] = 'Error adding note';
        $_SESSION['flash_type'] = 'error';
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>
                <i class="bi bi-person-badge"></i>
                <?php echo htmlspecialchars($customer['practice_name'] ?: ($customer['first_name'] . ' ' . $customer['last_name'])); ?>
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/sales/customers.php">Customers</a></li>
                    <li class="breadcrumb-item active">Customer Details</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="/sales/customers.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-md-4">
            <div class="customer-detail-section">
                <h5><i class="bi bi-info-circle"></i> Basic Information</h5>

                <span class="info-label">Account Number</span>
                <span class="info-value"><code><?php echo htmlspecialchars($customer['account_number']); ?></code></span>

                <span class="info-label">Customer Name</span>
                <span class="info-value">
                    <?php echo htmlspecialchars(($customer['title'] ? $customer['title'] . ' ' : '') . $customer['first_name'] . ' ' . $customer['last_name']); ?>
                </span>

                <?php if ($customer['practice_name']): ?>
                <span class="info-label">Practice Name</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['practice_name']); ?></span>
                <?php endif; ?>

                <span class="info-label">Account Type</span>
                <span class="info-value">
                    <span class="badge bg-info"><?php echo htmlspecialchars($customer['account_class'] ?: 'N/A'); ?></span>
                </span>

                <span class="info-label">Status</span>
                <span class="info-value">
                    <span class="badge <?php echo getStatusBadgeClass($customer['customer_status']); ?>">
                        <?php echo htmlspecialchars($customer['customer_status_description'] ?: $customer['customer_status']); ?>
                    </span>
                    <?php if ($customer['is_active']): ?>
                        <span class="badge bg-success">Active Customer</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="customer-detail-section">
                <h5><i class="bi bi-geo-alt"></i> Contact Information</h5>

                <?php if ($customer['address_line1']): ?>
                <span class="info-label">Address</span>
                <span class="info-value">
                    <?php echo htmlspecialchars($customer['address_line1']); ?><br>
                    <?php if ($customer['address_line2']): echo htmlspecialchars($customer['address_line2']) . '<br>'; endif; ?>
                    <?php echo htmlspecialchars($customer['city']); ?>, <?php echo htmlspecialchars($customer['state_code']); ?> <?php echo htmlspecialchars($customer['zip_code']); ?>
                </span>
                <?php endif; ?>

                <?php if ($customer['phone_number']): ?>
                <span class="info-label">Phone</span>
                <span class="info-value">
                    <i class="bi bi-telephone"></i> <a href="tel:<?php echo htmlspecialchars($customer['phone_number']); ?>">
                        <?php echo htmlspecialchars($customer['phone_number']); ?>
                    </a>
                </span>
                <?php endif; ?>

                <?php if ($customer['cell_phone']): ?>
                <span class="info-label">Mobile</span>
                <span class="info-value">
                    <i class="bi bi-phone"></i> <a href="tel:<?php echo htmlspecialchars($customer['cell_phone']); ?>">
                        <?php echo htmlspecialchars($customer['cell_phone']); ?>
                    </a>
                </span>
                <?php endif; ?>

                <?php if ($customer['fax_number']): ?>
                <span class="info-label">Fax</span>
                <span class="info-value">
                    <i class="bi bi-printer"></i> <?php echo htmlspecialchars($customer['fax_number']); ?>
                </span>
                <?php endif; ?>

                <?php if ($customer['email_address']): ?>
                <span class="info-label">Email</span>
                <span class="info-value">
                    <i class="bi bi-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($customer['email_address']); ?>">
                        <?php echo htmlspecialchars($customer['email_address']); ?>
                    </a>
                </span>
                <?php endif; ?>

                <?php if ($customer['website']): ?>
                <span class="info-label">Website</span>
                <span class="info-value">
                    <i class="bi bi-globe"></i> <a href="<?php echo htmlspecialchars($customer['website']); ?>" target="_blank">
                        <?php echo htmlspecialchars($customer['website']); ?>
                    </a>
                </span>
                <?php endif; ?>
            </div>

            <div class="customer-detail-section">
                <h5><i class="bi bi-person"></i> Assignment</h5>

                <?php if ($assignedRep): ?>
                <span class="info-label">Assigned Sales Rep</span>
                <span class="info-value">
                    <strong><?php echo htmlspecialchars($assignedRep['first_name'] . ' ' . $assignedRep['last_name']); ?></strong><br>
                    <small class="text-muted">Since <?php echo formatDate($assignedRep['assignment_date']); ?></small>
                </span>
                <?php else: ?>
                <p class="text-muted">Not assigned to any sales rep</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Interactions and Activities -->
        <div class="col-md-8">
            <!-- Quick Actions -->
            <div class="customer-detail-section">
                <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                <div class="row g-2">
                    <div class="col-auto">
                        <a href="/sales/interactions.php?customer_id=<?php echo $customerId; ?>&action=new&type=call"
                           class="btn btn-outline-primary">
                            <i class="bi bi-telephone"></i> Log Call
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="/sales/interactions.php?customer_id=<?php echo $customerId; ?>&action=new&type=email"
                           class="btn btn-outline-primary">
                            <i class="bi bi-envelope"></i> Log Email
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="/sales/calendar.php?customer_id=<?php echo $customerId; ?>&action=new"
                           class="btn btn-outline-success">
                            <i class="bi bi-calendar-plus"></i> Schedule Meeting
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Note -->
            <div class="customer-detail-section">
                <h5><i class="bi bi-journal-plus"></i> Add Quick Note</h5>
                <form method="POST" action="">
                    <div class="mb-3">
                        <textarea class="form-control" name="note_text" rows="3" placeholder="Enter note..." required></textarea>
                    </div>
                    <button type="submit" name="quick_note" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Note
                    </button>
                </form>
            </div>

            <!-- Upcoming Meetings -->
            <?php if (!empty($upcomingMeetings)): ?>
            <div class="customer-detail-section">
                <h5><i class="bi bi-calendar-event"></i> Upcoming Meetings</h5>
                <div class="list-group">
                    <?php foreach ($upcomingMeetings as $meeting): ?>
                    <div class="list-group-item calendar-event scheduled">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($meeting['title']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo formatDate($meeting['meeting_date'], 'm/d/Y h:i A'); ?>
                                    <?php if ($meeting['location']): ?>
                                        | <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($meeting['location']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <span class="badge bg-primary"><?php echo strtoupper($meeting['meeting_type']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Interactions -->
            <div class="customer-detail-section">
                <h5><i class="bi bi-clock-history"></i> Recent Interactions</h5>

                <?php if (!empty($interactions)): ?>
                <div class="timeline">
                    <?php foreach ($interactions as $interaction): ?>
                    <div class="interaction-item <?php echo $interaction['interaction_type']; ?>">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="badge bg-<?php echo $interaction['interaction_type'] === 'call' ? 'info' : ($interaction['interaction_type'] === 'meeting' ? 'success' : 'secondary'); ?>">
                                    <?php echo strtoupper($interaction['interaction_type']); ?>
                                </span>
                                <strong><?php echo htmlspecialchars($interaction['subject'] ?: 'No Subject'); ?></strong>
                            </div>
                            <small class="text-muted"><?php echo formatDate($interaction['interaction_date'], DISPLAY_DATETIME_FORMAT); ?></small>
                        </div>
                        <?php if ($interaction['description']): ?>
                        <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($interaction['description'])); ?></p>
                        <?php endif; ?>
                        <?php if ($interaction['outcome']): ?>
                        <p class="mb-0"><em>Outcome: <?php echo htmlspecialchars($interaction['outcome']); ?></em></p>
                        <?php endif; ?>
                        <small class="text-muted">
                            By: <?php echo htmlspecialchars($interaction['first_name'] . ' ' . $interaction['last_name']); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="/sales/interactions.php?customer_id=<?php echo $customerId; ?>" class="btn btn-sm btn-outline-primary">
                        View All Interactions
                    </a>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-4">No interactions recorded yet</p>
                <div class="text-center">
                    <a href="/sales/interactions.php?customer_id=<?php echo $customerId; ?>&action=new" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Log First Interaction
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
