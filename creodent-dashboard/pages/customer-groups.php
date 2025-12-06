<?php
/**
 * Creodent Dashboard - Customer Groups Management Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/GroupService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/table.php';
require_once dirname(__DIR__) . '/components/modal.php';
require_once dirname(__DIR__) . '/components/alert.php';

use Creodent\Core\{Auth, Request, Session};
use Creodent\Services\GroupService;

date_default_timezone_set(TIMEZONE);
Auth::requireRole(['admin']);

$pageTitle = 'Customer Groups';
$currentPage = 'customer-groups';

$branch = Request::branch();
$dateRange = Request::dateRange();

// Handle form submissions
if (Request::isPost() && Request::validateCsrf()) {
    $action = Request::post('form_action');

    switch ($action) {
        case 'create':
            $name = trim(Request::post('name', ''));
            $description = trim(Request::post('description', ''));
            if ($name) {
                GroupService::createGroup($name, $description);
                Session::flash('success', 'Group created successfully.');
            }
            break;

        case 'delete':
            $id = Request::int('group_id');
            if ($id) {
                GroupService::deleteGroup($id);
                Session::flash('success', 'Group deleted successfully.');
            }
            break;

        case 'add_member':
            $groupId = Request::int('group_id');
            $customerName = Request::post('customer_name');
            $memberBranch = Request::post('member_branch', 'ALL');
            if ($groupId && $customerName) {
                if (GroupService::addMember($groupId, $customerName, $memberBranch)) {
                    Session::flash('success', 'Member added successfully.');
                } else {
                    Session::flash('error', 'Customer already in group.');
                }
            }
            break;

        case 'remove_member':
            $groupId = Request::int('group_id');
            $customerName = Request::post('customer_name');
            if ($groupId && $customerName) {
                GroupService::removeMember($groupId, $customerName);
                Session::flash('success', 'Member removed successfully.');
            }
            break;
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$groups = GroupService::getGroups();
$selectedGroupId = Request::int('group_id');
$selectedGroup = $selectedGroupId ? GroupService::getGroup($selectedGroupId) : null;
$availableCustomers = GroupService::getAvailableCustomers($branch);

include dirname(__DIR__) . '/components/header.php';
?>

<?php renderFlashMessages(); ?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Total Groups',
        'value' => number_format(count($groups)),
        'icon' => 'users',
        'color' => 'blue'
    ],
    [
        'title' => 'Active Groups',
        'value' => number_format(count(array_filter($groups, fn($g) => $g['is_active']))),
        'icon' => 'chart-bar',
        'color' => 'green'
    ],
    [
        'title' => 'Total Members',
        'value' => number_format(array_sum(array_column($groups, 'member_count'))),
        'icon' => 'users',
        'color' => 'amber'
    ],
    [
        'title' => 'Available Customers',
        'value' => number_format(count($availableCustomers)),
        'icon' => 'users',
        'color' => 'purple'
    ]
]);
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Groups List -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Groups</h3>
                <button onclick="openModal('create-group-modal')" class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    + New Group
                </button>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (empty($groups)): ?>
                <div class="p-6 text-center text-gray-500">
                    No groups created yet.
                </div>
                <?php else: ?>
                <?php foreach ($groups as $group): ?>
                <a href="?group_id=<?= $group['id'] ?>&branch=<?= $branch ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 <?= $selectedGroupId === (int)$group['id'] ? 'bg-blue-50' : '' ?>">
                    <div>
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($group['name']) ?></p>
                        <p class="text-sm text-gray-500"><?= $group['member_count'] ?> members</p>
                    </div>
                    <?php if (!$group['is_active']): ?>
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">Inactive</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Group Details -->
    <div class="lg:col-span-2">
        <?php if ($selectedGroup): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($selectedGroup['name']) ?></h3>
                    <?php if ($selectedGroup['description']): ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($selectedGroup['description']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <button onclick="openModal('add-member-modal')" class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                        + Add Member
                    </button>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this group?');">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="form_action" value="delete">
                        <input type="hidden" name="group_id" value="<?= $selectedGroup['id'] ?>">
                        <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- Members List -->
            <div class="divide-y divide-gray-100">
                <?php if (empty($selectedGroup['members'])): ?>
                <div class="p-6 text-center text-gray-500">
                    No members in this group yet.
                </div>
                <?php else: ?>
                <?php foreach ($selectedGroup['members'] as $member): ?>
                <div class="flex items-center justify-between p-4 hover:bg-gray-50">
                    <div>
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($member['customer_name']) ?></p>
                        <p class="text-sm text-gray-500">Branch: <?= $member['branch'] ?></p>
                    </div>
                    <form method="POST">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="form_action" value="remove_member">
                        <input type="hidden" name="group_id" value="<?= $selectedGroup['id'] ?>">
                        <input type="hidden" name="customer_name" value="<?= htmlspecialchars($member['customer_name']) ?>">
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            <p class="text-gray-500">Select a group to view its members</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Group Modal -->
<?php renderModalOpen('create-group-modal', 'Create New Group'); ?>
<form method="POST">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="form_action" value="create">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
    </div>
<?php renderModalClose(true, 'Create Group'); ?>
</form>

<!-- Add Member Modal -->
<?php if ($selectedGroup): ?>
<?php renderModalOpen('add-member-modal', 'Add Member to ' . htmlspecialchars($selectedGroup['name'])); ?>
<form method="POST">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="form_action" value="add_member">
    <input type="hidden" name="group_id" value="<?= $selectedGroup['id'] ?>">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
            <select name="customer_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Select a customer...</option>
                <?php foreach ($availableCustomers as $customer): ?>
                <option value="<?= htmlspecialchars($customer) ?>"><?= htmlspecialchars($customer) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
            <select name="member_branch" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="ALL">All Branches</option>
                <option value="NYC">NYC Only</option>
                <option value="HV">HV Only</option>
            </select>
        </div>
    </div>
<?php renderModalClose(true, 'Add Member'); ?>
</form>
<?php endif; ?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
