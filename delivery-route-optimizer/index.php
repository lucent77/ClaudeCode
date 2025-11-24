<?php
/**
 * Main Customer Selection Page
 * Smart Delivery Route Optimizer
 */

require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .customer-row:hover { background-color: #f3f4f6; }
        .customer-row.selected { background-color: #dbeafe; }
        .loading { opacity: 0.5; pointer-events: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold"><?= APP_NAME ?></h1>
                <nav class="flex gap-4">
                    <a href="index.php" class="px-3 py-1 bg-blue-700 rounded">Customers</a>
                    <a href="route-view.php" class="px-3 py-1 hover:bg-blue-700 rounded">Route Map</a>
                    <a href="import_json_customers.php" class="px-3 py-1 hover:bg-blue-700 rounded">Import</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="searchInput" placeholder="Name, Account #, Practice..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Route</label>
                    <select id="routeFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="Local Courier">Local Courier</option>
                        <option value="all">All Routes</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <select id="cityFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Cities</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Coordinates</label>
                    <select id="coordsFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All</option>
                        <option value="1">Has Coordinates</option>
                        <option value="0">Missing Coordinates</option>
                    </select>
                </div>
                <button onclick="loadCustomers()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Search
                </button>
            </div>
        </div>

        <!-- Selection Info -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <span id="selectedCount" class="text-lg font-semibold">0</span>
                    <span class="text-gray-600">customers selected</span>
                    <button onclick="clearSelection()" class="ml-4 text-sm text-red-600 hover:underline">Clear</button>
                    <button onclick="selectAll()" class="ml-2 text-sm text-blue-600 hover:underline">Select All</button>
                </div>
                <button onclick="generateRoute()" id="generateRouteBtn" disabled
                        class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    Generate Optimized Route
                </button>
            </div>
        </div>

        <!-- Customer Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Practice/Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">City</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coords</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Loading customers...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalCount">0</span> customers
                </div>
                <div class="flex gap-2">
                    <button onclick="prevPage()" id="prevBtn" disabled class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50">Previous</button>
                    <span id="pageInfo" class="px-3 py-1">Page 1</span>
                    <button onclick="nextPage()" id="nextBtn" disabled class="px-3 py-1 border rounded hover:bg-gray-100 disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Route Generation Modal -->
    <div id="routeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h2 class="text-xl font-bold mb-4">Route Options</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Location</label>
                    <select id="startLocation" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="default">Default (<?= DEFAULT_START_ADDRESS ?>)</option>
                        <option value="first">First Customer</option>
                        <option value="current">Current Location</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departure Time</label>
                    <input type="datetime-local" id="departureTime"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" id="returnToStart" checked class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm">Return to start location</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button onclick="closeRouteModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-100">
                    Cancel
                </button>
                <button onclick="submitRoute()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Create Route
                </button>
            </div>
        </div>
    </div>

    <script>
        // State
        let customers = [];
        let selectedCustomers = new Set();
        let currentPage = 1;
        let totalPages = 1;
        let totalCount = 0;
        const limit = 50;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadCustomers();

            // Set default departure time to now
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('departureTime').value = now.toISOString().slice(0, 16);
        });

        // Load customers from API
        async function loadCustomers() {
            const tableBody = document.getElementById('customerTableBody');
            tableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Loading...</td></tr>';

            const params = new URLSearchParams({
                route_name: document.getElementById('routeFilter').value,
                search: document.getElementById('searchInput').value,
                city: document.getElementById('cityFilter').value,
                has_coords: document.getElementById('coordsFilter').value,
                limit: limit,
                offset: (currentPage - 1) * limit
            });

            try {
                const response = await fetch(`/api/customers.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    customers = data.data.customers;
                    totalCount = data.data.total;
                    totalPages = Math.ceil(totalCount / limit);

                    renderCustomers();
                    updatePagination();
                    updateFilters(data.data.filters);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">${data.error.message}</td></tr>`;
                }
            } catch (error) {
                tableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Error loading customers</td></tr>';
                console.error(error);
            }
        }

        // Render customer rows
        function renderCustomers() {
            const tableBody = document.getElementById('customerTableBody');

            if (customers.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No customers found</td></tr>';
                return;
            }

            tableBody.innerHTML = customers.map(c => `
                <tr class="customer-row ${selectedCustomers.has(c.id) ? 'selected' : ''}" onclick="toggleCustomer(${c.id}, event)">
                    <td class="px-4 py-3">
                        <input type="checkbox" ${selectedCustomers.has(c.id) ? 'checked' : ''}
                               onchange="toggleCustomer(${c.id}, event)"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">${escapeHtml(c.display_name)}</div>
                        <div class="text-sm text-gray-500">${escapeHtml(c.account_number)}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(c.addr1)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(c.city)}, ${escapeHtml(c.state)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(c.phone)}</td>
                    <td class="px-4 py-3">
                        ${c.has_coordinates
                            ? '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Yes</span>'
                            : '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">No</span>'
                        }
                    </td>
                </tr>
            `).join('');
        }

        // Toggle customer selection
        function toggleCustomer(id, event) {
            if (event) event.stopPropagation();

            if (selectedCustomers.has(id)) {
                selectedCustomers.delete(id);
            } else {
                selectedCustomers.add(id);
            }

            updateSelectionUI();
            renderCustomers();
        }

        // Update selection count
        function updateSelectionUI() {
            document.getElementById('selectedCount').textContent = selectedCustomers.size;
            document.getElementById('generateRouteBtn').disabled = selectedCustomers.size < 2;
        }

        // Clear selection
        function clearSelection() {
            selectedCustomers.clear();
            updateSelectionUI();
            renderCustomers();
        }

        // Select all visible
        function selectAll() {
            customers.forEach(c => {
                if (c.has_coordinates) {
                    selectedCustomers.add(c.id);
                }
            });
            updateSelectionUI();
            renderCustomers();
        }

        // Toggle select all checkbox
        function toggleSelectAll(checkbox) {
            if (checkbox.checked) {
                selectAll();
            } else {
                clearSelection();
            }
        }

        // Update filters dropdown
        function updateFilters(filters) {
            const citySelect = document.getElementById('cityFilter');
            const currentCity = citySelect.value;

            citySelect.innerHTML = '<option value="">All Cities</option>' +
                filters.cities.map(city => `<option value="${escapeHtml(city)}" ${city === currentCity ? 'selected' : ''}>${escapeHtml(city)}</option>`).join('');
        }

        // Pagination
        function updatePagination() {
            const start = (currentPage - 1) * limit + 1;
            const end = Math.min(currentPage * limit, totalCount);

            document.getElementById('showingStart').textContent = totalCount > 0 ? start : 0;
            document.getElementById('showingEnd').textContent = end;
            document.getElementById('totalCount').textContent = totalCount;
            document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;

            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
        }

        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                loadCustomers();
            }
        }

        function nextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                loadCustomers();
            }
        }

        // Route generation
        function generateRoute() {
            document.getElementById('routeModal').classList.remove('hidden');
            document.getElementById('routeModal').classList.add('flex');
        }

        function closeRouteModal() {
            document.getElementById('routeModal').classList.add('hidden');
            document.getElementById('routeModal').classList.remove('flex');
        }

        async function submitRoute() {
            const customerIds = Array.from(selectedCustomers);

            if (customerIds.length < 2) {
                alert('Please select at least 2 customers');
                return;
            }

            const body = {
                customer_ids: customerIds,
                departure_time: document.getElementById('departureTime').value,
                end_location: {
                    return_to_start: document.getElementById('returnToStart').checked
                }
            };

            // Handle start location
            const startOption = document.getElementById('startLocation').value;
            if (startOption === 'current') {
                if (navigator.geolocation) {
                    try {
                        const position = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject);
                        });
                        body.start_location = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                    } catch (e) {
                        alert('Could not get current location. Using default.');
                    }
                }
            }

            try {
                const response = await fetch('/api/get-route.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (data.success) {
                    // Store route data and redirect to map view
                    sessionStorage.setItem('routeData', JSON.stringify(data.data));
                    window.location.href = 'route-view.php?route_id=' + data.data.route_id;
                } else {
                    alert('Error: ' + data.error.message);
                }
            } catch (error) {
                alert('Error creating route');
                console.error(error);
            }
        }

        // Utility
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Search on Enter
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadCustomers();
        });
    </script>
</body>
</html>
