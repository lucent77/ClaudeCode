<?php
/**
 * User Dashboard
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
requireLogin();

$pageTitle = 'Dashboard';
$db = Database::getInstance();

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Get filter parameters
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build WHERE clause
$whereConditions = [];
$params = [];

// Filter by department for regular users, admin sees all
if (!isAdmin()) {
    $whereConditions[] = "department = ?";
    $params[] = $_SESSION['department'];
}

// Status filter
if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

// Search filter
if (!empty($searchQuery)) {
    $whereConditions[] = "(product_name LIKE ? OR vendor_name LIKE ? OR reason LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM purchase_requests {$whereClause}";
$totalResult = $db->fetchOne($countSql, $params);
$totalRequests = $totalResult['total'];
$totalPages = ceil($totalRequests / ITEMS_PER_PAGE);

// Get purchase requests
$sql = "SELECT pr.*, u.username as requester_name
        FROM purchase_requests pr
        LEFT JOIN users u ON pr.user_id = u.id
        {$whereClause}
        ORDER BY pr.requested_at DESC
        LIMIT ? OFFSET ?";

$params[] = ITEMS_PER_PAGE;
$params[] = $offset;
$requests = $db->fetchAll($sql, $params);

// Get statistics
$statsWhere = !isAdmin() ? "WHERE department = ?" : "";
$statsParams = !isAdmin() ? [$_SESSION['department']] : [];

$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM purchase_requests {$statsWhere}", $statsParams)['count'] ?? 0,
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM purchase_requests {$statsWhere}" . (!empty($statsWhere) ? " AND" : "WHERE") . " status = 'pending'", array_merge($statsParams, ['pending']))['count'] ?? 0,
    'approved' => $db->fetchOne("SELECT COUNT(*) as count FROM purchase_requests {$statsWhere}" . (!empty($statsWhere) ? " AND" : "WHERE") . " status IN ('approved', 'ordered')", $statsParams)['count'] ?? 0,
    'completed' => $db->fetchOne("SELECT COUNT(*) as count FROM purchase_requests {$statsWhere}" . (!empty($statsWhere) ? " AND" : "WHERE") . " status = 'completed'", array_merge($statsParams, ['completed']))['count'] ?? 0,
];

// Get total spending
$spendingSql = "SELECT SUM(price * quantity) as total FROM purchase_requests {$statsWhere}";
$spendingResult = $db->fetchOne($spendingSql, $statsParams);
$totalSpending = $spendingResult['total'] ?? 0;

include __DIR__ . '/includes/header.php';
?>

<!-- Dashboard Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total Requests -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="ml-5">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Requests</dt>
                    <dd class="text-2xl font-semibold text-gray-900"><?php echo $stats['total']; ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-5">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                    <dd class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending']; ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Approved/Ordered -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="ml-5">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                    <dd class="text-2xl font-semibold text-gray-900"><?php echo $stats['approved']; ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-5">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                    <dd class="text-2xl font-semibold text-gray-900"><?php echo $stats['completed']; ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Total Spending -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-5">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Spending</dt>
                    <dd class="text-2xl font-semibold text-gray-900"><?php echo formatCurrency($totalSpending); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Search -->
            <div class="flex-1">
                <form method="GET" action="" class="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        value="<?php echo e($searchQuery); ?>"
                        placeholder="Search by product, vendor, or reason..."
                        class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Search
                    </button>
                    <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                    <a href="/purchase-system/dashboard.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Status Filter -->
            <div>
                <select
                    name="status"
                    onchange="window.location.href='?status=' + this.value"
                    class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="ordered" <?php echo $statusFilter === 'ordered' ? 'selected' : ''; ?>>Ordered</option>
                    <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <!-- New Request Button -->
            <div>
                <a href="/purchase-system/new-request.php" class="inline-flex items-center px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Request
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Requests Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800">Purchase Requests</h2>
    </div>

    <?php if (empty($requests)): ?>
    <div class="p-8 text-center text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="mt-4">No purchase requests found</p>
        <a href="/purchase-system/new-request.php" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
            Create your first request
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                    <?php if (isAdmin()): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($requests as $request): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #<?php echo $request['id']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo e($request['product_name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo e($request['request_type']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php if (!empty($request['vendor_link'])): ?>
                        <a href="<?php echo e($request['vendor_link']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                            <?php echo e($request['vendor_name']); ?>
                        </a>
                        <?php else: ?>
                        <?php echo e($request['vendor_name']); ?>
                        <?php endif; ?>
                    </td>
                    <?php if (isAdmin()): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo e($request['department']); ?>
                    </td>
                    <?php endif; ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $request['quantity']; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo formatCurrency($request['price']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php echo getStatusBadge($request['status']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo formatDate($request['requested_at']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="/purchase-system/view-request.php?id=<?php echo $request['id']; ?>" class="text-blue-600 hover:text-blue-900">
                            View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php echo getPagination($page, $totalPages, '/purchase-system/dashboard.php'); ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
