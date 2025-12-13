<?php
/**
 * Case Detail View
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

auth()->requireLogin();

$caseId = (int) input('id');

if (!$caseId) {
    flash('error', 'Case not found.');
    redirect(url('/cases/index.php'));
}

$case = caseManager()->getCase($caseId);

if (!$case) {
    flash('error', 'Case not found.');
    redirect(url('/cases/index.php'));
}

$pageTitle = 'Case #' . $case['case_number'];
$pageDescription = $case['lab_name'] . ' - ' . ($case['patient_name'] ?? 'No patient name');

// Get timeline
$timeline = caseManager()->getCaseTimeline($caseId);

// Get files
$files = drive()->getCaseFiles($caseId);

// Get note tags
$noteTags = caseManager()->getNoteTags();

// Get email history if design confirm required
$emailHistory = [];
if ($case['design_confirm_required']) {
    $emailHistory = gmail()->getEmailHistory($caseId);
}

ob_start();
?>

<!-- Case Header -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <?= e($case['case_number']) ?>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded text-sm font-medium bg-gray-100 text-gray-800">
                        <?= e($case['site']) ?>
                    </span>
                    <?php if ($case['combo']): ?>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded text-sm font-medium bg-purple-100 text-purple-800">
                            COMBO
                        </span>
                    <?php endif; ?>
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    <?= e($case['lab_name']) ?>
                    <?php if ($case['patient_name']): ?>
                        | Patient: <?= e($case['patient_name']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= statusBadgeClass($case['status']) ?>">
                    <?= e($case['status']) ?>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= dueDateClass($case['due_date']) ?>">
                    Due: <?= formatDate($case['due_date']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Quick Info -->
    <div class="px-4 py-5 sm:px-6">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Type</dt>
                <dd class="text-sm text-gray-900"><?= $case['ld_type'] === 'L' ? 'Lab Case' : 'Doctor Case' ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Teeth</dt>
                <dd class="text-sm text-gray-900"><?= e($case['tooth_numbers'] ?: '-') ?> (<?= $case['tooth_count'] ?> total)</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="text-sm text-gray-900"><?= formatDateTime($case['created_timestamp']) ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Design Confirm</dt>
                <dd class="text-sm">
                    <?php if ($case['design_confirm_required']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            <?php
                            $dcStatus = $case['design_confirm_status'];
                            echo match($dcStatus) {
                                'PENDING' => 'bg-yellow-100 text-yellow-800',
                                'SENT' => 'bg-blue-100 text-blue-800',
                                'APPROVED' => 'bg-green-100 text-green-800',
                                'CHANGES_REQUESTED' => 'bg-orange-100 text-orange-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= e($dcStatus) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-gray-500">Not Required</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </div>
</div>

<!-- Tabs -->
<div x-data="{ activeTab: 'overview' }" class="bg-white shadow rounded-lg">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-4" aria-label="Tabs">
            <button @click="activeTab = 'overview'"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'overview' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Overview
            </button>
            <?php if ($case['has_cocr']): ?>
                <button @click="activeTab = 'cocr'"
                        :class="{ 'border-blue-500 text-blue-600': activeTab === 'cocr', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'cocr' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    COCR
                </button>
            <?php endif; ?>
            <?php if ($case['has_solidex']): ?>
                <button @click="activeTab = 'solidex'"
                        :class="{ 'border-emerald-500 text-emerald-600': activeTab === 'solidex', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'solidex' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    SOLIDEX
                </button>
            <?php endif; ?>
            <?php if ($case['has_3d_print']): ?>
                <button @click="activeTab = '3dprint'"
                        :class="{ 'border-violet-500 text-violet-600': activeTab === '3dprint', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== '3dprint' }"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    3D Print
                </button>
            <?php endif; ?>
            <button @click="activeTab = 'files'"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'files', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'files' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Files (<?= count($files) ?>)
            </button>
            <button @click="activeTab = 'timeline'"
                    :class="{ 'border-blue-500 text-blue-600': activeTab === 'timeline', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'timeline' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Timeline
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Instructions</h4>
                    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                        <?= nl2br(e($case['instructions'] ?: 'No instructions provided.')) ?>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Preferences</h4>
                    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                        <?= nl2br(e($case['preferences'] ?: 'No preferences specified.')) ?>
                    </div>
                </div>
            </div>

            <!-- Department Status Summary -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Department Status</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php if ($case['has_cocr'] && !empty($case['cocr'])): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">COCR</span>
                                <?php if ($case['cocr']['is_on_hold']): ?>
                                    <span class="text-xs text-yellow-600 font-medium">ON HOLD</span>
                                <?php elseif ($case['cocr']['is_completed']): ?>
                                    <span class="text-xs text-green-600 font-medium">COMPLETED</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600">
                                Current Step: <strong><?= e($case['cocr']['current_step_code'] ?? 'N/A') ?></strong>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($case['has_solidex'] && !empty($case['solidex'])): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">SOLIDEX</span>
                                <?php if ($case['solidex']['is_completed']): ?>
                                    <span class="text-xs text-green-600 font-medium">COMPLETED</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600">
                                Progress: <strong><?= $case['solidex']['teeth_completed_count'] ?>/<?= $case['solidex']['teeth_count'] ?> teeth</strong>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($case['has_3d_print'] && !empty($case['print'])): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-800">3D PRINT</span>
                                <?php if ($case['print']['is_on_hold']): ?>
                                    <span class="text-xs text-yellow-600 font-medium">ON HOLD</span>
                                <?php elseif ($case['print']['is_completed']): ?>
                                    <span class="text-xs text-green-600 font-medium">COMPLETED</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600">
                                Current Step: <strong><?= e($case['print']['current_step_code'] ?? 'N/A') ?></strong>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- COCR Tab -->
        <?php if ($case['has_cocr'] && !empty($case['cocr'])): ?>
            <div x-show="activeTab === 'cocr'" x-cloak>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Job Type</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['job_type'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">COCR Type</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['cocr_type'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Disk Material</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['disk_material'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Model/IO/File</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['model_io_file'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Shade</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['shade'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Milling Shade</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['milling_shade'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Facial Cutback</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['facial_cutback'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Access Hole</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['cocr']['access_hole'] ?: '-') ?></dd>
                    </div>
                </dl>
                <?php if ($case['cocr']['note']): ?>
                    <div class="mt-4">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900 bg-gray-50 rounded p-3"><?= nl2br(e($case['cocr']['note'])) ?></dd>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- SOLIDEX Tab -->
        <?php if ($case['has_solidex'] && !empty($case['solidex'])): ?>
            <div x-show="activeTab === 'solidex'" x-cloak>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Job Type</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['solidex']['job_type'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Implant System</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['solidex']['implant_system'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">LOT Number</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['solidex']['lot_number'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Teeth Progress</dt>
                        <dd class="text-sm text-gray-900"><?= $case['solidex']['teeth_completed_count'] ?> / <?= $case['solidex']['teeth_count'] ?> complete</dd>
                    </div>
                </dl>

                <!-- Teeth Tasks Grid -->
                <?php if (!empty($case['solidex']['teeth'])): ?>
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Per-Tooth Status</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php foreach ($case['solidex']['teeth'] as $tooth): ?>
                            <div class="border rounded-lg p-3 <?= $tooth['is_completed'] ? 'bg-green-50 border-green-200' : ($tooth['is_on_hold'] ? 'bg-yellow-50 border-yellow-200' : 'bg-white') ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-gray-900">#<?= e($tooth['tooth_number']) ?></span>
                                    <?php if ($tooth['is_completed']): ?>
                                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php elseif ($tooth['is_on_hold']): ?>
                                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= stepBadgeClass($tooth['current_step_code']) ?>">
                                    <?= e($tooth['current_step_name'] ?? $tooth['current_step_code']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 3D Print Tab -->
        <?php if ($case['has_3d_print'] && !empty($case['print'])): ?>
            <div x-show="activeTab === '3dprint'" x-cloak>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Print Type</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['print']['print_type'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nesting Job</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['print']['nesting_job_name'] ?: '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Current Step</dt>
                        <dd class="text-sm text-gray-900"><?= e($case['print']['current_step_code'] ?: '-') ?></dd>
                    </div>
                </dl>
                <?php if ($case['print']['note']): ?>
                    <div class="mt-4">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900 bg-gray-50 rounded p-3"><?= nl2br(e($case['print']['note'])) ?></dd>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Files Tab -->
        <div x-show="activeTab === 'files'" x-cloak>
            <?php if (empty($files)): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <p class="mt-2 text-gray-500">No files uploaded yet.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($files as $file): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 <?= fileIconClass($file['file_name']) ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?= e($file['file_name']) ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?= e($file['department']) ?> | <?= formatFileSize($file['file_size'] ?? 0) ?>
                                    </p>
                                    <p class="text-xs text-gray-400"><?= formatDateTime($file['uploaded_at']) ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="<?= e($file['drive_file_url']) ?>" target="_blank"
                                   class="text-sm text-blue-600 hover:text-blue-500">
                                    Open in Drive
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Timeline Tab -->
        <div x-show="activeTab === 'timeline'" x-cloak>
            <?php if (empty($timeline)): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-2 text-gray-500">No activity recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="flow-root">
                    <ul class="-mb-8">
                        <?php foreach ($timeline as $index => $event): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($index !== count($timeline) - 1): ?>
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                    <?php endif; ?>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <?php if ($event['type'] === 'transition'): ?>
                                                <span class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                                    </svg>
                                                </span>
                                            <?php else: ?>
                                                <span class="h-8 w-8 rounded-full <?= $event['data']['action'] === 'HOLD' ? 'bg-yellow-100' : 'bg-green-100' ?> flex items-center justify-center">
                                                    <svg class="h-4 w-4 <?= $event['data']['action'] === 'HOLD' ? 'text-yellow-600' : 'text-green-600' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <?php if ($event['data']['action'] === 'HOLD'): ?>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        <?php else: ?>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                        <?php endif; ?>
                                                    </svg>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5">
                                            <div>
                                                <?php if ($event['type'] === 'transition'): ?>
                                                    <p class="text-sm text-gray-900">
                                                        <span class="font-medium"><?= e($event['data']['initials'] ?? 'System') ?></span>
                                                        completed <span class="font-medium"><?= e($event['data']['from_step_code']) ?></span>
                                                        → <span class="font-medium"><?= e($event['data']['to_step_code']) ?></span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= departmentBadgeClass($event['data']['department']) ?> ml-1">
                                                            <?= e($event['data']['department']) ?>
                                                        </span>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-sm text-gray-900">
                                                        <span class="font-medium"><?= e($event['data']['initials'] ?? 'System') ?></span>
                                                        <?= $event['data']['action'] === 'HOLD' ? 'placed on hold' : 'released from hold' ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= departmentBadgeClass($event['data']['department']) ?> ml-1">
                                                            <?= e($event['data']['department']) ?>
                                                        </span>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    <?= timeAgo($event['date']) ?>
                                                </p>
                                                <?php if (!empty($event['data']['notes']) || !empty($event['data']['reason'])): ?>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        <?= e($event['data']['notes'] ?? $event['data']['reason']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
