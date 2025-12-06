<?php
/**
 * Creodent Dashboard - Remakes Overview Page
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/services/RemakeService.php';
require_once dirname(__DIR__) . '/components/card.php';
require_once dirname(__DIR__) . '/components/chart.php';

use Creodent\Core\{Auth, Request};
use Creodent\Services\RemakeService;

date_default_timezone_set(TIMEZONE);
Auth::require();

$pageTitle = 'Remakes Overview';
$currentPage = 'remakes';

$branch = Request::branch();
$dateRange = Request::dateRange();

$summary = RemakeService::getSummary($branch, $dateRange['start_date'], $dateRange['end_date']);
$trend = RemakeService::getTrend($branch, $dateRange['start_date'], $dateRange['end_date']);

$remakeRatio = $summary['total_sales'] > 0
    ? ($summary['total_remake'] / $summary['total_sales']) * 100
    : 0;

include dirname(__DIR__) . '/components/header.php';
include dirname(__DIR__) . '/components/filters.php';
?>

<!-- Summary Cards -->
<?php
renderCardRow([
    [
        'title' => 'Total Remakes',
        'value' => CURRENCY_SYMBOL . number_format($summary['total_remake'], 2),
        'icon' => 'refresh',
        'color' => 'red'
    ],
    [
        'title' => 'Remake Ratio',
        'value' => number_format($remakeRatio, 2) . '%',
        'icon' => 'chart-bar',
        'color' => $remakeRatio > 5 ? 'red' : ($remakeRatio > 2 ? 'amber' : 'green')
    ],
    [
        'title' => 'Remake Invoices',
        'value' => number_format($summary['remake_invoices']),
        'icon' => 'document',
        'color' => 'amber'
    ],
    [
        'title' => 'Affected Customers',
        'value' => number_format($summary['remake_customers']),
        'icon' => 'users',
        'color' => 'blue'
    ]
]);
?>

<!-- Trend Chart -->
<div class="mb-6">
    <?php
    $trendLabels = array_column($trend, 'month');
    $remakeData = array_map(fn($t) => (float)$t['remake_amount'], $trend);
    $salesData = array_map(fn($t) => (float)$t['total_sales'], $trend);

    renderChart([
        'id' => 'remake-trend',
        'title' => 'Remake Trend Over Time',
        'type' => 'line',
        'height' => 300,
        'data' => [
            'labels' => $trendLabels,
            'datasets' => [
                buildLineDataset('Remakes', $remakeData, '#ef4444'),
                buildLineDataset('Total Sales', $salesData, '#3b82f6')
            ]
        ]
    ]);
    ?>
</div>

<!-- Quick Links -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="remake-customers.php?<?= http_build_query(array_merge(['branch' => $branch], $dateRange)) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:border-blue-300 transition-colors">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-50 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Remakes by Customer</h3>
                <p class="text-sm text-gray-500">View remake amounts and ratios per customer</p>
            </div>
        </div>
    </a>

    <a href="remake-products.php?<?= http_build_query(array_merge(['branch' => $branch], $dateRange)) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:border-blue-300 transition-colors">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-50 rounded-lg">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Remakes by Product</h3>
                <p class="text-sm text-gray-500">View remake amounts and ratios per product</p>
            </div>
        </div>
    </a>
</div>

<?php include dirname(__DIR__) . '/components/footer.php'; ?>
