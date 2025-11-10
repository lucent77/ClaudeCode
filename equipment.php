<?php
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get all equipment with tool counts
$equipment = dbQuery("
    SELECT
        e.*,
        COUNT(DISTINCT ets.tool_id) as active_tools_count,
        COUNT(DISTINCT pr.id) as total_production_records
    FROM equipment e
    LEFT JOIN equipment_tool_settings ets ON e.id = ets.equipment_id AND ets.is_active = 1
    LEFT JOIN production_records pr ON e.id = pr.equipment_id
    GROUP BY e.id
    ORDER BY e.equipment_code
");
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">장비 관리</h2>
    <p class="text-gray-600 mt-1">CNC 장비 현황 및 상태 관리</p>
</div>

<!-- Equipment Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach ($equipment as $eq): ?>
    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-all duration-300" data-equipment-id="<?php echo $eq['id']; ?>">
        <!-- Equipment Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-white <?php
                    echo match($eq['status']) {
                        'running' => 'bg-green-500',
                        'idle' => 'bg-gray-400',
                        'maintenance' => 'bg-yellow-500',
                        'error' => 'bg-red-500',
                        default => 'bg-gray-400'
                    };
                ?>">
                    <?php echo htmlspecialchars($eq['equipment_code']); ?>
                </div>
                <div class="ml-3">
                    <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($eq['equipment_name']); ?></h3>
                    <p class="text-xs text-gray-600"><?php echo htmlspecialchars($eq['equipment_code']); ?></p>
                </div>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="mb-4">
            <select
                class="status-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                data-equipment-id="<?php echo $eq['id']; ?>"
                <?php echo $_SESSION['role'] !== 'admin' ? 'disabled' : ''; ?>
            >
                <option value="running" <?php echo $eq['status'] === 'running' ? 'selected' : ''; ?>>가동 중</option>
                <option value="idle" <?php echo $eq['status'] === 'idle' ? 'selected' : ''; ?>>대기</option>
                <option value="maintenance" <?php echo $eq['status'] === 'maintenance' ? 'selected' : ''; ?>>정비 중</option>
                <option value="error" <?php echo $eq['status'] === 'error' ? 'selected' : ''; ?>>오류</option>
            </select>
        </div>

        <!-- Statistics -->
        <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    총 가동시간
                </span>
                <span class="font-semibold text-gray-800"><?php echo round($eq['total_runtime_hours'], 1); ?>h</span>
            </div>

            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                    </svg>
                    장착된 공구
                </span>
                <span class="font-semibold text-gray-800"><?php echo $eq['active_tools_count']; ?>개</span>
            </div>

            <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    생산 기록
                </span>
                <span class="font-semibold text-gray-800"><?php echo $eq['total_production_records']; ?>건</span>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-4 pt-4 border-t border-gray-200 flex space-x-2">
            <a href="tool_settings.php?equipment=<?php echo $eq['id']; ?>" class="flex-1 text-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                공구 세팅
            </a>
            <a href="production.php?equipment=<?php echo $eq['id']; ?>" class="flex-1 text-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                생산 기록
            </a>
        </div>

        <?php if ($eq['description']): ?>
        <div class="mt-3 text-xs text-gray-600 italic">
            <?php echo htmlspecialchars($eq['description']); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Equipment Status Update Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.status-select');

    statusSelects.forEach(select => {
        select.addEventListener('change', async function() {
            const equipmentId = this.dataset.equipmentId;
            const newStatus = this.value;

            try {
                const response = await fetch('api/update_equipment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        equipment_id: equipmentId,
                        status: newStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('장비 상태가 업데이트되었습니다.', 'success');

                    // Update the equipment card styling
                    const card = this.closest('[data-equipment-id]');
                    const badge = card.querySelector('.w-12.h-12.rounded-full');

                    // Remove all status classes
                    badge.classList.remove('bg-green-500', 'bg-gray-400', 'bg-yellow-500', 'bg-red-500');

                    // Add new status class
                    switch(newStatus) {
                        case 'running':
                            badge.classList.add('bg-green-500');
                            break;
                        case 'idle':
                            badge.classList.add('bg-gray-400');
                            break;
                        case 'maintenance':
                            badge.classList.add('bg-yellow-500');
                            break;
                        case 'error':
                            badge.classList.add('bg-red-500');
                            break;
                    }
                } else {
                    showNotification(data.message || '상태 업데이트 실패', 'error');
                    // Revert select to previous value
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('상태 업데이트 중 오류가 발생했습니다.', 'error');
                location.reload();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
