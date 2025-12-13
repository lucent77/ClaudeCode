@extends('layouts.app')

@section('content')
<div x-data="kanban()" x-init="init()">
    <!-- Department Selector -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex space-x-4">
            <button @click="selectDept('COCR')" :class="dept === 'COCR' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-4 py-2 rounded-md text-sm font-medium border border-gray-300">
                COCR
            </button>
            <button @click="selectDept('SOLIDEX')" :class="dept === 'SOLIDEX' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-4 py-2 rounded-md text-sm font-medium border border-gray-300">
                SOLIDEX
            </button>
            <button @click="selectDept('PRINT')" :class="dept === 'PRINT' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-4 py-2 rounded-md text-sm font-medium border border-gray-300">
                3D PRINT
            </button>
        </div>
        <div class="flex items-center space-x-2">
            <input type="text" x-model="search" placeholder="Search cases..." class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <button @click="loadCases()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="flex space-x-4 overflow-x-auto pb-4">
        <template x-for="stage in stages" :key="stage">
            <div class="flex-shrink-0 w-80">
                <!-- Lane Header -->
                <div :class="deptColor" class="rounded-t-lg px-4 py-3 border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-white" x-text="stage"></h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white" x-text="getStageCount(stage)"></span>
                    </div>
                </div>

                <!-- Lane Content -->
                <div class="bg-gray-100 rounded-b-lg p-2 min-h-[calc(100vh-300px)] space-y-2">
                    <template x-for="item in getStageItems(stage)" :key="item.id">
                        <div class="bg-white rounded-lg shadow p-3 cursor-pointer hover:shadow-md transition-shadow" @click="openCase(item)">
                            <!-- Case Header -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-sm text-gray-900" x-text="item.case_number"></span>
                                <span :class="{
                                    'bg-red-100 text-red-800': item.is_overdue,
                                    'bg-yellow-100 text-yellow-800': item.is_due_soon && !item.is_overdue,
                                    'bg-gray-100 text-gray-800': !item.is_overdue && !item.is_due_soon
                                }" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" x-text="item.due_date || 'No due date'"></span>
                            </div>

                            <!-- Patient/Lab -->
                            <div class="text-xs text-gray-500 mb-2">
                                <span x-text="item.patient || 'No patient'"></span>
                                <span class="mx-1">|</span>
                                <span x-text="item.lab || 'No lab'"></span>
                            </div>

                            <!-- Tooth Number (for SOLIDEX) -->
                            <template x-if="dept === 'SOLIDEX' && item.tooth_number">
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Tooth <span x-text="item.tooth_number" class="ml-1"></span>
                                    </span>
                                </div>
                            </template>

                            <!-- Assignee -->
                            <div class="flex items-center justify-between">
                                <template x-if="item.assignee">
                                    <span class="inline-flex items-center text-xs text-gray-500">
                                        <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-800 flex items-center justify-center text-xs font-medium mr-1" x-text="item.assignee.initials"></span>
                                        <span x-text="item.assignee.name"></span>
                                    </span>
                                </template>
                                <template x-if="!item.assignee">
                                    <span class="text-xs text-gray-400">Unassigned</span>
                                </template>

                                <!-- Complete Button -->
                                <button @click.stop="completeStage(item)" class="text-xs bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700">
                                    Complete
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="getStageCount(stage) === 0">
                        <div class="text-center py-8 text-gray-400 text-sm">No items</div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Complete Stage Modal -->
    <div x-show="showCompleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCompleteModal = false"></div>
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Complete Stage</h3>

                    <div class="space-y-4">
                        <!-- Machine selector (for CNC stages) -->
                        <template x-if="selectedItem?.stage === 'CNC'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Machine *</label>
                                <select x-model="completeData.machine" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select machine...</option>
                                    <template x-for="machine in machines" :key="machine.id">
                                        <option :value="machine.name" x-text="machine.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <!-- Oven selector (for OVENS stage) -->
                        <template x-if="selectedItem?.stage === 'OVENS'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Oven *</label>
                                <select x-model="completeData.oven" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select oven...</option>
                                    <template x-for="oven in ovens" :key="oven.id">
                                        <option :value="oven.name" x-text="oven.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <!-- Printer selector (for NESTING/PRINT stages) -->
                        <template x-if="selectedItem?.stage === 'NESTING' || selectedItem?.stage === 'PRINT'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Printer *</label>
                                <select x-model="completeData.printer" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select printer...</option>
                                    <template x-for="machine in machines.filter(m => m.dept === 'PRINT')" :key="machine.id">
                                        <option :value="machine.name" x-text="machine.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <!-- Note -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Note (optional)</label>
                            <textarea x-model="completeData.note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button @click="submitComplete()" :disabled="completing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm disabled:opacity-50">
                        <span x-show="!completing">Complete</span>
                        <span x-show="completing">Completing...</span>
                    </button>
                    <button @click="showCompleteModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function kanban() {
        return {
            dept: 'COCR',
            search: '',
            items: [],
            machines: [],
            ovens: [],
            showCompleteModal: false,
            selectedItem: null,
            completeData: {},
            completing: false,

            get stages() {
                const stageMap = {
                    'COCR': ['TRANS', 'DESIGN', 'CAM', 'CNC', 'OVENS', 'QC'],
                    'SOLIDEX': ['TRANSCAN', 'PRECAD', 'CAD', 'PRECAM', 'CNC', 'QC'],
                    'PRINT': ['TRANS', 'PREP', 'DESIGN', 'NESTING', 'PRINT', 'POST', 'QC']
                };
                return stageMap[this.dept] || [];
            },

            get deptColor() {
                const colors = {
                    'COCR': 'bg-indigo-600',
                    'SOLIDEX': 'bg-green-600',
                    'PRINT': 'bg-purple-600'
                };
                return colors[this.dept] || 'bg-gray-600';
            },

            async init() {
                await Promise.all([
                    this.loadCases(),
                    this.loadLookups()
                ]);
            },

            async selectDept(dept) {
                this.dept = dept;
                await this.loadCases();
            },

            async loadCases() {
                const data = await Alpine.raw(this.$root).__x.$data.apiRequest(`/api/my/tasks?dept=${this.dept}`);
                if (data.success) {
                    this.items = data.data.tasks;
                }
            },

            async loadLookups() {
                const [machinesData, ovensData] = await Promise.all([
                    Alpine.raw(this.$root).__x.$data.apiRequest('/api/admin/lookups/machines'),
                    Alpine.raw(this.$root).__x.$data.apiRequest('/api/admin/lookups/ovens')
                ]);
                this.machines = machinesData.data?.items || [];
                this.ovens = ovensData.data?.items || [];
            },

            getStageItems(stage) {
                return this.items.filter(item => item.stage === stage);
            },

            getStageCount(stage) {
                return this.getStageItems(stage).length;
            },

            openCase(item) {
                window.location.href = `/cases/${item.case_id}`;
            },

            completeStage(item) {
                this.selectedItem = item;
                this.completeData = { machine: '', oven: '', printer: '', note: '' };
                this.showCompleteModal = true;
            },

            async submitComplete() {
                this.completing = true;

                const endpoint = this.dept === 'SOLIDEX'
                    ? `/api/solidex/tooth/stages/${this.selectedItem.id}/complete`
                    : this.dept === 'PRINT'
                        ? `/api/print/stages/${this.selectedItem.id}/complete`
                        : `/api/cocr/stages/${this.selectedItem.id}/complete`;

                const body = {
                    ...this.completeData,
                    _expected_version: this.selectedItem.version
                };

                const data = await Alpine.raw(this.$root).__x.$data.apiRequest(endpoint, {
                    method: 'POST',
                    body: JSON.stringify(body)
                });

                this.completing = false;
                this.showCompleteModal = false;

                if (data.conflict) {
                    // Refresh the list
                    await this.loadCases();
                } else if (data.success) {
                    Alpine.raw(this.$root).__x.$data.showToast('Stage completed successfully');
                    await this.loadCases();
                }
            }
        }
    }
</script>
@endpush
@endsection
