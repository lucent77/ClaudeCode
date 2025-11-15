<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$arch = $_GET['arch'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query
$db = getDB();
$where = ['1=1'];
$params = [];

if (!empty($search)) {
    $where[] = "(patient_name LIKE ? OR notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status === 'completed') {
    $where[] = "completed = 1";
} elseif ($status === 'pending') {
    $where[] = "completed = 0";
}

if (!empty($arch)) {
    $where[] = "arch = ?";
    $params[] = $arch;
}

if (!empty($dateFrom)) {
    $where[] = "surgery_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "surgery_date <= ?";
    $params[] = $dateTo;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM tasks WHERE $whereClause");
$countStmt->execute($params);
$totalTasks = $countStmt->fetch()['total'];
$totalPages = ceil($totalTasks / $limit);

// Get tasks
$stmt = $db->prepare("
    SELECT * FROM tasks
    WHERE $whereClause
    ORDER BY
        CASE WHEN surgery_date IS NOT NULL THEN 0 ELSE 1 END,
        surgery_date ASC,
        due_date ASC,
        created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get statistics
$statsStmt = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN completed = 0 THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN ready_for_surgery = 1 THEN 1 ELSE 0 END) as ready_for_surgery,
        SUM(CASE WHEN surgery_date = CURDATE() THEN 1 ELSE 0 END) as today_surgeries
    FROM tasks
");
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-indigo-600">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tasks text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-xl font-bold text-white"><?php echo APP_NAME; ?></h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php if (isAdmin()): ?>
                            <a href="users.php" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-users mr-2"></i>Users
                            </a>
                        <?php endif; ?>
                        <a href="sync.php" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sync mr-2"></i>Sync
                        </a>
                        <div class="text-white text-sm">
                            <i class="fas fa-user mr-2"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-indigo-500">
                                <?php echo htmlspecialchars($_SESSION['role']); ?>
                            </span>
                        </div>
                        <a href="logout.php" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Statistics -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Tasks</dt>
                            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($stats['total']); ?></dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                            <dd class="mt-1 text-3xl font-semibold text-yellow-600"><?php echo number_format($stats['pending']); ?></dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600"><?php echo number_format($stats['completed']); ?></dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Ready for Surgery</dt>
                            <dd class="mt-1 text-3xl font-semibold text-blue-600"><?php echo number_format($stats['ready_for_surgery']); ?></dd>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Surgeries</dt>
                            <dd class="mt-1 text-3xl font-semibold text-red-600"><?php echo number_format($stats['today_surgeries']); ?></dd>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <form method="GET" action="" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Patient name or notes..."
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                <option value="">All</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Arch</label>
                            <select name="arch" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                                <option value="">All</option>
                                <option value="Upper" <?php echo $arch === 'Upper' ? 'selected' : ''; ?>>Upper</option>
                                <option value="Lower" <?php echo $arch === 'Lower' ? 'selected' : ''; ?>>Lower</option>
                                <option value="Both" <?php echo $arch === 'Both' ? 'selected' : ''; ?>>Both</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-6 flex gap-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-search mr-2"></i>Filter
                            </button>
                            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-times mr-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tasks Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Surgery Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($tasks)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 text-gray-400"></i>
                                            <p>No tasks found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($task['patient_name']); ?>
                                                </div>
                                                <?php if ($task['ready_for_surgery']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        Ready
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($task['arch'] ?? '-'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo formatDate($task['surgery_date']); ?>
                                                <?php if ($task['surgery_time']): ?>
                                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($task['surgery_time']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php
                                                $dueDate = formatDate($task['due_date']);
                                                $isPastDue = $task['due_date'] && strtotime($task['due_date']) < time() && !$task['completed'];
                                                ?>
                                                <span class="<?php echo $isPastDue ? 'text-red-600 font-medium' : ''; ?>">
                                                    <?php echo $dueDate; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($task['completed']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check mr-1"></i>Completed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i>Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex gap-2">
                                                    <?php if (!empty($task['photos'])): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-purple-100 text-purple-800">
                                                            <i class="fas fa-camera mr-1"></i><?php echo count(explode(',', $task['photos'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($task['stls'])): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                                                            <i class="fas fa-cube mr-1"></i><?php echo count(explode(',', $task['stls'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($task['pre_op_cbct']) || !empty($task['post_op_cbct'])): ?>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                                                            <i class="fas fa-x-ray mr-1"></i>CBCT
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="task_detail.php?id=<?php echo $task['id']; ?>"
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $totalPages; ?></span>
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                               class="<?php echo $i === $page ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
