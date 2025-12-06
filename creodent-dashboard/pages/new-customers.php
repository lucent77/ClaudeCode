<?php
/**
 * Creodent Dashboard - New Customers Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/CustomerService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\CustomerService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'New Customers';
$currentPage = 'new-customers';

$branch = Request::branch();
$dateRange = Request::dateRange();

$customers = CustomerService::getNewCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);

$totalRevenue = array_sum(array_column($customers, 'total_sales'));
$totalUnits = array_sum(array_column($customers, 'total_units'));

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'New Customers',
        'value' => number_format(count($customers)),
        'icon' => 'users',
        'color' => 'green'
    ],
    [
        'title' => 'Revenue from New',
        'value' => CURRENCY_SYMBOL . number_format($totalRevenue, 2),
        'icon' => 'currency-dollar',
        'color' => 'blue'
    ],
    [
        'title' => 'Units Sold',
        'value' => number_format($totalUnits),
        'icon' => 'cube',
        'color' => 'amber'
    ],
    [
        'title' => 'Avg. First Order',
        'value' => CURRENCY_SYMBOL . number_format(count($customers) > 0 ? $totalRevenue / count($customers) : 0, 2),
        'icon' => 'chart-bar',
        'color' => 'purple'
    ]
]);
?>

<!-- New Customers Table -->
<?php
renderTable([
    'title' => 'New Customers in Period',
    'id' => 'new-customers-table',
    'columns' => [
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'first_purchase', 'label' => 'First Purchase', 'sortable' => true, 'format' => 'date'],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'invoice_count', 'label' => 'Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number']
    ],
    'data' => $customers,
    'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No new customers found for this period.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
