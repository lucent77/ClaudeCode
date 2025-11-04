<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Cases</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Showing <?= number_format(count($cases)) ?> of <?= number_format($total) ?> cases
                </p>
            </div>
            <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin', 'manager'])): ?>
                <a href="/cases/create" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Case
                </a>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <form method="GET" action="/cases" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text"
                               id="search"
                               name="search"
                               value="<?= htmlspecialchars($filters['search']) ?>"
                               placeholder="Case #, Patient, Lab..."
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status"
                                name="status"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="new" <?= $filters['status'] === 'new' ? 'selected' : '' ?>>New</option>
                            <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="done" <?= $filters['status'] === 'done' ? 'selected' : '' ?>>Done</option>
                            <option value="on_hold" <?= $filters['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                            <option value="canceled" <?= $filters['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select id="location"
                                name="location"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Locations</option>
                            <option value="HV" <?= $filters['location'] === 'HV' ? 'selected' : '' ?>>HV</option>
                            <option value="NYC" <?= $filters['location'] === 'NYC' ? 'selected' : '' ?>>NYC</option>
                            <option value="HVNYC" <?= $filters['location'] === 'HVNYC' ? 'selected' : '' ?>>HVNYC</option>
                        </select>
                    </div>

                    <!-- Source -->
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                        <select id="source"
                                name="source"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Sources</option>
                            <option value="evolution_web_portal" <?= $filters['source'] === 'evolution_web_portal' ? 'selected' : '' ?>>Evolution Portal</option>
                            <option value="windows_app" <?= $filters['source'] === 'windows_app' ? 'selected' : '' ?>>Windows App</option>
                            <option value="manual" <?= $filters['source'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                        </select>
                    </div>

                    <!-- Due Date From -->
                    <div>
                        <label for="due_date_from" class="block text-sm font-medium text-gray-700 mb-1">Due From</label>
                        <input type="date"
                               id="due_date_from"
                               name="due_date_from"
                               value="<?= htmlspecialchars($filters['due_date_from']) ?>"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Due Date To -->
                    <div>
                        <label for="due_date_to" class="block text-sm font-medium text-gray-700 mb-1">Due To</label>
                        <input type="date"
                               id="due_date_to"
                               name="due_date_to"
                               value="<?= htmlspecialchars($filters['due_date_to']) ?>"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Include Archived -->
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   name="include_archived"
                                   <?= $filters['include_archived'] ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Include Archived</span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Filter
                    </button>
                    <a href="/cases" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Cases Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <?php if (empty($cases)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No cases found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or create a new case.</p>
                    <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin', 'manager'])): ?>
                        <div class="mt-6">
                            <a href="/cases/create" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                New Case
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Case #
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Patient / Lab
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Due Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Source
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cases as $case): ?>
                            <?php
                            $statusColors = [
                                'new' => 'bg-green-100 text-green-800',
                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                'done' => 'bg-blue-100 text-blue-800',
                                'on_hold' => 'bg-gray-100 text-gray-800',
                                'canceled' => 'bg-red-100 text-red-800',
                                'archived' => 'bg-purple-100 text-purple-800'
                            ];
                            $statusColor = $statusColors[$case['status']] ?? 'bg-gray-100 text-gray-800';

                            $isOverdue = $case['due_date'] && strtotime($case['due_date']) < time() && !in_array($case['status'], ['done', 'canceled', 'archived']);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($case['external_case_no']) ?>
                                    </div>
                                    <?php if ($case['pan']): ?>
                                        <div class="text-xs text-gray-500">PAN: <?= htmlspecialchars($case['pan']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($case['patient_name'] ?? 'N/A') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($case['lab_name'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($case['location']): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                            <?= htmlspecialchars($case['location']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($case['due_date']): ?>
                                        <div class="text-sm <?= $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-900' ?>">
                                            <?= date('M d, Y', strtotime($case['due_date'])) ?>
                                        </div>
                                        <?php if ($isOverdue): ?>
                                            <div class="text-xs text-red-600">Overdue!</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $case['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= ucfirst(str_replace('_', ' ', $case['source'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/cases/<?= $case['id'] ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Page <span class="font-medium"><?= $page ?></span> of <span class="font-medium"><?= $total_pages ?></span>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= http_build_query(array_filter($filters)) ? '&' . http_build_query(array_filter($filters)) : '' ?>"
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= http_build_query(array_filter($filters)) ? '&' . http_build_query(array_filter($filters)) : '' ?>"
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
