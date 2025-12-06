<?php
/**
 * Creodent Dashboard - Customer Detail Page
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

$customerName = Request::input('name');
if (!$customerName) {
    header('Location: customers.php');
    exit;
}

$branch = Request::branch();
$dateRange = Request::dateRange();

$data = CustomerService::getCustomerDetail($customerName, $branch, $dateRange['start_date'], $dateRange['end_date']);
$summary = $data['summary'];
$products = $data['products'];
$trend = $data['trend'];

$pageTitle = $customerName;
$currentPage = 'customers';

include dirname(__DIR__) . '/components/header.php';
?>

<!-- Back Link -->
<a href="customers.php?<?= http_build_query(array_merge(['branch' => $branch], $dateRange)) ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
    </svg>
    Back to Customers
</a>

<!-- Customer Name Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($customerName) ?></h1>
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
        'title' => 'Invoices',
        'value' => number_format($summary['invoice_count'] ?? 0),
        'icon' => 'document',
        'color' => 'amber'
    ],
    [
        'title' => 'Products',
        'value' => number_format($summary['product_count'] ?? 0),
        'icon' => 'cube',
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
        'id' => 'customer-trend',
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

<!-- Products Purchased -->
<?php
renderTable([
    'title' => 'Products Purchased',
    'id' => 'products-table',
    'columns' => [
        ['key' => 'product_description', 'label' => 'Product', 'sortable' => true],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'invoice_count', 'label' => 'Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number']
    ],
    'data' => $products,
    'rowLink' => fn($row) => 'product-detail.php?name=' . urlencode($row['product_description']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No products found for this customer.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
