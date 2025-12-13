@extends('layouts.app')

@section('content')
<div x-data="caseDetail()" x-init="init()">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900" x-text="caseData.case_number"></h1>
                <div class="mt-1 flex items-center space-x-4">
                    <span class="text-sm text-gray-500" x-text="caseData.patient || 'No patient'"></span>
                    <span class="text-sm text-gray-400">|</span>
                    <span class="text-sm text-gray-500" x-text="caseData.lab || 'No lab'"></span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Status Badge -->
                <span :class="{
                    'bg-yellow-100 text-yellow-800': caseData.status === 'OPEN',
                    'bg-blue-100 text-blue-800': caseData.status === 'IN_PROGRESS',
                    'bg-green-100 text-green-800': caseData.status === 'DONE',
                    'bg-gray-100 text-gray-800': caseData.status === 'ON_HOLD'
                }" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" x-text="caseData.status"></span>

                <!-- NYC/HV -->
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800" x-text="caseData.nychv"></span>

                <!-- COMBO Badge -->
                <template x-if="caseData.combo">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">COMBO</span>
                </template>

                <!-- Due Date -->
                <span :class="isOverdue ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                    Due: <span x-text="caseData.due_date || 'Not set'" class="ml-1"></span>
                </span>
            </div>
        </div>

        <!-- Route Tabs -->
        <div class="border-t border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'timeline'" :class="activeTab === 'timeline' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    Timeline
                </button>
                <template x-if="hasRoute('COCR')">
                    <button @click="activeTab = 'cocr'" :class="activeTab === 'cocr' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        COCR
                    </button>
                </template>
                <template x-if="hasRoute('SOLIDEX')">
                    <button @click="activeTab = 'solidex'" :class="activeTab === 'solidex' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        SOLIDEX
                    </button>
                </template>
                <template x-if="hasRoute('PRINT')">
                    <button @click="activeTab = 'print'" :class="activeTab === 'print' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                        3D PRINT
                    </button>
                </template>
                <button @click="activeTab = 'notes'" :class="activeTab === 'notes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                    Notes
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white shadow rounded-lg">
        <!-- Timeline Tab -->
        <div x-show="activeTab === 'timeline'" class="p-6">
            <div class="flow-root">
                <ul class="-mb-8">
                    <template x-for="(event, idx) in timeline" :key="idx">
                        <li>
                            <div class="relative pb-8">
                                <template x-if="idx !== timeline.length - 1">
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                </template>
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span :class="event.status === 'DONE' ? 'bg-green-500' : 'bg-gray-400'" class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white">
                                            <template x-if="event.status === 'DONE'">
                                                <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </template>
                                            <template x-if="event.status !== 'DONE'">
                                                <span class="text-white text-xs" x-text="event.dept[0]"></span>
                                            </template>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                <span class="font-medium text-gray-900" x-text="event.stage"></span>
                                                <span class="text-xs ml-2" x-text="event.dept"></span>
                                            </p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            <template x-if="event.completed_at">
                                                <span x-text="event.completed_at"></span>
                                            </template>
                                            <template x-if="event.completed_by_initials">
                                                <span class="ml-2 font-medium" x-text="event.completed_by_initials"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <!-- COCR Tab -->
        <div x-show="activeTab === 'cocr'" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Meta Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">COCR Details</h3>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Type</dt><dd class="font-medium" x-text="cocrMeta?.type || '-'"></dd></div>
                        <div><dt class="text-gray-500">Material</dt><dd class="font-medium" x-text="cocrMeta?.disk_material || '-'"></dd></div>
                        <div><dt class="text-gray-500">MI</dt><dd class="font-medium" x-text="cocrMeta?.mi || '-'"></dd></div>
                        <div><dt class="text-gray-500">Shade</dt><dd class="font-medium" x-text="cocrMeta?.shade || '-'"></dd></div>
                        <div><dt class="text-gray-500">Contact</dt><dd class="font-medium" x-text="cocrMeta?.contact || '-'"></dd></div>
                        <div><dt class="text-gray-500">Occ</dt><dd class="font-medium" x-text="cocrMeta?.occ || '-'"></dd></div>
                    </dl>
                </div>

                <!-- Stages -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Stages</h3>
                    <div class="space-y-2">
                        <template x-for="stage in cocrStages" :key="stage.id">
                            <div :class="stage.status === 'DONE' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'" class="border rounded-lg p-3 flex items-center justify-between">
                                <div>
                                    <span class="font-medium" x-text="stage.stage"></span>
                                    <template x-if="stage.assignee">
                                        <span class="text-sm text-gray-500 ml-2">(<span x-text="stage.assignee.initials"></span>)</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span :class="{
                                        'bg-gray-100 text-gray-800': stage.status === 'PENDING',
                                        'bg-blue-100 text-blue-800': stage.status === 'IN_PROGRESS',
                                        'bg-green-100 text-green-800': stage.status === 'DONE'
                                    }" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" x-text="stage.status"></span>
                                    <template x-if="stage.completed_at">
                                        <span class="text-xs text-gray-500" x-text="stage.completed_at"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- SOLIDEX Tab with Tooth Grid -->
        <div x-show="activeTab === 'solidex'" class="p-6">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tooth Grid</h3>
                <p class="text-sm text-gray-500 mb-4">Click a tooth to view/edit its stages</p>

                <!-- Upper Teeth (1-16) -->
                <div class="mb-4">
                    <p class="text-xs text-gray-400 mb-2">Upper</p>
                    <div class="grid grid-cols-16 gap-1">
                        <template x-for="num in [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16]" :key="num">
                            <button @click="selectTooth(num)" :class="getToothClass(num)" class="w-9 h-9 rounded-lg border text-xs font-medium flex items-center justify-center transition-colors">
                                <span x-text="num"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Lower Teeth (32-17) -->
                <div>
                    <p class="text-xs text-gray-400 mb-2">Lower</p>
                    <div class="grid grid-cols-16 gap-1">
                        <template x-for="num in [32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17]" :key="num">
                            <button @click="selectTooth(num)" :class="getToothClass(num)" class="w-9 h-9 rounded-lg border text-xs font-medium flex items-center justify-center transition-colors">
                                <span x-text="num"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Tooth Detail Panel -->
            <template x-if="selectedTooth">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-medium text-gray-900">Tooth <span x-text="selectedTooth.tooth_number"></span></h4>
                        <span :class="{
                            'bg-yellow-100 text-yellow-800': selectedTooth.status === 'OPEN',
                            'bg-blue-100 text-blue-800': selectedTooth.status === 'IN_PROGRESS',
                            'bg-green-100 text-green-800': selectedTooth.status === 'DONE'
                        }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" x-text="selectedTooth.status"></span>
                    </div>

                    <div class="space-y-2">
                        <template x-for="stage in selectedToothStages" :key="stage.id">
                            <div :class="stage.status === 'DONE' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'" class="border rounded-lg p-3 flex items-center justify-between">
                                <div>
                                    <span class="font-medium" x-text="stage.stage"></span>
                                    <template x-if="stage.assignee">
                                        <span class="text-sm text-gray-500 ml-2">(<span x-text="stage.assignee.initials"></span>)</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span :class="{
                                        'bg-gray-100 text-gray-800': stage.status === 'PENDING',
                                        'bg-blue-100 text-blue-800': stage.status === 'IN_PROGRESS',
                                        'bg-green-100 text-green-800': stage.status === 'DONE'
                                    }" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" x-text="stage.status"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- 3D Print Tab -->
        <div x-show="activeTab === 'print'" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Print Details</h3>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Type</dt><dd class="font-medium" x-text="printMeta?.type || '-'"></dd></div>
                        <div><dt class="text-gray-500">Implant</dt><dd class="font-medium" x-text="printMeta?.implant || '-'"></dd></div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Stages</h3>
                    <div class="space-y-2">
                        <template x-for="stage in printStages" :key="stage.id">
                            <div :class="stage.status === 'DONE' ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'" class="border rounded-lg p-3 flex items-center justify-between">
                                <div>
                                    <span class="font-medium" x-text="stage.stage"></span>
                                </div>
                                <span :class="{
                                    'bg-gray-100 text-gray-800': stage.status === 'PENDING',
                                    'bg-blue-100 text-blue-800': stage.status === 'IN_PROGRESS',
                                    'bg-green-100 text-green-800': stage.status === 'DONE'
                                }" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" x-text="stage.status"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Tab -->
        <div x-show="activeTab === 'notes'" class="p-6">
            <!-- Add Note Form -->
            <div class="mb-6">
                <div class="flex space-x-2 mb-2">
                    <template x-for="tag in availableTags" :key="tag">
                        <button @click="toggleTag(tag)" :class="selectedTags.includes(tag) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-2 py-1 rounded text-xs font-medium" x-text="tag"></button>
                    </template>
                </div>
                <div class="flex space-x-2">
                    <textarea x-model="newNoteBody" placeholder="Add a note..." rows="2" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    <button @click="addNote()" :disabled="!newNoteBody" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                        Add
                    </button>
                </div>
            </div>

            <!-- Notes List -->
            <div class="space-y-4">
                <template x-for="note in notes" :key="note.id">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <!-- Tags -->
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <template x-for="tag in note.tags || []" :key="tag">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-800" x-text="tag"></span>
                                    </template>
                                </div>
                                <!-- Body -->
                                <p class="text-sm text-gray-900" x-text="note.body"></p>
                            </div>
                            <div class="text-right text-xs text-gray-500 ml-4">
                                <p x-text="note.creator?.name"></p>
                                <p x-text="note.created_at"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="notes.length === 0">
                    <p class="text-center text-gray-500 py-8">No notes yet</p>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function caseDetail() {
        const caseId = window.location.pathname.split('/').pop();

        return {
            caseId,
            caseData: {},
            activeTab: 'timeline',
            cocrMeta: null,
            cocrStages: [],
            solidexMeta: null,
            solidexTeeth: [],
            printMeta: null,
            printStages: [],
            notes: [],
            selectedTooth: null,
            selectedToothStages: [],
            newNoteBody: '',
            selectedTags: [],
            availableTags: ['3D PRINT', 'CR', 'COCR', 'SOLIDEX', 'URGENT', 'QC ISSUE'],

            get isOverdue() {
                if (!this.caseData.due_date) return false;
                return new Date(this.caseData.due_date) < new Date() && this.caseData.status !== 'DONE';
            },

            get timeline() {
                const events = [];
                this.cocrStages.forEach(s => events.push({ ...s, dept: 'COCR' }));
                this.printStages.forEach(s => events.push({ ...s, dept: 'PRINT' }));
                this.solidexTeeth.forEach(t => {
                    (t.stages || []).forEach(s => events.push({ ...s, dept: 'SOLIDEX', tooth: t.tooth_number }));
                });
                return events.sort((a, b) => {
                    if (a.completed_at && b.completed_at) return new Date(a.completed_at) - new Date(b.completed_at);
                    return 0;
                });
            },

            async init() {
                await this.loadCase();
            },

            async loadCase() {
                const data = await Alpine.raw(this.$root).__x.$data.apiRequest(`/api/cases/${this.caseId}`);
                if (data.success) {
                    this.caseData = data.data.case;
                    this.cocrMeta = data.data.case.cocr_meta;
                    this.cocrStages = data.data.case.cocr_stages || [];
                    this.solidexMeta = data.data.case.solidex_meta;
                    this.solidexTeeth = data.data.case.solidex_teeth || [];
                    this.printMeta = data.data.case.print_meta;
                    this.printStages = data.data.case.print_stages || [];
                    this.notes = data.data.case.notes || [];
                }
            },

            hasRoute(dept) {
                return this.caseData.active_routes?.some(r => r.target_dept === dept);
            },

            getToothClass(num) {
                const tooth = this.solidexTeeth.find(t => t.tooth_number == num);
                if (!tooth) return 'bg-gray-100 text-gray-400 border-gray-200';
                if (tooth.status === 'DONE') return 'bg-green-500 text-white border-green-600';
                if (tooth.status === 'IN_PROGRESS') return 'bg-blue-500 text-white border-blue-600';
                return 'bg-yellow-100 text-yellow-800 border-yellow-300';
            },

            selectTooth(num) {
                const tooth = this.solidexTeeth.find(t => t.tooth_number == num);
                if (tooth) {
                    this.selectedTooth = tooth;
                    this.selectedToothStages = tooth.stages || [];
                }
            },

            toggleTag(tag) {
                if (this.selectedTags.includes(tag)) {
                    this.selectedTags = this.selectedTags.filter(t => t !== tag);
                } else {
                    this.selectedTags.push(tag);
                }
            },

            async addNote() {
                if (!this.newNoteBody) return;

                await Alpine.raw(this.$root).__x.$data.apiRequest(`/api/cases/${this.caseId}/notes`, {
                    method: 'POST',
                    body: JSON.stringify({
                        body: this.newNoteBody,
                        tags: this.selectedTags
                    })
                });

                this.newNoteBody = '';
                this.selectedTags = [];
                await this.loadCase();
            }
        }
    }
</script>
@endpush
@endsection
