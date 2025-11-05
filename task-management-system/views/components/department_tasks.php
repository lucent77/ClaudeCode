<?php
/**
 * Reusable Department Tasks Component
 * Pass in $departmentId, $departmentName, $departmentCode, $tasks
 */
?>

<meta name="department-id" content="<?php echo $departmentId; ?>">

<div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <?php echo $departmentIcon ?? '<i class="fas fa-tasks"></i>'; ?>
                <?php echo Security::escape($departmentName); ?>
            </h1>
            <p class="mt-1 text-sm text-gray-500">Department tasks and workflow</p>
        </div>
        <div class="flex space-x-3">
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
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
                    <option value="">All Statuses</option>
                    <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Pending</option>
                    <option value="2" <?php echo (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="3" <?php echo (isset($_GET['status']) && $_GET['status'] == '3') ? 'selected' : ''; ?>>On Hold</option>
                    <option value="4" <?php echo (isset($_GET['status']) && $_GET['status'] == '4') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date Range</label>
                <input type="date" name="date_from" value="<?php echo Security::escape($_GET['date_from'] ?? ''); ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 border">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <?php
        $pending = count(array_filter($tasks, fn($t) => $t['status_id'] == 1));
        $inProgress = count(array_filter($tasks, fn($t) => $t['status_id'] == 2));
        $onHold = count(array_filter($tasks, fn($t) => $t['status_id'] == 3));
        $completed = count(array_filter($tasks, fn($t) => $t['status_id'] == 4));
        ?>
        <div class="rounded-lg bg-yellow-50 p-4">
            <div class="text-sm font-medium text-yellow-800">Pending</div>
            <div class="mt-1 text-2xl font-semibold text-yellow-900"><?php echo $pending; ?></div>
        </div>
        <div class="rounded-lg bg-blue-50 p-4">
            <div class="text-sm font-medium text-blue-800">In Progress</div>
            <div class="mt-1 text-2xl font-semibold text-blue-900"><?php echo $inProgress; ?></div>
        </div>
        <div class="rounded-lg bg-orange-50 p-4">
            <div class="text-sm font-medium text-orange-800">On Hold</div>
            <div class="mt-1 text-2xl font-semibold text-orange-900"><?php echo $onHold; ?></div>
        </div>
        <div class="rounded-lg bg-green-50 p-4">
            <div class="text-sm font-medium text-green-800">Completed</div>
            <div class="mt-1 text-2xl font-semibold text-green-900"><?php echo $completed; ?></div>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tasksTable">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach ($columns as $col): ?>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <?php echo $col['label']; ?>
                        </th>
                        <?php endforeach; ?>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($tasks as $task): ?>
                    <tr class="hover:bg-gray-50 <?php echo (isset($task['due_date']) && $task['due_date'] < date('Y-m-d') && $task['status_id'] != 4) ? 'bg-red-50' : ''; ?>"
                        data-task-id="<?php echo $task['id']; ?>">
                        <?php foreach ($columns as $col): ?>
                        <td class="px-3 py-4 text-sm text-gray-900 <?php echo $col['nowrap'] ?? false ? 'whitespace-nowrap' : ''; ?>">
                            <?php
                            $value = $task[$col['field']] ?? '-';
                            if ($col['field'] === 'date' || $col['field'] === 'due_date') {
                                $value = $value ? date('m/d/Y', strtotime($value)) : '-';
                            }
                            echo Security::escape($value);
                            ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <select class="status-select rounded px-2 py-1 text-xs font-semibold
                                <?php
                                switch($task['status_id']) {
                                    case 1: echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 2: echo 'bg-blue-100 text-blue-800'; break;
                                    case 3: echo 'bg-orange-100 text-orange-800'; break;
                                    case 4: echo 'bg-green-100 text-green-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>"
                                data-task-id="<?php echo $task['id']; ?>"
                                onchange="updateTaskStatus(this)">
                                <option value="1" <?php echo $task['status_id'] == 1 ? 'selected' : ''; ?>>Pending</option>
                                <option value="2" <?php echo $task['status_id'] == 2 ? 'selected' : ''; ?>>In Progress</option>
                                <option value="3" <?php echo $task['status_id'] == 3 ? 'selected' : ''; ?>>On Hold</option>
                                <option value="4" <?php echo $task['status_id'] == 4 ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-medium">
                            <button onclick="viewTask(<?php echo $task['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="viewHistory(<?php echo $task['id']; ?>)" class="text-gray-600 hover:text-gray-900" title="View History">
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
        <p class="text-gray-500">No tasks found. Create a new task to get started.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Create Task Modal -->
<div id="createModal" class="hidden fixed inset-0 z-10 overflow-y-auto">
    <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
            <form id="createTaskForm" onsubmit="createTask(event, <?php echo $departmentId; ?>)">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Create New Task</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($formFields as $field): ?>
                        <div class="<?php echo $field['fullWidth'] ?? false ? 'col-span-2' : ''; ?>">
                            <label class="block text-sm font-medium text-gray-700"><?php echo $field['label']; ?></label>
                            <?php if ($field['type'] === 'textarea'): ?>
                            <textarea name="<?php echo $field['name']; ?>"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border"></textarea>
                            <?php else: ?>
                            <input type="<?php echo $field['type']; ?>"
                                   name="<?php echo $field['name']; ?>"
                                   <?php echo $field['required'] ?? false ? 'required' : ''; ?>
                                   value="<?php echo $field['default'] ?? ''; ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
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

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function hideCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createTaskForm').reset();
}

function createTask(event, departmentId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('department_id', departmentId);

    fetch('/public/api/tasks.php', {
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

    fetch(`/public/api/tasks.php?id=${taskId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ status_id: statusId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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
        '4': 'bg-green-100 text-green-800'
    };
    return classes[statusId] || 'bg-gray-100 text-gray-800';
}
</script>
