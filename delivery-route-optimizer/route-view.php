<?php
/**
 * Route Visualization Page
 * Smart Delivery Route Optimizer
 */

require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Map - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #map { height: calc(100vh - 200px); min-height: 400px; }
        .stop-badge {
            background: white;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        .waypoint-card { transition: all 0.2s; }
        .waypoint-card:hover { background-color: #f3f4f6; }
        .waypoint-card.completed { opacity: 0.5; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold"><?= APP_NAME ?></h1>
                <nav class="flex gap-4">
                    <a href="index.php" class="px-3 py-1 hover:bg-blue-700 rounded">Customers</a>
                    <a href="route-view.php" class="px-3 py-1 bg-blue-700 rounded">Route Map</a>
                    <a href="import_json_customers.php" class="px-3 py-1 hover:bg-blue-700 rounded">Import</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Sidebar -->
            <div class="w-full lg:w-80 flex-shrink-0">
                <!-- Route Summary -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                    <h2 class="text-lg font-bold mb-3">Route Summary</h2>
                    <div id="routeSummary" class="space-y-2 text-sm">
                        <p class="text-gray-500">No route loaded</p>
                    </div>
                </div>

                <!-- Live Tracking Toggle -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">Live Tracking</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="liveTrackingToggle" class="sr-only peer" onchange="toggleLiveTracking()">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <p id="liveTrackingStatus" class="text-xs text-gray-500 mt-2">Disabled</p>
                </div>

                <!-- Waypoints List -->
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-lg font-bold mb-3">Stops (<span id="stopCount">0</span>)</h2>
                    <div id="waypointsList" class="space-y-2 max-h-[calc(100vh-500px)] overflow-y-auto">
                        <p class="text-gray-500 text-sm">No stops loaded</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-4 space-y-2">
                    <button onclick="recalculateRoute()" id="recalculateBtn" disabled
                            class="w-full px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 disabled:bg-gray-400">
                        Recalculate from Current Location
                    </button>
                    <a href="index.php" class="block text-center w-full px-4 py-2 border border-gray-300 rounded hover:bg-gray-100">
                        Edit Selection
                    </a>
                </div>
            </div>

            <!-- Map Container -->
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Google Maps Script -->
    <script>
        // Configuration
        const GOOGLE_API_KEY = '<?= GOOGLE_API_KEY ?>';

        // State
        let map;
        let directionsService;
        let directionsRenderer;
        let routeData = null;
        let markers = [];
        let currentLocationMarker = null;
        let watchPositionId = null;
        let completedStops = new Set();

        // Initialize
        function initMap() {
            // Create map
            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: <?= DEFAULT_START_LAT ?>, lng: <?= DEFAULT_START_LNG ?> },
                zoom: 10,
                mapTypeControl: true,
                streetViewControl: false
            });

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#3b82f6',
                    strokeWeight: 5,
                    strokeOpacity: 0.8
                }
            });

            // Load route data
            loadRouteData();
        }

        // Load route data from session storage or URL
        function loadRouteData() {
            // Try session storage first
            const storedData = sessionStorage.getItem('routeData');
            if (storedData) {
                routeData = JSON.parse(storedData);
                displayRoute();
                return;
            }

            // Try URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const routeId = urlParams.get('route_id');

            if (!routeId) {
                document.getElementById('routeSummary').innerHTML = '<p class="text-gray-500">No route selected. <a href="index.php" class="text-blue-600 hover:underline">Select customers</a></p>';
                return;
            }

            // Fetch from API (would need a get-route-by-id endpoint)
            document.getElementById('routeSummary').innerHTML = '<p class="text-gray-500">Route ID: ' + routeId + '</p>';
        }

        // Display route on map
        function displayRoute() {
            if (!routeData || !routeData.waypoints || routeData.waypoints.length === 0) {
                return;
            }

            // Update summary
            updateRouteSummary();

            // Update waypoints list
            updateWaypointsList();

            // Draw route using polyline
            if (routeData.polyline) {
                const path = google.maps.geometry.encoding.decodePath(routeData.polyline);
                const routeLine = new google.maps.Polyline({
                    path: path,
                    geodesic: true,
                    strokeColor: '#3b82f6',
                    strokeOpacity: 0.8,
                    strokeWeight: 5,
                    map: map
                });
            }

            // Add markers for each waypoint
            routeData.waypoints.forEach((waypoint, index) => {
                const marker = new google.maps.Marker({
                    position: { lat: waypoint.lat, lng: waypoint.lng },
                    map: map,
                    label: {
                        text: String(waypoint.stop_number),
                        color: 'white',
                        fontWeight: 'bold'
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 15,
                        fillColor: '#3b82f6',
                        fillOpacity: 1,
                        strokeColor: 'white',
                        strokeWeight: 2
                    },
                    title: waypoint.practice_name
                });

                // Info window
                const infoContent = `
                    <div class="p-2">
                        <h3 class="font-bold">${waypoint.stop_number}. ${escapeHtml(waypoint.practice_name)}</h3>
                        <p class="text-sm text-gray-600">${escapeHtml(waypoint.address)}</p>
                        <p class="text-sm mt-2"><strong>ETA:</strong> ${formatTime(waypoint.eta)}</p>
                        <button onclick="markCompleted(${waypoint.customer_id})"
                                class="mt-2 px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600">
                            Mark Completed
                        </button>
                    </div>
                `;

                const infoWindow = new google.maps.InfoWindow({ content: infoContent });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });

                markers.push(marker);
            });

            // Fit map to route bounds
            if (routeData.bounds) {
                const bounds = new google.maps.LatLngBounds(
                    routeData.bounds.southwest,
                    routeData.bounds.northeast
                );
                map.fitBounds(bounds);
            }

            // Enable recalculate button
            document.getElementById('recalculateBtn').disabled = false;
        }

        // Update route summary
        function updateRouteSummary() {
            const summary = routeData.summary;
            document.getElementById('routeSummary').innerHTML = `
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Distance:</span>
                    <span class="font-semibold">${summary.total_distance_text}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Est. Duration:</span>
                    <span class="font-semibold">${summary.total_duration_text}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">With Traffic:</span>
                    <span class="font-semibold">${summary.duration_in_traffic_text}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Stops:</span>
                    <span class="font-semibold">${routeData.waypoints.length}</span>
                </div>
            `;
        }

        // Update waypoints list
        function updateWaypointsList() {
            const list = document.getElementById('waypointsList');
            document.getElementById('stopCount').textContent = routeData.waypoints.length;

            list.innerHTML = routeData.waypoints.map(wp => `
                <div class="waypoint-card p-3 border rounded ${completedStops.has(wp.customer_id) ? 'completed' : ''}"
                     onclick="focusWaypoint(${wp.lat}, ${wp.lng})">
                    <div class="flex items-start gap-3">
                        <div class="stop-badge text-blue-600">${wp.stop_number}</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate">${escapeHtml(wp.practice_name)}</div>
                            <div class="text-xs text-gray-500 truncate">${escapeHtml(wp.address)}</div>
                            <div class="text-xs text-blue-600 mt-1">ETA: ${formatTime(wp.eta)}</div>
                        </div>
                        <button onclick="event.stopPropagation(); markCompleted(${wp.customer_id})"
                                class="text-green-600 hover:text-green-800" title="Mark completed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Focus on waypoint
        function focusWaypoint(lat, lng) {
            map.panTo({ lat, lng });
            map.setZoom(15);
        }

        // Mark stop as completed
        function markCompleted(customerId) {
            completedStops.add(customerId);
            updateWaypointsList();
        }

        // Toggle live tracking
        function toggleLiveTracking() {
            const toggle = document.getElementById('liveTrackingToggle');
            const status = document.getElementById('liveTrackingStatus');

            if (toggle.checked) {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser');
                    toggle.checked = false;
                    return;
                }

                status.textContent = 'Acquiring location...';

                watchPositionId = navigator.geolocation.watchPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        status.textContent = 'Tracking active';

                        // Update or create current location marker
                        if (currentLocationMarker) {
                            currentLocationMarker.setPosition(pos);
                        } else {
                            currentLocationMarker = new google.maps.Marker({
                                position: pos,
                                map: map,
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 10,
                                    fillColor: '#22c55e',
                                    fillOpacity: 1,
                                    strokeColor: 'white',
                                    strokeWeight: 3
                                },
                                title: 'Your Location'
                            });
                        }
                    },
                    (error) => {
                        status.textContent = 'Error: ' + error.message;
                        toggle.checked = false;
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 5000
                    }
                );
            } else {
                if (watchPositionId) {
                    navigator.geolocation.clearWatch(watchPositionId);
                    watchPositionId = null;
                }

                if (currentLocationMarker) {
                    currentLocationMarker.setMap(null);
                    currentLocationMarker = null;
                }

                status.textContent = 'Disabled';
            }
        }

        // Recalculate route from current location
        async function recalculateRoute() {
            if (!routeData) return;

            // Get remaining customer IDs
            const remainingIds = routeData.waypoints
                .filter(wp => !completedStops.has(wp.customer_id))
                .map(wp => wp.customer_id);

            if (remainingIds.length === 0) {
                alert('All stops completed!');
                return;
            }

            // Get current location
            let currentLocation;

            if (currentLocationMarker) {
                const pos = currentLocationMarker.getPosition();
                currentLocation = { lat: pos.lat(), lng: pos.lng() };
            } else {
                try {
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject);
                    });
                    currentLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                } catch (e) {
                    alert('Could not get current location');
                    return;
                }
            }

            try {
                const response = await fetch('/api/recalculate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        route_id: routeData.route_id,
                        current_location: currentLocation,
                        remaining_customer_ids: remainingIds
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Update route data
                    routeData.waypoints = data.data.remaining_waypoints;
                    routeData.summary = data.data.summary;
                    routeData.polyline = data.data.polyline;

                    // Clear existing markers
                    markers.forEach(m => m.setMap(null));
                    markers = [];

                    // Redisplay route
                    displayRoute();

                    alert('Route recalculated successfully!');
                } else {
                    alert('Error: ' + data.error.message);
                }
            } catch (error) {
                alert('Error recalculating route');
                console.error(error);
            }
        }

        // Format time
        function formatTime(isoString) {
            if (!isoString) return 'N/A';
            const date = new Date(isoString);
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <!-- Load Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_API_KEY ?>&libraries=geometry&callback=initMap">
    </script>
</body>
</html>
