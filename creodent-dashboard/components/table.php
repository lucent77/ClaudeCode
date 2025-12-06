<?php
/**
 * Creodent Dashboard - Data Table Component
 *
 * Usage:
 * renderTable([
 *     'title' => 'Customers',
 *     'columns' => [
 *         ['key' => 'name', 'label' => 'Customer Name', 'sortable' => true],
 *         ['key' => 'sales', 'label' => 'Total Sales', 'sortable' => true, 'align' => 'right', 'format' => 'currency'],
 *     ],
 *     'data' => [...],
 *     'searchable' => true,
 *     'pagination' => ['page' => 1, 'total' => 100, 'per_page' => 50]
 * ]);
 */

function renderTable(array $options): void {
    $title = $options['title'] ?? 'Data';
    $columns = $options['columns'] ?? [];
    $data = $options['data'] ?? [];
    $searchable = $options['searchable'] ?? true;
    $pagination = $options['pagination'] ?? null;
    $tableId = $options['id'] ?? 'data-table-' . uniqid();
    $emptyMessage = $options['emptyMessage'] ?? 'No data found';
    $rowLink = $options['rowLink'] ?? null; // Function to generate row link
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($title) ?></h3>
                <?php if ($searchable): ?>
                <div class="relative">
                    <input
                        type="search"
                        id="<?= $tableId ?>-search"
                        placeholder="Search..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table id="<?= $tableId ?>" class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach ($columns as $col): ?>
                        <th class="px-6 py-3 text-<?= $col['align'] ?? 'left' ?> text-xs font-medium text-gray-500 uppercase tracking-wider <?= ($col['sortable'] ?? false) ? 'cursor-pointer hover:bg-gray-100' : '' ?>"
                            <?= ($col['sortable'] ?? false) ? 'data-sort="' . $col['key'] . '"' : '' ?>>
                            <span class="inline-flex items-center gap-1">
                                <?= htmlspecialchars($col['label']) ?>
                                <?php if ($col['sortable'] ?? false): ?>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                                <?php endif; ?>
                            </span>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="<?= count($columns) ?>" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <?= htmlspecialchars($emptyMessage) ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($data as $row): ?>
                    <tr class="hover:bg-gray-50 transition-colors <?= $rowLink ? 'cursor-pointer' : '' ?>"
                        <?= $rowLink ? 'onclick="window.location=\'' . htmlspecialchars($rowLink($row)) . '\'"' : '' ?>>
                        <?php foreach ($columns as $col): ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-<?= $col['align'] ?? 'left' ?> <?= ($col['key'] === array_key_first($columns) ? 'font-medium text-gray-900' : 'text-gray-600') ?>">
                            <?php
                            $value = $row[$col['key']] ?? '';
                            $format = $col['format'] ?? null;

                            if ($format === 'currency') {
                                echo CURRENCY_SYMBOL . number_format((float)$value, 2);
                            } elseif ($format === 'number') {
                                echo number_format((float)$value);
                            } elseif ($format === 'percent') {
                                echo number_format((float)$value, 1) . '%';
                            } elseif ($format === 'date') {
                                echo $value ? date(DATE_FORMAT, strtotime($value)) : '-';
                            } elseif (is_callable($format)) {
                                echo $format($value, $row);
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination && $pagination['total'] > $pagination['per_page']): ?>
        <?php
        $totalPages = ceil($pagination['total'] / $pagination['per_page']);
        $currentPage = $pagination['page'];
        ?>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Showing <?= (($currentPage - 1) * $pagination['per_page']) + 1 ?> to <?= min($currentPage * $pagination['per_page'], $pagination['total']) ?> of <?= number_format($pagination['total']) ?> results
            </p>
            <nav class="flex gap-1">
                <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>"
                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Previous</a>
                <?php endif; ?>

                <?php
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);

                if ($start > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>"
                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">1</a>
                <?php if ($start > 2): ?>
                <span class="px-2 py-1 text-gray-400">...</span>
                <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                   class="px-3 py-1 border rounded text-sm <?= $i === $currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                <span class="px-2 py-1 text-gray-400">...</span>
                <?php endif; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"
                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>"
                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        const table = document.getElementById('<?= $tableId ?>');
        const searchInput = document.getElementById('<?= $tableId ?>-search');

        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const filter = this.value.toLowerCase();
                    const rows = table.querySelectorAll('tbody tr');

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                }, 300);
            });
        }
    })();
    </script>
    <?php
}
