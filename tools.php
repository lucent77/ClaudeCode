<?php
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT
        t.*,
        e.equipment_code,
        e.equipment_name,
        ROUND((t.current_usage_hours / NULLIF(t.lifespan_limit, 0)) * 100, 2) as usage_percentage
    FROM tools t
    LEFT JOIN equipment_tool_settings ets ON t.id = ets.tool_id AND ets.is_active = 1
    LEFT JOIN equipment e ON ets.equipment_id = e.id
    WHERE 1=1
";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery) {
    $query .= " AND (t.tool_code LIKE ? OR t.tool_name LIKE ? OR t.category_name LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY t.tool_code";

$tools = dbQuery($query, $params);

// Get statistics
$toolStats = dbQueryOne("
    SELECT
        COUNT(*) as total_tools,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_tools,
        SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_tools,
        SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used_tools,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_tools,
        SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired_tools
    FROM tools
");
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">공구 관리</h2>
    <p class="text-gray-600 mt-1">공구 목록 및 수명 관리</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-gray-500 text-sm">전체</p>
        <p class="text-2xl font-bold text-gray-800"><?php echo $toolStats['total_tools']; ?></p>
    </div>
    <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
        <p class="text-blue-600 text-sm">신규</p>
        <p class="text-2xl font-bold text-blue-800"><?php echo $toolStats['new_tools']; ?></p>
    </div>
    <div class="bg-green-50 rounded-lg shadow p-4 text-center">
        <p class="text-green-600 text-sm">사용중</p>
        <p class="text-2xl font-bold text-green-800"><?php echo $toolStats['in_use_tools']; ?></p>
    </div>
    <div class="bg-gray-50 rounded-lg shadow p-4 text-center">
        <p class="text-gray-600 text-sm">사용완료</p>
        <p class="text-2xl font-bold text-gray-800"><?php echo $toolStats['used_tools']; ?></p>
    </div>
    <div class="bg-yellow-50 rounded-lg shadow p-4 text-center">
        <p class="text-yellow-600 text-sm">정비중</p>
        <p class="text-2xl font-bold text-yellow-800"><?php echo $toolStats['maintenance_tools']; ?></p>
    </div>
    <div class="bg-red-50 rounded-lg shadow p-4 text-center">
        <p class="text-red-600 text-sm">폐기</p>
        <p class="text-2xl font-bold text-red-800"><?php echo $toolStats['retired_tools']; ?></p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">상태 필터</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>전체</option>
                <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>신규</option>
                <option value="in_use" <?php echo $statusFilter === 'in_use' ? 'selected' : ''; ?>>사용 중</option>
                <option value="used" <?php echo $statusFilter === 'used' ? 'selected' : ''; ?>>사용 완료</option>
                <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>정비 중</option>
                <option value="retired" <?php echo $statusFilter === 'retired' ? 'selected' : ''; ?>>폐기</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">검색</label>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                placeholder="공구 코드, 이름, 카테고리..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                검색
            </button>
            <?php if ($statusFilter !== 'all' || $searchQuery): ?>
            <a href="tools.php" class="ml-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                초기화
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tools Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">공구 코드</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">공구 이름</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">카테고리</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">크기</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">사용/수명</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">수명률</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">장착 장비</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">상태</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">재고</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($tools)): ?>
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        공구가 없습니다. CSV 파일을 업로드하여 공구 데이터를 추가하세요.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($tools as $tool): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                        <?php echo htmlspecialchars($tool['tool_code']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-800">
                        <?php echo htmlspecialchars($tool['tool_name']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($tool['category_name']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($tool['tool_size']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo round($tool['current_usage_hours'], 1); ?>h / <?php echo round($tool['lifespan_limit'], 1); ?>h
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <?php
                        $percentage = $tool['usage_percentage'] ?? 0;
                        $progressColor = $percentage >= 80 ? 'bg-red-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="<?php echo $progressColor; ?> h-2 rounded-full progress-bar" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                        <span class="text-xs text-gray-600"><?php echo round($percentage, 1); ?>%</span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <?php if ($tool['equipment_code']): ?>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                            <?php echo htmlspecialchars($tool['equipment_code']); ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-400 text-xs">미장착</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded text-xs font-semibold <?php
                            echo match($tool['status']) {
                                'new' => 'bg-blue-100 text-blue-800',
                                'in_use' => 'bg-green-100 text-green-800',
                                'used' => 'bg-gray-100 text-gray-800',
                                'maintenance' => 'bg-yellow-100 text-yellow-800',
                                'retired' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>">
                            <?php
                            echo match($tool['status']) {
                                'new' => '신규',
                                'in_use' => '사용중',
                                'used' => '사용완료',
                                'maintenance' => '정비중',
                                'retired' => '폐기',
                                default => $tool['status']
                            };
                            ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo $tool['current_stock']; ?> / <?php echo $tool['minimum_stock']; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
