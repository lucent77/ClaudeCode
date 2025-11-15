<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Creodent AoX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <!-- Logo and Title -->
                <div class="flex items-center space-x-3">
                    <i class="fas fa-tooth text-2xl text-indigo-600"></i>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Creodent AoX</h1>
                        <p class="text-xs text-gray-500">Elevate Dashboard</p>
                    </div>
                </div>

                <!-- Navigation Items -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="/dashboard" class="text-indigo-600 font-medium border-b-2 border-indigo-600 pb-1">
                        <i class="fas fa-th-large mr-2"></i>Dashboard
                    </a>
                    <a href="#" class="text-gray-600 hover:text-indigo-600 transition">
                        <i class="fas fa-folder-open mr-2"></i>Cases
                    </a>
                    <a href="#" class="text-gray-600 hover:text-indigo-600 transition">
                        <i class="fas fa-chart-bar mr-2"></i>Analytics
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">

                    <!-- Sync Button -->
                    <button id="syncButton" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center shadow-sm">
                        <i class="fas fa-sync-alt mr-2"></i>Sync with Slack
                    </button>

                    <!-- User Profile -->
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-2 hover:bg-gray-100 rounded-lg px-3 py-2 transition">
                            <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <span id="userName" class="text-sm font-medium text-gray-700"></span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-2"></i>Profile
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                            <hr class="my-1">
                            <a href="#" id="logoutButton" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Total Cases -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Cases</p>
                        <p id="totalCases" class="text-3xl font-bold text-gray-800">0</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-folder text-xl text-indigo-600"></i>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">In Progress</p>
                        <p id="inProgressCases" class="text-3xl font-bold text-gray-800">0</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-spinner text-xl text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <!-- Upcoming Surgeries -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Upcoming Surgeries</p>
                        <p id="upcomingSurgeries" class="text-3xl font-bold text-gray-800">0</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-check text-xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Overdue Cases -->
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Overdue Cases</p>
                        <p id="overdueCases" class="text-3xl font-bold text-gray-800">0</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-xl text-red-600"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">

                <!-- Search Bar -->
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search cases by patient name..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex items-center space-x-4">

                    <!-- Status Filter -->
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <!-- New Case Button -->
                    <button id="newCaseButton" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center shadow-sm">
                        <i class="fas fa-plus mr-2"></i>New Case
                    </button>

                </div>

            </div>
        </div>

        <!-- Cases Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">

            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list mr-2"></i>Cases
                </h2>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-indigo-600 mb-4"></i>
                <p class="text-gray-600">Loading cases...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden p-8 text-center">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-600">No cases found</p>
                <button class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Create your first case
                </button>
            </div>

            <!-- Table -->
            <div id="casesTableContainer" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Surgery Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="casesTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Table rows will be inserted here -->
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- JavaScript -->
    <script src="/js/dashboard.js"></script>

</body>
</html>
