<?php
/**
 * Creodent Dashboard - Customer List Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/CustomerService.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\CustomerService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Customers';
$currentPage = 'customers';

$branch = Request::branch();
$dateRange = Request::dateRange();
$pagination = Request::pagination();
$groupId = Request::int('group_id');

$result = CustomerService::getCustomers($branch, $dateRange['start_date'], $dateRange['end_date'], $groupId, $pagination);
$customers = $result['data'];
$total = $result['total'];

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<?php
renderTable([
    'title' => 'All Customers',
    'id' => 'customers-table',
    'columns' => [
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'invoice_count', 'label' => 'Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'product_count', 'label' => 'Products', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'last_purchase', 'label' => 'Last Purchase', 'sortable' => true, 'format' => 'date']
    ],
    'data' => $customers,
    'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'pagination' => [
        'page' => $pagination['page'],
        'per_page' => $pagination['per_page'],
        'total' => $total
    ],
    'emptyMessage' => 'No customers found for the selected period.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
