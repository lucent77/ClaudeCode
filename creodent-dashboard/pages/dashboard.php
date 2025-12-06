<?php
/**
 * Creodent Dashboard - Main Dashboard Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/core/Response.php';
require_once dirname(__DIR__) . '/services/DashboardService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/chart.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\DashboardService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Get filter parameters
$branch = Request::branch();
$dateRange = Request::dateRange();

// Get dashboard data
$summary = DashboardService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
$prevSummary = DashboardService::getPreviousPeriodSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
$trend = DashboardService::getMonthlySalesTrend($branch, $dateRange['year']);
$topCustomers = DashboardService::getTopCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
$topProducts = DashboardService::getTopProducts($branch, $dateRange['start_date'], $dateRange['end_date']);
$distribution = DashboardService::getCustomerDistribution($branch, $dateRange['start_date'], $dateRange['end_date']);

// Calculate changes
$salesChange = $prevSummary['total_sales'] > 0
    ? (($summary['total_sales'] - $prevSummary['total_sales']) / $prevSummary['total_sales']) * 100
    : 0;
$customerChange = $prevSummary['customer_count'] > 0
    ? (($summary['customer_count'] - $prevSummary['customer_count']) / $prevSummary['customer_count']) * 100
    : 0;
$invoiceChange = $prevSummary['invoice_count'] > 0
    ? (($summary['invoice_count'] - $prevSummary['invoice_count']) / $prevSummary['invoice_count']) * 100
    : 0;
$unitsChange = $prevSummary['total_units'] > 0
    ? (($summary['total_units'] - $prevSummary['total_units']) / $prevSummary['total_units']) * 100
    : 0;

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Total Sales',
        'value' => CURRENCY_SYMBOL . number_format($summary['total_sales'], 2),
        'change' => $salesChange,
        'changeLabel' => 'vs previous period',
        'icon' => 'currency-dollar',
        'color' => 'blue'
    ],
    [
        'title' => 'Customers',
        'value' => number_format($summary['customer_count']),
        'change' => $customerChange,
        'changeLabel' => 'vs previous period',
        'icon' => 'users',
        'color' => 'green'
    ],
    [
        'title' => 'Invoices',
        'value' => number_format($summary['invoice_count']),
        'change' => $invoiceChange,
        'changeLabel' => 'vs previous period',
        'icon' => 'document',
        'color' => 'amber'
    ],
    [
        'title' => 'Total Units',
        'value' => number_format($summary['total_units']),
        'change' => $unitsChange,
        'changeLabel' => 'vs previous period',
        'icon' => 'cube',
        'color' => 'purple'
    ]
]);
?>

<!-- Charts Row 1: Monthly Trend -->
<div class="mb-6">
    <?php
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $trendData = array_values($trend);

    renderChart([
        'id' => 'sales-trend',
        'title' => 'Monthly Sales Trend - ' . $dateRange['year'],
        'type' => 'line',
        'height' => 300,
        'data' => [
            'labels' => $months,
            'datasets' => [
                buildLineDataset($dateRange['year'], $trendData, '#3b82f6')
            ]
        ]
    ]);
    ?>
</div>

<!-- Charts Row 2: Top Customers & Products -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php
    // Top Customers
    $customerNames = array_column($topCustomers, 'customer_name');
    $customerSales = array_map(fn($c) => (float)$c['total_sales'], $topCustomers);

    renderChart([
        'id' => 'top-customers',
        'title' => 'Top 10 Customers',
        'type' => 'bar',
        'height' => 350,
        'data' => [
            'labels' => array_map(fn($n) => strlen($n) > 25 ? substr($n, 0, 22) . '...' : $n, $customerNames),
            'datasets' => [
                buildBarDataset('Sales', $customerSales, '#10b981')
            ]
        ],
        'options' => [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]]
        ]
    ]);

    // Top Products
    $productNames = array_column($topProducts, 'product_description');
    $productSales = array_map(fn($p) => (float)$p['total_sales'], $topProducts);

    renderChart([
        'id' => 'top-products',
        'title' => 'Top 10 Products',
        'type' => 'bar',
        'height' => 350,
        'data' => [
            'labels' => array_map(fn($n) => strlen($n) > 25 ? substr($n, 0, 22) . '...' : $n, $productNames),
            'datasets' => [
                buildBarDataset('Sales', $productSales, '#f59e0b')
            ]
        ],
        'options' => [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]]
        ]
    ]);
    ?>
</div>

<!-- Charts Row 3: Customer Distribution -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php
    renderChart([
        'id' => 'customer-distribution',
        'title' => 'Customer Distribution by Sales',
        'type' => 'doughnut',
        'height' => 300,
        'data' => buildPieData(array_keys($distribution), array_values($distribution)),
        'options' => [
            'cutout' => '60%'
        ]
    ]);
    ?>

    <!-- Quick Stats -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Average Sale Value</span>
                <span class="font-semibold text-gray-900">
                    <?= CURRENCY_SYMBOL . number_format($summary['invoice_count'] > 0 ? $summary['total_sales'] / $summary['invoice_count'] : 0, 2) ?>
                </span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Average Units per Invoice</span>
                <span class="font-semibold text-gray-900">
                    <?= number_format($summary['invoice_count'] > 0 ? $summary['total_units'] / $summary['invoice_count'] : 0, 1) ?>
                </span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">Products Sold</span>
                <span class="font-semibold text-gray-900"><?= number_format($summary['product_count']) ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                <span class="text-gray-600">Total Remakes</span>
                <span class="font-semibold text-red-600"><?= CURRENCY_SYMBOL . number_format($summary['remake_amount'], 2) ?></span>
            </div>
            <div class="flex justify-between items-center p-3 bg-amber-50 rounded-lg">
                <span class="text-gray-600">Total Discounts</span>
                <span class="font-semibold text-amber-600"><?= CURRENCY_SYMBOL . number_format($summary['discount_amount'], 2) ?></span>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
