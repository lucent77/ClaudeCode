<?php
/**
 * Tool Management
 * View and manage manufacturing tools
 */

require_once 'config/database.php';
checkAuth();

$page_title = 'Tool Management - Production Management System';
$pdo = getDbConnection();

// Get filter
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM tools WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery) {
    $query .= " AND (tool_code LIKE ? OR tool_name LIKE ? OR category_name LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY tool_code";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tools = $stmt->fetchAll();

// Get status counts
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM tools
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get low stock count
$lowStockCount = $pdo->query("
    SELECT COUNT(*) FROM tools WHERE current_stock <= minimum_stock
")->fetchColumn();

include 'includes/header.php';
?>

<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Tool Management</h1>
        <?php if (getCurrentUser()['role'] === 'admin'): ?>
        <button onclick="showAddToolModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
            Add New Tool
        </button>
        <?php endif; ?>
    </div>

    <!-- Search and Filter -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
        <form method="GET" action="tools.php" class="flex gap-4">
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                placeholder="Search by code, name, or category..."
                class="flex-1 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium">
                Search
            </button>
            <?php if ($searchQuery): ?>
            <a href="tools.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-md font-medium">
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="?status=all<?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"
                   class="<?php echo $statusFilter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    All
                </a>
                <a href="?status=new<?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"
                   class="<?php echo $statusFilter === 'new' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    New (<?php echo $statusCounts['new'] ?? 0; ?>)
                </a>
                <a href="?status=in_use<?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"
                   class="<?php echo $statusFilter === 'in_use' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    In Use (<?php echo $statusCounts['in_use'] ?? 0; ?>)
                </a>
                <a href="?status=used<?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"
                   class="<?php echo $statusFilter === 'used' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Used (<?php echo $statusCounts['used'] ?? 0; ?>)
                </a>
            </nav>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if ($lowStockCount > 0): ?>
    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong><?php echo $lowStockCount; ?></strong> tool(s) have stock levels at or below minimum threshold.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tools Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <?php if (getCurrentUser()['role'] === 'admin'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($tools) === 0): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                            No tools found. <?php if (getCurrentUser()['role'] === 'admin'): ?>Click "Add New Tool" to get started or <a href="upload.php" class="text-blue-600 hover:underline">upload tools from CSV</a>.<?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($tools as $tool):
                        $usagePercentage = 0;
                        if ($tool['lifespan_limit'] > 0) {
                            $usagePercentage = min(100, ($tool['current_usage_hours'] / $tool['lifespan_limit']) * 100);
                        }
                        $lowStock = $tool['current_stock'] <= $tool['minimum_stock'];
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($tool['tool_code']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div><?php echo htmlspecialchars($tool['tool_name']); ?></div>
                            <?php if ($tool['supplier_name']): ?>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($tool['supplier_name']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($tool['category_name'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($tool['tool_size'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="<?php echo $lowStock ? 'text-red-600 font-medium' : 'text-gray-900'; ?>">
                                <?php echo $tool['current_stock']; ?>
                            </span>
                            <span class="text-gray-400"> / <?php echo $tool['minimum_stock']; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($tool['lifespan_limit'] > 0): ?>
                            <div class="flex items-center">
                                <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="<?php echo $usagePercentage >= 80 ? 'bg-red-600' : 'bg-green-500'; ?> h-2 rounded-full"
                                         style="width: <?php echo min(100, $usagePercentage); ?>%"></div>
                                </div>
                                <span class="text-xs"><?php echo number_format($usagePercentage, 1); ?>%</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                <?php echo number_format($tool['current_usage_hours'], 1); ?>h / <?php echo number_format($tool['lifespan_limit'], 1); ?>h
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php
                                    echo match($tool['status']) {
                                        'new' => 'bg-blue-100 text-blue-800',
                                        'in_use' => 'bg-green-100 text-green-800',
                                        'used' => 'bg-gray-100 text-gray-800',
                                        'maintenance' => 'bg-yellow-100 text-yellow-800',
                                        'retired' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $tool['status'])); ?>
                            </span>
                        </td>
                        <?php if (getCurrentUser()['role'] === 'admin'): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick='editTool(<?php echo json_encode($tool); ?>)' class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <button onclick="deleteTool(<?php echo $tool['id']; ?>, '<?php echo htmlspecialchars($tool['tool_code']); ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Tool Modal -->
<div id="toolModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mb-4 flex justify-between items-center">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add New Tool</h3>
            <button onclick="closeToolModal()" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="toolForm" onsubmit="saveTool(event)" class="space-y-4">
            <input type="hidden" id="tool_id" name="tool_id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tool Code *</label>
                    <input type="text" id="tool_code" name="tool_code" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tool Name *</label>
                    <input type="text" id="tool_name" name="tool_name" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" id="category_name" name="category_name"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tool Size</label>
                    <input type="text" id="tool_size" name="tool_size"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier Name</label>
                    <input type="text" id="supplier_name" name="supplier_name"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier Model</label>
                    <input type="text" id="supplier_model_number" name="supplier_model_number"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Stock</label>
                    <input type="number" id="current_stock" name="current_stock" value="0" min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Minimum Stock</label>
                    <input type="number" id="minimum_stock" name="minimum_stock" value="0" min="0"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Lifespan Type</label>
                    <select id="lifespan_type" name="lifespan_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="time">Time (hours)</option>
                        <option value="cycles">Cycles</option>
                        <option value="distance">Distance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Lifespan Limit</label>
                    <input type="number" id="lifespan_limit" name="lifespan_limit" value="0" min="0" step="0.01"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="new">New</option>
                        <option value="in_use">In Use</option>
                        <option value="used">Used</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeToolModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save Tool
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddToolModal() {
    document.getElementById('modalTitle').textContent = 'Add New Tool';
    document.getElementById('toolForm').reset();
    document.getElementById('tool_id').value = '';
    document.getElementById('toolModal').classList.remove('hidden');
}

function editTool(tool) {
    document.getElementById('modalTitle').textContent = 'Edit Tool';
    document.getElementById('tool_id').value = tool.id;
    document.getElementById('tool_code').value = tool.tool_code;
    document.getElementById('tool_name').value = tool.tool_name;
    document.getElementById('category_name').value = tool.category_name || '';
    document.getElementById('tool_size').value = tool.tool_size || '';
    document.getElementById('supplier_name').value = tool.supplier_name || '';
    document.getElementById('supplier_model_number').value = tool.supplier_model_number || '';
    document.getElementById('current_stock').value = tool.current_stock;
    document.getElementById('minimum_stock').value = tool.minimum_stock;
    document.getElementById('lifespan_type').value = tool.lifespan_type;
    document.getElementById('lifespan_limit').value = tool.lifespan_limit;
    document.getElementById('status').value = tool.status;
    document.getElementById('description').value = tool.description || '';
    document.getElementById('toolModal').classList.remove('hidden');
}

function closeToolModal() {
    document.getElementById('toolModal').classList.add('hidden');
}

function saveTool(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    const url = data.tool_id ? 'api/update_tool.php' : 'api/add_tool.php';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Error saving tool: ' + error);
    });
}

function deleteTool(toolId, toolCode) {
    if (!confirm('Are you sure you want to delete tool "' + toolCode + '"?')) {
        return;
    }

    fetch('api/delete_tool.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ tool_id: toolId })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Error deleting tool: ' + error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
