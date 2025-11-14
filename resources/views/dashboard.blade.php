<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creodent AoX Elevate Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .badge {
            @apply px-2 py-1 text-xs rounded-full;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Login Page -->
    <div id="loginPage" class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold text-center mb-6">Creodent AoX Dashboard</h1>
            <form id="loginForm">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Email</label>
                    <input type="email" id="loginEmail" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Password</label>
                    <input type="password" id="loginPassword" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                    Login
                </button>
                <div id="loginError" class="mt-4 text-red-500 text-sm text-center hidden"></div>
            </form>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div id="dashboard" class="hidden">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Creodent AoX Elevate</h1>
                    <p class="text-sm text-gray-500">Case Management Dashboard</p>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="syncCases()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <i class="fas fa-sync"></i> Sync
                    </button>
                    <button onclick="showAdminPanel()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        <i class="fas fa-cog"></i> Admin
                    </button>
                    <div class="flex items-center gap-2">
                        <span id="userName" class="text-gray-700"></span>
                        <button onclick="logout()" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Statistics -->
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="grid grid-cols-5 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Total Cases</div>
                    <div id="statTotal" class="text-2xl font-bold">0</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Open</div>
                    <div id="statOpen" class="text-2xl font-bold text-blue-600">0</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm text-gray-500">In Progress</div>
                    <div id="statInProgress" class="text-2xl font-bold text-yellow-600">0</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Completed</div>
                    <div id="statCompleted" class="text-2xl font-bold text-green-600">0</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="text-sm text-gray-500">Overdue</div>
                    <div id="statOverdue" class="text-2xl font-bold text-red-600">0</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Search</label>
                        <input type="text" id="filterSearch" placeholder="Patient name..." class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Status</label>
                        <select id="filterStatus" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Sort By</label>
                        <select id="filterSort" class="w-full px-3 py-2 border rounded-lg">
                            <option value="surgery_date">Surgery Date</option>
                            <option value="due_date">Due Date</option>
                            <option value="created_at">Created Date</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="applyFilters()" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cases Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surgery Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Photos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Files</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="casesTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Cases will be loaded here -->
                    </tbody>
                </table>
                <div id="loadingSpinner" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                </div>
                <div id="noCases" class="text-center py-8 text-gray-500 hidden">
                    No cases found
                </div>
            </div>
        </div>
    </div>

    <!-- Case Detail Modal -->
    <div id="caseDetailModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto m-4">
            <div class="p-6 border-b flex justify-between items-center">
                <h2 id="modalTitle" class="text-2xl font-bold">Case Details</h2>
                <button onclick="closeCaseModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Case details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
</body>
</html>
