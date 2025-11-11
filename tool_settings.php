<?php
/**
 * Equipment Tool Settings
 * Manage equipment-to-tool relationships
 */

require_once 'config/database.php';
checkAuth();

$page_title = 'Equipment Tool Settings - Production Management System';
$pdo = getDbConnection();

// Get equipment filter
$equipmentFilter = $_GET['equipment_id'] ?? 'all';

// Get all equipment
$equipment = $pdo->query("SELECT * FROM equipment ORDER BY equipment_code")->fetchAll();

// Get equipment-tool settings
$query = "
    SELECT
        ets.*,
        e.equipment_code,
        e.equipment_name,
        t.tool_code,
        t.tool_name,
        t.category_name,
        t.tool_size
    FROM equipment_tool_settings ets
    JOIN equipment e ON ets.equipment_id = e.id
    JOIN tools t ON ets.tool_id = t.id
    WHERE ets.is_active = 1
";

$params = [];
if ($equipmentFilter !== 'all') {
    $query .= " AND ets.equipment_id = ?";
    $params[] = $equipmentFilter;
}

$query .= " ORDER BY e.equipment_code, ets.slot_number, t.tool_code";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$settings = $stmt->fetchAll();

// Get available tools for dropdown
$availableTools = $pdo->query("
    SELECT * FROM tools
    WHERE status IN ('new', 'in_use')
    ORDER BY tool_code
")->fetchAll();

include 'includes/header.php';
?>

<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Equipment Tool Settings</h1>
        <?php if (getCurrentUser()['role'] === 'admin'): ?>
        <button onclick="showAddSettingModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
            Install Tool
        </button>
        <?php endif; ?>
    </div>

    <!-- Equipment Filter -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Equipment:</label>
        <select onchange="location.href='?equipment_id=' + this.value"
                class="block w-full max-w-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="all" <?php echo $equipmentFilter === 'all' ? 'selected' : ''; ?>>All Equipment</option>
            <?php foreach ($equipment as $eq): ?>
            <option value="<?php echo $eq['id']; ?>" <?php echo $equipmentFilter == $eq['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($eq['equipment_code'] . ' - ' . $eq['equipment_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Settings Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slot</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tool Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tool Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage Ratio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Installed</th>
                        <?php if (getCurrentUser()['role'] === 'admin'): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($settings) === 0): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                            No tools installed. <?php if (getCurrentUser()['role'] === 'admin'): ?>Click "Install Tool" to get started.<?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($settings as $setting): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($setting['equipment_code']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($setting['slot_number'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($setting['tool_code']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo htmlspecialchars($setting['tool_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($setting['category_name'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo number_format($setting['usage_ratio'], 2); ?>%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('n/j/Y', strtotime($setting['installed_at'])); ?>
                        </td>
                        <?php if (getCurrentUser()['role'] === 'admin'): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick='editSetting(<?php echo json_encode($setting); ?>)'
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                Edit
                            </button>
                            <button onclick="removeTool(<?php echo $setting['id']; ?>, '<?php echo htmlspecialchars($setting['equipment_code']); ?>', '<?php echo htmlspecialchars($setting['tool_code']); ?>')"
                                    class="text-red-600 hover:text-red-900">
                                Remove
                            </button>
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

<!-- Add/Edit Setting Modal -->
<div id="settingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mb-4 flex justify-between items-center">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Install Tool</h3>
            <button onclick="closeSettingModal()" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="settingForm" onsubmit="saveSetting(event)" class="space-y-4">
            <input type="hidden" id="setting_id" name="setting_id">

            <div>
                <label class="block text-sm font-medium text-gray-700">Equipment *</label>
                <select id="equipment_id" name="equipment_id" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Equipment</option>
                    <?php foreach ($equipment as $eq): ?>
                    <option value="<?php echo $eq['id']; ?>">
                        <?php echo htmlspecialchars($eq['equipment_code'] . ' - ' . $eq['equipment_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tool *</label>
                <select id="tool_id" name="tool_id" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Tool</option>
                    <?php foreach ($availableTools as $tool): ?>
                    <option value="<?php echo $tool['id']; ?>">
                        <?php echo htmlspecialchars($tool['tool_code'] . ' - ' . $tool['tool_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Slot Number</label>
                <input type="text" id="slot_number" name="slot_number" placeholder="e.g., T01, T02"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Usage Ratio (%)</label>
                <input type="number" id="usage_ratio" name="usage_ratio" value="100" min="0" max="100" step="0.01"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Percentage of equipment runtime applied to this tool (default: 100%)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeSettingModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddSettingModal() {
    document.getElementById('modalTitle').textContent = 'Install Tool';
    document.getElementById('settingForm').reset();
    document.getElementById('setting_id').value = '';
    document.getElementById('usage_ratio').value = '100';
    document.getElementById('settingModal').classList.remove('hidden');
}

function editSetting(setting) {
    document.getElementById('modalTitle').textContent = 'Edit Tool Installation';
    document.getElementById('setting_id').value = setting.id;
    document.getElementById('equipment_id').value = setting.equipment_id;
    document.getElementById('tool_id').value = setting.tool_id;
    document.getElementById('slot_number').value = setting.slot_number || '';
    document.getElementById('usage_ratio').value = setting.usage_ratio;
    document.getElementById('notes').value = setting.notes || '';
    document.getElementById('settingModal').classList.remove('hidden');
}

function closeSettingModal() {
    document.getElementById('settingModal').classList.add('hidden');
}

function saveSetting(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());

    const url = data.setting_id ? 'api/update_tool_setting.php' : 'api/add_tool_to_equipment.php';

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
        alert('Error saving setting: ' + error);
    });
}

function removeTool(settingId, equipmentCode, toolCode) {
    if (!confirm(`Remove tool "${toolCode}" from equipment "${equipmentCode}"?`)) {
        return;
    }

    fetch('api/remove_tool_from_equipment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ setting_id: settingId })
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
        alert('Error removing tool: ' + error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
