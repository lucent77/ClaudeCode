<?php
/**
 * Dashboard
 * Main dashboard with system metrics and monitoring
 */

require_once 'config/database.php';
checkAuth();

$page_title = 'Dashboard - Production Management System';
$pdo = getDbConnection();

// Get total equipment count and status
$equipmentStats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running,
        SUM(CASE WHEN status = 'idle' THEN 1 ELSE 0 END) as idle,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
        SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count
    FROM equipment
")->fetch();

// Get active tools count
$toolStats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
        SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used,
        SUM(CASE WHEN current_stock <= minimum_stock THEN 1 ELSE 0 END) as low_stock
    FROM tools
")->fetch();

// Get weekly runtime and production count
$weeklyStats = $pdo->query("
    SELECT
        COUNT(*) as record_count,
        SUM(cnc_run_time) as total_runtime,
        AVG(total_achievement) as avg_achievement
    FROM production_records
    WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetch();

// Get equipment details with tool count and recent production
$equipment = $pdo->query("
    SELECT
        e.*,
        COUNT(DISTINCT ets.tool_id) as tool_count,
        COUNT(DISTINCT pr.id) as recent_production_count
    FROM equipment e
    LEFT JOIN equipment_tool_settings ets ON e.id = ets.equipment_id AND ets.is_active = 1
    LEFT JOIN production_records pr ON e.id = pr.equipment_id
        AND pr.record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY e.id
    ORDER BY e.equipment_code
")->fetchAll();

// Get tools needing replacement (80%+ usage)
$toolsNeedingReplacement = $pdo->query("
    SELECT
        t.*,
        ROUND((t.current_usage_hours / NULLIF(t.lifespan_limit, 0)) * 100, 2) as usage_percentage,
        GROUP_CONCAT(DISTINCT e.equipment_code ORDER BY e.equipment_code SEPARATOR ', ') as equipment_list
    FROM tools t
    LEFT JOIN equipment_tool_settings ets ON t.id = ets.tool_id AND ets.is_active = 1
    LEFT JOIN equipment e ON ets.equipment_id = e.id
    WHERE t.lifespan_limit > 0
        AND (t.current_usage_hours / t.lifespan_limit) >= 0.80
        AND t.status IN ('in_use', 'used')
    GROUP BY t.id
    ORDER BY usage_percentage DESC
    LIMIT 10
")->fetchAll();

// Get recent production records
$recentProduction = $pdo->query("
    SELECT
        pr.*,
        e.equipment_code,
        e.equipment_name
    FROM production_records pr
    JOIN equipment e ON pr.equipment_id = e.id
    ORDER BY pr.record_date DESC, pr.created_at DESC
    LIMIT 10
")->fetchAll();

// Calculate average OEE (simplified calculation)
$avgOEE = 0;
if ($weeklyStats['avg_achievement']) {
    $avgOEE = min(100, $weeklyStats['avg_achievement']);
}

include 'includes/header.php';
?>

<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard</h1>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Equipment Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Equipment</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $equipmentStats['total']; ?></div>
                                <div class="ml-2 text-sm text-green-600"><?php echo $equipmentStats['running']; ?> running</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Tools Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Tools</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo $toolStats['in_use']; ?></div>
                                <div class="ml-2 text-sm text-gray-600"><?php echo $toolStats['used']; ?> used</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average OEE Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Average OEE</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($avgOEE, 1); ?>%</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Runtime Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Weekly Runtime</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"><?php echo number_format($weeklyStats['total_runtime'] ?? 0, 1); ?>h</div>
                                <div class="ml-2 text-sm text-gray-600"><?php echo $weeklyStats['record_count']; ?> records</div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Status -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Equipment Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($equipment as $eq):
                    $statusColors = [
                        'running' => 'bg-green-100 text-green-800 border-green-300',
                        'idle' => 'bg-gray-100 text-gray-800 border-gray-300',
                        'maintenance' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'error' => 'bg-red-100 text-red-800 border-red-300'
                    ];
                    $colorClass = $statusColors[$eq['status']] ?? $statusColors['idle'];
                ?>
                <div class="border <?php echo $colorClass; ?> rounded-lg p-4">
                    <div class="font-semibold text-lg"><?php echo htmlspecialchars($eq['equipment_code']); ?></div>
                    <div class="text-sm opacity-75"><?php echo htmlspecialchars($eq['equipment_name']); ?></div>
                    <div class="mt-2 text-xs">
                        <div>Status: <span class="font-medium"><?php echo ucfirst($eq['status']); ?></span></div>
                        <div>Runtime: <?php echo number_format($eq['total_runtime_hours'], 1); ?>h</div>
                        <div>Tools: <?php echo $eq['tool_count']; ?></div>
                        <div>7-Day Records: <?php echo $eq['recent_production_count']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tool Replacement Alerts -->
    <?php if (count($toolsNeedingReplacement) > 0): ?>
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Tools Needing Replacement (80%+ Usage)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tool Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tool Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($toolsNeedingReplacement as $tool):
                            $percentage = min(100, $tool['usage_percentage']);
                            $progressColor = $percentage >= 95 ? 'bg-red-600' : ($percentage >= 90 ? 'bg-orange-500' : 'bg-yellow-500');
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($tool['tool_code']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($tool['tool_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="<?php echo $progressColor; ?> h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <span class="font-medium"><?php echo number_format($percentage, 1); ?>%</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    <?php echo number_format($tool['current_usage_hours'], 1); ?>h / <?php echo number_format($tool['lifespan_limit'], 1); ?>h
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($tool['equipment_list'] ?: 'Not installed'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Production Records -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Production Records</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LOT NO</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worker</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Run Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Achievement</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($recentProduction) === 0): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                No production records found. Start by entering data in <a href="production_input.php" class="text-blue-600 hover:underline">Daily Input</a>.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentProduction as $record):
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['model_name'] ?: '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['lot_no'] ?: '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['worker_name'] ?: '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $record['cnc_run_time'] ? number_format($record['cnc_run_time'], 1) . 'h' : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $achievementColor; ?>">
                                <?php echo $record['total_achievement'] ? number_format($record['total_achievement'], 1) . '%' : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($recentProduction) > 0): ?>
            <div class="mt-4 text-center">
                <a href="production.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All Production Records →
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Optional: Auto-refresh every 30 seconds
    // setTimeout(function() {
    //     location.reload();
    // }, 30000);
</script>

<?php include 'includes/footer.php'; ?>
