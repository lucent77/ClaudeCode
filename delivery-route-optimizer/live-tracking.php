<?php
/**
 * Live Tracking Page
 * Real-time driver location tracking and route updates
 */

require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #map { height: calc(100vh - 120px); min-height: 500px; }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="statusIndicator" class="w-3 h-3 bg-gray-400 rounded-full"></div>
                    <h1 class="text-xl font-bold">Live Tracking</h1>
                </div>
                <nav class="flex gap-4">
                    <a href="index.php" class="px-3 py-1 hover:bg-green-700 rounded">Customers</a>
                    <a href="route-view.php" class="px-3 py-1 hover:bg-green-700 rounded">Route Map</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Next Stop Banner -->
    <div id="nextStopBanner" class="bg-blue-600 text-white px-4 py-3 hidden">
        <div class="container mx-auto flex items-center justify-between">
            <div>
                <span class="text-sm opacity-80">Next Stop:</span>
                <span id="nextStopName" class="font-semibold ml-2">-</span>
            </div>
            <div class="text-right">
                <span class="text-sm opacity-80">ETA:</span>
                <span id="nextStopETA" class="font-semibold ml-2">-</span>
                <span id="nextStopDistance" class="text-sm ml-4">-</span>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div id="map" class="w-full"></div>

    <!-- Bottom Controls -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg p-4">
        <div class="container mx-auto">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <button onclick="toggleTracking()" id="trackingBtn"
                            class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 flex items-center gap-2">
                        <svg id="trackingIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span id="trackingBtnText">Start Tracking</span>
                    </button>
                    <div id="locationInfo" class="text-sm text-gray-600 hidden">
                        <div>Lat: <span id="currentLat">-</span></div>
                        <div>Lng: <span id="currentLng">-</span></div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button onclick="markCurrentStopComplete()" id="completeBtn" disabled
                            class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:bg-gray-400">
                        Complete Current Stop
                    </button>
                    <button onclick="recalculateFromHere()" id="recalcBtn" disabled
                            class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50">
                        Recalculate Route
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const GOOGLE_API_KEY = '<?= GOOGLE_API_KEY ?>';

        // State
        let map;
        let routeData = null;
        let currentPosition = null;
        let watchId = null;
        let isTracking = false;
        let currentLocationMarker = null;
        let routePolyline = null;
        let waypointMarkers = [];
        let currentStopIndex = 0;
        let completedStops = [];

        // Initialize map
        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: <?= DEFAULT_START_LAT ?>, lng: <?= DEFAULT_START_LNG ?> },
                zoom: 12,
                disableDefaultUI: false,
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true
            });

            // Try to load route from session storage
            loadRouteData();
        }

        // Load route data
        function loadRouteData() {
            const stored = sessionStorage.getItem('routeData');
            if (stored) {
                routeData = JSON.parse(stored);
                displayRoute();
                document.getElementById('completeBtn').disabled = false;
                updateNextStopBanner();
            }
        }

        // Display route
        function displayRoute() {
            if (!routeData) return;

            // Clear existing
            if (routePolyline) routePolyline.setMap(null);
            waypointMarkers.forEach(m => m.setMap(null));
            waypointMarkers = [];

            // Draw polyline
            if (routeData.polyline) {
                const path = google.maps.geometry.encoding.decodePath(routeData.polyline);
                routePolyline = new google.maps.Polyline({
                    path: path,
                    geodesic: true,
                    strokeColor: '#3b82f6',
                    strokeOpacity: 0.8,
                    strokeWeight: 5,
                    map: map
                });
            }

            // Add waypoint markers
            routeData.waypoints.forEach((wp, index) => {
                const isCompleted = completedStops.includes(wp.customer_id);
                const isCurrent = index === currentStopIndex;

                const marker = new google.maps.Marker({
                    position: { lat: wp.lat, lng: wp.lng },
                    map: map,
                    label: {
                        text: String(wp.stop_number),
                        color: 'white',
                        fontWeight: 'bold',
                        fontSize: '12px'
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: isCurrent ? 18 : 14,
                        fillColor: isCompleted ? '#9ca3af' : (isCurrent ? '#22c55e' : '#3b82f6'),
                        fillOpacity: 1,
                        strokeColor: 'white',
                        strokeWeight: 3
                    },
                    title: wp.practice_name
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="p-3 min-w-[200px]">
                            <div class="font-bold text-lg">${wp.stop_number}. ${escapeHtml(wp.practice_name)}</div>
                            <div class="text-gray-600 text-sm mt-1">${escapeHtml(wp.address)}</div>
                            <div class="mt-3 pt-2 border-t">
                                <div class="text-sm"><strong>ETA:</strong> ${formatTime(wp.eta)}</div>
                            </div>
                        </div>
                    `
                });

                marker.addListener('click', () => infoWindow.open(map, marker));
                waypointMarkers.push(marker);
            });

            // Fit bounds
            if (routeData.bounds) {
                map.fitBounds(new google.maps.LatLngBounds(
                    routeData.bounds.southwest,
                    routeData.bounds.northeast
                ));
            }
        }

        // Toggle tracking
        function toggleTracking() {
            if (isTracking) {
                stopTracking();
            } else {
                startTracking();
            }
        }

        // Start tracking
        function startTracking() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            document.getElementById('trackingBtnText').textContent = 'Stop Tracking';
            document.getElementById('trackingBtn').classList.remove('bg-green-600', 'hover:bg-green-700');
            document.getElementById('trackingBtn').classList.add('bg-red-600', 'hover:bg-red-700');
            document.getElementById('statusIndicator').classList.remove('bg-gray-400');
            document.getElementById('statusIndicator').classList.add('bg-green-500', 'pulse');
            document.getElementById('locationInfo').classList.remove('hidden');
            document.getElementById('recalcBtn').disabled = false;

            isTracking = true;

            watchId = navigator.geolocation.watchPosition(
                updatePosition,
                handleLocationError,
                {
                    enableHighAccuracy: true,
                    maximumAge: 5000,
                    timeout: 10000
                }
            );
        }

        // Stop tracking
        function stopTracking() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            document.getElementById('trackingBtnText').textContent = 'Start Tracking';
            document.getElementById('trackingBtn').classList.add('bg-green-600', 'hover:bg-green-700');
            document.getElementById('trackingBtn').classList.remove('bg-red-600', 'hover:bg-red-700');
            document.getElementById('statusIndicator').classList.add('bg-gray-400');
            document.getElementById('statusIndicator').classList.remove('bg-green-500', 'pulse');
            document.getElementById('locationInfo').classList.add('hidden');
            document.getElementById('recalcBtn').disabled = true;

            isTracking = false;

            if (currentLocationMarker) {
                currentLocationMarker.setMap(null);
                currentLocationMarker = null;
            }
        }

        // Update position
        function updatePosition(position) {
            currentPosition = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            document.getElementById('currentLat').textContent = currentPosition.lat.toFixed(6);
            document.getElementById('currentLng').textContent = currentPosition.lng.toFixed(6);

            // Update marker
            if (!currentLocationMarker) {
                currentLocationMarker = new google.maps.Marker({
                    position: currentPosition,
                    map: map,
                    icon: {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 6,
                        fillColor: '#22c55e',
                        fillOpacity: 1,
                        strokeColor: 'white',
                        strokeWeight: 2,
                        rotation: position.coords.heading || 0
                    },
                    zIndex: 999
                });
            } else {
                currentLocationMarker.setPosition(currentPosition);
                if (position.coords.heading) {
                    currentLocationMarker.setIcon({
                        ...currentLocationMarker.getIcon(),
                        rotation: position.coords.heading
                    });
                }
            }

            // Update next stop info
            updateNextStopBanner();

            // Auto-center if close to edge
            const bounds = map.getBounds();
            if (bounds && !bounds.contains(currentPosition)) {
                map.panTo(currentPosition);
            }
        }

        // Handle location error
        function handleLocationError(error) {
            console.error('Geolocation error:', error);
            document.getElementById('statusIndicator').classList.remove('bg-green-500');
            document.getElementById('statusIndicator').classList.add('bg-yellow-500');

            let message = 'Location error';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location permission denied';
                    stopTracking();
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location unavailable';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timeout';
                    break;
            }
            console.warn(message);
        }

        // Update next stop banner
        function updateNextStopBanner() {
            if (!routeData || !routeData.waypoints) return;

            const nextStop = routeData.waypoints[currentStopIndex];
            if (!nextStop) {
                document.getElementById('nextStopBanner').classList.add('hidden');
                return;
            }

            document.getElementById('nextStopBanner').classList.remove('hidden');
            document.getElementById('nextStopName').textContent = nextStop.practice_name;
            document.getElementById('nextStopETA').textContent = formatTime(nextStop.eta);

            if (currentPosition) {
                const distance = calculateDistance(
                    currentPosition.lat, currentPosition.lng,
                    nextStop.lat, nextStop.lng
                );
                document.getElementById('nextStopDistance').textContent = formatDistance(distance);
            }
        }

        // Mark current stop complete
        function markCurrentStopComplete() {
            if (!routeData || currentStopIndex >= routeData.waypoints.length) return;

            const currentStop = routeData.waypoints[currentStopIndex];
            completedStops.push(currentStop.customer_id);
            currentStopIndex++;

            // Update display
            displayRoute();
            updateNextStopBanner();

            if (currentStopIndex >= routeData.waypoints.length) {
                alert('Route completed! All stops visited.');
                document.getElementById('completeBtn').disabled = true;
            }
        }

        // Recalculate route
        async function recalculateFromHere() {
            if (!currentPosition || !routeData) return;

            const remainingIds = routeData.waypoints
                .slice(currentStopIndex)
                .map(wp => wp.customer_id);

            if (remainingIds.length === 0) {
                alert('No remaining stops to recalculate');
                return;
            }

            try {
                const response = await fetch('/api/recalculate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        route_id: routeData.route_id,
                        current_location: currentPosition,
                        remaining_customer_ids: remainingIds
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Update route data
                    routeData.waypoints = data.data.remaining_waypoints;
                    routeData.polyline = data.data.polyline;
                    currentStopIndex = 0;

                    // Store updated data
                    sessionStorage.setItem('routeData', JSON.stringify(routeData));

                    // Refresh display
                    displayRoute();
                    updateNextStopBanner();

                    alert('Route recalculated!');
                } else {
                    alert('Error: ' + data.error.message);
                }
            } catch (error) {
                alert('Error recalculating route');
                console.error(error);
            }
        }

        // Utility functions
        function formatTime(isoString) {
            if (!isoString) return '-';
            return new Date(isoString).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatDistance(meters) {
            if (meters >= 1000) {
                return (meters / 1000).toFixed(1) + ' km';
            }
            return Math.round(meters) + ' m';
        }

        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000; // Earth radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_API_KEY ?>&libraries=geometry&callback=initMap">
    </script>
</body>
</html>
