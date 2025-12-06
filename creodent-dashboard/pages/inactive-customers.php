<?php
/**
 * Creodent Dashboard - Inactive Customers Page
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

$pageTitle = 'Inactive Customers';
$currentPage = 'inactive-customers';

$branch = Request::branch();
$days = Request::int('days', 90);

$customers = CustomerService::getInactiveCustomers($branch, $days);

$totalLostRevenue = array_sum(array_column($customers, 'total_sales'));

include dirname(__DIR__) . '/components/header.php';
?>

<!-- Days Filter -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
            <select name="branch" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="ALL" <?= $branch === 'ALL' ? 'selected' : '' ?>>All Branches</option>
                <option value="NYC" <?= $branch === 'NYC' ? 'selected' : '' ?>>NYC Branch</option>
                <option value="HV" <?= $branch === 'HV' ? 'selected' : '' ?>>HV Branch</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Inactive For</label>
            <select name="days" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="30" <?= $days === 30 ? 'selected' : '' ?>>30+ days</option>
                <option value="60" <?= $days === 60 ? 'selected' : '' ?>>60+ days</option>
                <option value="90" <?= $days === 90 ? 'selected' : '' ?>>90+ days</option>
                <option value="180" <?= $days === 180 ? 'selected' : '' ?>>180+ days</option>
                <option value="365" <?= $days === 365 ? 'selected' : '' ?>>365+ days</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            Apply
        </button>
    </form>
</div>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Inactive Customers',
        'value' => number_format(count($customers)),
        'icon' => 'users',
        'color' => 'amber'
    ],
    [
        'title' => 'Historical Revenue',
        'value' => CURRENCY_SYMBOL . number_format($totalLostRevenue, 2),
        'icon' => 'currency-dollar',
        'color' => 'red'
    ],
    [
        'title' => 'Avg. Customer Value',
        'value' => CURRENCY_SYMBOL . number_format(count($customers) > 0 ? $totalLostRevenue / count($customers) : 0, 2),
        'icon' => 'chart-bar',
        'color' => 'blue'
    ],
    [
        'title' => 'Threshold',
        'value' => $days . '+ days',
        'icon' => 'exclamation',
        'color' => 'purple'
    ]
]);
?>

<!-- Inactive Customers Table -->
<?php
renderTable([
    'title' => 'Inactive Customers (No purchases in ' . $days . '+ days)',
    'id' => 'inactive-table',
    'columns' => [
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'total_sales', 'label' => 'Historical Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
        ['key' => 'invoice_count', 'label' => 'Invoices', 'sortable' => true, 'align' => 'right', 'format' => 'number'],
        ['key' => 'last_purchase', 'label' => 'Last Purchase', 'sortable' => true, 'format' => 'date'],
        ['key' => 'days_inactive', 'label' => 'Days Inactive', 'sortable' => true, 'align' => 'right', 'format' => function($val, $row) {
            $days = floor((time() - strtotime($row['last_purchase'])) / 86400);
            $color = $days > 180 ? 'text-red-600' : ($days > 90 ? 'text-amber-600' : 'text-gray-600');
            return "<span class=\"$color font-medium\">{$days} days</span>";
        }]
    ],
    'data' => $customers,
    'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch,
    'emptyMessage' => 'No inactive customers found for this period.'
]);
?>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
