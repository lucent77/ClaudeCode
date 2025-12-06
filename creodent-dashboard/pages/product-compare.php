<?php
/**
 * Creodent Dashboard - Product Comparison Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/ProductService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/chart.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\ProductService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Product Comparison';
$currentPage = 'product-compare';

$branch = Request::branch();
$dateRange = Request::dateRange();

$comparison = ProductService::compareProducts($branch, $dateRange['start_date'], $dateRange['end_date']);

$gainers = array_filter($comparison, fn($p) => $p['change_amount'] > 0);
$losers = array_filter($comparison, fn($p) => $p['change_amount'] < 0);

usort($gainers, fn($a, $b) => $b['change_amount'] <=> $a['change_amount']);
usort($losers, fn($a, $b) => $a['change_amount'] <=> $b['change_amount']);

$topGainers = array_slice($gainers, 0, 10);
$topLosers = array_slice($losers, 0, 10);

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Summary Cards -->
<?php
$totalGain = array_sum(array_column($gainers, 'change_amount'));
$totalLoss = abs(array_sum(array_column($losers, 'change_amount')));

renderCardRow([
    [
        'title' => 'Products Compared',
        'value' => number_format(count($comparison)),
        'icon' => 'cube',
        'color' => 'blue'
    ],
    [
        'title' => 'Growing Products',
        'value' => number_format(count($gainers)),
        'icon' => 'chart-bar',
        'color' => 'green'
    ],
    [
        'title' => 'Declining Products',
        'value' => number_format(count($losers)),
        'icon' => 'chart-bar',
        'color' => 'red'
    ],
    [
        'title' => 'Net Change',
        'value' => ($totalGain - $totalLoss >= 0 ? '+' : '') . CURRENCY_SYMBOL . number_format($totalGain - $totalLoss, 2),
        'icon' => 'currency-dollar',
        'color' => $totalGain >= $totalLoss ? 'green' : 'red'
    ]
]);
?>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php
    $gainerNames = array_column($topGainers, 'product_description');
    $gainerChanges = array_column($topGainers, 'change_amount');

    renderChart([
        'id' => 'top-gainers',
        'title' => 'Top 10 Growing Products',
        'type' => 'bar',
        'height' => 350,
        'data' => [
            'labels' => array_map(fn($n) => strlen($n) > 20 ? substr($n, 0, 17) . '...' : $n, $gainerNames),
            'datasets' => [buildBarDataset('Gain', $gainerChanges, '#10b981')]
        ],
        'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]]
    ]);

    $loserNames = array_column($topLosers, 'product_description');
    $loserChanges = array_map(fn($c) => abs($c), array_column($topLosers, 'change_amount'));

    renderChart([
        'id' => 'top-losers',
        'title' => 'Top 10 Declining Products',
        'type' => 'bar',
        'height' => 350,
        'data' => [
            'labels' => array_map(fn($n) => strlen($n) > 20 ? substr($n, 0, 17) . '...' : $n, $loserNames),
            'datasets' => [buildBarDataset('Loss', $loserChanges, '#ef4444')]
        ],
        'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]]
    ]);
    ?>
</div>

<!-- Comparison Table -->
<?php
renderTable([
    'title' => 'All Product Comparisons',
    'id' => 'comparison-table',
    'columns' => [
        ['key' => 'product_description', 'label' => 'Product', 'sortable' => true],
        ['key' => 'current_sales', 'label' => 'Current', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'previous_sales', 'label' => 'Previous', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'change_amount', 'label' => 'Change', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
            $color = $val >= 0 ? 'text-emerald-600' : 'text-red-600';
            return "<span class=\"$color\">" . ($val >= 0 ? '+' : '') . CURRENCY_SYMBOL . number_format($val, 2) . "</span>";
        }],
        ['key' => 'change_percent', 'label' => '% Change', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
            $color = $val >= 0 ? 'text-emerald-600' : 'text-red-600';
            return "<span class=\"$color\">" . ($val >= 0 ? '+' : '') . number_format($val, 1) . "%</span>";
        }]
    ],
    'data' => array_slice($comparison, 0, 100),
    'rowLink' => fn($row) => 'product-detail.php?name=' . urlencode($row['product_description']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No comparison data available.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
