<?php
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$equipmentFilter = $_GET['equipment'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Build query
$query = "
    SELECT
        pr.*,
        e.equipment_code,
        e.equipment_name
    FROM production_records pr
    LEFT JOIN equipment e ON pr.equipment_id = e.id
    WHERE pr.record_date BETWEEN ? AND ?
";

$params = [$dateFrom, $dateTo];

if ($equipmentFilter !== 'all') {
    $query .= " AND pr.equipment_id = ?";
    $params[] = $equipmentFilter;
}

$query .= " ORDER BY pr.record_date DESC, pr.created_at DESC LIMIT 100";

$records = dbQuery($query, $params);

// Get equipment list for filter
$equipmentList = dbQuery("SELECT * FROM equipment ORDER BY equipment_code");

// Calculate statistics
$stats = dbQueryOne("
    SELECT
        COUNT(*) as total_records,
        SUM(cnc_run_time) as total_runtime,
        AVG(total_achievement) as avg_achievement,
        SUM(unit_total) as total_production
    FROM production_records
    WHERE record_date BETWEEN ? AND ?
" . ($equipmentFilter !== 'all' ? " AND equipment_id = ?" : ""),
$equipmentFilter !== 'all' ? [$dateFrom, $dateTo, $equipmentFilter] : [$dateFrom, $dateTo]
);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">생산 기록</h2>
    <p class="text-gray-600 mt-1">일일 생산 실적 및 장비 가동 기록</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <p class="text-gray-500 text-sm font-semibold">총 기록 수</p>
        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['total_records'] ?? 0; ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <p class="text-gray-500 text-sm font-semibold">총 가동시간</p>
        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo round($stats['total_runtime'] ?? 0, 1); ?>h</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <p class="text-gray-500 text-sm font-semibold">평균 달성률</p>
        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo round($stats['avg_achievement'] ?? 0, 1); ?>%</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6">
        <p class="text-gray-500 text-sm font-semibold">총 생산량</p>
        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_production'] ?? 0); ?></p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">장비</label>
            <select name="equipment" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all" <?php echo $equipmentFilter === 'all' ? 'selected' : ''; ?>>전체</option>
                <?php foreach ($equipmentList as $eq): ?>
                <option value="<?php echo $eq['id']; ?>" <?php echo $equipmentFilter == $eq['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($eq['equipment_code']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">시작일</label>
            <input
                type="date"
                name="date_from"
                value="<?php echo $dateFrom; ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">종료일</label>
            <input
                type="date"
                name="date_to"
                value="<?php echo $dateTo; ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                검색
            </button>
        </div>
    </form>
</div>

<!-- Production Records Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">일자</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">장비</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">모델명</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">LOT NO</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">작업자</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">계획/실적</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">가동시간</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">달성률</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        생산 기록이 없습니다. CSV 파일을 업로드하거나 새 기록을 추가하세요.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($records as $record): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-sm text-gray-800">
                        <?php echo date('Y-m-d', strtotime($record['record_date'])); ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                            <?php echo htmlspecialchars($record['equipment_code']); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">
                        <?php echo htmlspecialchars($record['model_name']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($record['lot_no']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">
                        <?php echo htmlspecialchars($record['worker_name']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">
                        <?php echo number_format($record['unit_total'] ?? 0); ?> /
                        <?php echo number_format($record['plan_count'] ?? 0); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">
                        <?php echo round($record['cnc_run_time'] ?? 0, 1); ?>h
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <?php
                        $achievement = $record['total_achievement'] ?? 0;
                        $achievementClass = $achievement >= 90 ? 'bg-green-100 text-green-800' :
                                          ($achievement >= 70 ? 'bg-yellow-100 text-yellow-800' :
                                           'bg-red-100 text-red-800');
                        ?>
                        <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $achievementClass; ?>">
                            <?php echo round($achievement, 1); ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
