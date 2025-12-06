<?php
/**
 * Creodent Dashboard - Alert Component
 */

function renderAlert(string $message, string $type = 'info', bool $dismissible = true): void {
    $styles = match($type) {
        'success' => [
            'bg' => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'text' => 'text-emerald-700',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'iconColor' => 'text-emerald-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50',
            'border' => 'border-amber-200',
            'text' => 'text-amber-700',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
            'iconColor' => 'text-amber-500'
        ],
        'error' => [
            'bg' => 'bg-red-50',
            'border' => 'border-red-200',
            'text' => 'text-red-700',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'iconColor' => 'text-red-500'
        ],
        default => [
            'bg' => 'bg-blue-50',
            'border' => 'border-blue-200',
            'text' => 'text-blue-700',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            'iconColor' => 'text-blue-500'
        ]
    };
    ?>
    <div class="<?= $styles['bg'] ?> border <?= $styles['border'] ?> rounded-lg p-4 mb-6 flex items-start gap-3" role="alert">
        <svg class="w-5 h-5 <?= $styles['iconColor'] ?> mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <?= $styles['icon'] ?>
        </svg>
        <p class="flex-1 text-sm <?= $styles['text'] ?>"><?= htmlspecialchars($message) ?></p>
        <?php if ($dismissible): ?>
        <button onclick="this.parentElement.remove()" class="<?= $styles['text'] ?> hover:opacity-70">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render flash message from session
 */
function renderFlashMessages(): void {
    $types = ['success', 'error', 'warning', 'info'];

    foreach ($types as $type) {
        $message = \Creodent\Core\Session::getFlash($type);
        if ($message) {
            renderAlert($message, $type);
        }
    }
}
