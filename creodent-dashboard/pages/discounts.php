<?php
/**
 * Creodent Dashboard - Discount Analysis Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/DiscountService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/chart.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\DiscountService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Discount Analysis';
$currentPage = 'discounts';

$branch = Request::branch();
$dateRange = Request::dateRange();

$summary = DiscountService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
$trend = DiscountService::getTrend($branch, $dateRange['start_date'], $dateRange['end_date']);
$distribution = DiscountService::getDistribution($branch, $dateRange['start_date'], $dateRange['end_date']);
$topCustomers = DiscountService::getTopCustomers($branch, $dateRange['start_date'], $dateRange['end_date'], 10);
$topProducts = DiscountService::getTopProducts($branch, $dateRange['start_date'], $dateRange['end_date'], 10);

$discountRate = $summary['gross_sales'] > 0
    ? ($summary['total_discount'] / $summary['gross_sales']) * 100
    : 0;

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Total Discounts',
        'value' => CURRENCY_SYMBOL . number_format($summary['total_discount'], 2),
        'icon' => 'tag',
        'color' => 'amber'
    ],
    [
        'title' => 'Discount Rate',
        'value' => number_format($discountRate, 2) . '%',
        'icon' => 'chart-bar',
        'color' => $discountRate > 15 ? 'red' : ($discountRate > 10 ? 'amber' : 'green')
    ],
    [
        'title' => 'Discount Invoices',
        'value' => number_format($summary['discount_invoices']),
        'icon' => 'document',
        'color' => 'blue'
    ],
    [
        'title' => 'Customers w/ Discount',
        'value' => number_format($summary['discount_customers']),
        'icon' => 'users',
        'color' => 'purple'
    ]
]);
?>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php
    // Trend Chart
    $trendLabels = array_column($trend, 'month');
    $discountData = array_map(fn($t) => (float)$t['discount_amount'], $trend);

    renderChart([
        'id' => 'discount-trend',
        'title' => 'Discount Trend Over Time',
        'type' => 'line',
        'height' => 300,
        'data' => [
            'labels' => $trendLabels,
            'datasets' => [buildLineDataset('Discounts', $discountData, '#f59e0b')]
        ]
    ]);

    // Distribution Chart
    renderChart([
        'id' => 'discount-distribution',
        'title' => 'Discount Distribution',
        'type' => 'doughnut',
        'height' => 300,
        'data' => buildPieData(array_keys($distribution), array_values($distribution)),
        'options' => ['cutout' => '60%']
    ]);
    ?>
</div>

<!-- Top Discount Recipients -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php
    renderTable([
        'title' => 'Top Discount Customers',
        'id' => 'discount-customers-table',
        'columns' => [
            ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
            ['key' => 'discount_amount', 'label' => 'Discount', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
            ['key' => 'discount_rate', 'label' => 'Rate', 'sortable' => true, 'align' => 'right', 'format' => 'percent']
        ],
        'data' => $topCustomers,
        'searchable' => false,
        'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
        'emptyMessage' => 'No discount data found.'
    ]);

    renderTable([
        'title' => 'Top Discount Products',
        'id' => 'discount-products-table',
        'columns' => [
            ['key' => 'product_description', 'label' => 'Product', 'sortable' => true],
            ['key' => 'discount_amount', 'label' => 'Discount', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
            ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number']
        ],
        'data' => $topProducts,
        'searchable' => false,
        'rowLink' => fn($row) => 'product-detail.php?name=' . urlencode($row['product_description']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
        'emptyMessage' => 'No discount data found.'
    ]);
    ?>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
