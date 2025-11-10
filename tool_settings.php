<?php
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get equipment list
$equipmentList = dbQuery("SELECT * FROM equipment ORDER BY equipment_code");

// Get selected equipment
$selectedEquipmentId = $_GET['equipment'] ?? ($equipmentList[0]['id'] ?? null);

// Get equipment details and its active tools
$equipmentTools = [];
if ($selectedEquipmentId) {
    $equipmentTools = dbQuery("
        SELECT
            ets.*,
            t.tool_code,
            t.tool_name,
            t.category_name,
            t.current_usage_hours,
            t.lifespan_limit,
            ROUND((t.current_usage_hours / NULLIF(t.lifespan_limit, 0)) * 100, 2) as usage_percentage
        FROM equipment_tool_settings ets
        JOIN tools t ON ets.tool_id = t.id
        WHERE ets.equipment_id = ? AND ets.is_active = 1
        ORDER BY ets.slot_number, ets.installed_at
    ", [$selectedEquipmentId]);
}

// Get available tools (not currently installed or retired)
$availableTools = dbQuery("
    SELECT * FROM tools
    WHERE status NOT IN ('retired')
    AND id NOT IN (
        SELECT tool_id FROM equipment_tool_settings
        WHERE equipment_id = ? AND is_active = 1
    )
    ORDER BY tool_code
", [$selectedEquipmentId]);
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">장비별 공구 세팅</h2>
    <p class="text-gray-600 mt-1">각 장비에 장착된 공구 관리</p>
</div>

<!-- Equipment Selector -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <label class="block text-sm font-semibold text-gray-700 mb-3">장비 선택</label>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
        <?php foreach ($equipmentList as $eq): ?>
        <a href="?equipment=<?php echo $eq['id']; ?>"
           class="px-4 py-3 rounded-lg text-center font-semibold transition <?php
               echo $eq['id'] == $selectedEquipmentId
                   ? 'bg-blue-600 text-white'
                   : 'bg-gray-100 text-gray-700 hover:bg-gray-200';
           ?>">
            <?php echo htmlspecialchars($eq['equipment_code']); ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($selectedEquipmentId): ?>
<!-- Current Tools -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            장착된 공구 (<?php echo count($equipmentTools); ?>개)
        </h3>

        <?php if (empty($equipmentTools)): ?>
        <div class="text-center py-8 text-gray-500">
            <p>장착된 공구가 없습니다.</p>
            <p class="text-sm mt-2">오른쪽에서 공구를 선택하여 장착하세요.</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($equipmentTools as $et): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($et['tool_name']); ?></p>
                        <p class="text-xs text-gray-600">
                            <?php echo htmlspecialchars($et['tool_code']); ?>
                            <?php if ($et['slot_number']): ?>
                            <span class="ml-2 text-blue-600">슬롯 #<?php echo $et['slot_number']; ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button
                        onclick="removeTool(<?php echo $et['id']; ?>)"
                        class="ml-3 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition text-sm"
                    >
                        제거
                    </button>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-600">사용시간:</span>
                        <span class="font-semibold"><?php echo round($et['current_usage_hours'], 1); ?>h</span>
                    </div>
                    <div>
                        <span class="text-gray-600">수명:</span>
                        <span class="font-semibold"><?php echo round($et['lifespan_limit'], 1); ?>h</span>
                    </div>
                </div>

                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <?php
                        $percentage = $et['usage_percentage'] ?? 0;
                        $progressColor = $percentage >= 80 ? 'bg-red-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="<?php echo $progressColor; ?> h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-600 mt-1">
                        <span>사용률: <?php echo round($percentage, 1); ?>%</span>
                        <span>사용 비율: <?php echo $et['usage_ratio']; ?>%</span>
                    </div>
                </div>

                <?php if ($et['notes']): ?>
                <div class="mt-2 text-xs text-gray-600 italic">
                    <?php echo htmlspecialchars($et['notes']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Available Tools -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            사용 가능한 공구 (<?php echo count($availableTools); ?>개)
        </h3>

        <?php if (empty($availableTools)): ?>
        <div class="text-center py-8 text-gray-500">
            <p>사용 가능한 공구가 없습니다.</p>
        </div>
        <?php else: ?>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php foreach ($availableTools as $tool): ?>
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($tool['tool_name']); ?></p>
                        <p class="text-xs text-gray-600">
                            <?php echo htmlspecialchars($tool['tool_code']); ?>
                            <span class="ml-2 px-2 py-0.5 rounded text-xs <?php
                                echo match($tool['status']) {
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'in_use' => 'bg-green-100 text-green-800',
                                    'used' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php
                                echo match($tool['status']) {
                                    'new' => '신규',
                                    'in_use' => '사용중',
                                    'used' => '사용완료',
                                    default => $tool['status']
                                };
                                ?>
                            </span>
                        </p>
                    </div>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button
                        onclick="addTool(<?php echo $tool['id']; ?>)"
                        class="ml-3 px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition text-sm"
                    >
                        장착
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
const equipmentId = <?php echo $selectedEquipmentId ?? 'null'; ?>;

async function addTool(toolId) {
    if (!confirmAction('이 공구를 장비에 장착하시겠습니까?')) {
        return;
    }

    try {
        const response = await fetch('api/add_tool_to_equipment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                equipment_id: equipmentId,
                tool_id: toolId,
                usage_ratio: 100
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('공구가 장착되었습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || '공구 장착 실패', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('공구 장착 중 오류가 발생했습니다.', 'error');
    }
}

async function removeTool(settingId) {
    if (!confirmAction('이 공구를 장비에서 제거하시겠습니까?')) {
        return;
    }

    try {
        const response = await fetch('api/remove_tool_from_equipment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ setting_id: settingId })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('공구가 제거되었습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || '공구 제거 실패', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('공구 제거 중 오류가 발생했습니다.', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
