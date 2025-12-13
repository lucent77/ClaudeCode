@extends('layouts.app')

@section('content')
<div x-data="myTasks()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Tasks</h1>
        <p class="text-sm text-gray-500">Tasks assigned to you or available for pickup</p>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6 p-4">
        <div class="flex flex-wrap gap-4 items-center">
            <!-- Department Filter -->
            <div class="flex space-x-2">
                <button @click="setFilter('dept', null)" :class="!filters.dept ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    All
                </button>
                <button @click="setFilter('dept', 'COCR')" :class="filters.dept === 'COCR' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    COCR
                </button>
                <button @click="setFilter('dept', 'SOLIDEX')" :class="filters.dept === 'SOLIDEX' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    SOLIDEX
                </button>
                <button @click="setFilter('dept', 'PRINT')" :class="filters.dept === 'PRINT' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    3D PRINT
                </button>
            </div>

            <!-- Due Filter -->
            <div class="flex space-x-2">
                <button @click="setFilter('overdue', !filters.overdue)" :class="filters.overdue ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    Overdue Only
                </button>
            </div>

            <!-- Stage Filter -->
            <select x-model="filters.stage" @change="loadTasks()" class="rounded-md border-gray-300 text-sm">
                <option value="">All Stages</option>
                <template x-for="stage in availableStages" :key="stage">
                    <option :value="stage" x-text="stage"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Task List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <ul class="divide-y divide-gray-200">
            <template x-for="task in tasks" :key="`${task.dept}-${task.id}`">
                <li class="hover:bg-gray-50">
                    <div class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Department Badge -->
                                <span :class="{
                                    'bg-indigo-100 text-indigo-800': task.dept === 'COCR',
                                    'bg-green-100 text-green-800': task.dept === 'SOLIDEX',
                                    'bg-purple-100 text-purple-800': task.dept === 'PRINT'
                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="task.dept"></span>

                                <!-- Stage Chip -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 ring-2 ring-inset ring-gray-300" x-text="task.stage"></span>

                                <!-- Case Info -->
                                <div>
                                    <a :href="'/cases/' + task.case_id" class="text-sm font-medium text-indigo-600 hover:text-indigo-900" x-text="task.case_number"></a>
                                    <p class="text-sm text-gray-500">
                                        <span x-text="task.patient || 'No patient'"></span>
                                        <span class="mx-1">|</span>
                                        <span x-text="task.lab || 'No lab'"></span>
                                        <template x-if="task.tooth_number">
                                            <span class="ml-2 text-green-600">(Tooth <span x-text="task.tooth_number"></span>)</span>
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <!-- Due Date Badge -->
                                <span :class="{
                                    'bg-red-100 text-red-800': task.is_overdue,
                                    'bg-yellow-100 text-yellow-800': task.is_due_soon && !task.is_overdue,
                                    'bg-gray-100 text-gray-800': !task.is_overdue && !task.is_due_soon
                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <template x-if="task.is_overdue">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </template>
                                    <span x-text="task.due_date || 'No due date'"></span>
                                </span>

                                <!-- NYC/HV Badge -->
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" x-text="task.nychv"></span>

                                <!-- Complete Button -->
                                <button @click="openCompleteModal(task)" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Complete
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </template>

            <template x-if="tasks.length === 0 && !loading">
                <li class="px-4 py-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No tasks</h3>
                    <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
                </li>
            </template>
        </ul>
    </div>

    <!-- Complete Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showModal = false"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Mark Stage Complete</h3>
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <p class="text-sm text-gray-600">Case: <span class="font-medium" x-text="selectedTask?.case_number"></span></p>
                        <p class="text-sm text-gray-600">Stage: <span class="font-medium" x-text="selectedTask?.stage"></span></p>
                    </div>

                    <div class="space-y-4">
                        <!-- Dynamic required fields -->
                        <template x-if="requiresMachine">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Machine *</label>
                                <select x-model="formData.machine" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select...</option>
                                    <template x-for="m in machines" :key="m.id">
                                        <option :value="m.name" x-text="m.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <template x-if="requiresOven">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Oven *</label>
                                <select x-model="formData.oven" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select...</option>
                                    <template x-for="o in ovens" :key="o.id">
                                        <option :value="o.name" x-text="o.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <template x-if="requiresPrinter">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Printer *</label>
                                <select x-model="formData.printer" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select...</option>
                                    <template x-for="p in printers" :key="p.id">
                                        <option :value="p.name" x-text="p.name"></option>
                                    </template>
                                </select>
                            </div>
                        </template>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Note</label>
                            <textarea x-model="formData.note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="submitComplete()" :disabled="submitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span x-show="!submitting">Complete</span>
                        <span x-show="submitting">Processing...</span>
                    </button>
                    <button @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function myTasks() {
        return {
            tasks: [],
            loading: false,
            filters: { dept: null, stage: '', overdue: false },
            showModal: false,
            selectedTask: null,
            formData: { machine: '', oven: '', printer: '', note: '' },
            submitting: false,
            machines: [],
            ovens: [],

            get availableStages() {
                const all = ['TRANS', 'DESIGN', 'CAM', 'CNC', 'OVENS', 'QC', 'TRANSCAN', 'PRECAD', 'CAD', 'PRECAM', 'PREP', 'NESTING', 'PRINT', 'POST'];
                return [...new Set(all)];
            },

            get requiresMachine() {
                return this.selectedTask?.stage === 'CNC';
            },

            get requiresOven() {
                return this.selectedTask?.stage === 'OVENS';
            },

            get requiresPrinter() {
                return ['NESTING', 'PRINT'].includes(this.selectedTask?.stage);
            },

            get printers() {
                return this.machines.filter(m => m.dept === 'PRINT');
            },

            async init() {
                await Promise.all([this.loadTasks(), this.loadLookups()]);
            },

            setFilter(key, value) {
                this.filters[key] = value;
                this.loadTasks();
            },

            async loadTasks() {
                this.loading = true;
                let url = '/api/my/tasks?';
                if (this.filters.dept) url += `dept=${this.filters.dept}&`;
                if (this.filters.stage) url += `stage=${this.filters.stage}&`;
                if (this.filters.overdue) url += `overdue=1&`;

                const data = await Alpine.raw(this.$root).__x.$data.apiRequest(url);
                this.tasks = data.data?.tasks || [];
                this.loading = false;
            },

            async loadLookups() {
                const [m, o] = await Promise.all([
                    Alpine.raw(this.$root).__x.$data.apiRequest('/api/admin/lookups/machines'),
                    Alpine.raw(this.$root).__x.$data.apiRequest('/api/admin/lookups/ovens')
                ]);
                this.machines = m.data?.items || [];
                this.ovens = o.data?.items || [];
            },

            openCompleteModal(task) {
                this.selectedTask = task;
                this.formData = { machine: '', oven: '', printer: '', note: '' };
                this.showModal = true;
            },

            async submitComplete() {
                this.submitting = true;
                const t = this.selectedTask;

                const endpoint = t.dept === 'SOLIDEX'
                    ? `/api/solidex/tooth/stages/${t.id}/complete`
                    : t.dept === 'PRINT'
                        ? `/api/print/stages/${t.id}/complete`
                        : `/api/cocr/stages/${t.id}/complete`;

                const data = await Alpine.raw(this.$root).__x.$data.apiRequest(endpoint, {
                    method: 'POST',
                    body: JSON.stringify({ ...this.formData, _expected_version: t.version })
                });

                this.submitting = false;
                this.showModal = false;

                if (data.success) {
                    Alpine.raw(this.$root).__x.$data.showToast('Stage completed!');
                }
                await this.loadTasks();
            }
        }
    }
</script>
@endpush
@endsection
