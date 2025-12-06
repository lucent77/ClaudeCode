<?php
/**
 * Creodent Dashboard - Customer Comparison Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/CustomerService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/chart.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\CustomerService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Customer Comparison';
$currentPage = 'customer-compare';

$branch = Request::branch();
$dateRange = Request::dateRange();

$comparison = CustomerService::compareCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);

// Separate gainers and losers
$gainers = array_filter($comparison, fn($c) => $c['change_amount'] > 0);
$losers = array_filter($comparison, fn($c) => $c['change_amount'] < 0);

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
        'title' => 'Customers Compared',
        'value' => number_format(count($comparison)),
        'icon' => 'users',
        'color' => 'blue'
    ],
    [
        'title' => 'Growing Customers',
        'value' => number_format(count($gainers)),
        'icon' => 'chart-bar',
        'color' => 'green'
    ],
    [
        'title' => 'Declining Customers',
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

<!-- Charts: Gainers & Losers -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php
    // Top Gainers Chart
    $gainerNames = array_column($topGainers, 'customer_name');
    $gainerChanges = array_column($topGainers, 'change_amount');

    renderChart([
        'id' => 'top-gainers',
        'title' => 'Top 10 Gainers',
        'type' => 'bar',
        'height' => 350,
        'data' => [
            'labels' => array_map(fn($n) => strlen($n) > 20 ? substr($n, 0, 17) . '...' : $n, $gainerNames),
            'datasets' => [
                buildBarDataset('Gain', $gainerChanges, '#10b981')
            ]
        ],
        'options' => [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]]
        ]
    ]);

    // Top Losers Chart
    $loserNames = array_column($topLosers, 'customer_name');
    $loserChanges = array_map(fn($c) => abs($c), array_column($topLosers, 'change_amount'));

    renderChart([
        'id' => 'top-losers',
        'title' => 'Top 10 Decliners',
        'type' => 'bar',
        'height' => 350,
        'data' => [
            'labels' => array_map(fn($n) => strlen($n) > 20 ? substr($n, 0, 17) . '...' : $n, $loserNames),
            'datasets' => [
                buildBarDataset('Loss', $loserChanges, '#ef4444')
            ]
        ],
        'options' => [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]]
        ]
    ]);
    ?>
</div>

<!-- Comparison Table -->
<?php
renderTable([
    'title' => 'All Customer Comparisons',
    'id' => 'comparison-table',
    'columns' => [
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'current_sales', 'label' => 'Current Period', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'previous_sales', 'label' => 'Previous Period', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'change_amount', 'label' => 'Change', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
            $color = $val >= 0 ? 'text-emerald-600' : 'text-red-600';
            $prefix = $val >= 0 ? '+' : '';
            return "<span class=\"$color\">{$prefix}" . CURRENCY_SYMBOL . number_format($val, 2) . "</span>";
        }],
        ['key' => 'change_percent', 'label' => '% Change', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
            $color = $val >= 0 ? 'text-emerald-600' : 'text-red-600';
            $prefix = $val >= 0 ? '+' : '';
            return "<span class=\"$color\">{$prefix}" . number_format($val, 1) . "%</span>";
        }]
    ],
    'data' => array_slice($comparison, 0, 100),
    'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No comparison data available.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
