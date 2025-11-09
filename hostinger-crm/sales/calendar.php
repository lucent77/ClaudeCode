<?php
$page_title = 'Calendar & Meetings';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();
$role = getCurrentUserRole();

// Handle new meeting submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_meeting'])) {
    try {
        $customerId = intval($_POST['customer_id']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $meetingDate = $_POST['meeting_date'];
        $meetingTime = $_POST['meeting_time'];
        $duration = intval($_POST['duration'] ?? 30);
        $location = sanitize($_POST['location'] ?? '');
        $meetingType = sanitize($_POST['meeting_type']);

        $meetingDateTime = $meetingDate . ' ' . $meetingTime . ':00';

        $stmt = $db->prepare("
            INSERT INTO meetings (customer_id, user_id, title, description, meeting_date, duration_minutes, location, meeting_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customerId, $userId, $title, $description, $meetingDateTime, $duration, $location, $meetingType]);

        logAction('create_meeting', 'meetings', $db->lastInsertId(), "Created meeting: $title");
        redirect('/sales/calendar.php', 'Meeting scheduled successfully', 'success');
    } catch (PDOException $e) {
        error_log("Create Meeting Error: " . $e->getMessage());
        $_SESSION['flash_message'] = 'Error creating meeting';
        $_SESSION['flash_type'] = 'error';
    }
}

// Get upcoming meetings
try {
    if ($role === 'sales_rep') {
        $stmt = $db->prepare("
            SELECT m.*, c.first_name, c.last_name, c.practice_name
            FROM meetings m
            INNER JOIN customers c ON m.customer_id = c.customer_id
            WHERE m.user_id = ?
            AND m.meeting_date >= NOW()
            ORDER BY m.meeting_date ASC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->query("
            SELECT m.*, c.first_name, c.last_name, c.practice_name, u.first_name as rep_first, u.last_name as rep_last
            FROM meetings m
            INNER JOIN customers c ON m.customer_id = c.customer_id
            INNER JOIN users u ON m.user_id = u.user_id
            WHERE m.meeting_date >= NOW()
            ORDER BY m.meeting_date ASC
        ");
    }
    $upcomingMeetings = $stmt->fetchAll();

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
    error_log("Calendar Error: " . $e->getMessage());
    $upcomingMeetings = [];
    $customers = [];
}

$showNewForm = isset($_GET['action']) && $_GET['action'] === 'new';
$preselectedCustomer = $_GET['customer_id'] ?? null;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-calendar-event"></i> Calendar & Meetings</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Calendar</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
                <i class="bi bi-plus-circle"></i> Schedule Meeting
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-calendar3"></i> Upcoming Meetings</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingMeetings)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Meeting</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <?php if ($role !== 'sales_rep'): ?>
                                        <th>Sales Rep</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingMeetings as $meeting): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo formatDate($meeting['meeting_date'], 'm/d/Y'); ?></strong><br>
                                            <small><?php echo formatDate($meeting['meeting_date'], 'h:i A'); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($meeting['title']); ?></strong><br>
                                            <?php if ($meeting['description']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($meeting['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($meeting['practice_name'] ?: ($meeting['first_name'] . ' ' . $meeting['last_name'])); ?></td>
                                        <td><span class="badge bg-info"><?php echo strtoupper($meeting['meeting_type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($meeting['location'] ?: '-'); ?></td>
                                        <?php if ($role !== 'sales_rep'): ?>
                                        <td><?php echo htmlspecialchars($meeting['rep_first'] . ' ' . $meeting['rep_last']); ?></td>
                                        <?php endif; ?>
                                        <td><span class="badge bg-primary"><?php echo ucfirst($meeting['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No upcoming meetings</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Meeting Modal -->
<div class="modal fade" id="newMeetingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Schedule New Meeting</h5>
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

                    <div class="mb-3">
                        <label class="form-label required">Meeting Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Date</label>
                                <input type="date" class="form-control" name="meeting_date" required
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Time</label>
                                <input type="time" class="form-control" name="meeting_time" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" value="30" min="15" step="15">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Meeting Type</label>
                                <select class="form-select" name="meeting_type" required>
                                    <option value="call">Phone Call</option>
                                    <option value="visit">In-Person Visit</option>
                                    <option value="online">Online Meeting</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="Meeting location or link">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_meeting" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Schedule Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($showNewForm): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('newMeetingModal'));
    modal.show();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
