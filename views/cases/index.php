<?php
$content = <<<'HTML'
<?php require __DIR__ . '/../components/nav.php'; ?>

<div class="min-h-screen bg-gray-50" x-data="casesData()">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 sm:px-0 mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Cases</h1>
                <p class="mt-1 text-sm text-gray-600">Manage and track all cases</p>
            </div>
            <button @click="showCreateModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                + New Case
            </button>
        </div>

        <!-- Filters -->
        <div class="px-4 sm:px-0 mb-6">
            <div class="bg-white shadow rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.500ms="loadCases()"
                           placeholder="Search cases..."
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <select x-model="filters.status"
                            @change="loadCases()"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                        <option value="on_hold">On Hold</option>
                    </select>

                    <select x-model="filters.location"
                            @change="loadCases()"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Locations</option>
                        <option value="HV">HV</option>
                        <option value="NYC">NYC</option>
                        <option value="HVNYC">HVNYC</option>
                    </select>

                    <button @click="resetFilters()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium">
                        Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Cases Table -->
        <div class="px-4 sm:px-0">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <!-- Loading -->
                <div x-show="loading" class="flex justify-center items-center py-12">
                    <div class="spinner"></div>
                </div>

                <!-- Table -->
                <div x-show="!loading" x-cloak class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="caseItem in cases" :key="caseItem.id">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a :href="'/cases/' + caseItem.id"
                                           class="text-blue-600 hover:text-blue-900 font-medium"
                                           x-text="caseItem.external_case_no"></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="caseItem.lab_name"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="caseItem.patient_name"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(caseItem.due_date)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="caseItem.location"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-800': caseItem.status === 'done',
                                                  'bg-blue-100 text-blue-800': caseItem.status === 'in_progress',
                                                  'bg-yellow-100 text-yellow-800': caseItem.status === 'new',
                                                  'bg-gray-100 text-gray-800': caseItem.status === 'on_hold'
                                              }"
                                              x-text="caseItem.status"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span x-text="caseItem.items_done + '/' + caseItem.items_count"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a :href="'/cases/' + caseItem.id"
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="cases.length === 0">
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        No cases found
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div x-show="!loading && pagination.total_pages > 1" x-cloak
                     class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button @click="prevPage()"
                                :disabled="pagination.current_page === 1"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                            Previous
                        </button>
                        <button @click="nextPage()"
                                :disabled="pagination.current_page === pagination.total_pages"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium" x-text="pagination.from"></span> to
                                <span class="font-medium" x-text="pagination.to"></span> of
                                <span class="font-medium" x-text="pagination.total"></span> results
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-700">
                                Page <span class="font-medium" x-text="pagination.current_page"></span> of
                                <span class="font-medium" x-text="pagination.total_pages"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function casesData() {
    return {
        loading: true,
        cases: [],
        pagination: {
            total: 0,
            per_page: 50,
            current_page: 1,
            total_pages: 1
        },
        filters: {
            search: '',
            status: '',
            location: ''
        },

        async init() {
            await this.loadCases();
        },

        async loadCases() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });

                const response = await apiRequest(`/api/cases?${params}`);
                if (response.success) {
                    this.cases = response.data.data;
                    this.pagination = response.data.pagination;
                }
            } catch (error) {
                showToast(error.message || 'Failed to load cases', 'error');
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: '',
                location: ''
            };
            this.pagination.current_page = 1;
            this.loadCases();
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.total_pages) {
                this.pagination.current_page++;
                this.loadCases();
            }
        },

        prevPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.loadCases();
            }
        }
    }
}
</script>
HTML;

require __DIR__ . '/../layout.php';
