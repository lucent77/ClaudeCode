<?php
/**
 * Production Records
 * View and filter production records
 */

require_once 'config/database.php';
checkAuth();

$page_title = 'Production Records - Production Management System';
$pdo = getDbConnection();

// Get filters
$equipmentFilter = $_GET['equipment_id'] ?? 'all';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get all equipment for filter
$equipment = $pdo->query("SELECT * FROM equipment ORDER BY equipment_code")->fetchAll();

// Build query
$query = "
    SELECT
        pr.*,
        e.equipment_code,
        e.equipment_name
    FROM production_records pr
    JOIN equipment e ON pr.equipment_id = e.id
    WHERE pr.record_date BETWEEN ? AND ?
";

$params = [$startDate, $endDate];

if ($equipmentFilter !== 'all') {
    $query .= " AND pr.equipment_id = ?";
    $params[] = $equipmentFilter;
}

$query .= " ORDER BY pr.record_date DESC, pr.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get statistics
$statsQuery = "
    SELECT
        COUNT(*) as total_records,
        SUM(cnc_run_time) as total_runtime,
        AVG(total_achievement) as avg_achievement,
        SUM(unit_total) as total_production
    FROM production_records pr
    WHERE pr.record_date BETWEEN ? AND ?
";

$statsParams = [$startDate, $endDate];

if ($equipmentFilter !== 'all') {
    $statsQuery .= " AND pr.equipment_id = ?";
    $statsParams[] = $equipmentFilter;
}

$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute($statsParams);
$stats = $statsStmt->fetch();

include 'includes/header.php';
?>

<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Production Records</h1>
        <a href="production_input.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
            Add New Record
        </a>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
        <form method="GET" action="production.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equipment</label>
                <select name="equipment_id" class="block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" <?php echo $equipmentFilter === 'all' ? 'selected' : ''; ?>>All Equipment</option>
                    <?php foreach ($equipment as $eq): ?>
                    <option value="<?php echo $eq['id']; ?>" <?php echo $equipmentFilter == $eq['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($eq['equipment_code']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?php echo $startDate; ?>"
                       class="block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>"
                       class="block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500 truncate">Total Records</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $stats['total_records']; ?></div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500 truncate">Total Runtime</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($stats['total_runtime'] ?? 0, 1); ?>h</div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500 truncate">Avg Achievement</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($stats['avg_achievement'] ?? 0, 1); ?>%</div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="text-sm font-medium text-gray-500 truncate">Total Production</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900"><?php echo number_format($stats['total_production'] ?? 0); ?></div>
            </div>
        </div>
    </div>

    <!-- Records Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LOT NO</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worker</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan/Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Run Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($records) === 0): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                            No production records found for the selected criteria.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($records as $record):
                        $achievementColor = 'text-gray-900';
                        if ($record['total_achievement'] >= 90) {
                            $achievementColor = 'text-green-600';
                        } elseif ($record['total_achievement'] >= 70) {
                            $achievementColor = 'text-yellow-600';
                        } elseif ($record['total_achievement'] > 0) {
                            $achievementColor = 'text-red-600';
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('n/j/Y', strtotime($record['record_date'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($record['equipment_code']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo htmlspecialchars($record['model_name'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($record['lot_no'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($record['worker_name'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo number_format($record['plan_count'] ?: 0); ?> / <?php echo number_format($record['unit_total'] ?: 0); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $record['cnc_run_time'] ? number_format($record['cnc_run_time'], 1) . 'h' : '-'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $achievementColor; ?>">
                            <?php echo $record['total_achievement'] ? number_format($record['total_achievement'], 1) . '%' : '-'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick='viewRecord(<?php echo json_encode($record); ?>)'
                                    class="text-blue-600 hover:text-blue-900">
                                View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (count($records) >= 100): ?>
    <div class="mt-4 text-center text-sm text-gray-600">
        Showing latest 100 records. Use filters to narrow down results.
    </div>
    <?php endif; ?>
</div>

<!-- View Record Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mb-4 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Production Record Details</h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="recordDetails" class="grid grid-cols-2 gap-4 text-sm">
            <!-- Details will be populated by JavaScript -->
        </div>

        <div class="mt-6 flex justify-end">
            <button onclick="closeViewModal()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function viewRecord(record) {
    const detailsDiv = document.getElementById('recordDetails');

    const fields = [
        { label: 'Date', value: new Date(record.record_date).toLocaleDateString('en-US') },
        { label: 'Equipment', value: record.equipment_code },
        { label: 'Model Name', value: record.model_name },
        { label: 'SKU', value: record.sku },
        { label: 'LOT NO', value: record.lot_no },
        { label: 'Worker', value: record.worker_name },
        { label: 'Plan Count', value: record.plan_count },
        { label: 'Unit Total', value: record.unit_total },
        { label: 'CNC Run Time', value: record.cnc_run_time ? record.cnc_run_time + 'h' : '-' },
        { label: 'Working Time', value: record.working_time ? record.working_time + 'h' : '-' },
        { label: 'Day Achievement', value: record.day_achievement ? record.day_achievement + '%' : '-' },
        { label: 'Total Achievement', value: record.total_achievement ? record.total_achievement + '%' : '-' },
        { label: 'Part Length', value: record.part_length },
        { label: 'Diameter', value: record.diameter },
        { label: 'Length', value: record.length },
        { label: 'Note', value: record.note }
    ];

    detailsDiv.innerHTML = fields.map(field => `
        <div>
            <dt class="font-medium text-gray-500">${field.label}</dt>
            <dd class="mt-1 text-gray-900">${field.value || '-'}</dd>
        </div>
    `).join('');

    document.getElementById('viewModal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
}
</script>

<?php include 'includes/footer.php'; ?>
