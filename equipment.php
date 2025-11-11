<?php
/**
 * Equipment Management
 * View and manage equipment/machines
 */

require_once 'config/database.php';
checkAuth();

$page_title = 'Equipment Management - Production Management System';
$pdo = getDbConnection();

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$query = "
    SELECT
        e.*,
        COUNT(DISTINCT ets.tool_id) as tool_count,
        COUNT(DISTINCT pr.id) as production_count
    FROM equipment e
    LEFT JOIN equipment_tool_settings ets ON e.id = ets.equipment_id AND ets.is_active = 1
    LEFT JOIN production_records pr ON e.id = pr.equipment_id
";

$params = [];
if ($statusFilter !== 'all') {
    $query .= " WHERE e.status = ?";
    $params[] = $statusFilter;
}

$query .= " GROUP BY e.id ORDER BY e.equipment_code";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$equipment = $stmt->fetchAll();

// Get status counts
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM equipment
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Equipment Management</h1>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="?status=all"
                   class="<?php echo $statusFilter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    All (<?php echo count($equipment); ?>)
                </a>
                <a href="?status=running"
                   class="<?php echo $statusFilter === 'running' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Running (<?php echo $statusCounts['running'] ?? 0; ?>)
                </a>
                <a href="?status=idle"
                   class="<?php echo $statusFilter === 'idle' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Idle (<?php echo $statusCounts['idle'] ?? 0; ?>)
                </a>
                <a href="?status=maintenance"
                   class="<?php echo $statusFilter === 'maintenance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Maintenance (<?php echo $statusCounts['maintenance'] ?? 0; ?>)
                </a>
                <a href="?status=error"
                   class="<?php echo $statusFilter === 'error' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Error (<?php echo $statusCounts['error'] ?? 0; ?>)
                </a>
            </nav>
        </div>
    </div>

    <!-- Equipment Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($equipment as $eq):
            $statusColors = [
                'running' => 'border-green-500 bg-green-50',
                'idle' => 'border-gray-400 bg-gray-50',
                'maintenance' => 'border-yellow-500 bg-yellow-50',
                'error' => 'border-red-500 bg-red-50'
            ];
            $statusTextColors = [
                'running' => 'text-green-700',
                'idle' => 'text-gray-700',
                'maintenance' => 'text-yellow-700',
                'error' => 'text-red-700'
            ];
            $borderClass = $statusColors[$eq['status']] ?? $statusColors['idle'];
            $textClass = $statusTextColors[$eq['status']] ?? $statusTextColors['idle'];
        ?>
        <div class="bg-white border-l-4 <?php echo $borderClass; ?> shadow rounded-lg p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($eq['equipment_code']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($eq['equipment_name']); ?></p>
                </div>
                <div class="flex-shrink-0">
                    <?php if (getCurrentUser()['role'] === 'admin'): ?>
                    <select
                        class="text-sm border-gray-300 rounded-md <?php echo $textClass; ?> font-medium"
                        onchange="updateEquipmentStatus(<?php echo $eq['id']; ?>, this.value)"
                    >
                        <option value="running" <?php echo $eq['status'] === 'running' ? 'selected' : ''; ?>>Running</option>
                        <option value="idle" <?php echo $eq['status'] === 'idle' ? 'selected' : ''; ?>>Idle</option>
                        <option value="maintenance" <?php echo $eq['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="error" <?php echo $eq['status'] === 'error' ? 'selected' : ''; ?>>Error</option>
                    </select>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium <?php echo $textClass; ?>">
                        <?php echo ucfirst($eq['status']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="space-y-2">
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Total Runtime:</dt>
                    <dd class="font-medium text-gray-900"><?php echo number_format($eq['total_runtime_hours'], 1); ?>h</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Installed Tools:</dt>
                    <dd class="font-medium text-gray-900"><?php echo $eq['tool_count']; ?></dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Production Records:</dt>
                    <dd class="font-medium text-gray-900"><?php echo $eq['production_count']; ?></dd>
                </div>
            </dl>

            <?php if ($eq['description']): ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-600"><?php echo htmlspecialchars($eq['description']); ?></p>
            </div>
            <?php endif; ?>

            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex space-x-2">
                    <a href="tool_settings.php?equipment_id=<?php echo $eq['id']; ?>"
                       class="flex-1 text-center px-3 py-2 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">
                        Manage Tools
                    </a>
                    <a href="production.php?equipment_id=<?php echo $eq['id']; ?>"
                       class="flex-1 text-center px-3 py-2 bg-gray-200 text-gray-700 text-xs font-medium rounded hover:bg-gray-300">
                        View Records
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($equipment) === 0): ?>
    <div class="bg-white shadow rounded-lg p-8 text-center">
        <p class="text-gray-500">No equipment found matching the selected filter.</p>
    </div>
    <?php endif; ?>
</div>

<script>
function updateEquipmentStatus(equipmentId, newStatus) {
    if (!confirm('Are you sure you want to change the equipment status to "' + newStatus + '"?')) {
        location.reload();
        return;
    }

    fetch('api/update_equipment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            equipment_id: equipmentId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
            location.reload();
        }
    })
    .catch(error => {
        alert('Error updating status: ' + error);
        location.reload();
    });
}
</script>

<?php include 'includes/footer.php'; ?>
