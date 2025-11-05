<?php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/TaskManager.php';
require_once __DIR__ . '/../includes/UserManager.php';

$auth = new Auth();
$auth->requireAdmin();

$taskManager = new TaskManager();
$userManager = new UserManager();

// Get overall statistics
$stats = $taskManager->getDashboardStats();
$users = $userManager->getAllUsers();
$roles = $userManager->getAllRoles();
$departments = $userManager->getAllDepartments();

$pageTitle = 'Admin Dashboard';
$showNav = true;
?>

<?php include __DIR__ . '/../views/layouts/header.php'; ?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-cog mr-2"></i> Admin Dashboard
        </h1>
        <p class="mt-1 text-sm text-gray-500">System overview and user management</p>
    </div>

    <!-- Global Statistics -->
    <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">System Statistics</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Total Tasks</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                    <?php echo number_format($stats['total']); ?>
                </dd>
            </div>

            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Pending Tasks</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">
                    <?php echo number_format($stats['pending']); ?>
                </dd>
            </div>

            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">In Progress</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">
                    <?php echo number_format($stats['in_progress']); ?>
                </dd>
            </div>

            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Completed</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">
                    <?php echo number_format($stats['completed']); ?>
                </dd>
            </div>

            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Due Today</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-orange-600">
                    <?php echo number_format($stats['due_today']); ?>
                </dd>
            </div>

            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Overdue</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">
                    <?php echo number_format($stats['overdue']); ?>
                </dd>
            </div>
        </div>
    </div>

    <!-- User Management -->
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">User Management</h2>
            <button onclick="showCreateUserModal()" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <i class="fas fa-user-plus mr-2"></i> Add User
            </button>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                            <?php echo Security::escape($user['name']); ?>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            <?php echo Security::escape($user['email']); ?>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                <?php echo $user['role_name'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo Security::escape($user['role_name']); ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            <?php echo Security::escape($user['department_name'] ?: 'N/A'); ?>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            <?php echo $user['last_login'] ? date('m/d/Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                            <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($user['id'] != $auth->getUserId()): ?>
                            <?php if ($user['is_active']): ?>
                            <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, 0)" class="text-orange-600 hover:text-orange-900 mr-3">
                                <i class="fas fa-ban"></i> Deactivate
                            </button>
                            <?php else: ?>
                            <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, 1)" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-check"></i> Activate
                            </button>
                            <?php endif; ?>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo Security::escape($user['name']); ?>')" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div id="userModal" class="hidden fixed inset-0 z-10 overflow-y-auto">
    <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="user_id">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add New User</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input type="text" name="name" id="userName" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="email" id="userEmail" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password <span id="passwordRequired">*</span></label>
                            <input type="password" name="password" id="userPassword"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                            <p class="mt-1 text-sm text-gray-500" id="passwordHelp">Leave blank to keep current password</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role *</label>
                            <select name="role_id" id="userRole" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo Security::escape($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <select name="department_id" id="userDepartment"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                <option value="">None</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo Security::escape($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                        Save User
                    </button>
                    <button type="button" onclick="hideUserModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateUserModal() {
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('userId').value = '';
    document.getElementById('userName').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('passwordRequired').style.display = '';
    document.getElementById('passwordHelp').style.display = 'none';
    document.getElementById('userRole').value = '2';
    document.getElementById('userDepartment').value = '';
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = user.id;
    document.getElementById('userName').value = user.name;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = false;
    document.getElementById('passwordRequired').style.display = 'none';
    document.getElementById('passwordHelp').style.display = '';
    document.getElementById('userRole').value = user.role_id;
    document.getElementById('userDepartment').value = user.department_id || '';
    document.getElementById('userModal').classList.remove('hidden');
}

function hideUserModal() {
    document.getElementById('userModal').classList.add('hidden');
    document.getElementById('userForm').reset();
}

function saveUser(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const userId = formData.get('user_id');

    // Convert FormData to JSON
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key !== 'user_id' && value !== '') {
            data[key] = value;
        }
    }

    const url = userId ? `/public/api/users.php?id=${userId}` : '/public/api/users.php';
    const method = userId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User saved successfully!');
            hideUserModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error saving user');
        console.error('Error:', error);
    });
}

function toggleUserStatus(userId, status) {
    const action = status ? 'activate' : 'deactivate';
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }

    fetch(`/public/api/users.php?id=${userId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ is_active: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function deleteUser(userId, userName) {
    if (!confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        return;
    }

    fetch(`/public/api/users.php?id=${userId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User deleted successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
