<?php
/**
 * Mandal-Art Page
 * 만다라트 목표 설정 및 관리
 */

$pageTitle = '만다라트';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Goal.php';

Session::requireAuth();

$goal = new Goal();
$user = Session::getUser();

// Get user's core goals
$coreGoals = $goal->getUserCoreGoals($user->getId());
$selectedGoal = null;
$mandalart = null;

// Check if specific goal selected
if (isset($_GET['goal'])) {
    $selectedGoal = $goal->getCoreGoal($_GET['goal'], $user->getId());
    if ($selectedGoal) {
        $mandalart = $goal->getFullMandalart($selectedGoal['id']);
    }
} elseif (!empty($coreGoals)) {
    $selectedGoal = $coreGoals[0];
    $mandalart = $goal->getFullMandalart($selectedGoal['id']);
}

// Prepare sub goals array indexed by position
$subGoalsByPosition = [];
if ($mandalart) {
    foreach ($mandalart['sub_goals'] as $sg) {
        $subGoalsByPosition[$sg['position']] = $sg;
    }
}

// Colors for positions
$positionColors = [
    1 => '#ef4444', 2 => '#f97316', 3 => '#eab308',
    4 => '#22c55e', 5 => '#06b6d4', 6 => '#3b82f6',
    7 => '#8b5cf6', 8 => '#ec4899'
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">만다라트 목표 관리</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">9×9 격자로 목표를 체계적으로 세분화하세요</p>
        </div>

        <div class="flex items-center gap-3">
            <?php if (!empty($coreGoals)): ?>
            <select id="goalSelector" onchange="selectGoal(this.value)"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                <?php foreach ($coreGoals as $cg): ?>
                <option value="<?= $cg['id'] ?>" <?= ($selectedGoal && $selectedGoal['id'] == $cg['id']) ? 'selected' : '' ?>>
                    <?= e($cg['title']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <button onclick="openNewGoalModal()"
                    class="px-4 py-2 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                새 목표
            </button>
        </div>
    </div>

    <?php if ($selectedGoal && $mandalart): ?>
    <!-- Goal Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">🎯</span>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?= e($selectedGoal['title']) ?></h2>
                        <?php if ($selectedGoal['description']): ?>
                        <p class="text-gray-600 dark:text-gray-400 mt-1"><?= e($selectedGoal['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Progress -->
                <div class="text-center">
                    <div class="relative w-16 h-16">
                        <svg class="w-16 h-16 transform -rotate-90">
                            <circle cx="32" cy="32" r="28" stroke="#e5e7eb" stroke-width="6" fill="none"/>
                            <circle cx="32" cy="32" r="28" stroke="url(#progressGradient)" stroke-width="6" fill="none"
                                    stroke-dasharray="175.93"
                                    stroke-dashoffset="<?= 175.93 - (175.93 * $selectedGoal['progress_percent'] / 100) ?>"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-sm font-bold text-gray-900 dark:text-white"><?= $selectedGoal['progress_percent'] ?>%</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">진행률</p>
                </div>

                <!-- Target Date -->
                <?php if ($selectedGoal['target_date']): ?>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary-600">
                        <?php
                        $remaining = (strtotime($selectedGoal['target_date']) - time()) / 86400;
                        echo max(0, ceil($remaining));
                        ?>
                    </div>
                    <p class="text-xs text-gray-500">남은 일수</p>
                </div>
                <?php endif; ?>

                <button onclick="editCoreGoal(<?= $selectedGoal['id'] ?>)"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mandal-Art Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">만다라트 차트</h3>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <span class="w-3 h-3 bg-primary-500 rounded-full"></span>
                <span>핵심 목표</span>
                <span class="w-3 h-3 bg-purple-500 rounded-full ml-4"></span>
                <span>세부 목표 (8개)</span>
                <span class="w-3 h-3 bg-gray-300 rounded-full ml-4"></span>
                <span>실행 과제 (64개)</span>
            </div>
        </div>

        <!-- 9x9 Grid -->
        <div class="grid grid-cols-9 gap-1 md:gap-2">
            <?php
            // Generate 9x9 grid
            // Center (4,4) is core goal
            // Positions 1-8 are at specific locations
            // Each subgoal expands into its 3x3 area

            $positionMap = [
                // row => [col => position or 'center']
                0 => [0 => [1,0], 1 => [1,1], 2 => [1,2], 3 => [2,0], 4 => [2,1], 5 => [2,2], 6 => [3,0], 7 => [3,1], 8 => [3,2]],
                1 => [0 => [1,3], 1 => [1,4], 2 => [1,5], 3 => [2,3], 4 => [2,4], 5 => [2,5], 6 => [3,3], 7 => [3,4], 8 => [3,5]],
                2 => [0 => [1,6], 1 => [1,7], 2 => [1,8], 3 => [2,6], 4 => [2,7], 5 => [2,8], 6 => [3,6], 7 => [3,7], 8 => [3,8]],
                3 => [0 => [4,0], 1 => [4,1], 2 => [4,2], 3 => ['sub',1], 4 => ['sub',2], 5 => ['sub',3], 6 => [5,0], 7 => [5,1], 8 => [5,2]],
                4 => [0 => [4,3], 1 => [4,4], 2 => [4,5], 3 => ['sub',8], 4 => ['center'], 5 => ['sub',4], 6 => [5,3], 7 => [5,4], 8 => [5,5]],
                5 => [0 => [4,6], 1 => [4,7], 2 => [4,8], 3 => ['sub',7], 4 => ['sub',6], 5 => ['sub',5], 6 => [5,6], 7 => [5,7], 8 => [5,8]],
                6 => [0 => [6,0], 1 => [6,1], 2 => [6,2], 3 => [7,0], 4 => [7,1], 5 => [7,2], 6 => [8,0], 7 => [8,1], 8 => [8,2]],
                7 => [0 => [6,3], 1 => [6,4], 2 => [6,5], 3 => [7,3], 4 => [7,4], 5 => [7,5], 6 => [8,3], 7 => [8,4], 8 => [8,5]],
                8 => [0 => [6,6], 1 => [6,7], 2 => [6,8], 3 => [7,6], 4 => [7,7], 5 => [7,8], 6 => [8,6], 7 => [8,7], 8 => [8,8]],
            ];

            // Subgoal positions in center area (3,3) to (5,5)
            // Position 1-8 around center
            $subPositions = [
                1 => [3,3], 2 => [3,4], 3 => [3,5],
                8 => [4,3], 4 => [4,5],
                7 => [5,3], 6 => [5,4], 5 => [5,5]
            ];

            // Subgoal task area mapping
            $subTaskAreas = [
                1 => ['startRow' => 0, 'startCol' => 0],
                2 => ['startRow' => 0, 'startCol' => 3],
                3 => ['startRow' => 0, 'startCol' => 6],
                4 => ['startRow' => 3, 'startCol' => 6],
                5 => ['startRow' => 6, 'startCol' => 6],
                6 => ['startRow' => 6, 'startCol' => 3],
                7 => ['startRow' => 6, 'startCol' => 0],
                8 => ['startRow' => 3, 'startCol' => 0]
            ];

            for ($row = 0; $row < 9; $row++):
                for ($col = 0; $col < 9; $col++):
                    $cellType = '';
                    $cellContent = '';
                    $cellColor = '#f3f4f6';
                    $subGoalId = null;
                    $taskId = null;
                    $position = null;

                    // Determine cell type
                    if ($row == 4 && $col == 4) {
                        // Center - Core Goal
                        $cellType = 'core';
                        $cellContent = $selectedGoal['title'];
                        $cellColor = '#6366f1';
                    } elseif ($row >= 3 && $row <= 5 && $col >= 3 && $col <= 5) {
                        // Center 3x3 area - Sub Goals
                        foreach ($subPositions as $pos => $coords) {
                            if ($coords[0] == $row && $coords[1] == $col) {
                                $cellType = 'sub';
                                $position = $pos;
                                if (isset($subGoalsByPosition[$pos])) {
                                    $cellContent = $subGoalsByPosition[$pos]['title'];
                                    $cellColor = $subGoalsByPosition[$pos]['color'] ?? $positionColors[$pos];
                                    $subGoalId = $subGoalsByPosition[$pos]['id'];
                                } else {
                                    $cellColor = $positionColors[$pos];
                                }
                                break;
                            }
                        }
                    } else {
                        // Task cells
                        $cellType = 'task';
                        // Find which subgoal area this belongs to
                        foreach ($subTaskAreas as $subPos => $area) {
                            if ($row >= $area['startRow'] && $row < $area['startRow'] + 3 &&
                                $col >= $area['startCol'] && $col < $area['startCol'] + 3) {

                                $localRow = $row - $area['startRow'];
                                $localCol = $col - $area['startCol'];
                                $taskPosition = $localRow * 3 + $localCol + 1;

                                // Skip center of task area (that's where subgoal title goes in expanded view)
                                if ($localRow == 1 && $localCol == 1) {
                                    // This is subgoal position in task area
                                    $cellType = 'sub-mirror';
                                    $position = $subPos;
                                    if (isset($subGoalsByPosition[$subPos])) {
                                        $cellContent = $subGoalsByPosition[$subPos]['title'];
                                        $cellColor = $subGoalsByPosition[$subPos]['color'] ?? $positionColors[$subPos];
                                        $subGoalId = $subGoalsByPosition[$subPos]['id'];
                                    } else {
                                        $cellColor = $positionColors[$subPos];
                                    }
                                } else {
                                    // Adjust task position (skip center)
                                    if ($taskPosition > 5) $taskPosition--;
                                    $position = $taskPosition;

                                    if (isset($subGoalsByPosition[$subPos])) {
                                        $tasks = $subGoalsByPosition[$subPos]['tasks'] ?? [];
                                        foreach ($tasks as $task) {
                                            if ($task['position'] == $taskPosition) {
                                                $cellContent = $task['title'];
                                                $taskId = $task['id'];
                                                break;
                                            }
                                        }
                                        $subGoalId = $subGoalsByPosition[$subPos]['id'];
                                        $cellColor = ($subGoalsByPosition[$subPos]['color'] ?? $positionColors[$subPos]) . '33';
                                    }
                                }
                                break;
                            }
                        }
                    }
            ?>
            <div class="mandal-cell aspect-square rounded-lg p-1 md:p-2 flex items-center justify-center text-center cursor-pointer transition-all hover:shadow-lg <?= $cellType === 'core' ? 'col-span-1' : '' ?>"
                 style="background-color: <?= $cellType === 'core' ? $cellColor : ($cellType === 'sub' || $cellType === 'sub-mirror' ? $cellColor : ($cellContent ? $cellColor : '#f9fafb')) ?>;"
                 onclick="<?= $cellType === 'core' ? "editCoreGoal({$selectedGoal['id']})" : ($cellType === 'sub' || $cellType === 'sub-mirror' ? ($subGoalId ? "editSubGoal($subGoalId)" : "addSubGoal($position)") : ($subGoalId ? ($taskId ? "editTask($taskId)" : "addTask($subGoalId, $position)") : "")) ?>"
                 title="<?= e($cellContent ?: ($cellType === 'task' ? '실행 과제 추가' : ($cellType === 'sub' ? '세부 목표 추가' : ''))) ?>">
                <span class="text-[10px] md:text-xs font-medium <?= $cellType === 'core' || $cellType === 'sub' || $cellType === 'sub-mirror' ? 'text-white' : 'text-gray-700 dark:text-gray-300' ?> line-clamp-2 md:line-clamp-3">
                    <?php if ($cellContent): ?>
                        <?= e(mb_substr($cellContent, 0, 20)) ?><?= mb_strlen($cellContent) > 20 ? '...' : '' ?>
                    <?php elseif ($cellType === 'sub'): ?>
                        <span class="opacity-50">+</span>
                    <?php elseif ($cellType === 'task' && $subGoalId): ?>
                        <span class="opacity-30">+</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php
                endfor;
            endfor;
            ?>
        </div>

        <!-- Legend -->
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-primary-500"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">핵심 목표</span>
                </div>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded" style="background-color: <?= $positionColors[$i] ?>"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <?= isset($subGoalsByPosition[$i]) ? e($subGoalsByPosition[$i]['title']) : "세부 목표 $i" ?>
                    </span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Sub Goals Cards -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php for ($i = 1; $i <= 8; $i++): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border-l-4" style="border-color: <?= $positionColors[$i] ?>">
            <?php if (isset($subGoalsByPosition[$i])): ?>
                <?php $sg = $subGoalsByPosition[$i]; ?>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white"><?= e($sg['title']) ?></h4>
                        <p class="text-sm text-gray-500 mt-1"><?= count($sg['tasks'] ?? []) ?>개 실행 과제</p>
                    </div>
                    <button onclick="editSubGoal(<?= $sg['id'] ?>)" class="p-1 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
                <div class="mt-3 space-y-2">
                    <?php foreach (($sg['tasks'] ?? []) as $task): ?>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full" style="background-color: <?= $positionColors[$i] ?>"></span>
                        <span class="text-gray-700 dark:text-gray-300 truncate"><?= e($task['title']) ?></span>
                        <?php if ($task['priority'] === 'high' || $task['priority'] === 'critical'): ?>
                        <span class="ml-auto text-xs px-1.5 py-0.5 bg-red-100 text-red-700 rounded">중요</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($sg['tasks'] ?? []) < 8): ?>
                <button onclick="addTask(<?= $sg['id'] ?>, <?= count($sg['tasks'] ?? []) + 1 ?>)"
                        class="mt-3 text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    과제 추가
                </button>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">세부 목표 <?= $i ?></p>
                    <button onclick="addSubGoal(<?= $i ?>)"
                            class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        + 추가하기
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>

    <?php else: ?>
    <!-- No Goals State -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-12 text-center">
        <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-primary-100 to-purple-100 dark:from-primary-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center">
            <span class="text-4xl">🎯</span>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">첫 번째 목표를 설정해보세요</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
            만다라트 기법으로 당신의 꿈을 64개의 구체적인 실행 과제로 만들어보세요.
            오타니 쇼헤이 선수도 이 방법으로 MLB 드래프트 1순위라는 꿈을 이루었습니다.
        </p>
        <button onclick="openNewGoalModal()"
                class="px-6 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
            핵심 목표 설정하기
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- SVG Gradient Definition -->
<svg class="hidden">
    <defs>
        <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#6366f1"/>
            <stop offset="100%" style="stop-color:#a855f7"/>
        </linearGradient>
    </defs>
</svg>

<!-- New Goal Modal -->
<div id="newGoalModal" class="fixed inset-0 z-50 hidden" x-data="{ show: false }">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeNewGoalModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg transform transition-all">
            <form id="newGoalForm" onsubmit="saveNewGoal(event)">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">새 핵심 목표 설정</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                핵심 목표 *
                            </label>
                            <input type="text" name="title" required
                                   placeholder="예: 영어 책 한 권 출간하기"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                            <p class="mt-1 text-xs text-gray-500">당신이 이루고 싶은 가장 중요한 목표를 적어주세요</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                설명 (선택)
                            </label>
                            <textarea name="description" rows="3"
                                      placeholder="목표에 대한 부가 설명을 적어주세요"
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                목표 달성 기한 (선택)
                            </label>
                            <input type="date" name="target_date"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" onclick="closeNewGoalModal()"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                        취소
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                        만들기
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sub Goal Modal -->
<div id="subGoalModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSubGoalModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg">
            <form id="subGoalForm" onsubmit="saveSubGoal(event)">
                <input type="hidden" name="id" id="subGoalId">
                <input type="hidden" name="position" id="subGoalPosition">

                <div class="p-6">
                    <h3 id="subGoalModalTitle" class="text-xl font-bold text-gray-900 dark:text-white mb-6">세부 목표</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                세부 목표명 *
                            </label>
                            <input type="text" name="title" id="subGoalTitle" required
                                   placeholder="예: 글쓰기 실력 향상"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                색상
                            </label>
                            <div class="flex gap-2">
                                <?php foreach ($positionColors as $color): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="color" value="<?= $color ?>" class="hidden peer">
                                    <div class="w-8 h-8 rounded-lg peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-primary-500 transition-all"
                                         style="background-color: <?= $color ?>"></div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl flex justify-between">
                    <button type="button" id="deleteSubGoalBtn" onclick="deleteSubGoal()" class="hidden px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                        삭제
                    </button>
                    <div class="flex gap-3 ml-auto">
                        <button type="button" onclick="closeSubGoalModal()"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                            취소
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                            저장
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Task Modal -->
<div id="taskModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeTaskModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg">
            <form id="taskForm" onsubmit="saveTask(event)">
                <input type="hidden" name="id" id="taskId">
                <input type="hidden" name="sub_goal_id" id="taskSubGoalId">
                <input type="hidden" name="position" id="taskPosition">

                <div class="p-6">
                    <h3 id="taskModalTitle" class="text-xl font-bold text-gray-900 dark:text-white mb-6">실행 과제</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                과제명 *
                            </label>
                            <input type="text" name="title" id="taskTitle" required
                                   placeholder="예: 블로그 주 3회 글쓰기"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    유형
                                </label>
                                <select name="task_type" id="taskType"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    <option value="habit">습관 (반복)</option>
                                    <option value="one_time">일회성</option>
                                    <option value="milestone">마일스톤</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    빈도
                                </label>
                                <select name="frequency" id="taskFrequency"
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    <option value="daily">매일</option>
                                    <option value="weekly">매주</option>
                                    <option value="monthly">매월</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                우선순위
                            </label>
                            <div class="flex gap-2">
                                <label class="flex-1">
                                    <input type="radio" name="priority" value="low" class="hidden peer">
                                    <div class="px-4 py-2 text-center border border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 cursor-pointer transition-all">
                                        <span class="text-sm">낮음</span>
                                    </div>
                                </label>
                                <label class="flex-1">
                                    <input type="radio" name="priority" value="medium" class="hidden peer" checked>
                                    <div class="px-4 py-2 text-center border border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-yellow-500 peer-checked:bg-yellow-50 dark:peer-checked:bg-yellow-900/30 cursor-pointer transition-all">
                                        <span class="text-sm">보통</span>
                                    </div>
                                </label>
                                <label class="flex-1">
                                    <input type="radio" name="priority" value="high" class="hidden peer">
                                    <div class="px-4 py-2 text-center border border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/30 cursor-pointer transition-all">
                                        <span class="text-sm">높음</span>
                                    </div>
                                </label>
                                <label class="flex-1">
                                    <input type="radio" name="priority" value="critical" class="hidden peer">
                                    <div class="px-4 py-2 text-center border border-gray-300 dark:border-gray-600 rounded-lg peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/30 cursor-pointer transition-all">
                                        <span class="text-sm">중요</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                포인트 가치
                            </label>
                            <input type="number" name="points_value" id="taskPoints" value="10" min="1" max="100"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl flex justify-between">
                    <button type="button" id="deleteTaskBtn" onclick="deleteTask()" class="hidden px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                        삭제
                    </button>
                    <div class="flex gap-3 ml-auto">
                        <button type="button" onclick="closeTaskModal()"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                            취소
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                            저장
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const currentGoalId = <?= $selectedGoal ? $selectedGoal['id'] : 'null' ?>;

function selectGoal(goalId) {
    window.location.href = `/mandalart.php?goal=${goalId}`;
}

// New Goal Modal
function openNewGoalModal() {
    document.getElementById('newGoalModal').classList.remove('hidden');
    document.getElementById('newGoalForm').reset();
}

function closeNewGoalModal() {
    document.getElementById('newGoalModal').classList.add('hidden');
}

async function saveNewGoal(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const response = await apiRequest('/api/goals.php', 'POST', {
        action: 'create_core_goal',
        title: formData.get('title'),
        description: formData.get('description'),
        target_date: formData.get('target_date')
    });

    if (response.success) {
        window.location.href = `/mandalart.php?goal=${response.goal_id}`;
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

function editCoreGoal(goalId) {
    // For simplicity, redirect to edit or open modal
    showToast('핵심 목표 편집 기능 준비 중', 'info');
}

// Sub Goal Modal
function addSubGoal(position) {
    document.getElementById('subGoalModalTitle').textContent = `세부 목표 ${position} 추가`;
    document.getElementById('subGoalId').value = '';
    document.getElementById('subGoalPosition').value = position;
    document.getElementById('subGoalTitle').value = '';
    document.getElementById('deleteSubGoalBtn').classList.add('hidden');

    // Set default color
    const colorRadios = document.querySelectorAll('input[name="color"]');
    colorRadios.forEach((radio, idx) => {
        radio.checked = idx === (position - 1);
    });

    document.getElementById('subGoalModal').classList.remove('hidden');
}

function editSubGoal(subGoalId) {
    // Fetch sub goal data and open modal
    apiRequest(`/api/goals.php?action=get_sub_goal&id=${subGoalId}`).then(response => {
        if (response.success) {
            const sg = response.sub_goal;
            document.getElementById('subGoalModalTitle').textContent = '세부 목표 편집';
            document.getElementById('subGoalId').value = sg.id;
            document.getElementById('subGoalPosition').value = sg.position;
            document.getElementById('subGoalTitle').value = sg.title;
            document.getElementById('deleteSubGoalBtn').classList.remove('hidden');

            // Set color
            const colorRadios = document.querySelectorAll('input[name="color"]');
            colorRadios.forEach(radio => {
                radio.checked = radio.value === sg.color;
            });

            document.getElementById('subGoalModal').classList.remove('hidden');
        }
    });
}

function closeSubGoalModal() {
    document.getElementById('subGoalModal').classList.add('hidden');
}

async function saveSubGoal(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const subGoalId = formData.get('id');

    const data = {
        action: subGoalId ? 'update_sub_goal' : 'create_sub_goal',
        core_goal_id: currentGoalId,
        position: formData.get('position'),
        title: formData.get('title'),
        color: formData.get('color')
    };

    if (subGoalId) {
        data.id = subGoalId;
    }

    const response = await apiRequest('/api/goals.php', 'POST', data);

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

async function deleteSubGoal() {
    if (!confirm('이 세부 목표와 관련된 모든 과제가 삭제됩니다. 계속하시겠습니까?')) return;

    const subGoalId = document.getElementById('subGoalId').value;
    const response = await apiRequest('/api/goals.php', 'POST', {
        action: 'delete_sub_goal',
        id: subGoalId
    });

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

// Task Modal
function addTask(subGoalId, position) {
    document.getElementById('taskModalTitle').textContent = '실행 과제 추가';
    document.getElementById('taskId').value = '';
    document.getElementById('taskSubGoalId').value = subGoalId;
    document.getElementById('taskPosition').value = position;
    document.getElementById('taskForm').reset();
    document.getElementById('taskSubGoalId').value = subGoalId;
    document.getElementById('taskPosition').value = position;
    document.getElementById('deleteTaskBtn').classList.add('hidden');

    document.getElementById('taskModal').classList.remove('hidden');
}

function editTask(taskId) {
    apiRequest(`/api/goals.php?action=get_task&id=${taskId}`).then(response => {
        if (response.success) {
            const task = response.task;
            document.getElementById('taskModalTitle').textContent = '실행 과제 편집';
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskSubGoalId').value = task.sub_goal_id;
            document.getElementById('taskPosition').value = task.position;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskType').value = task.task_type;
            document.getElementById('taskFrequency').value = task.frequency;
            document.getElementById('taskPoints').value = task.points_value;
            document.getElementById('deleteTaskBtn').classList.remove('hidden');

            // Set priority
            const priorityRadios = document.querySelectorAll('input[name="priority"]');
            priorityRadios.forEach(radio => {
                radio.checked = radio.value === task.priority;
            });

            document.getElementById('taskModal').classList.remove('hidden');
        }
    });
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
}

async function saveTask(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const taskId = formData.get('id');

    const data = {
        action: taskId ? 'update_task' : 'create_task',
        sub_goal_id: formData.get('sub_goal_id'),
        position: formData.get('position'),
        title: formData.get('title'),
        task_type: formData.get('task_type'),
        frequency: formData.get('frequency'),
        priority: formData.get('priority'),
        points_value: formData.get('points_value')
    };

    if (taskId) {
        data.id = taskId;
    }

    const response = await apiRequest('/api/goals.php', 'POST', data);

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

async function deleteTask() {
    if (!confirm('이 실행 과제를 삭제하시겠습니까?')) return;

    const taskId = document.getElementById('taskId').value;
    const response = await apiRequest('/api/goals.php', 'POST', {
        action: 'delete_task',
        id: taskId
    });

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
