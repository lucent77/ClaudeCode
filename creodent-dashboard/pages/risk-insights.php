<?php
/**
 * Creodent Dashboard - Risk Insights Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/RiskService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/chart.php';
require_once dirname(__DIR__) . '/components/table.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\RiskService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Risk Insights';
$currentPage = 'risk-insights';

$branch = Request::branch();
$dateRange = Request::dateRange();

$summary = RiskService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
$atRiskCustomers = RiskService::getAtRiskCustomers($branch, $dateRange['start_date'], $dateRange['end_date']);
$atRiskProducts = RiskService::getAtRiskProducts($branch, $dateRange['start_date'], $dateRange['end_date']);
$distribution = RiskService::getDistribution($branch, $dateRange['start_date'], $dateRange['end_date']);

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Warning Banner -->
<?php if (count($atRiskCustomers) > 0): ?>
<div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
    <svg class="w-5 h-5 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
    </svg>
    <div>
        <p class="font-medium text-red-700">Attention Required</p>
        <p class="text-sm text-red-600"><?= count($atRiskCustomers) ?> customers showing significant sales decline (20%+ drop vs previous period)</p>
    </div>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'At-Risk Customers',
        'value' => number_format($summary['at_risk_customers']),
        'icon' => 'exclamation',
        'color' => 'red'
    ],
    [
        'title' => 'At-Risk Products',
        'value' => number_format($summary['at_risk_products']),
        'icon' => 'cube',
        'color' => 'amber'
    ],
    [
        'title' => 'Potential Revenue Loss',
        'value' => CURRENCY_SYMBOL . number_format($summary['potential_loss_customers'], 2),
        'icon' => 'currency-dollar',
        'color' => 'red'
    ],
    [
        'title' => 'Avg. Customer Decline',
        'value' => number_format(abs($summary['avg_decline_customers']), 1) . '%',
        'icon' => 'chart-bar',
        'color' => 'purple'
    ]
]);
?>

<!-- Risk Distribution Chart -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php
    renderChart([
        'id' => 'risk-distribution',
        'title' => 'Risk Distribution',
        'type' => 'doughnut',
        'height' => 300,
        'data' => buildPieData(
            array_keys($distribution),
            array_values($distribution),
            ['#22c55e', '#f59e0b', '#f97316', '#ef4444']
        ),
        'options' => ['cutout' => '60%']
    ]);
    ?>

    <!-- Risk Explanation -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Understanding Risk Levels</h3>
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="w-3 h-3 rounded-full bg-green-500 mt-1.5"></span>
                <div>
                    <p class="font-medium text-gray-900">Low Risk (10-20% decline)</p>
                    <p class="text-sm text-gray-500">Minor fluctuation, monitor closely</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-3 h-3 rounded-full bg-amber-500 mt-1.5"></span>
                <div>
                    <p class="font-medium text-gray-900">Medium Risk (20-40% decline)</p>
                    <p class="text-sm text-gray-500">Significant drop, proactive outreach recommended</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-3 h-3 rounded-full bg-orange-500 mt-1.5"></span>
                <div>
                    <p class="font-medium text-gray-900">High Risk (40-60% decline)</p>
                    <p class="text-sm text-gray-500">Major concern, immediate action needed</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="w-3 h-3 rounded-full bg-red-500 mt-1.5"></span>
                <div>
                    <p class="font-medium text-gray-900">Critical Risk (60%+ decline)</p>
                    <p class="text-sm text-gray-500">Customer may be lost, urgent intervention required</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- At-Risk Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php
    renderTable([
        'title' => 'At-Risk Customers',
        'id' => 'risk-customers-table',
        'columns' => [
            ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
            ['key' => 'previous_sales', 'label' => 'Previous', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
            ['key' => 'current_sales', 'label' => 'Current', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
            ['key' => 'change_percent', 'label' => 'Decline', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
                return "<span class=\"text-red-600 font-medium\">" . number_format(abs($val), 1) . "%</span>";
            }]
        ],
        'data' => array_slice($atRiskCustomers, 0, 15),
        'searchable' => false,
        'rowLink' => fn($row) => 'customer-detail.php?name=' . urlencode($row['customer_name']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
        'emptyMessage' => 'No at-risk customers identified.'
    ]);

    renderTable([
        'title' => 'At-Risk Products',
        'id' => 'risk-products-table',
        'columns' => [
            ['key' => 'product_description', 'label' => 'Product', 'sortable' => true],
            ['key' => 'previous_sales', 'label' => 'Previous', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
            ['key' => 'current_sales', 'label' => 'Current', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
            ['key' => 'change_percent', 'label' => 'Decline', 'sortable' => true, 'align' => 'right', 'format' => function($val) {
                return "<span class=\"text-red-600 font-medium\">" . number_format(abs($val), 1) . "%</span>";
            }]
        ],
        'data' => array_slice($atRiskProducts, 0, 15),
        'searchable' => false,
        'rowLink' => fn($row) => 'product-detail.php?name=' . urlencode($row['product_description']) . '&branch=' . $branch . '&' . http_build_query($dateRange),
        'emptyMessage' => 'No at-risk products identified.'
    ]);
    ?>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
