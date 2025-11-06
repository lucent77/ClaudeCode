<?php
/**
 * Admin Panel Dashboard
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin access
requireAdmin();

$pageTitle = 'Admin Panel';
$db = Database::getInstance();

// Get statistics
$stats = [
    'total_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM purchase_requests")['count'] ?? 0,
    'pending_requests' => $db->fetchOne("SELECT COUNT(*) as count FROM purchase_requests WHERE status = 'pending'")['count'] ?? 0,
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'total_vendors' => $db->fetchOne("SELECT COUNT(*) as count FROM vendors")['count'] ?? 0,
    'total_products' => $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'] ?? 0,
    'total_spending' => $db->fetchOne("SELECT SUM(price * quantity) as total FROM purchase_requests WHERE status IN ('completed', 'delivered')")['total'] ?? 0,
    'monthly_spending' => $db->fetchOne("SELECT SUM(price * quantity) as total FROM purchase_requests WHERE MONTH(requested_at) = MONTH(CURRENT_DATE()) AND YEAR(requested_at) = YEAR(CURRENT_DATE())")['total'] ?? 0,
];

// Get recent requests
$recentRequests = $db->fetchAll(
    "SELECT pr.*, u.username as requester_name
     FROM purchase_requests pr
     LEFT JOIN users u ON pr.user_id = u.id
     ORDER BY pr.requested_at DESC
     LIMIT 10"
);

// Get department spending
$departmentSpending = $db->fetchAll(
    "SELECT department, COUNT(*) as request_count, SUM(price * quantity) as total_spent
     FROM purchase_requests
     GROUP BY department
     ORDER BY total_spent DESC
     LIMIT 5"
);

// Get top products
$topProducts = $db->fetchAll(
    "SELECT product_name, COUNT(*) as purchase_count, SUM(quantity) as total_quantity
     FROM purchase_requests
     GROUP BY product_name
     ORDER BY purchase_count DESC
     LIMIT 5"
);

include __DIR__ . '/../includes/header.php';
?>

<!-- Admin Panel Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Admin Panel</h1>
    <p class="mt-2 text-sm text-gray-600">Manage purchase requests, users, vendors, and products.</p>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Requests -->
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Total Requests</p>
                <p class="text-3xl font-bold mt-1"><?php echo $stats['total_requests']; ?></p>
            </div>
            <svg class="h-12 w-12 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Pending Requests</p>
                <p class="text-3xl font-bold mt-1"><?php echo $stats['pending_requests']; ?></p>
            </div>
            <svg class="h-12 w-12 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <a href="/purchase-system/admin/manage-requests.php?status=pending" class="mt-3 text-sm underline opacity-90 hover:opacity-100">
            Review Pending →
        </a>
    </div>

    <!-- Monthly Spending -->
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">This Month</p>
                <p class="text-3xl font-bold mt-1"><?php echo formatCurrency($stats['monthly_spending']); ?></p>
            </div>
            <svg class="h-12 w-12 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Total Spending -->
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90">Total Spending</p>
                <p class="text-3xl font-bold mt-1"><?php echo formatCurrency($stats['total_spending']); ?></p>
            </div>
            <svg class="h-12 w-12 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="/purchase-system/admin/manage-requests.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">Manage Requests</h3>
                <p class="text-sm text-gray-500">View and update purchase requests</p>
            </div>
        </div>
    </a>

    <a href="/purchase-system/admin/manage-users.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">Manage Users</h3>
                <p class="text-sm text-gray-500"><?php echo $stats['total_users']; ?> users</p>
            </div>
        </div>
    </a>

    <a href="/purchase-system/admin/manage-vendors.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">Manage Vendors</h3>
                <p class="text-sm text-gray-500"><?php echo $stats['total_vendors']; ?> vendors</p>
            </div>
        </div>
    </a>
</div>

<!-- Recent Requests and Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Recent Requests -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Recent Requests</h2>
            <a href="/purchase-system/admin/manage-requests.php" class="text-sm text-blue-600 hover:text-blue-800">
                View All →
            </a>
        </div>
        <div class="divide-y divide-gray-200">
            <?php if (empty($recentRequests)): ?>
            <div class="p-6 text-center text-gray-500">No requests found</div>
            <?php else: ?>
            <?php foreach ($recentRequests as $request): ?>
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900"><?php echo e($request['product_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo e($request['department']); ?> • <?php echo formatDate($request['requested_at']); ?></p>
                    </div>
                    <div class="ml-4">
                        <?php echo getStatusBadge($request['status']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Department Spending -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Department Spending</h2>
        </div>
        <div class="p-6">
            <?php if (empty($departmentSpending)): ?>
            <p class="text-center text-gray-500">No data available</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($departmentSpending as $dept): ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700"><?php echo e($dept['department']); ?></span>
                        <span class="text-sm font-semibold text-gray-900"><?php echo formatCurrency($dept['total_spent']); ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <?php
                        $maxSpent = $departmentSpending[0]['total_spent'];
                        $percentage = $maxSpent > 0 ? ($dept['total_spent'] / $maxSpent) * 100 : 0;
                        ?>
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1"><?php echo $dept['request_count']; ?> requests</p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top Products -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Top Requested Products</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchases</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Quantity</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($topProducts)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No data available</td>
                </tr>
                <?php else: ?>
                <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($product['product_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['purchase_count']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $product['total_quantity']; ?> units</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
