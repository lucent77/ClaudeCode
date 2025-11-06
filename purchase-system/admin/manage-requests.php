<?php
/**
 * Admin - Manage All Purchase Requests
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pageTitle = 'Manage Requests';
$db = Database::getInstance();

// Get filters
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(product_name LIKE ? OR vendor_name LIKE ? OR department LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM purchase_requests {$whereClause}";
$totalResult = $db->fetchOne($countSql, $params);
$totalRequests = $totalResult['total'];
$totalPages = ceil($totalRequests / ITEMS_PER_PAGE);

// Get requests
$sql = "SELECT pr.*, u.username as requester_name
        FROM purchase_requests pr
        LEFT JOIN users u ON pr.user_id = u.id
        {$whereClause}
        ORDER BY pr.requested_at DESC
        LIMIT ? OFFSET ?";

$params[] = ITEMS_PER_PAGE;
$params[] = $offset;
$requests = $db->fetchAll($sql, $params);

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Manage Purchase Requests</h1>
    <p class="mt-2 text-sm text-gray-600">View, edit, and manage all purchase requests</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <form method="GET" action="" class="flex-1 flex gap-2">
            <input
                type="text"
                name="search"
                value="<?php echo e($searchQuery); ?>"
                placeholder="Search requests..."
                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Search</button>
            <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
            <a href="/purchase-system/admin/manage-requests.php" class="px-6 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </form>

        <div class="flex gap-2">
            <select
                name="status"
                onchange="window.location.href='?status=' + this.value"
                class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            >
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="ordered" <?php echo $statusFilter === 'ordered' ? 'selected' : ''; ?>>Ordered</option>
                <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>

            <a href="/purchase-system/admin/export-csv.php?type=requests<?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if (empty($requests)): ?>
    <div class="p-8 text-center text-gray-500">No requests found</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($requests as $request): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $request['id']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($request['product_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($request['department']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $request['quantity']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatCurrency($request['price']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?php echo getStatusBadge($request['status']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatDate($request['requested_at']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="/purchase-system/view-request.php?id=<?php echo $request['id']; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                        <a href="/purchase-system/admin/edit-request.php?id=<?php echo $request['id']; ?>" class="text-green-600 hover:text-green-900">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php echo getPagination($page, $totalPages, '/purchase-system/admin/manage-requests.php'); ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
