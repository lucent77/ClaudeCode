<?php
$content = <<<'HTML'
<?php require __DIR__ . '/components/nav.php'; ?>

<div class="min-h-screen bg-gray-50" x-data="dashboardData()">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 sm:px-0 mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-600">Overview of current work status</p>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-12">
            <div class="spinner"></div>
        </div>

        <!-- Stats Cards -->
        <div x-show="!loading" x-cloak class="px-4 sm:px-0">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <!-- Total Cases -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Cases</dt>
                                    <dd class="text-3xl font-semibold text-gray-900" x-text="stats.total_cases"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Today -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">New Today</dt>
                                    <dd class="text-3xl font-semibold text-green-600" x-text="stats.new_cases_today"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- In Progress -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                                    <dd class="text-3xl font-semibold text-blue-600" x-text="stats.in_progress"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                                    <dd class="text-3xl font-semibold text-red-600" x-text="stats.overdue"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Workload -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Department Workload</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Cases</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Working</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Done</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="dept in stats.department_workload" :key="dept.department_code">
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="dept.department_name"></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="dept.active_cases"></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="dept.pending_items"></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="dept.working_items"></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="dept.done_items"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Cases -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Cases</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="caseItem in stats.recent_cases" :key="caseItem.id">
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                            <a :href="'/cases/' + caseItem.id" x-text="caseItem.external_case_no"></a>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="caseItem.lab_name"></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="caseItem.patient_name"></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(caseItem.due_date)"></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="{
                                                      'bg-green-100 text-green-800': caseItem.status === 'done',
                                                      'bg-blue-100 text-blue-800': caseItem.status === 'in_progress',
                                                      'bg-yellow-100 text-yellow-800': caseItem.status === 'new',
                                                      'bg-gray-100 text-gray-800': caseItem.status === 'on_hold'
                                                  }"
                                                  x-text="caseItem.status">
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a :href="'/cases/' + caseItem.id" class="text-blue-600 hover:text-blue-900">View</a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardData() {
    return {
        loading: true,
        stats: {
            total_cases: 0,
            new_cases_today: 0,
            in_progress: 0,
            overdue: 0,
            department_workload: [],
            recent_cases: []
        },

        async init() {
            await this.loadStats();
        },

        async loadStats() {
            this.loading = true;
            try {
                const response = await apiRequest('/api/dashboard/stats');
                if (response.success) {
                    this.stats = response.data;
                }
            } catch (error) {
                showToast(error.message || 'Failed to load dashboard stats', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
HTML;

require __DIR__ . '/layout.php';
