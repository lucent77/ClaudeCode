<?php
/**
 * Creodent Dashboard - Parts (REF Products) Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/ProductService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\ProductService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Parts (REF Products)';
$currentPage = 'parts';

$branch = Request::branch();
$dateRange = Request::dateRange();

$parts = ProductService::getParts($branch, $dateRange['start_date'], $dateRange['end_date']);

$totalSales = array_sum(array_column($parts, 'total_sales'));
$totalUnits = array_sum(array_column($parts, 'total_units'));

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Total Parts',
        'value' => number_format(count($parts)),
        'icon' => 'cube',
        'color' => 'blue'
    ],
    [
        'title' => 'Parts Sales',
        'value' => CURRENCY_SYMBOL . number_format($totalSales, 2),
        'icon' => 'currency-dollar',
        'color' => 'green'
    ],
    [
        'title' => 'Units Sold',
        'value' => number_format($totalUnits),
        'icon' => 'chart-bar',
        'color' => 'amber'
    ],
    [
        'title' => 'Avg. Part Value',
        'value' => CURRENCY_SYMBOL . number_format(count($parts) > 0 ? $totalSales / count($parts) : 0, 2),
        'icon' => 'tag',
        'color' => 'purple'
    ]
]);
?>

<!-- Parts Table -->
<?php
renderTable([
    'title' => 'Parts (Products containing REF)',
    'id' => 'parts-table',
    'columns' => [
        ['key' => 'product_description', 'label' => 'Product', 'sortable' => true],
        ['key' => 'product_number', 'label' => 'Code', 'sortable' => true],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'customer_count', 'label' => 'Customers', 'sortable' => true, 'align' => 'right', 'format' => 'number']
    ],
    'data' => $parts,
    'rowLink' => fn($row) => 'product-detail.php?name=' . urlencode($row['product_description']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No parts (REF products) found for this period.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
