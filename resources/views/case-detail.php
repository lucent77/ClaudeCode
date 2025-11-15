<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Creodent AoX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-tooth text-2xl text-indigo-600"></i>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Creodent AoX</h1>
                        <p class="text-xs text-gray-500">Elevate Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 hover:text-indigo-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Loading State -->
    <div id="loadingState" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
        <i class="fas fa-spinner fa-spin text-4xl text-indigo-600 mb-4"></i>
        <p class="text-gray-600">Loading case details...</p>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="hidden max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Case Header -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 id="patientName" class="text-2xl font-bold text-gray-800 mb-2"></h2>
                    <div class="flex items-center space-x-4">
                        <span id="statusBadge"></span>
                        <span id="priorityBadge"></span>
                    </div>
                </div>
                <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    <i class="fas fa-edit mr-2"></i>Edit Case
                </button>
            </div>
        </div>

        <!-- Case Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Main Details -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Basic Information -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Basic Information
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Surgery Date</p>
                            <p id="surgeryDate" class="text-base font-medium text-gray-800"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Surgery Time</p>
                            <p id="surgeryTime" class="text-base font-medium text-gray-800"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Assignee</p>
                            <p id="assignee" class="text-base font-medium text-gray-800"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Arch</p>
                            <p id="arch" class="text-base font-medium text-gray-800"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Due Date</p>
                            <p id="dueDate" class="text-base font-medium text-gray-800"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Ready for Surgery</p>
                            <p id="readyForSurgery" class="text-base font-medium text-gray-800"></p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-sticky-note mr-2"></i>Notes
                    </h3>
                    <p id="notes" class="text-gray-700 whitespace-pre-wrap"></p>
                </div>

                <!-- Attachments -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-paperclip mr-2"></i>Attachments
                    </h3>
                    <div id="attachmentsList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Attachments will be inserted here -->
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Progress Steps -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-tasks mr-2"></i>Progress Steps
                    </h3>
                    <div id="stepsList" class="space-y-3">
                        <!-- Steps will be inserted here -->
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-history mr-2"></i>Activity Log
                    </h3>
                    <div id="activityLog" class="space-y-3 max-h-96 overflow-y-auto">
                        <!-- Activity logs will be inserted here -->
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script>
        // Get case ID from URL
        const pathParts = window.location.pathname.split('/');
        const caseId = pathParts[pathParts.length - 1];

        // Check authentication
        const token = localStorage.getItem('token');
        if (!token) {
            window.location.href = '/login';
        }

        // Load case details
        async function loadCaseDetails() {
            try {
                const response = await fetch(`/api/cases/${caseId}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load case');
                }

                const data = await response.json();

                if (data.success && data.case) {
                    renderCaseDetails(data.case);
                } else {
                    alert('Case not found');
                    window.location.href = '/dashboard';
                }

            } catch (error) {
                console.error('Failed to load case:', error);
                alert('Failed to load case details');
                window.location.href = '/dashboard';
            }
        }

        function renderCaseDetails(caseData) {
            // Hide loading, show content
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('mainContent').classList.remove('hidden');

            // Patient name
            document.getElementById('patientName').textContent = caseData.patient_name;

            // Status and priority badges
            document.getElementById('statusBadge').innerHTML = getStatusBadge(caseData.status);
            document.getElementById('priorityBadge').innerHTML = getPriorityBadge(caseData.priority);

            // Basic information
            document.getElementById('surgeryDate').textContent = formatDate(caseData.surgery_date);
            document.getElementById('surgeryTime').textContent = caseData.surgery_time || 'Not set';
            document.getElementById('assignee').textContent = caseData.assignee_name_full || caseData.assignee_name || 'Unassigned';
            document.getElementById('arch').textContent = caseData.arch || 'Not specified';
            document.getElementById('dueDate').textContent = formatDate(caseData.due_date);
            document.getElementById('readyForSurgery').textContent = caseData.ready_for_surgery ? 'Yes' : 'No';

            // Notes
            document.getElementById('notes').textContent = caseData.notes || 'No notes available';

            // Attachments
            renderAttachments(caseData.attachments || []);

            // Steps
            renderSteps(caseData.steps || []);

            // Activity log
            renderActivityLog(caseData.activity_logs || []);
        }

        function renderAttachments(attachments) {
            const container = document.getElementById('attachmentsList');

            if (attachments.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No attachments</p>';
                return;
            }

            container.innerHTML = attachments.map(att => `
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="fas ${getFileIcon(att.type)} text-2xl text-indigo-600"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-800">${att.filename || 'File'}</p>
                                <p class="text-xs text-gray-500">${att.type}</p>
                            </div>
                        </div>
                        <a href="${att.file_url}" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            `).join('');
        }

        function renderSteps(steps) {
            const container = document.getElementById('stepsList');

            if (steps.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No steps defined</p>';
                return;
            }

            container.innerHTML = steps.map(step => `
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        ${step.status === 'completed' ?
                            '<i class="fas fa-check-circle text-green-500 text-xl"></i>' :
                            step.status === 'in_progress' ?
                            '<i class="fas fa-spinner text-yellow-500 text-xl"></i>' :
                            '<i class="fas fa-circle text-gray-300 text-xl"></i>'}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">${step.step_name}</p>
                        ${step.assigned_to_name ? `<p class="text-xs text-gray-500">${step.assigned_to_name}</p>` : ''}
                    </div>
                </div>
            `).join('');
        }

        function renderActivityLog(logs) {
            const container = document.getElementById('activityLog');

            if (logs.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">No activity yet</p>';
                return;
            }

            container.innerHTML = logs.map(log => `
                <div class="border-l-2 border-indigo-600 pl-3 py-2">
                    <p class="text-sm font-medium text-gray-800">${log.action}</p>
                    <p class="text-xs text-gray-600">${log.description}</p>
                    <p class="text-xs text-gray-400">${formatDateTime(log.created_at)} ${log.user_name ? '• ' + log.user_name : ''}</p>
                </div>
            `).join('');
        }

        function getStatusBadge(status) {
            const badges = {
                'open': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Open</span>',
                'in_progress': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">In Progress</span>',
                'completed': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Completed</span>',
                'cancelled': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Cancelled</span>'
            };
            return badges[status] || badges['open'];
        }

        function getPriorityBadge(priority) {
            const badges = {
                'low': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Low</span>',
                'normal': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Normal</span>',
                'high': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-orange-100 text-orange-800">High</span>',
                'critical': '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">Critical</span>'
            };
            return badges[priority] || badges['normal'];
        }

        function getFileIcon(type) {
            const icons = {
                'photo': 'fa-image',
                'stl': 'fa-cube',
                'cbct': 'fa-x-ray',
                'scan': 'fa-file-medical',
                'other': 'fa-file'
            };
            return icons[type] || icons['other'];
        }

        function formatDate(dateString) {
            if (!dateString) return 'Not set';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }

        // Load case on page load
        loadCaseDetails();
    </script>

</body>
</html>
