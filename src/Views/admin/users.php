<?php
$roleColors = [
    'moderator' => 'bg-purple-100 text-purple-800',
    'owner' => 'bg-blue-100 text-blue-800',
    'exec' => 'bg-red-100 text-red-800',
    'viewer' => 'bg-gray-100 text-gray-800',
];
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Users</h1>
            <p class="mt-1 text-gray-600"><?= count($users ?? []) ?> users</p>
        </div>
        <a href="/admin" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Dashboard</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Users List -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users ?? [] as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-primary-100 flex items-center justify-center">
                                        <span class="text-primary-700 font-medium"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div><?= $user['assigned_count'] ?> assigned</div>
                                <div><?= $user['audit_count'] ?> actions</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add User Form -->
        <div>
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Add New User</h2>
                <form action="/admin/users" method="POST" class="space-y-4">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" required minlength="8"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="viewer">Viewer - Read-only access</option>
                            <option value="owner">Owner - Can be assigned items</option>
                            <option value="exec">Executive - Dashboard access</option>
                            <option value="moderator">Moderator - Full access</option>
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        Create User
                    </button>
                </form>
            </div>

            <!-- Role Descriptions -->
            <div class="mt-6 bg-white shadow rounded-lg p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Role Permissions</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-purple-700">Moderator</dt>
                        <dd class="text-gray-500">Full access: approve/reject posts, assign owners, manage users</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-blue-700">Owner</dt>
                        <dd class="text-gray-500">Can be assigned items, update status, add comments</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-red-700">Executive</dt>
                        <dd class="text-gray-500">Dashboard access, view reports and exports</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-700">Viewer</dt>
                        <dd class="text-gray-500">Read-only access to admin area</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
