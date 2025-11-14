<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Creodent AoX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .gallery-item {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .gallery-item:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-indigo-600 hover:text-indigo-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900" id="case-title">
                        Case Details
                    </h1>
                </div>
                <div id="status-badge"></div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Case Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Patient Name</label>
                    <p id="patient-name" class="text-lg font-semibold text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Case Code</label>
                    <p id="case-code" class="text-lg font-mono text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Surgery Date</label>
                    <p id="surgery-date" class="text-lg text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Arch</label>
                    <p id="arch" class="text-lg text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Surgeon</label>
                    <p id="surgeon" class="text-lg text-gray-900">-</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Clinic</label>
                    <p id="clinic" class="text-lg text-gray-900">-</p>
                </div>
            </div>

            <!-- Notes -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-600 mb-2">Notes</label>
                <p id="notes" class="text-gray-700 whitespace-pre-wrap">-</p>
            </div>
        </div>

        <!-- File Statistics -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4 text-center border border-gray-200">
                <div class="text-3xl mb-2">📷</div>
                <div class="text-2xl font-bold text-gray-900" id="count-photo">0</div>
                <div class="text-xs text-gray-600">Photos</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center border border-gray-200">
                <div class="text-3xl mb-2">🧊</div>
                <div class="text-2xl font-bold text-gray-900" id="count-stl">0</div>
                <div class="text-xs text-gray-600">STL Files</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center border border-gray-200">
                <div class="text-3xl mb-2">🔬</div>
                <div class="text-2xl font-bold text-gray-900" id="count-preop_scan">0</div>
                <div class="text-xs text-gray-600">Pre-op Scans</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center border border-gray-200">
                <div class="text-3xl mb-2">✅</div>
                <div class="text-2xl font-bold text-gray-900" id="count-postop_scan">0</div>
                <div class="text-xs text-gray-600">Post-op Scans</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center border border-gray-200">
                <div class="text-3xl mb-2">🦴</div>
                <div class="text-2xl font-bold text-gray-900" id="count-cbct">0</div>
                <div class="text-xs text-gray-600">CBCT</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center border border-gray-200">
                <div class="text-3xl mb-2">🎨</div>
                <div class="text-2xl font-bold text-gray-900" id="count-design">0</div>
                <div class="text-xs text-gray-600">Design Files</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="switchTab('photos')" class="tab-button active px-6 py-3 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600">
                        <i class="fas fa-camera mr-2"></i>Photos
                    </button>
                    <button onclick="switchTab('stl')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">
                        <i class="fas fa-cube mr-2"></i>STL Files
                    </button>
                    <button onclick="switchTab('scans')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">
                        <i class="fas fa-microscope mr-2"></i>Scans
                    </button>
                    <button onclick="switchTab('cbct')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">
                        <i class="fas fa-bone mr-2"></i>CBCT
                    </button>
                    <button onclick="switchTab('design')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">
                        <i class="fas fa-palette mr-2"></i>Design
                    </button>
                    <button onclick="switchTab('activity')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">
                        <i class="fas fa-history mr-2"></i>Activity Log
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Photos Tab -->
                <div id="tab-photos" class="tab-content">
                    <div id="photos-gallery" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <!-- Photos will be loaded here -->
                    </div>
                </div>

                <!-- STL Files Tab -->
                <div id="tab-stl" class="tab-content hidden">
                    <div id="stl-list" class="space-y-2">
                        <!-- STL files will be loaded here -->
                    </div>
                </div>

                <!-- Scans Tab -->
                <div id="tab-scans" class="tab-content hidden">
                    <h3 class="text-lg font-semibold mb-4">Pre-operative Scans</h3>
                    <div id="preop-scans" class="space-y-2 mb-6">
                        <!-- Pre-op scans will be loaded here -->
                    </div>
                    <h3 class="text-lg font-semibold mb-4">Post-operative Scans</h3>
                    <div id="postop-scans" class="space-y-2">
                        <!-- Post-op scans will be loaded here -->
                    </div>
                </div>

                <!-- CBCT Tab -->
                <div id="tab-cbct" class="tab-content hidden">
                    <h3 class="text-lg font-semibold mb-4">Pre-operative CBCT</h3>
                    <div id="preop-cbct" class="space-y-2 mb-6">
                        <!-- Pre-op CBCT will be loaded here -->
                    </div>
                    <h3 class="text-lg font-semibold mb-4">Post-operative CBCT</h3>
                    <div id="postop-cbct" class="space-y-2">
                        <!-- Post-op CBCT will be loaded here -->
                    </div>
                </div>

                <!-- Design Files Tab -->
                <div id="tab-design" class="tab-content hidden">
                    <div id="design-list" class="space-y-2">
                        <!-- Design files will be loaded here -->
                    </div>
                </div>

                <!-- Activity Log Tab -->
                <div id="tab-activity" class="tab-content hidden">
                    <div id="activity-log" class="space-y-3">
                        <!-- Activity log will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        const caseId = new URLSearchParams(window.location.search).get('id');
        let caseData = null;

        // Load case data
        async function loadCaseData() {
            if (!caseId) {
                alert('No case ID provided');
                window.location.href = 'index.php';
                return;
            }

            try {
                const response = await fetch(`/api/cases.php?id=${caseId}`);
                const result = await response.json();

                if (result.success) {
                    caseData = result.data;
                    renderCaseData();
                } else {
                    alert('Case not found');
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('Failed to load case:', error);
                alert('Failed to load case data');
            }
        }

        // Render case data
        function renderCaseData() {
            // Header
            document.getElementById('case-title').textContent = caseData.patient_name;
            document.getElementById('status-badge').innerHTML = `
                <span class="px-4 py-2 text-sm font-semibold rounded-full ${getStatusBadgeClass(caseData.status)}">
                    ${formatStatus(caseData.status)}
                </span>
            `;

            // Details
            document.getElementById('patient-name').textContent = caseData.patient_name || '-';
            document.getElementById('case-code').textContent = caseData.case_code || '-';
            document.getElementById('surgery-date').textContent = formatDate(caseData.surgery_date) || '-';
            document.getElementById('arch').textContent = caseData.arch || '-';
            document.getElementById('surgeon').textContent = caseData.surgeon || '-';
            document.getElementById('clinic').textContent = caseData.clinic || '-';
            document.getElementById('notes').textContent = caseData.notes || 'No notes available';

            // File counts
            const counts = {
                photo: 0, stl: 0, preop_scan: 0, postop_scan: 0,
                preop_cbct: 0, postop_cbct: 0, design: 0
            };

            caseData.attachments.forEach(file => {
                if (counts.hasOwnProperty(file.file_type)) {
                    counts[file.file_type]++;
                }
            });

            document.getElementById('count-photo').textContent = counts.photo;
            document.getElementById('count-stl').textContent = counts.stl;
            document.getElementById('count-preop_scan').textContent = counts.preop_scan;
            document.getElementById('count-postop_scan').textContent = counts.postop_scan;
            document.getElementById('count-cbct').textContent = counts.preop_cbct + counts.postop_cbct;
            document.getElementById('count-design').textContent = counts.design;

            // Render files
            renderPhotos();
            renderSTLFiles();
            renderScans();
            renderCBCT();
            renderDesignFiles();
            renderActivityLog();
        }

        // Render photos
        function renderPhotos() {
            const photos = caseData.attachments.filter(f => f.file_type === 'photo');
            const container = document.getElementById('photos-gallery');

            if (photos.length === 0) {
                container.innerHTML = '<p class="text-gray-500 col-span-full text-center py-8">No photos available</p>';
                return;
            }

            container.innerHTML = photos.map(photo => `
                <div class="gallery-item rounded-lg overflow-hidden border border-gray-200 hover:border-indigo-300">
                    <a href="${photo.permalink || photo.file_url}" target="_blank">
                        <img src="${photo.preview_url || '/assets/img/placeholder.jpg'}" alt="${photo.file_name}"
                             class="w-full h-48 object-cover">
                        <div class="p-2 bg-white">
                            <p class="text-xs text-gray-600 truncate">${photo.file_name}</p>
                        </div>
                    </a>
                </div>
            `).join('');
        }

        // Render STL files
        function renderSTLFiles() {
            const stlFiles = caseData.attachments.filter(f => f.file_type === 'stl');
            const container = document.getElementById('stl-list');

            if (stlFiles.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No STL files available</p>';
                return;
            }

            container.innerHTML = stlFiles.map(file => renderFileItem(file)).join('');
        }

        // Render scans
        function renderScans() {
            const preopScans = caseData.attachments.filter(f => f.file_type === 'preop_scan');
            const postopScans = caseData.attachments.filter(f => f.file_type === 'postop_scan');

            document.getElementById('preop-scans').innerHTML = preopScans.length > 0
                ? preopScans.map(file => renderFileItem(file)).join('')
                : '<p class="text-gray-500 text-center py-4">No pre-operative scans available</p>';

            document.getElementById('postop-scans').innerHTML = postopScans.length > 0
                ? postopScans.map(file => renderFileItem(file)).join('')
                : '<p class="text-gray-500 text-center py-4">No post-operative scans available</p>';
        }

        // Render CBCT
        function renderCBCT() {
            const preopCBCT = caseData.attachments.filter(f => f.file_type === 'preop_cbct');
            const postopCBCT = caseData.attachments.filter(f => f.file_type === 'postop_cbct');

            document.getElementById('preop-cbct').innerHTML = preopCBCT.length > 0
                ? preopCBCT.map(file => renderFileItem(file)).join('')
                : '<p class="text-gray-500 text-center py-4">No pre-operative CBCT available</p>';

            document.getElementById('postop-cbct').innerHTML = postopCBCT.length > 0
                ? postopCBCT.map(file => renderFileItem(file)).join('')
                : '<p class="text-gray-500 text-center py-4">No post-operative CBCT available</p>';
        }

        // Render design files
        function renderDesignFiles() {
            const designFiles = caseData.attachments.filter(f => f.file_type === 'design');
            const container = document.getElementById('design-list');

            if (designFiles.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No design files available</p>';
                return;
            }

            container.innerHTML = designFiles.map(file => renderFileItem(file)).join('');
        }

        // Render activity log
        function renderActivityLog() {
            const container = document.getElementById('activity-log');

            if (!caseData.activity_logs || caseData.activity_logs.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No activity recorded</p>';
                return;
            }

            container.innerHTML = caseData.activity_logs.map(log => `
                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <i class="fas fa-circle text-indigo-600 text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${log.message}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            ${formatDateTime(log.created_at)} • ${log.user}
                        </p>
                    </div>
                </div>
            `).join('');
        }

        // Render file item
        function renderFileItem(file) {
            return `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file text-gray-400 text-xl"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-900">${file.file_name}</p>
                            <p class="text-xs text-gray-500">${formatFileSize(file.size)} • ${formatDateTime(file.uploaded_at)}</p>
                        </div>
                    </div>
                    <a href="${file.permalink || file.file_url}" target="_blank"
                       class="text-indigo-600 hover:text-indigo-700">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            `;
        }

        // Switch tabs
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-indigo-600', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-gray-600');
            });
            event.target.classList.add('active', 'border-indigo-600', 'text-indigo-600');
            event.target.classList.remove('border-transparent', 'text-gray-600');

            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(`tab-${tabName}`).classList.remove('hidden');
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

        function formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }

        function formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Initial load
        loadCaseData();
    </script>

</body>
</html>
