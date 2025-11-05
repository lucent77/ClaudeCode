<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Security.php';
require_once __DIR__ . '/includes/TaskManager.php';
require_once __DIR__ . '/includes/UserManager.php';

$auth = new Auth();
$auth->requireLogin();

// Only admin and front desk can access
if (!$auth->isAdmin() && !$auth->isFrontDesk()) {
    header('Location: /index.php');
    exit;
}

$taskManager = new TaskManager();
$userManager = new UserManager();

// Get filters from query string
$filters = [];
if (isset($_GET['department'])) {
    $filters['department_id'] = $_GET['department'];
}
if (isset($_GET['status'])) {
    $filters['status_id'] = $_GET['status'];
}
if (isset($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$tasks = $taskManager->getTasks($filters);
$departments = $userManager->getAllDepartments();
$users = $userManager->getAllUsers();

$pageTitle = 'Front Desk - All Tasks';
$showNav = true;
?>

<?php include __DIR__ . '/views/layouts/header.php'; ?>

<div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-th-list mr-2"></i> Front Desk - All Tasks
            </h1>
            <p class="mt-1 text-sm text-gray-500">Manage tasks across all departments</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="showImportModal()" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                <i class="fas fa-file-import mr-2"></i> Import JSON
            </button>
            <button onclick="showCreateModal()" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <i class="fas fa-plus mr-2"></i> New Task
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 rounded-lg bg-white p-4 shadow">
        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search" placeholder="ID, Lab, Notes..."
                       value="<?php echo Security::escape($_GET['search'] ?? ''); ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Department</label>
                <select name="department" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['id']) ? 'selected' : ''; ?>>
                        <?php echo Security::escape($dept['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                    <option value="">All Statuses</option>
                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Pending</option>
                    <option value="2" <?php echo (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="3" <?php echo (isset($_GET['status']) && $_GET['status'] == '3') ? 'selected' : ''; ?>>On Hold</option>
                    <option value="4" <?php echo (isset($_GET['status']) && $_GET['status'] == '4') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Tasks Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tasksTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ID</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Department</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Details</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Lab</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assigned To</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($tasks as $task): ?>
                    <tr class="hover:bg-gray-50" data-task-id="<?php echo $task['id']; ?>">
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                            <?php echo Security::escape($task['external_id'] ?: $task['id']); ?>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <span class="inline-flex rounded-full px-2 text-xs font-semibold leading-5
                                <?php
                                switch($task['department_code']) {
                                    case 'SOLIDEX': echo 'bg-purple-100 text-purple-800'; break;
                                    case 'COCR': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case '3D_PRINT': echo 'bg-green-100 text-green-800'; break;
                                    default: echo 'bg-blue-100 text-blue-800';
                                }
                                ?>">
                                <?php echo Security::escape($task['department_name']); ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <?php echo Security::escape($task['date'] ? date('m/d/Y', strtotime($task['date'])) : '-'); ?>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <?php echo Security::escape($task['type'] ?: '-'); ?>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">
                            <?php if ($task['tooth']): ?>
                                <div>Tooth: <?php echo Security::escape($task['tooth']); ?></div>
                            <?php endif; ?>
                            <?php if ($task['teeth']): ?>
                                <div>Teeth: <?php echo Security::escape($task['teeth']); ?></div>
                            <?php endif; ?>
                            <?php if ($task['design']): ?>
                                <div>Design: <?php echo Security::escape($task['design']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-500">
                            <?php echo Security::escape($task['lab'] ?: '-'); ?>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <select class="status-select rounded px-2 py-1 text-xs font-semibold
                                <?php
                                switch($task['status_name']) {
                                    case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'In Progress': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'On Hold': echo 'bg-orange-100 text-orange-800'; break;
                                    case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>"
                                data-task-id="<?php echo $task['id']; ?>"
                                onchange="updateTaskStatus(this)">
                                <option value="1" <?php echo $task['status_id'] == 1 ? 'selected' : ''; ?>>Pending</option>
                                <option value="2" <?php echo $task['status_id'] == 2 ? 'selected' : ''; ?>>In Progress</option>
                                <option value="3" <?php echo $task['status_id'] == 3 ? 'selected' : ''; ?>>On Hold</option>
                                <option value="4" <?php echo $task['status_id'] == 4 ? 'selected' : ''; ?>>Completed</option>
                                <option value="5" <?php echo $task['status_id'] == 5 ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <?php echo Security::escape($task['assigned_to_name'] ?: 'Unassigned'); ?>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium">
                            <button onclick="editTask(<?php echo $task['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="viewHistory(<?php echo $task['id']; ?>)" class="text-gray-600 hover:text-gray-900 mr-3">
                                <i class="fas fa-history"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (empty($tasks)): ?>
    <div class="mt-6 text-center">
        <p class="text-gray-500">No tasks found. Try adjusting your filters or create a new task.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Create Task Modal -->
<div id="createModal" class="hidden fixed inset-0 z-10 overflow-y-auto">
    <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
            <form id="createTaskForm" onsubmit="createTask(event)">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Create New Task</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">External ID</label>
                            <input type="text" name="external_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department *</label>
                            <select name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo Security::escape($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" name="due_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <input type="text" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assign To</label>
                            <select name="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo Security::escape($user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tooth</label>
                            <input type="text" name="tooth" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Design</label>
                            <input type="text" name="design" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Lab</label>
                            <input type="text" name="lab" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                        Create Task
                    </button>
                    <button type="button" onclick="hideCreateModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import JSON Modal -->
<div id="importModal" class="hidden fixed inset-0 z-10 overflow-y-auto">
    <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <form id="importForm" onsubmit="importJSON(event)" enctype="multipart/form-data">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Import Tasks from JSON</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <select name="department_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo Security::escape($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">JSON File</label>
                            <input type="file" name="json_file" accept=".json" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto">
                        Import
                    </button>
                    <button type="button" onclick="hideImportModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function hideCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createTaskForm').reset();
}

function showImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
}

function hideImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    document.getElementById('importForm').reset();
}

function createTask(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('/api/tasks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Task created successfully!');
            hideCreateModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating task');
        console.error('Error:', error);
    });
}

function updateTaskStatus(select) {
    const taskId = select.dataset.taskId;
    const statusId = select.value;

    fetch(`/api/tasks.php?id=${taskId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ status_id: statusId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the select styling
            select.className = 'status-select rounded px-2 py-1 text-xs font-semibold ' + getStatusClass(statusId);
        } else {
            alert('Error updating status');
        }
    });
}

function getStatusClass(statusId) {
    const classes = {
        '1': 'bg-yellow-100 text-yellow-800',
        '2': 'bg-blue-100 text-blue-800',
        '3': 'bg-orange-100 text-orange-800',
        '4': 'bg-green-100 text-green-800',
        '5': 'bg-red-100 text-red-800'
    };
    return classes[statusId] || 'bg-gray-100 text-gray-800';
}

function importJSON(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    fetch('/api/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully imported ${data.count} tasks!`);
            hideImportModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error importing tasks');
        console.error('Error:', error);
    });
}
</script>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
