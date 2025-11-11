<?php
/**
 * Daily Production Input
 * Input form for daily production data for all equipment
 */

require_once 'config/database.php';
checkAuth();

$page_title = 'Daily Production Input - Production Management System';
$pdo = getDbConnection();

// Get selected date (default: today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Get all equipment
$equipment = $pdo->query("SELECT * FROM equipment ORDER BY equipment_code")->fetchAll();

// Get existing data for selected date
$stmt = $pdo->prepare("
    SELECT * FROM production_records
    WHERE record_date = ?
    ORDER BY equipment_id
");
$stmt->execute([$selectedDate]);
$existingData = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// Reorganize by equipment_id for easy access
$dataByEquipment = [];
foreach ($existingData as $equipId => $record) {
    $dataByEquipment[$equipId] = $record;
}

include 'includes/header.php';
?>

<style>
.production-table {
    min-width: 3000px;
}
.production-table th,
.production-table td {
    min-width: 80px;
    padding: 8px 4px;
    font-size: 0.75rem;
}
.production-table input,
.production-table textarea {
    width: 100%;
    padding: 4px;
    font-size: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}
.production-table input:focus,
.production-table textarea:focus {
    outline: none;
    border-color: #3b82f6;
    ring: 2px;
    ring-color: #3b82f6;
}
.production-table textarea {
    min-height: 60px;
    resize: vertical;
}
.equipment-col {
    position: sticky;
    left: 0;
    background-color: white;
    z-index: 10;
    min-width: 100px !important;
    font-weight: 600;
}
.header-row {
    position: sticky;
    top: 0;
    background-color: #f9fafb;
    z-index: 20;
}
.equipment-header {
    position: sticky;
    left: 0;
    z-index: 30;
    background-color: #f9fafb;
}
.readonly-field {
    background-color: #f3f4f6;
    cursor: not-allowed;
}
.prev-time-ref {
    font-size: 0.65rem;
    color: #6b7280;
    margin-top: 2px;
}
</style>

<div class="px-4 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Daily Production Input</h1>
        <div class="flex space-x-2">
            <button onclick="showLoadLatestModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                Load Latest Data
            </button>
            <button onclick="showResetModal()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md font-medium">
                Reset
            </button>
            <button onclick="saveAllData()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                Save All
            </button>
        </div>
    </div>

    <!-- Date Navigation -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow flex items-center justify-between">
        <button onclick="changeDate(-1)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-md font-medium">
            ← Previous Day
        </button>

        <div class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700">Date:</label>
            <input type="date"
                   id="selectedDate"
                   value="<?php echo $selectedDate; ?>"
                   onchange="location.href='?date=' + this.value"
                   class="border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <button onclick="location.href='production_input.php'"
                    class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-md text-sm font-medium">
                Today
            </button>
        </div>

        <button onclick="changeDate(1)" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-md font-medium">
            Next Day →
        </button>
    </div>

    <!-- Production Input Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto" style="max-height: 80vh;">
            <form id="productionForm">
                <table class="production-table border-collapse border border-gray-300">
                    <thead>
                        <tr class="header-row">
                            <th class="equipment-header border border-gray-300">Equipment</th>
                            <th class="border border-gray-300">Model Name</th>
                            <th class="border border-gray-300">SKU</th>
                            <th class="border border-gray-300">LOT NO</th>
                            <th class="border border-gray-300">Part Length</th>
                            <th class="border border-gray-300">Worker</th>
                            <th class="border border-gray-300">Diameter</th>
                            <th class="border border-gray-300">Length</th>
                            <th class="border border-gray-300">Lot</th>
                            <th class="border border-gray-300">Unit</th>
                            <th class="border border-gray-300">Exped Count</th>
                            <th class="border border-gray-300">Exped 24H</th>
                            <th class="border border-gray-300">Plan Count</th>
                            <th class="border border-gray-300">Unit Total</th>
                            <th class="border border-gray-300">M</th>
                            <th class="border border-gray-300">S</th>
                            <th class="border border-gray-300">CNC Run (H)</th>
                            <th class="border border-gray-300">Working (H)</th>
                            <th class="border border-gray-300">Setting</th>
                            <th class="border border-gray-300">CNC Total</th>
                            <th class="border border-gray-300">Test Unit</th>
                            <th class="border border-gray-300">Day Achieve %</th>
                            <th class="border border-gray-300">24H Achieve %</th>
                            <th class="border border-gray-300">Total Achieve %</th>
                            <th class="border border-gray-300">Milling Days</th>
                            <th class="border border-gray-300">Setting Qty</th>
                            <th class="border border-gray-300">Tool Broken</th>
                            <th class="border border-gray-300">Dent Fail</th>
                            <th class="border border-gray-300">Dimension Fail</th>
                            <th class="border border-gray-300">Overnight Fail</th>
                            <th class="border border-gray-300">ETC Fail</th>
                            <th class="border border-gray-300">Inspected By</th>
                            <th class="border border-gray-300">Description</th>
                            <th class="border border-gray-300">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipment as $eq):
                            $data = $dataByEquipment[$eq['id']] ?? null;
                            $equipId = $eq['id'];
                        ?>
                        <tr data-equipment-id="<?php echo $equipId; ?>">
                            <td class="equipment-col border border-gray-300"><?php echo htmlspecialchars($eq['equipment_code']); ?></td>
                            <td class="border border-gray-300">
                                <input type="text" name="model_name_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['model_name'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="sku_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['sku'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="lot_no_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['lot_no'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" step="0.01" name="part_length_<?php echo $equipId; ?>" value="<?php echo $data['part_length'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="worker_name_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['worker_name'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="diameter_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['diameter'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="length_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['length'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="lot_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['lot'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="unit_<?php echo $equipId; ?>" value="<?php echo $data['unit'] ?? ''; ?>"
                                       onchange="calculateAchievements(<?php echo $equipId; ?>)">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="exped_count_<?php echo $equipId; ?>" value="<?php echo $data['exped_count'] ?? ''; ?>"
                                       onchange="calculateAchievements(<?php echo $equipId; ?>)">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="exped_count_24h_<?php echo $equipId; ?>" value="<?php echo $data['exped_count_24h'] ?? ''; ?>"
                                       onchange="calculateAchievements(<?php echo $equipId; ?>)">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="plan_count_<?php echo $equipId; ?>" value="<?php echo $data['plan_count'] ?? ''; ?>"
                                       onchange="calculateAchievements(<?php echo $equipId; ?>)">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="unit_total_<?php echo $equipId; ?>" value="<?php echo $data['unit_total'] ?? ''; ?>"
                                       onchange="calculateAchievements(<?php echo $equipId; ?>)">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="M_<?php echo $equipId; ?>" value="<?php echo $data['M'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="S_<?php echo $equipId; ?>" value="<?php echo $data['S'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" step="0.01" name="cnc_run_time_<?php echo $equipId; ?>" value="<?php echo $data['cnc_run_time'] ?? ''; ?>"
                                       class="readonly-field" readonly>
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" step="0.01" name="working_time_<?php echo $equipId; ?>" value="<?php echo $data['working_time'] ?? ''; ?>"
                                       onchange="calculateCncRunTime(<?php echo $equipId; ?>)">
                                <div class="prev-time-ref" id="prev_time_<?php echo $equipId; ?>">Previous: -</div>
                            </td>
                            <td class="border border-gray-300">
                                <textarea name="setting_<?php echo $equipId; ?>"><?php echo htmlspecialchars($data['setting'] ?? ''); ?></textarea>
                            </td>
                            <td class="border border-gray-300">
                                <textarea name="cnc_total_<?php echo $equipId; ?>"><?php echo htmlspecialchars($data['cnc_total'] ?? ''); ?></textarea>
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="test_unit_<?php echo $equipId; ?>" value="<?php echo $data['test_unit'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" step="0.01" name="day_achievement_<?php echo $equipId; ?>" value="<?php echo $data['day_achievement'] ?? ''; ?>"
                                       class="readonly-field" readonly>
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" step="0.01" name="h24_achievement_<?php echo $equipId; ?>" value="<?php echo $data['h24_achievement'] ?? ''; ?>"
                                       class="readonly-field" readonly>
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" step="0.01" name="total_achievement_<?php echo $equipId; ?>" value="<?php echo $data['total_achievement'] ?? ''; ?>"
                                       class="readonly-field" readonly>
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="milling_days_remaining_<?php echo $equipId; ?>" value="<?php echo $data['milling_days_remaining'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="setting_qty_<?php echo $equipId; ?>" value="<?php echo $data['setting_qty'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="tool_broken_fail_qty_<?php echo $equipId; ?>" value="<?php echo $data['tool_broken_fail_qty'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="dent_failed_qty_<?php echo $equipId; ?>" value="<?php echo $data['dent_failed_qty'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="dimension_fail_qty_<?php echo $equipId; ?>" value="<?php echo $data['dimension_fail_qty'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="overnight_fail_qty_<?php echo $equipId; ?>" value="<?php echo $data['overnight_fail_qty'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="number" name="etc_fail_qty_<?php echo $equipId; ?>" value="<?php echo $data['etc_fail_qty'] ?? ''; ?>">
                            </td>
                            <td class="border border-gray-300">
                                <input type="text" name="inspected_by_<?php echo $equipId; ?>" value="<?php echo htmlspecialchars($data['inspected_by'] ?? ''); ?>">
                            </td>
                            <td class="border border-gray-300">
                                <textarea name="description_<?php echo $equipId; ?>"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
                            </td>
                            <td class="border border-gray-300">
                                <textarea name="note_<?php echo $equipId; ?>"><?php echo htmlspecialchars($data['note'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <div class="mt-4 text-sm text-gray-600">
        <p><strong>Note:</strong> Gray fields are auto-calculated. Achievement rates and CNC run time are calculated automatically.</p>
        <p class="mt-1"><strong>Total Achieve % = (Unit Total / Plan Count) × 100</strong></p>
        <p class="mt-1"><strong>CNC Run Time = Current Working Time - Previous Working Time</strong></p>
    </div>
</div>

<!-- Load Latest Data Modal -->
<div id="loadLatestModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900">Load Latest Data</h3>
            <p class="mt-2 text-sm text-gray-600">
                This will load the most recent production data for each equipment. Fields from Unit onwards will be cleared.
            </p>
        </div>
        <div class="flex justify-end space-x-3">
            <button onclick="closeLoadLatestModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Cancel
            </button>
            <button onclick="loadLatestData()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Load Data
            </button>
        </div>
    </div>
</div>

<!-- Reset Modal -->
<div id="resetModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900">Reset Equipment Data</h3>
            <p class="mt-2 text-sm text-gray-600">Select equipment to reset:</p>
        </div>
        <div class="mb-4 space-y-2 max-h-60 overflow-y-auto">
            <label class="flex items-center">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="mr-2">
                <span class="font-medium">Select All</span>
            </label>
            <hr>
            <?php foreach ($equipment as $eq): ?>
            <label class="flex items-center">
                <input type="checkbox" class="equipment-reset-checkbox mr-2" value="<?php echo $eq['id']; ?>">
                <span><?php echo htmlspecialchars($eq['equipment_code']); ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-end space-x-3">
            <button onclick="closeResetModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Cancel
            </button>
            <button onclick="resetSelected()" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                Reset Selected
            </button>
        </div>
    </div>
</div>

<script>
// Load previous working times on page load
document.addEventListener('DOMContentLoaded', function() {
    const selectedDate = '<?php echo $selectedDate; ?>';
    const equipmentIds = <?php echo json_encode(array_column($equipment, 'id')); ?>;

    equipmentIds.forEach(equipId => {
        loadPreviousWorkingTime(equipId, selectedDate);
        calculateAchievements(equipId);
    });
});

// Change date
function changeDate(days) {
    const currentDate = new Date(document.getElementById('selectedDate').value);
    currentDate.setDate(currentDate.getDate() + days);
    const newDate = currentDate.toISOString().split('T')[0];
    location.href = '?date=' + newDate;
}

// Load previous working time
function loadPreviousWorkingTime(equipId, currentDate) {
    fetch(`api/get_previous_working_time.php?equipment_id=${equipId}&current_date=${currentDate}`)
        .then(response => response.json())
        .then(data => {
            const prevTimeDiv = document.getElementById(`prev_time_${equipId}`);
            if (data.success && data.previous_working_time !== null) {
                prevTimeDiv.textContent = `Previous: ${parseFloat(data.previous_working_time).toFixed(1)}h`;
                prevTimeDiv.dataset.prevTime = data.previous_working_time;
            } else {
                prevTimeDiv.textContent = 'Previous: N/A';
                prevTimeDiv.dataset.prevTime = '';
            }
        });
}

// Calculate CNC Run Time
function calculateCncRunTime(equipId) {
    const workingTimeInput = document.querySelector(`input[name="working_time_${equipId}"]`);
    const cncRunTimeInput = document.querySelector(`input[name="cnc_run_time_${equipId}"]`);
    const prevTimeDiv = document.getElementById(`prev_time_${equipId}`);

    const currentWorkingTime = parseFloat(workingTimeInput.value) || 0;
    const previousWorkingTime = parseFloat(prevTimeDiv.dataset.prevTime) || 0;

    if (currentWorkingTime > 0 && previousWorkingTime > 0) {
        const cncRunTime = currentWorkingTime - previousWorkingTime;
        if (cncRunTime >= 0) {
            cncRunTimeInput.value = cncRunTime.toFixed(2);
        } else {
            cncRunTimeInput.value = '';
        }
    }
}

// Calculate achievements
function calculateAchievements(equipId) {
    const unit = parseFloat(document.querySelector(`input[name="unit_${equipId}"]`).value) || 0;
    const expedCount = parseFloat(document.querySelector(`input[name="exped_count_${equipId}"]`).value) || 0;
    const expedCount24h = parseFloat(document.querySelector(`input[name="exped_count_24h_${equipId}"]`).value) || 0;
    const planCount = parseFloat(document.querySelector(`input[name="plan_count_${equipId}"]`).value) || 0;
    const unitTotal = parseFloat(document.querySelector(`input[name="unit_total_${equipId}"]`).value) || 0;

    // Day Achievement
    if (expedCount > 0) {
        const dayAchieve = (unit / expedCount) * 100;
        document.querySelector(`input[name="day_achievement_${equipId}"]`).value = dayAchieve.toFixed(2);
    }

    // 24H Achievement
    if (expedCount24h > 0) {
        const h24Achieve = (unit / expedCount24h) * 100;
        document.querySelector(`input[name="h24_achievement_${equipId}"]`).value = h24Achieve.toFixed(2);
    }

    // Total Achievement
    if (planCount > 0) {
        const totalAchieve = (unitTotal / planCount) * 100;
        document.querySelector(`input[name="total_achievement_${equipId}"]`).value = totalAchieve.toFixed(2);
    }
}

// Save all data
function saveAllData() {
    if (!confirm('Save all production data?')) {
        return;
    }

    const selectedDate = document.getElementById('selectedDate').value;
    const formData = new FormData(document.getElementById('productionForm'));
    const data = {
        date: selectedDate,
        records: []
    };

    const equipmentIds = <?php echo json_encode(array_column($equipment, 'id')); ?>;

    equipmentIds.forEach(equipId => {
        const record = {
            equipment_id: equipId
        };

        // Get all fields for this equipment
        formData.forEach((value, key) => {
            if (key.endsWith('_' + equipId)) {
                const fieldName = key.replace('_' + equipId, '');
                record[fieldName] = value;
            }
        });

        // Only add if there's some data
        if (Object.keys(record).length > 1) {
            data.records.push(record);
        }
    });

    fetch('api/save_production_records.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Data saved successfully!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Error saving data: ' + error);
    });
}

// Load latest data
function showLoadLatestModal() {
    document.getElementById('loadLatestModal').classList.remove('hidden');
}

function closeLoadLatestModal() {
    document.getElementById('loadLatestModal').classList.add('hidden');
}

function loadLatestData() {
    fetch('api/get_latest_production_data.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Populate form with latest data
                Object.keys(result.data).forEach(equipId => {
                    const record = result.data[equipId];
                    Object.keys(record).forEach(field => {
                        const input = document.querySelector(`input[name="${field}_${equipId}"], textarea[name="${field}_${equipId}"]`);
                        if (input && record[field] !== null) {
                            input.value = record[field];
                        }
                    });
                });
                closeLoadLatestModal();
                alert('Latest data loaded successfully!');
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            alert('Error loading data: ' + error);
        });
}

// Reset functionality
function showResetModal() {
    document.getElementById('resetModal').classList.remove('hidden');
}

function closeResetModal() {
    document.getElementById('resetModal').classList.add('hidden');
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.equipment-reset-checkbox').forEach(cb => cb.checked = false);
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll').checked;
    document.querySelectorAll('.equipment-reset-checkbox').forEach(cb => {
        cb.checked = selectAll;
    });
}

function resetSelected() {
    const selected = Array.from(document.querySelectorAll('.equipment-reset-checkbox:checked'))
        .map(cb => cb.value);

    if (selected.length === 0) {
        alert('Please select at least one equipment to reset.');
        return;
    }

    if (!confirm(`Reset data for ${selected.length} equipment?`)) {
        return;
    }

    selected.forEach(equipId => {
        const row = document.querySelector(`tr[data-equipment-id="${equipId}"]`);
        row.querySelectorAll('input, textarea').forEach(input => {
            input.value = '';
        });
    });

    closeResetModal();
    alert('Selected equipment data has been reset.');
}
</script>

<?php include 'includes/footer.php'; ?>
