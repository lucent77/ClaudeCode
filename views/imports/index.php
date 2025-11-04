<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Import Cases from Evolution Portal</h1>
            <p class="mt-1 text-sm text-gray-600">
                Import cases in bulk from Evolution Web Portal by specifying a date range
            </p>
        </div>

        <!-- Connection Test Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">Test Evolution Connection</h3>
            <p class="text-sm text-blue-700 mb-4">
                Test your connection to Evolution Portal before importing cases
            </p>
            <form method="POST" action="/imports/test-connection" class="flex gap-4">
                <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">
                <div>
                    <select name="location" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="HV">HV</option>
                        <option value="NYC">NYC</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Test Connection
                </button>
            </form>
        </div>

        <!-- Import Form -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Import Settings</h3>
            <form method="POST" action="/imports/execute" x-data="importForm()">
                <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">

                <div class="space-y-6">
                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">
                                From Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="from_date"
                                   name="from_date"
                                   required
                                   x-model="fromDate"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">
                                To Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="to_date"
                                   name="to_date"
                                   required
                                   x-model="toDate"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Quick Date Range Buttons -->
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" @click="setDateRange('today')" class="text-sm px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Today</button>
                        <button type="button" @click="setDateRange('yesterday')" class="text-sm px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Yesterday</button>
                        <button type="button" @click="setDateRange('last7days')" class="text-sm px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Last 7 Days</button>
                        <button type="button" @click="setDateRange('last30days')" class="text-sm px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Last 30 Days</button>
                        <button type="button" @click="setDateRange('thismonth')" class="text-sm px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">This Month</button>
                        <button type="button" @click="setDateRange('lastmonth')" class="text-sm px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-50">Last Month</button>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <select id="location"
                                name="location"
                                required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="HV">HV</option>
                            <option value="NYC">NYC</option>
                        </select>
                    </div>

                    <!-- Options -->
                    <div>
                        <label class="flex items-start">
                            <input type="checkbox"
                                   name="overwrite_existing"
                                   value="1"
                                   class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2">
                                <span class="block text-sm font-medium text-gray-700">Overwrite Existing Cases</span>
                                <span class="block text-xs text-gray-500">
                                    If enabled, existing cases will be updated with new data. If disabled, existing cases will be skipped.
                                </span>
                            </span>
                        </label>
                    </div>

                    <!-- Warning -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Important Notes</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Date range cannot exceed 60 days</li>
                                        <li>Large imports may take several minutes to complete</li>
                                        <li>All changes will be logged in the audit trail</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-between items-center">
                    <a href="/cases" class="text-sm text-gray-600 hover:text-gray-900">
                        ← Back to Cases
                    </a>
                    <div class="flex gap-4">
                        <button type="button" @click="window.location.reload()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Reset
                        </button>
                        <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Start Import
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Recent Import History -->
        <div class="mt-6 bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Import History</h3>
            <div x-data="importHistory()" x-init="loadHistory()">
                <div x-show="loading" class="text-center py-8 text-gray-500">
                    Loading history...
                </div>
                <div x-show="!loading && history.length === 0" class="text-center py-8 text-gray-500">
                    No import history found
                </div>
                <div x-show="!loading && history.length > 0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cases</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">By User</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="item in history" :key="item.import_date">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.import_date"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100" x-text="item.location"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.cases_count"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.user_name || item.username"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function importForm() {
    return {
        fromDate: '',
        toDate: '',

        setDateRange(range) {
            const today = new Date();
            const formatDate = (date) => date.toISOString().split('T')[0];

            switch(range) {
                case 'today':
                    this.fromDate = formatDate(today);
                    this.toDate = formatDate(today);
                    break;
                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    this.fromDate = formatDate(yesterday);
                    this.toDate = formatDate(yesterday);
                    break;
                case 'last7days':
                    const last7 = new Date(today);
                    last7.setDate(last7.getDate() - 7);
                    this.fromDate = formatDate(last7);
                    this.toDate = formatDate(today);
                    break;
                case 'last30days':
                    const last30 = new Date(today);
                    last30.setDate(last30.getDate() - 30);
                    this.fromDate = formatDate(last30);
                    this.toDate = formatDate(today);
                    break;
                case 'thismonth':
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    this.fromDate = formatDate(firstDay);
                    this.toDate = formatDate(today);
                    break;
                case 'lastmonth':
                    const lastMonthFirst = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastMonthLast = new Date(today.getFullYear(), today.getMonth(), 0);
                    this.fromDate = formatDate(lastMonthFirst);
                    this.toDate = formatDate(lastMonthLast);
                    break;
            }
        }
    }
}

function importHistory() {
    return {
        loading: true,
        history: [],

        async loadHistory() {
            try {
                const response = await apiRequest('/api/imports/history');
                this.history = response.history || [];
            } catch (error) {
                console.error('Error loading import history:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
