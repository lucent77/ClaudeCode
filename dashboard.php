<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get equipment statistics
$equipmentStats = dbQuery("
    SELECT
        e.id,
        e.equipment_code,
        e.equipment_name,
        e.status,
        e.total_runtime_hours,
        COUNT(DISTINCT ets.tool_id) as active_tools_count,
        (SELECT COUNT(*) FROM production_records WHERE equipment_id = e.id AND record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)) as recent_production_count
    FROM equipment e
    LEFT JOIN equipment_tool_settings ets ON e.id = ets.equipment_id AND ets.is_active = 1
    GROUP BY e.id
    ORDER BY e.equipment_code
");

// Get tools requiring attention (>80% used or <20% remaining)
$toolsAttention = dbQuery("
    SELECT
        t.id,
        t.tool_code,
        t.tool_name,
        t.current_usage_hours,
        t.lifespan_limit,
        ROUND((t.current_usage_hours / NULLIF(t.lifespan_limit, 0)) * 100, 2) as usage_percentage,
        e.equipment_code
    FROM tools t
    LEFT JOIN equipment_tool_settings ets ON t.id = ets.tool_id AND ets.is_active = 1
    LEFT JOIN equipment e ON ets.equipment_id = e.id
    WHERE t.status = 'in_use'
        AND t.lifespan_limit > 0
        AND (t.current_usage_hours / NULLIF(t.lifespan_limit, 0)) >= 0.8
    ORDER BY usage_percentage DESC
    LIMIT 10
");

// Get recent production records
$recentProduction = dbQuery("
    SELECT
        pr.*,
        e.equipment_code,
        e.equipment_name
    FROM production_records pr
    LEFT JOIN equipment e ON pr.equipment_id = e.id
    ORDER BY pr.record_date DESC, pr.created_at DESC
    LIMIT 10
");

// Get overall statistics
$stats = dbQueryOne("
    SELECT
        (SELECT COUNT(*) FROM equipment) as total_equipment,
        (SELECT COUNT(*) FROM equipment WHERE status = 'running') as running_equipment,
        (SELECT COUNT(*) FROM tools WHERE status = 'in_use') as active_tools,
        (SELECT COUNT(*) FROM tools WHERE status = 'used') as used_tools,
        (SELECT COUNT(*) FROM production_records WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)) as weekly_production_records,
        (SELECT SUM(cnc_run_time) FROM production_records WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)) as weekly_runtime
");

// Calculate average OEE (simplified calculation)
$averageOEE = 0;
$totalEquipment = count($equipmentStats);
if ($totalEquipment > 0) {
    foreach ($equipmentStats as $eq) {
        // Simplified OEE calculation based on status
        $oee = match($eq['status']) {
            'running' => 85,
            'idle' => 0,
            'maintenance' => 0,
            'error' => 0,
            default => 0
        };
        $averageOEE += $oee;
    }
    $averageOEE = round($averageOEE / $totalEquipment, 1);
}
?>

<!-- Dashboard Header -->
<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">대시보드</h2>
    <p class="text-gray-600 mt-1">Swissturn 생산 현황 및 공구 관리 통합 모니터링</p>
</div>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Equipment -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-semibold">총 장비 수</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['total_equipment']; ?></p>
                <p class="text-sm text-green-600 mt-1"><?php echo $stats['running_equipment']; ?>대 가동 중</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Active Tools -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-semibold">사용 중 공구</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['active_tools']; ?></p>
                <p class="text-sm text-gray-600 mt-1"><?php echo $stats['used_tools']; ?>개 사용 완료</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Average OEE -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-semibold">평균 OEE</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $averageOEE; ?>%</p>
                <p class="text-sm text-gray-600 mt-1">설비 종합 효율</p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Weekly Runtime -->
    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-semibold">주간 가동시간</p>
                <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo round($stats['weekly_runtime'] ?? 0, 1); ?>h</p>
                <p class="text-sm text-gray-600 mt-1"><?php echo $stats['weekly_production_records']; ?>건 생산 기록</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Status and Tools Attention -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Equipment Status -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            장비 현황
        </h3>
        <div class="space-y-3">
            <?php foreach ($equipmentStats as $eq): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full <?php
                            echo match($eq['status']) {
                                'running' => 'bg-green-100 text-green-800',
                                'idle' => 'bg-gray-100 text-gray-800',
                                'maintenance' => 'bg-yellow-100 text-yellow-800',
                                'error' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?> font-bold text-sm">
                            <?php echo htmlspecialchars($eq['equipment_code']); ?>
                        </span>
                        <div class="ml-3">
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($eq['equipment_name']); ?></p>
                            <p class="text-xs text-gray-600"><?php echo $eq['active_tools_count']; ?>개 공구 장착</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php
                        echo match($eq['status']) {
                            'running' => 'bg-green-100 text-green-800',
                            'idle' => 'bg-gray-100 text-gray-800',
                            'maintenance' => 'bg-yellow-100 text-yellow-800',
                            'error' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?>">
                        <?php
                        echo match($eq['status']) {
                            'running' => '가동중',
                            'idle' => '대기',
                            'maintenance' => '정비',
                            'error' => '오류',
                            default => '알 수 없음'
                        };
                        ?>
                    </span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    총 가동시간: <?php echo round($eq['total_runtime_hours'], 1); ?>시간
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tools Requiring Attention -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            교체 필요 공구 (80% 이상 사용)
        </h3>
        <?php if (empty($toolsAttention)): ?>
        <div class="text-center py-8 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="font-semibold">모든 공구가 양호한 상태입니다</p>
            <p class="text-sm">교체가 필요한 공구가 없습니다</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($toolsAttention as $tool): ?>
            <div class="border border-red-200 bg-red-50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($tool['tool_name']); ?></p>
                        <p class="text-xs text-gray-600">
                            <?php echo htmlspecialchars($tool['tool_code']); ?>
                            <?php if ($tool['equipment_code']): ?>
                            <span class="ml-2 text-blue-600">→ <?php echo htmlspecialchars($tool['equipment_code']); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 bg-red-600 text-white rounded-full text-xs font-semibold">
                        <?php echo $tool['usage_percentage']; ?>%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-red-600 h-2 rounded-full progress-bar" style="width: <?php echo min($tool['usage_percentage'], 100); ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-600 mt-1">
                    <span><?php echo round($tool['current_usage_hours'], 1); ?>h 사용</span>
                    <span><?php echo round($tool['lifespan_limit'], 1); ?>h 수명</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Production Records -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        최근 생산 기록
    </h3>
    <?php if (empty($recentProduction)): ?>
    <div class="text-center py-8 text-gray-500">
        <p>생산 기록이 없습니다.</p>
        <a href="production.php" class="text-blue-600 hover:underline mt-2 inline-block">생산 기록 입력하기</a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">일자</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">장비</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">모델명</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">LOT NO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">작업자</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">가동시간</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">달성률</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($recentProduction as $prod): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-sm text-gray-800"><?php echo htmlspecialchars($prod['record_date']); ?></td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                            <?php echo htmlspecialchars($prod['equipment_code']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800"><?php echo htmlspecialchars($prod['model_name']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($prod['lot_no']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-800"><?php echo htmlspecialchars($prod['worker_name']); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-800"><?php echo round($prod['cnc_run_time'] ?? 0, 1); ?>h</td>
                    <td class="px-4 py-3 text-sm">
                        <?php
                        $achievement = $prod['total_achievement'] ?? 0;
                        $achievementClass = $achievement >= 90 ? 'text-green-600' : ($achievement >= 70 ? 'text-yellow-600' : 'text-red-600');
                        ?>
                        <span class="font-semibold <?php echo $achievementClass; ?>">
                            <?php echo round($achievement, 1); ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4 text-center">
        <a href="production.php" class="text-blue-600 hover:text-blue-800 font-semibold">
            모든 생산 기록 보기 →
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Auto Refresh -->
<script>
    // Auto refresh dashboard every 30 seconds
    startAutoRefresh(() => {
        location.reload();
    }, 30000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
