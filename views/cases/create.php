<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <a href="/cases" class="mr-4 text-gray-600 hover:text-gray-900">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Create New Case</h1>
            </div>
            <p class="text-sm text-gray-600">Enter case information manually or fetch from Evolution Portal</p>
        </div>

        <form method="POST" action="/cases" x-data="createCaseForm()" class="space-y-6">
            <!-- CSRF Token -->
            <input type="hidden" name="_csrf_token" value="<?= \App\Core\Session::generateCsrfToken() ?>">

            <!-- Fetch from Evolution -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-4">Fetch from Evolution Portal</h3>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <input type="text"
                               x-model="evolutionCaseNo"
                               placeholder="Enter Case Number (e.g., 2025-48801)"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <select x-model="evolutionLocation" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="HV">HV</option>
                            <option value="NYC">NYC</option>
                        </select>
                    </div>
                    <button type="button"
                            @click="fetchFromEvolution"
                            :disabled="!evolutionCaseNo || loading"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading">Fetch Case</span>
                        <span x-show="loading">Loading...</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-blue-700">This will fetch case information from Evolution Portal and pre-fill the form below</p>
            </div>

            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- External Case Number -->
                    <div>
                        <label for="external_case_no" class="block text-sm font-medium text-gray-700 mb-1">
                            Case Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="external_case_no"
                               name="external_case_no"
                               required
                               x-model="formData.external_case_no"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Source -->
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700 mb-1">
                            Source <span class="text-red-500">*</span>
                        </label>
                        <select id="source"
                                name="source"
                                required
                                x-model="formData.source"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="manual">Manual Entry</option>
                            <option value="evolution_web_portal">Evolution Portal</option>
                            <option value="windows_app">Windows App</option>
                        </select>
                    </div>

                    <!-- Patient Name -->
                    <div>
                        <label for="patient_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Patient Name
                        </label>
                        <input type="text"
                               id="patient_name"
                               name="patient_name"
                               x-model="formData.patient_name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Lab Name -->
                    <div>
                        <label for="lab_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Lab Name
                        </label>
                        <input type="text"
                               id="lab_name"
                               name="lab_name"
                               x-model="formData.lab_name"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Due Date
                        </label>
                        <input type="date"
                               id="due_date"
                               name="due_date"
                               x-model="formData.due_date"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>
                        <select id="status"
                                name="status"
                                x-model="formData.status"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                            Priority
                        </label>
                        <select id="priority"
                                name="priority"
                                x-model="formData.priority"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">
                            Location
                        </label>
                        <select id="location"
                                name="location"
                                x-model="formData.location"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select Location</option>
                            <option value="HV">HV</option>
                            <option value="NYC">NYC</option>
                            <option value="HVNYC">HVNYC</option>
                        </select>
                    </div>
                </div>

                <!-- Additional Fields -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- PAN -->
                    <div>
                        <label for="pan" class="block text-sm font-medium text-gray-700 mb-1">
                            PAN #
                        </label>
                        <input type="text"
                               id="pan"
                               name="pan"
                               x-model="formData.pan"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Patient Number -->
                    <div>
                        <label for="patient_number" class="block text-sm font-medium text-gray-700 mb-1">
                            Patient Number
                        </label>
                        <input type="text"
                               id="patient_number"
                               name="patient_number"
                               x-model="formData.patient_number"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mt-6">
                    <label for="instructions" class="block text-sm font-medium text-gray-700 mb-1">
                        Instructions
                    </label>
                    <textarea id="instructions"
                              name="instructions"
                              rows="3"
                              x-model="formData.instructions"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <!-- Notes -->
                <div class="mt-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              x-model="formData.notes"
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <a href="/cases" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    Create Case
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function createCaseForm() {
    return {
        evolutionCaseNo: '',
        evolutionLocation: 'HV',
        loading: false,
        formData: {
            external_case_no: '',
            source: 'manual',
            patient_name: '',
            lab_name: '',
            due_date: '',
            status: 'new',
            priority: 'normal',
            location: '',
            pan: '',
            patient_number: '',
            instructions: '',
            notes: ''
        },

        async fetchFromEvolution() {
            if (!this.evolutionCaseNo) return;

            this.loading = true;

            try {
                const response = await apiRequest('/api/cases/fetch-evolution', {
                    method: 'POST',
                    body: JSON.stringify({
                        case_number: this.evolutionCaseNo,
                        location: this.evolutionLocation
                    })
                });

                // Fill form with fetched data
                const data = response.data;
                this.formData.external_case_no = data.external_case_no || this.evolutionCaseNo;
                this.formData.source = 'evolution_web_portal';
                this.formData.patient_name = data.patient_name || '';
                this.formData.lab_name = data.lab_name || '';
                this.formData.due_date = data.due_date || '';
                this.formData.location = this.evolutionLocation;
                this.formData.pan = data.pan || '';
                this.formData.patient_number = data.patient_number || '';
                this.formData.instructions = data.instructions || '';

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: 'Case information fetched successfully',
                        type: 'success'
                    }
                }));

            } catch (error) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: error.message || 'Failed to fetch case from Evolution Portal',
                        type: 'error'
                    }
                }));
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
