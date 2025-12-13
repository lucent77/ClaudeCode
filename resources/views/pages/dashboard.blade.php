@extends('layouts.app')

@section('content')
<div x-data="dashboard()" x-init="init()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Intake</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="stats.today_intake || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Due Today</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="stats.due_today || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                            <dd class="text-lg font-semibold text-red-600" x-text="stats.overdue || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="stats.in_progress || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WIP by Department -->
    <div class="bg-white shadow rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Work in Progress by Department</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="text-center">
                    <div class="text-3xl font-bold text-indigo-600" x-text="stats.wip_by_dept?.COCR || 0"></div>
                    <div class="text-sm text-gray-500">COCR</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600" x-text="stats.wip_by_dept?.SOLIDEX || 0"></div>
                    <div class="text-sm text-gray-500">SOLIDEX</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600" x-text="stats.wip_by_dept?.PRINT || 0"></div>
                    <div class="text-sm text-gray-500">3D PRINT</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stage Distribution -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 mb-8">
        <!-- COCR Stages -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-indigo-50">
                <h3 class="text-lg leading-6 font-medium text-indigo-900">COCR Stages</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <template x-for="(count, stage) in stats.cocr_stages || {}" :key="stage">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600" x-text="stage"></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800" x-text="count"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- SOLIDEX Stages -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-green-50">
                <h3 class="text-lg leading-6 font-medium text-green-900">SOLIDEX Stages</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <template x-for="(count, stage) in stats.solidex_stages || {}" :key="stage">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600" x-text="stage"></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" x-text="count"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- 3D Print Stages -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-purple-50">
                <h3 class="text-lg leading-6 font-medium text-purple-900">3D Print Stages</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                <template x-for="(count, stage) in stats.print_stages || {}" :key="stage">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600" x-text="stage"></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800" x-text="count"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Due Risk Cases -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Due Risk Cases</h3>
            <p class="mt-1 text-sm text-gray-500">Cases due within 24 hours or overdue</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Routes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="item in dueRisk.due_soon || []" :key="item.id">
                        <tr :class="item.is_overdue ? 'bg-red-50' : ''">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a :href="'/cases/' + item.id" class="text-indigo-600 hover:text-indigo-900 font-medium" x-text="item.case_number"></a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.patient"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="item.lab"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="item.is_overdue ? 'text-red-600 font-medium' : 'text-gray-900'" x-text="item.due_date"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-yellow-100 text-yellow-800': item.status === 'OPEN',
                                    'bg-blue-100 text-blue-800': item.status === 'IN_PROGRESS',
                                    'bg-green-100 text-green-800': item.status === 'DONE',
                                    'bg-gray-100 text-gray-800': item.status === 'ON_HOLD'
                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="item.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-for="route in item.routes" :key="route">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mr-1" x-text="route"></span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function dashboard() {
        return {
            stats: {},
            dueRisk: {},

            async init() {
                await Promise.all([
                    this.loadStats(),
                    this.loadDueRisk()
                ]);
            },

            async loadStats() {
                const data = await Alpine.raw(this.$root).__x.$data.apiRequest('/api/dashboard/stats');
                if (data.success) {
                    this.stats = data.data;
                }
            },

            async loadDueRisk() {
                const data = await Alpine.raw(this.$root).__x.$data.apiRequest('/api/dashboard/due-risk');
                if (data.success) {
                    this.dueRisk = data.data;
                }
            }
        }
    }
</script>
@endpush
@endsection
