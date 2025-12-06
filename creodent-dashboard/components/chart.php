<?php
/**
 * Creodent Dashboard - Chart Component
 *
 * Usage:
 * renderChart([
 *     'id' => 'sales-trend',
 *     'title' => 'Monthly Sales Trend',
 *     'type' => 'line', // line, bar, pie, doughnut
 *     'height' => 300,
 *     'data' => [...] // Chart.js data format
 * ]);
 */

function renderChart(array $options): void {
    $id = $options['id'] ?? 'chart-' . uniqid();
    $title = $options['title'] ?? '';
    $type = $options['type'] ?? 'line';
    $height = $options['height'] ?? 300;
    $data = $options['data'] ?? [];
    $chartOptions = $options['options'] ?? [];

    $defaultOptions = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'position' => 'bottom',
                'labels' => [
                    'usePointStyle' => true,
                    'padding' => 20
                ]
            ]
        ]
    ];

    if ($type === 'line') {
        $defaultOptions['scales'] = [
            'y' => [
                'beginAtZero' => true,
                'grid' => ['color' => 'rgba(0, 0, 0, 0.05)'],
                'ticks' => [
                    'callback' => "function(value) { return '$' + value.toLocaleString(); }"
                ]
            ],
            'x' => [
                'grid' => ['display' => false]
            ]
        ];
        $defaultOptions['elements'] = [
            'line' => ['tension' => 0.3],
            'point' => ['radius' => 4, 'hoverRadius' => 6]
        ];
    } elseif ($type === 'bar') {
        $defaultOptions['scales'] = [
            'y' => [
                'beginAtZero' => true,
                'grid' => ['color' => 'rgba(0, 0, 0, 0.05)']
            ],
            'x' => [
                'grid' => ['display' => false]
            ]
        ];
    }

    $finalOptions = array_replace_recursive($defaultOptions, $chartOptions);
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <?php if ($title): ?>
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?= htmlspecialchars($title) ?></h3>
        <?php endif; ?>
        <div style="height: <?= $height ?>px;">
            <canvas id="<?= htmlspecialchars($id) ?>"></canvas>
        </div>
    </div>
    <script>
    (function() {
        const ctx = document.getElementById('<?= htmlspecialchars($id) ?>');
        new Chart(ctx, {
            type: '<?= $type ?>',
            data: <?= json_encode($data) ?>,
            options: <?= json_encode($finalOptions, JSON_UNESCAPED_SLASHES) ?>
        });
    })();
    </script>
    <?php
}

/**
 * Generate chart colors
 */
function getChartColors(int $count = 10): array {
    $colors = [
        '#3b82f6', // blue
        '#10b981', // emerald
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // purple
        '#06b6d4', // cyan
        '#f97316', // orange
        '#84cc16', // lime
        '#ec4899', // pink
        '#6366f1', // indigo
    ];

    while (count($colors) < $count) {
        $colors = array_merge($colors, $colors);
    }

    return array_slice($colors, 0, $count);
}

/**
 * Build dataset for line chart
 */
function buildLineDataset(string $label, array $data, string $color): array {
    return [
        'label' => $label,
        'data' => $data,
        'borderColor' => $color,
        'backgroundColor' => $color . '20',
        'fill' => true,
        'tension' => 0.3
    ];
}

/**
 * Build dataset for bar chart
 */
function buildBarDataset(string $label, array $data, string $color): array {
    return [
        'label' => $label,
        'data' => $data,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'borderWidth' => 0,
        'borderRadius' => 4
    ];
}

/**
 * Build horizontal bar chart data
 */
function buildHorizontalBarData(array $labels, array $data, array $colors = null): array {
    if (!$colors) {
        $colors = getChartColors(count($data));
    }

    return [
        'labels' => $labels,
        'datasets' => [[
            'data' => $data,
            'backgroundColor' => $colors,
            'borderWidth' => 0,
            'borderRadius' => 4
        ]]
    ];
}

/**
 * Build pie/doughnut chart data
 */
function buildPieData(array $labels, array $data, array $colors = null): array {
    if (!$colors) {
        $colors = getChartColors(count($data));
    }

    return [
        'labels' => $labels,
        'datasets' => [[
            'data' => $data,
            'backgroundColor' => $colors,
            'borderWidth' => 2,
            'borderColor' => '#ffffff'
        ]]
    ];
}
