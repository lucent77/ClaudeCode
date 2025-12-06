<?php
/**
 * Creodent Dashboard - Remakes by Customer Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/RemakeService.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\RemakeService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Remakes by Customer';
$currentPage = 'remake-customers';

$branch = Request::branch();
$dateRange = Request::dateRange();

$customers = RemakeService::getByCustomer($branch, $dateRange['start_date'], $dateRange['end_date']);

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<?php
renderTable([
    'title' => 'Customers with Remakes',
    'id' => 'remake-customers-table',
    'columns' => [
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'remake_amount', 'label' => 'Remake Amount', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'remake_ratio', 'label' => 'Remake %', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
            $color = $val > 10 ? 'text-red-600' : ($val > 5 ? 'text-amber-600' : 'text-gray-600');
            return "<span class=\"$color font-medium\">" . number_format($val, 2) . "%</span>";
        }],
        ['key' => 'remake_count', 'label' => 'Remake Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'invoice_count', 'label' => 'Total Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number']
    ],
    'data' => $customers,
    'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'emptyMessage' => 'No customers with remakes found for this period.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
