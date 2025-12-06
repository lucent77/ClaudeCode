<?php
/**
 * Creodent Dashboard - Summary Card Component
 *
 * Usage:
 * renderCard([
 *     'title' => 'Total Sales',
 *     'value' => '$125,430',
 *     'change' => 12.5,
 *     'changeLabel' => 'vs last period',
 *     'icon' => 'currency-dollar',
 *     'color' => 'blue'
 * ]);
 */

function renderCard(array $options): void {
    $title = $options['title'] ?? 'Metric';
    $value = $options['value'] ?? '0';
    $change = $options['change'] ?? null;
    $changeLabel = $options['changeLabel'] ?? 'vs previous';
    $icon = $options['icon'] ?? 'chart-bar';
    $color = $options['color'] ?? 'blue';

    $colorClasses = match($color) {
        'green' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-600'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
        'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
        default => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
    };

    $icons = [
        'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'cube' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
        'exclamation' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    ];

    $iconPath = $icons[$icon] ?? $icons['chart-bar'];
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500"><?= htmlspecialchars($title) ?></p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($value) ?></p>
                <?php if ($change !== null): ?>
                <p class="text-sm mt-1 <?= $change >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                    <?= $change >= 0 ? '+' : '' ?><?= number_format($change, 1) ?>% <?= htmlspecialchars($changeLabel) ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="p-3 <?= $colorClasses['bg'] ?> rounded-lg">
                <svg class="w-6 h-6 <?= $colorClasses['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $iconPath ?>
                </svg>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render a row of summary cards
 */
function renderCardRow(array $cards): void {
    echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">';
    foreach ($cards as $card) {
        renderCard($card);
    }
    echo '</div>';
}
