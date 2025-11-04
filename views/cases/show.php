<?php
$statusColors = [
    'new' => 'bg-green-100 text-green-800',
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'done' => 'bg-blue-100 text-blue-800',
    'on_hold' => 'bg-gray-100 text-gray-800',
    'canceled' => 'bg-red-100 text-red-800',
    'archived' => 'bg-purple-100 text-purple-800'
];
$statusColor = $statusColors[$case['status']] ?? 'bg-gray-100 text-gray-800';

$priorityColors = [
    'low' => 'text-gray-600',
    'normal' => 'text-blue-600',
    'high' => 'text-orange-600',
    'urgent' => 'text-red-600'
];
$priorityColor = $priorityColors[$case['priority']] ?? 'text-gray-600';

$isOverdue = $case['due_date'] && strtotime($case['due_date']) < time() && !in_array($case['status'], ['done', 'canceled', 'archived']);
?>

<div class="py-6" x-data="caseDetail(<?= htmlspecialchars(json_encode($case)) ?>)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <a href="/cases" class="mr-4 text-gray-600 hover:text-gray-900">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($case['external_case_no']) ?></h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="px-3 py-1 text-sm font-medium rounded-full <?= $statusColor ?>">
                                <?= ucfirst(str_replace('_', ' ', $case['status'])) ?>
                            </span>
                            <span class="text-sm <?= $priorityColor ?> font-medium">
                                <?= ucfirst($case['priority']) ?> Priority
                            </span>
                            <?php if ($case['location']): ?>
                                <span class="text-sm text-gray-600">
                                    📍 <?= htmlspecialchars($case['location']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin', 'manager'])): ?>
                    <div class="flex gap-2">
                        <button @click="editing = !editing" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Case Information</h2>

                    <form x-show="editing" @submit.prevent="saveCase" class="space-y-4">
                        <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">
                        <input type="hidden" name="version" x-model="caseData.version">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                                <input type="text" x-model="caseData.patient_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lab Name</label>
                                <input type="text" x-model="caseData.lab_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input type="date" x-model="caseData.due_date" class="block w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select x-model="caseData.status" class="block w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="new">New</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="done">Done</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="canceled">Canceled</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea x-model="caseData.notes" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="editing = false" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                Save Changes
                            </button>
                        </div>
                    </form>

                    <dl x-show="!editing" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Patient Name</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($case['patient_name'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Lab Name</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($case['lab_name'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Patient Number</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($case['patient_number'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Lab Number</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($case['lab_number'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                            <dd class="mt-1 text-sm <?= $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-900' ?>">
                                <?= $case['due_date'] ? date('M d, Y', strtotime($case['due_date'])) : '-' ?>
                                <?php if ($isOverdue): ?>
                                    <span class="ml-2 text-xs">⚠️ OVERDUE</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">PAN #</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($case['pan'] ?? '-') ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Source</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= ucfirst(str_replace('_', ' ', $case['source'])) ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= date('M d, Y H:i', strtotime($case['created_at'])) ?></dd>
                        </div>
                        <?php if ($case['instructions']): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Instructions</dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($case['instructions']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($case['notes']): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Notes</dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($case['notes']) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- Case Items -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Work Items</h2>

                    <?php if (empty($case['items'])): ?>
                        <p class="text-gray-500 text-center py-8">No work items yet</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($case['items'] as $item): ?>
                                <?php
                                $itemStatusColors = [
                                    'pending' => 'bg-gray-100 text-gray-800',
                                    'assigned' => 'bg-blue-100 text-blue-800',
                                    'working' => 'bg-yellow-100 text-yellow-800',
                                    'done' => 'bg-green-100 text-green-800',
                                    'remake' => 'bg-orange-100 text-orange-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                $itemStatusColor = $itemStatusColors[$item['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="font-semibold text-gray-900"><?= htmlspecialchars($item['work_type']) ?></span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $itemStatusColor ?>">
                                                    <?= ucfirst($item['status']) ?>
                                                </span>
                                                <?php if ($item['count']): ?>
                                                    <span class="text-sm text-gray-500">× <?= $item['count'] ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($item['tooth_no']): ?>
                                                <p class="text-sm text-gray-600">Teeth: <?= htmlspecialchars($item['tooth_no']) ?></p>
                                            <?php endif; ?>

                                            <?php if ($item['assigned_to_name']): ?>
                                                <p class="text-sm text-gray-600">Assigned to: <?= htmlspecialchars($item['assigned_to_name']) ?></p>
                                            <?php endif; ?>

                                            <?php if ($item['instruction']): ?>
                                                <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($item['instruction']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin', 'manager'])): ?>
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <?php if ($case['status'] !== 'archived'): ?>
                                <form method="POST" action="/cases/<?= $case['id'] ?>/archive">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">
                                    <input type="hidden" name="version" value="<?= $case['version'] ?>">
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md border border-gray-300">
                                        Archive Case
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin'])): ?>
                                <form method="POST" action="/cases/<?= $case['id'] ?>/delete" onsubmit="return confirm('Are you sure you want to delete this case? This cannot be undone.')">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md border border-red-300">
                                        Delete Case
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Audit Log -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Activity Log</h3>
                    <?php if (empty($audit_logs)): ?>
                        <p class="text-gray-500 text-sm">No activity yet</p>
                    <?php else: ?>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php foreach ($audit_logs as $log): ?>
                                <div class="text-sm border-l-2 border-gray-300 pl-3 pb-3">
                                    <div class="font-medium text-gray-900">
                                        <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?> •
                                        <?= date('M d, Y H:i', strtotime($log['created_at'])) ?>
                                    </div>
                                    <?php if ($log['field_name']): ?>
                                        <div class="text-xs text-gray-600 mt-1">
                                            <?= ucfirst(str_replace('_', ' ', $log['field_name'])) ?>:
                                            <?= htmlspecialchars($log['old_value'] ?? 'null') ?> → <?= htmlspecialchars($log['new_value'] ?? 'null') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function caseDetail(initialCase) {
    return {
        editing: false,
        caseData: { ...initialCase },

        async saveCase() {
            try {
                const response = await apiRequest('/api/cases/<?= $case['id'] ?>', {
                    method: 'POST',
                    body: JSON.stringify(this.caseData)
                });

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'Case updated successfully', type: 'success' }
                }));

                // Refresh page to show updated data
                window.location.reload();

            } catch (error) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: error.message, type: 'error' }
                }));

                if (error.message.includes('Concurrent modification')) {
                    window.location.reload();
                }
            }
        }
    }
}
</script>
