<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireAnyRole(['admin', 'manager']);

$db = getDB();

try {
    // Get statistics
    $stats = [];

    // Total customers
    $stats['total_customers'] = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

    // Active customers
    $stats['active_customers'] = $db->query("SELECT COUNT(*) FROM customers WHERE is_active = 1")->fetchColumn();

    // Total sales reps
    $stats['total_sales_reps'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'sales_rep' AND is_active = 1")->fetchColumn();

    // Unassigned customers
    $stats['unassigned'] = $db->query("
        SELECT COUNT(DISTINCT c.customer_id)
        FROM customers c
        LEFT JOIN assignments a ON c.customer_id = a.customer_id AND a.is_current = 1
        WHERE a.assignment_id IS NULL
    ")->fetchColumn();

    // This month's new active customers
    $stats['monthly_active'] = $db->query("
        SELECT COUNT(*)
        FROM customers
        WHERE is_active = 1
        AND MONTH(active_since) = MONTH(CURRENT_DATE())
        AND YEAR(active_since) = YEAR(CURRENT_DATE())
    ")->fetchColumn();

    // Get customers by state
    $customersByState = $db->query("
        SELECT state_code, COUNT(*) as count
        FROM customers
        WHERE state_code IS NOT NULL AND state_code != ''
        GROUP BY state_code
        ORDER BY count DESC
        LIMIT 10
    ")->fetchAll();

    // Get customers by account class
    $customersByClass = $db->query("
        SELECT account_class, COUNT(*) as count
        FROM customers
        WHERE account_class IS NOT NULL AND account_class != ''
        GROUP BY account_class
        ORDER BY count DESC
    ")->fetchAll();

    // Get sales rep performance
    $repPerformance = $db->query("
        SELECT u.user_id, u.first_name, u.last_name,
               COUNT(DISTINCT a.customer_id) as total_customers,
               COUNT(DISTINCT CASE WHEN c.is_active = 1 THEN c.customer_id END) as active_customers,
               COUNT(DISTINCT i.interaction_id) as total_interactions
        FROM users u
        LEFT JOIN assignments a ON u.user_id = a.user_id AND a.is_current = 1
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        LEFT JOIN interactions i ON u.user_id = i.user_id
            AND MONTH(i.interaction_date) = MONTH(CURRENT_DATE())
            AND YEAR(i.interaction_date) = YEAR(CURRENT_DATE())
        WHERE u.role = 'sales_rep' AND u.is_active = 1
        GROUP BY u.user_id
        ORDER BY total_customers DESC
    ")->fetchAll();

    // Recent system activity
    $recentActivity = $db->query("
        SELECT l.*, u.username, u.first_name, u.last_name
        FROM system_logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        ORDER BY l.created_at DESC
        LIMIT 15
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
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
            <div class="card dashboard-card warning">
                <div class="card-body">
                    <div class="stat-number text-warning"><?php echo number_format($stats['unassigned']); ?></div>
                    <div class="stat-label">Unassigned Customers</div>
                    <a href="/admin/assignments.php" class="btn btn-sm btn-outline-warning mt-2">Assign Now</a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card dashboard-card info">
                <div class="card-body">
                    <div class="stat-number text-info"><?php echo number_format($stats['total_sales_reps']); ?></div>
                    <div class="stat-label">Sales Team</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales Rep Performance -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Sales Rep Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sales Rep</th>
                                    <th>Customers</th>
                                    <th>Active</th>
                                    <th>Interactions (This Month)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repPerformance as $rep): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rep['first_name'] . ' ' . $rep['last_name']); ?></td>
                                    <td><?php echo number_format($rep['total_customers']); ?></td>
                                    <td><span class="badge bg-success"><?php echo number_format($rep['active_customers']); ?></span></td>
                                    <td><?php echo number_format($rep['total_interactions']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers by State -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Customers by State (Top 10)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>State</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customersByState as $state): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($state['state_code']); ?></strong></td>
                                    <td><?php echo number_format($state['count']); ?></td>
                                    <td>
                                        <?php $percentage = ($state['count'] / $stats['total_customers']) * 100; ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: <?php echo $percentage; ?>%"
                                                 aria-valuenow="<?php echo $percentage; ?>"
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Customers by Account Class -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Customers by Type</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($customersByClass as $class): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><strong><?php echo htmlspecialchars($class['account_class']); ?></strong></span>
                            <span><?php echo number_format($class['count']); ?> customers</span>
                        </div>
                        <?php $percentage = ($class['count'] / $stats['total_customers']) * 100; ?>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar"
                                 style="width: <?php echo $percentage; ?>%">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent System Activity -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent System Activity</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                                    - <?php echo htmlspecialchars($activity['action']); ?>
                                </div>
                                <small class="text-muted"><?php echo formatDate($activity['created_at'], 'm/d h:i A'); ?></small>
                            </div>
                            <?php if ($activity['description']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
