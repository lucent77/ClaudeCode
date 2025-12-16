<!-- Dashboard Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" x-data="dashboardStats()">
    <!-- Total Cases -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Total Cases</p>
                <p class="text-3xl font-bold text-gray-800" x-text="stats.total">0</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">
            <span class="text-green-500" x-text="'+' + stats.created_today">+0</span> today
        </p>
    </div>

    <!-- Open Cases -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Open Cases</p>
                <p class="text-3xl font-bold text-yellow-600" x-text="stats.open">0</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Awaiting response</p>
    </div>

    <!-- Confirmed -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Confirmed</p>
                <p class="text-3xl font-bold text-green-600" x-text="stats.by_status?.Confirmed || 0">0</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Ready for production</p>
    </div>

    <!-- Action Needed -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-1">Action Needed</p>
                <p class="text-3xl font-bold text-red-600" x-text="stats.by_status?.['Action Needed'] || 0">0</p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">Requires attention</p>
    </div>
</div>

<!-- Recent Cases and Activity -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Cases Table -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100" x-data="recentCases()">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Recent Cases</h3>
                <a href="/cases" class="text-sm text-primary-600 hover:text-primary-700">View All</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="case_ in cases" :key="case_.id">
                        <tr class="hover:bg-gray-50 cursor-pointer" @click="window.location.href = '/cases/' + case_.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-primary-600" x-text="case_.case_number"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-800" x-text="case_.patient_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600" x-text="case_.client_name"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(case_.status)" x-text="case_.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500" x-text="formatDate(case_.created_at)"></span>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="cases.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No cases found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Stats / Activity -->
    <div class="space-y-6">
        <!-- Status Breakdown -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ stats: {} }" x-init="fetch('/api/cases/stats').then(r => r.json()).then(d => stats = d.data?.stats || {})">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Breakdown</h3>
            <div class="space-y-3">
                <template x-for="(count, status) in stats.by_status" :key="status">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2" :class="{
                                'bg-yellow-400': status === 'Pending',
                                'bg-green-400': status === 'Confirmed',
                                'bg-red-400': status === 'Action Needed',
                                'bg-orange-400': status === 'Confirmed with Action Needed',
                                'bg-gray-400': status === 'Resolved'
                            }"></span>
                            <span class="text-sm text-gray-600" x-text="status"></span>
                        </div>
                        <span class="text-sm font-semibold text-gray-800" x-text="count"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Priority Breakdown -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ stats: {} }" x-init="fetch('/api/cases/stats').then(r => r.json()).then(d => stats = d.data?.stats || {})">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Priority Distribution</h3>
            <div class="space-y-3">
                <template x-for="(count, priority) in stats.by_priority" :key="priority">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2" :class="{
                                'bg-gray-300': priority === 'Low',
                                'bg-blue-400': priority === 'Normal',
                                'bg-orange-400': priority === 'High',
                                'bg-red-500': priority === 'Urgent'
                            }"></span>
                            <span class="text-sm text-gray-600" x-text="priority"></span>
                        </div>
                        <span class="text-sm font-semibold text-gray-800" x-text="count"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <button @click="$dispatch('open-new-case')" class="w-full flex items-center px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create New Case
                </button>
                <a href="/clients" class="w-full flex items-center px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Add New Client
                </a>
                <a href="/templates" class="w-full flex items-center px-4 py-3 text-left text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Manage Templates
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardStats() {
    return {
        stats: {},

        init() {
            this.loadStats();
        },

        async loadStats() {
            try {
                const response = await fetch('/api/cases/stats');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.data.stats;
                }
            } catch (e) {
                console.error('Failed to load stats', e);
            }
        }
    }
}

function recentCases() {
    return {
        cases: [],

        init() {
            this.loadCases();
        },

        async loadCases() {
            try {
                const response = await fetch('/api/cases?limit=5&sort=received_desc');
                const data = await response.json();
                if (data.success) {
                    this.cases = data.data.cases;
                }
            } catch (e) {
                console.error('Failed to load cases', e);
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getStatusClass(status) {
            const classes = {
                'Pending': 'status-pending',
                'Confirmed': 'status-confirmed',
                'Action Needed': 'status-action-needed',
                'Confirmed with Action Needed': 'status-confirmed-action',
                'Resolved': 'status-resolved'
            };
            return classes[status] || 'status-pending';
        }
    }
}
</script>
