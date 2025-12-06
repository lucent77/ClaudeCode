<?php
/**
 * Creodent Dashboard - Products List Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/ProductService.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\ProductService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Products';
$currentPage = 'products';

$branch = Request::branch();
$dateRange = Request::dateRange();
$pagination = Request::pagination();

$result = ProductService::getProducts($branch, $dateRange['start_date'], $dateRange['end_date'], $pagination);
$products = $result['data'];
$total = $result['total'];

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<?php
renderTable([
    'title' => 'All Products',
    'id' => 'products-table',
    'columns' => [
        ['key' => 'product_description', 'label' => 'Product', 'sortable' => true],
        ['key' => 'product_number', 'label' => 'Code', 'sortable' => true],
        ['key' => 'total_sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'total_units', 'label' => 'Units', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'customer_count', 'label' => 'Customers', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'invoice_count', 'label' => 'Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number']
    ],
    'data' => $products,
    'rowLink' => fn($row) => 'product-detail.php?name=' . urlencode($row['product_description']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
    'pagination' => [
        'page' => $pagination['page'],
        'per_page' => $pagination['per_page'],
        'total' => $total
    ],
    'emptyMessage' => 'No products found for the selected period.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
