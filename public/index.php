<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creodent AoX Elevate Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">
                        <i class="fas fa-tooth mr-2"></i>
                        Creodent AoX Elevate
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="syncSlackFiles()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition duration-150 ease-in-out flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Sync Slack Files
                    </button>
                    <div id="sync-status" class="text-sm text-gray-600"></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Total Cases</p>
                        <p id="stat-total-cases" class="text-3xl font-bold text-gray-900 mt-2">-</p>
                    </div>
                    <div class="bg-indigo-100 rounded-full p-3">
                        <i class="fas fa-folder text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Open</p>
                        <p id="stat-open-cases" class="text-3xl font-bold text-blue-600 mt-2">-</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <i class="fas fa-folder-open text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">In Progress</p>
                        <p id="stat-in-progress-cases" class="text-3xl font-bold text-yellow-600 mt-2">-</p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-spinner text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 font-medium">Completed</p>
                        <p id="stat-completed-cases" class="text-3xl font-bold text-green-600 mt-2">-</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search-input" placeholder="Search by patient name or case code..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                    <select id="sort-by" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="created_at">Date Created</option>
                        <option value="surgery_date">Surgery Date</option>
                        <option value="patient_name">Patient Name</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Cases Grid -->
        <div id="cases-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Cases will be loaded here -->
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-600">Loading cases...</p>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-12">
            <i class="fas fa-folder-open text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No cases found</h3>
            <p class="text-gray-600 mb-6">Sync Slack files to get started</p>
            <button onclick="syncSlackFiles()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium">
                <i class="fas fa-sync-alt mr-2"></i>
                Sync Now
            </button>
        </div>

    </div>

    <script>
        let allCases = [];

        // Load statistics
        async function loadStatistics() {
            try {
                const response = await fetch('/api/stats.php');
                const result = await response.json();

                if (result.success) {
                    const stats = result.data;
                    document.getElementById('stat-total-cases').textContent = stats.total_cases || 0;
                    document.getElementById('stat-open-cases').textContent = stats.open_cases || 0;
                    document.getElementById('stat-in-progress-cases').textContent = stats.in_progress_cases || 0;
                    document.getElementById('stat-completed-cases').textContent = stats.completed_cases || 0;

                    if (stats.last_sync) {
                        document.getElementById('sync-status').textContent =
                            `Last sync: ${new Date(stats.last_sync.timestamp).toLocaleString()}`;
                    }
                }
            } catch (error) {
                console.error('Failed to load statistics:', error);
            }
        }

        // Load cases
        async function loadCases(filters = {}) {
            const container = document.getElementById('cases-container');
            const loadingState = document.getElementById('loading-state');
            const emptyState = document.getElementById('empty-state');

            loadingState.classList.remove('hidden');
            container.innerHTML = '';

            try {
                let url = '/api/cases.php?';
                if (filters.search) url += `search=${encodeURIComponent(filters.search)}&`;
                if (filters.status) url += `status=${filters.status}&`;
                if (filters.orderBy) url += `order_by=${filters.orderBy}&`;

                const response = await fetch(url);
                const result = await response.json();

                loadingState.classList.add('hidden');

                if (result.success && result.data.length > 0) {
                    allCases = result.data;
                    emptyState.classList.add('hidden');
                    renderCases(result.data);
                } else {
                    emptyState.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Failed to load cases:', error);
                loadingState.classList.add('hidden');
                container.innerHTML = '<div class="col-span-3 text-center text-red-600">Failed to load cases</div>';
            }
        }

        // Render cases
        function renderCases(cases) {
            const container = document.getElementById('cases-container');
            container.innerHTML = cases.map(caseData => `
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition duration-150 ease-in-out">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    ${caseData.patient_name}
                                </h3>
                                <p class="text-sm text-gray-500">${caseData.case_code}</p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full ${getStatusBadgeClass(caseData.status)}">
                                ${formatStatus(caseData.status)}
                            </span>
                        </div>

                        <!-- Details -->
                        <div class="space-y-2 mb-4">
                            ${caseData.surgery_date ? `
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-calendar text-gray-400 w-5"></i>
                                    <span>${formatDate(caseData.surgery_date)}</span>
                                </div>
                            ` : ''}
                            ${caseData.surgeon ? `
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-user-md text-gray-400 w-5"></i>
                                    <span>${caseData.surgeon}</span>
                                </div>
                            ` : ''}
                            ${caseData.arch ? `
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-teeth text-gray-400 w-5"></i>
                                    <span>${caseData.arch}</span>
                                </div>
                            ` : ''}
                        </div>

                        <!-- File Stats -->
                        <div class="grid grid-cols-3 gap-2 mb-4 pt-4 border-t border-gray-100">
                            <div class="text-center">
                                <div class="text-2xl mb-1">📷</div>
                                <div class="text-xs text-gray-600">${caseData.photo_count || 0} photos</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl mb-1">🧊</div>
                                <div class="text-xs text-gray-600">${caseData.stl_count || 0} STL</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl mb-1">🦴</div>
                                <div class="text-xs text-gray-600">${caseData.cbct_count || 0} CBCT</div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <a href="case.php?id=${caseData.id}" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-medium transition duration-150 ease-in-out">
                            View Details
                        </a>
                    </div>
                </div>
            `).join('');
        }

        // Sync Slack files
        async function syncSlackFiles() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...';

            try {
                const response = await fetch('/api/sync.php');
                const result = await response.json();

                if (result.success) {
                    alert(`Sync completed!\n\nNew files: ${result.stats.new_files}\nNew cases: ${result.stats.new_cases}\nExecution time: ${result.execution_time}`);
                    loadCases();
                    loadStatistics();
                } else {
                    alert('Sync failed: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Sync error:', error);
                alert('Sync failed: ' + error.message);
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        // Helper functions
        function getStatusBadgeClass(status) {
            const classes = {
                'open': 'bg-blue-100 text-blue-800',
                'in_progress': 'bg-yellow-100 text-yellow-800',
                'completed': 'bg-green-100 text-green-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }

        function formatStatus(status) {
            return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Event listeners
        document.getElementById('search-input').addEventListener('input', (e) => {
            loadCases({ search: e.target.value });
        });

        document.getElementById('status-filter').addEventListener('change', (e) => {
            loadCases({ status: e.target.value });
        });

        document.getElementById('sort-by').addEventListener('change', (e) => {
            loadCases({ orderBy: e.target.value });
        });

        // Initial load
        loadCases();
        loadStatistics();

        // Auto-refresh statistics every 30 seconds
        setInterval(loadStatistics, 30000);
    </script>

</body>
</html>
