<?php
/**
 * Creodent Dashboard - Filter Component
 *
 * Usage: include this file after header.php
 * Provides branch selector, date type, and date range filters
 */

use Creodent\Core\{Request, Database};

// Get current filter values
$branch = Request::branch();
$dateRange = Request::dateRange();
$groupId = Request::int('group_id');

// Get customer groups for filter
$groups = [];
try {
    $groups = Database::query("SELECT id, name FROM customer_groups WHERE is_active = 1 ORDER BY name", [], 'nyc');
} catch (\Exception $e) {
    // Groups table may not exist yet
}

// Generate years for dropdown (last 10 years)
$currentYear = (int) date('Y');
$years = range($currentYear, $currentYear - 10);

// Months
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<div id="filters" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <form id="filter-form" method="GET" class="flex flex-wrap gap-4 items-end">
        <!-- Branch Filter -->
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Branch</label>
            <select name="branch" id="filter-branch" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="ALL" <?= $branch === 'ALL' ? 'selected' : '' ?>>All Branches</option>
                <option value="NYC" <?= $branch === 'NYC' ? 'selected' : '' ?>>NYC Branch</option>
                <option value="HV" <?= $branch === 'HV' ? 'selected' : '' ?>>HV Branch</option>
            </select>
        </div>

        <!-- Date Type -->
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Date Type</label>
            <select name="date_type" id="filter-date-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="year" <?= $dateRange['type'] === 'year' ? 'selected' : '' ?>>Year</option>
                <option value="month" <?= $dateRange['type'] === 'month' ? 'selected' : '' ?>>Month</option>
                <option value="custom" <?= $dateRange['type'] === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
        </div>

        <!-- Year Selector -->
        <div id="year-filter" class="flex-1 min-w-[120px] <?= $dateRange['type'] === 'custom' ? 'hidden' : '' ?>">
            <label class="block text-xs font-medium text-gray-500 mb-1">Year</label>
            <select name="year" id="filter-year" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $dateRange['year'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Month Selector -->
        <div id="month-filter" class="flex-1 min-w-[140px] <?= $dateRange['type'] !== 'month' ? 'hidden' : '' ?>">
            <label class="block text-xs font-medium text-gray-500 mb-1">Month</label>
            <select name="month" id="filter-month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php foreach ($months as $m => $name): ?>
                <option value="<?= $m ?>" <?= $dateRange['month'] === $m ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Custom Date Range -->
        <div id="custom-date-filter" class="flex-1 min-w-[280px] <?= $dateRange['type'] !== 'custom' ? 'hidden' : '' ?>">
            <label class="block text-xs font-medium text-gray-500 mb-1">Date Range</label>
            <div class="flex gap-2 items-center">
                <input type="date" name="start_date" id="filter-start-date"
                    value="<?= $dateRange['start_date'] ?>"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <span class="text-gray-400">to</span>
                <input type="date" name="end_date" id="filter-end-date"
                    value="<?= $dateRange['end_date'] ?>"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <!-- Customer Group -->
        <?php if (!empty($groups)): ?>
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Customer Group</label>
            <select name="group_id" id="filter-group" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Customers</option>
                <?php foreach ($groups as $group): ?>
                <option value="<?= $group['id'] ?>" <?= $groupId === (int)$group['id'] ? 'selected' : '' ?>><?= htmlspecialchars($group['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Apply Button -->
        <div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Current Period Display -->
<div class="mb-6 flex items-center gap-3 text-sm">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
        <?php
        echo match($branch) {
            'NYC' => 'bg-amber-100 text-amber-700',
            'HV' => 'bg-emerald-100 text-emerald-700',
            default => 'bg-blue-100 text-blue-700'
        };
        ?>">
        <span class="w-2 h-2 rounded-full <?php
            echo match($branch) {
                'NYC' => 'bg-amber-500',
                'HV' => 'bg-emerald-500',
                default => 'bg-blue-500'
            };
        ?>"></span>
        <?= BRANCHES[$branch]['name'] ?>
    </span>
    <span class="text-gray-400">|</span>
    <span class="text-gray-600">
        <?php
        if ($dateRange['type'] === 'month') {
            echo $months[$dateRange['month']] . ' ' . $dateRange['year'];
        } elseif ($dateRange['type'] === 'custom') {
            echo date(DATE_FORMAT, strtotime($dateRange['start_date'])) . ' - ' . date(DATE_FORMAT, strtotime($dateRange['end_date']));
        } else {
            echo $dateRange['year'];
        }
        ?>
    </span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateType = document.getElementById('filter-date-type');
    const yearFilter = document.getElementById('year-filter');
    const monthFilter = document.getElementById('month-filter');
    const customFilter = document.getElementById('custom-date-filter');

    dateType.addEventListener('change', function() {
        const type = this.value;

        yearFilter.classList.toggle('hidden', type === 'custom');
        monthFilter.classList.toggle('hidden', type !== 'month');
        customFilter.classList.toggle('hidden', type !== 'custom');
    });
});
</script>
