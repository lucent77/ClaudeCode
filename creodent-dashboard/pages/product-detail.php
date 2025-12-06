<?php
/**
 * Creodent Dashboard - Product Detail Page
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

$productName = Request::input('name');
if (!$productName) {
    header('Location: products.php');
    exit;
}

$branch = Request::branch();
$dateRange = Request::dateRange();

$data = ProductService::getProductDetail($productName, $branch, $dateRange['start_date'], $dateRange['end_date']);
$summary = $data['summary'];
$customers = $data['customers'];
$trend = $data['trend'];

$pageTitle = $productName;
$currentPage = 'products';

include dirname(__DIR__) . '/components/header.php';
?>

<!-- Back Link -->
<a href="products.php?<?= http_build_query(array_merge(['branch' => $branch], $dateRange)) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
    </svg>
    Back to Products
</a>

<!-- Product Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($productName) ?></h1>
    <p class="text-gray-500 mt-1">
        <?= date(DATE_FORMAT, strtotime($dateRange['start_date'])) ?> - <?= date(DATE_FORMAT, strtotime($dateRange['end_date'])) ?>
    </p>
</div>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Total Sales',
        'value' => CURRENCY_SYMBOL . number_format($summary['total_sales'] ?? 0, 2),
        'icon' => 'currency-dollar',
        'color' => 'blue'
    ],
    [
        'title' => 'Total Units',
        'value' => number_format($summary['total_units'] ?? 0),
        'icon' => 'cube',
        'color' => 'green'
    ],
    [
        'title' => 'Customers',
        'value' => number_format($summary['customer_count'] ?? 0),
        'icon' => 'users',
        'color' => 'amber'
    ],
    [
        'title' => 'Avg. Unit Price',
        'value' => CURRENCY_SYMBOL . number_format($summary['avg_unit_price'] ?? 0, 2),
        'icon' => 'chart-bar',
        'color' => 'purple'
    ]
]);
?>

<!-- Monthly Trend -->
<div class="mb-6">
    <?php
    $trendLabels = array_column($trend, 'month');
    $trendData = array_map(fn($t) => (float)$t['sales'], $trend);

    renderChart([
        'id' => 'product-trend',
        'title' => 'Monthly Sales Trend',
        'type' => 'line',
        'height' => 300,
        'data' => [
            'labels' => $trendLabels,
            'datasets' => [
                buildLineDataset('Sales', $trendData, '#3b82f6')
            ]
        ]
    ]);
    ?>
</div>

<!-- Customers Who Purchased -->
<?php
renderTable([
    'title' => 'Customers Who Purchased',
    'id' => 'customers-table',
    'columns' => [
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'invoice_count', 'label' => 'Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number']
    ],
    'data' => $customers,
    'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No customers found for this product.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
