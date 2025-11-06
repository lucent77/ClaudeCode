<?php
/**
 * Admin - Manage Users
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pageTitle = 'Manage Users';
$db = Database::getInstance();

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $department = sanitize($_POST['department'] ?? '');
    $role = sanitize($_POST['role'] ?? 'user');
    $email = sanitize($_POST['email'] ?? '');

    if (!empty($username) && !empty($password) && !empty($department)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db->insert('users', [
                'username' => $username,
                'password' => $hashedPassword,
                'department' => $department,
                'role' => $role,
                'email' => $email
            ]);
            setFlash('User created successfully', 'success');
        } catch (Exception $e) {
            setFlash('Error creating user: ' . $e->getMessage(), 'error');
        }
    }
}

// Get all users
$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Manage Users</h1>
    <p class="mt-2 text-sm text-gray-600">Create and manage user accounts</p>
</div>

<!-- Create User Form -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Create New User</h2>
    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="action" value="create">
        <input type="text" name="username" placeholder="Username" required class="px-4 py-2 border rounded-lg">
        <input type="password" name="password" placeholder="Password" required class="px-4 py-2 border rounded-lg">
        <input type="text" name="department" placeholder="Department" required class="px-4 py-2 border rounded-lg">
        <input type="email" name="email" placeholder="Email (optional)" class="px-4 py-2 border rounded-lg">
        <select name="role" class="px-4 py-2 border rounded-lg">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create User</button>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($users as $user): ?>
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $user['id']; ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($user['username']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($user['department']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($user['email'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo formatDate($user['created_at']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
