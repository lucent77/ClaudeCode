<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$db = getDB();
$userId = getCurrentUserId();
$role = getCurrentUserRole();

// Get statistics based on role
$stats = [];

try {
    if ($role === 'admin' || $role === 'manager') {
        // Total customers
        $stmt = $db->query("SELECT COUNT(*) as total FROM customers");
        $stats['total_customers'] = $stmt->fetch()['total'];

        // Active customers
        $stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE is_active = 1");
        $stats['active_customers'] = $stmt->fetch()['total'];

        // Total users
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
        $stats['total_users'] = $stmt->fetch()['total'];

        // This month's new active customers
        $stmt = $db->query("
            SELECT COUNT(*) as total
            FROM customers
            WHERE is_active = 1
            AND MONTH(active_since) = MONTH(CURRENT_DATE())
            AND YEAR(active_since) = YEAR(CURRENT_DATE())
        ");
        $stats['monthly_new_active'] = $stmt->fetch()['total'];
    } else {
        // Sales rep stats - only their assigned customers
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT c.customer_id) as total
            FROM customers c
            INNER JOIN assignments a ON c.customer_id = a.customer_id
            WHERE a.user_id = ? AND a.is_current = 1
        ");
        $stmt->execute([$userId]);
        $stats['my_customers'] = $stmt->fetch()['total'];

        // My active customers
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT c.customer_id) as total
            FROM customers c
            INNER JOIN assignments a ON c.customer_id = a.customer_id
            WHERE a.user_id = ? AND a.is_current = 1 AND c.is_active = 1
        ");
        $stmt->execute([$userId]);
        $stats['my_active'] = $stmt->fetch()['total'];

        // My meetings today
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM meetings
            WHERE user_id = ?
            AND DATE(meeting_date) = CURRENT_DATE()
            AND status = 'scheduled'
        ");
        $stmt->execute([$userId]);
        $stats['meetings_today'] = $stmt->fetch()['total'];

        // My pending follow-ups
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM interactions
            WHERE user_id = ?
            AND follow_up_required = 1
            AND follow_up_date <= CURRENT_DATE()
        ");
        $stmt->execute([$userId]);
        $stats['pending_followups'] = $stmt->fetch()['total'];
    }

    // Recent activities
    if ($role === 'admin' || $role === 'manager') {
        $recentActivities = $db->query("
            SELECT l.*, u.username, u.first_name, u.last_name
            FROM system_logs l
            LEFT JOIN users u ON l.user_id = u.user_id
            ORDER BY l.created_at DESC
            LIMIT 10
        ")->fetchAll();
    } else {
        $stmt = $db->prepare("
            SELECT i.*, c.first_name, c.last_name, c.practice_name
            FROM interactions i
            INNER JOIN customers c ON i.customer_id = c.customer_id
            WHERE i.user_id = ?
            ORDER BY i.interaction_date DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $recentActivities = $stmt->fetchAll();
    }

    // Upcoming meetings
    if ($role === 'sales_rep') {
        $stmt = $db->prepare("
            SELECT m.*, c.first_name, c.last_name, c.practice_name
            FROM meetings m
            INNER JOIN customers c ON m.customer_id = c.customer_id
            WHERE m.user_id = ?
            AND m.meeting_date >= NOW()
            AND m.status = 'scheduled'
            ORDER BY m.meeting_date ASC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $upcomingMeetings = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>
                <i class="bi bi-speedometer2"></i> Dashboard
            </h2>
            <p class="text-muted">Welcome back, <?php echo getCurrentUserName(); ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <?php if ($role === 'admin' || $role === 'manager'): ?>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="stat-number text-primary"><?php echo number_format($stats['total_customers']); ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card success">
                    <div class="card-body">
                        <div class="stat-number text-success"><?php echo number_format($stats['active_customers']); ?></div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card info">
                    <div class="card-body">
                        <div class="stat-number text-info"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Sales Team</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card warning">
                    <div class="card-body">
                        <div class="stat-number text-warning"><?php echo number_format($stats['monthly_new_active']); ?></div>
                        <div class="stat-label">New Active This Month</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="stat-number text-primary"><?php echo number_format($stats['my_customers']); ?></div>
                        <div class="stat-label">My Customers</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card success">
                    <div class="card-body">
                        <div class="stat-number text-success"><?php echo number_format($stats['my_active']); ?></div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card warning">
                    <div class="card-body">
                        <div class="stat-number text-warning"><?php echo number_format($stats['meetings_today']); ?></div>
                        <div class="stat-label">Meetings Today</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card danger">
                    <div class="card-body">
                        <div class="stat-number text-danger"><?php echo number_format($stats['pending_followups']); ?></div>
                        <div class="stat-label">Pending Follow-ups</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Recent Activities -->
        <div class="col-md-<?php echo $role === 'sales_rep' ? '6' : '12'; ?> mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i>
                        <?php echo ($role === 'admin' || $role === 'manager') ? 'Recent System Activities' : 'Recent Interactions'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivities)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="list-group-item">
                                    <?php if ($role === 'admin' || $role === 'manager'): ?>
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                                                - <?php echo htmlspecialchars($activity['action']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo formatDate($activity['created_at'], DISPLAY_DATETIME_FORMAT); ?></small>
                                        </div>
                                        <?php if ($activity['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <span class="badge bg-<?php echo $activity['interaction_type'] === 'call' ? 'info' : ($activity['interaction_type'] === 'meeting' ? 'success' : 'secondary'); ?>">
                                                    <?php echo strtoupper($activity['interaction_type']); ?>
                                                </span>
                                                <strong><?php echo htmlspecialchars($activity['practice_name'] ?: ($activity['first_name'] . ' ' . $activity['last_name'])); ?></strong>
                                                <?php if ($activity['subject']): ?>
                                                    - <?php echo htmlspecialchars($activity['subject']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?php echo formatDate($activity['interaction_date'], DISPLAY_DATETIME_FORMAT); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center my-4">No recent activities</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Meetings (Sales Rep only) -->
        <?php if ($role === 'sales_rep'): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-event"></i> Upcoming Meetings
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingMeetings)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingMeetings as $meeting): ?>
                                <div class="list-group-item calendar-event scheduled">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($meeting['title']); ?></strong><br>
                                            <small>
                                                <?php echo htmlspecialchars($meeting['practice_name'] ?: ($meeting['first_name'] . ' ' . $meeting['last_name'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div><?php echo formatDate($meeting['meeting_date'], 'm/d/Y'); ?></div>
                                            <small class="text-muted"><?php echo formatDate($meeting['meeting_date'], 'h:i A'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/sales/calendar.php" class="btn btn-sm btn-outline-primary">View All Meetings</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center my-4">No upcoming meetings</p>
                        <div class="text-center">
                            <a href="/sales/calendar.php" class="btn btn-sm btn-primary">Schedule a Meeting</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($role === 'admin' || $role === 'manager'): ?>
                            <div class="col-md-3 mb-2">
                                <a href="/admin/import.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-upload"></i> Import Customers
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/admin/assignments.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-person-plus"></i> Assign Customers
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/manager/reports.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-graph-up"></i> View Reports
                                </a>
                            </div>
                            <?php if ($role === 'admin'): ?>
                            <div class="col-md-3 mb-2">
                                <a href="/admin/users.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-people"></i> Manage Users
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="col-md-3 mb-2">
                                <a href="/sales/customers.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-people"></i> View Customers
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/sales/calendar.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-calendar-plus"></i> Schedule Meeting
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="/sales/interactions.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-journal-text"></i> Log Interaction
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
