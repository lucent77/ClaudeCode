<?php
require_once 'config.php';
require_once 'database.php';

$db = Database::getInstance();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            transition: opacity 0.3s ease;
        }
        .toast {
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            min-width: 100%;
        }
        th, td {
            white-space: nowrap;
        }
        .cell-input {
            min-width: 100px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-teeth text-3xl"></i>
                    <h1 class="text-3xl font-bold"><?php echo APP_NAME; ?></h1>
                </div>
                <div class="text-sm opacity-75">
                    <i class="fas fa-code-branch mr-1"></i> v<?php echo APP_VERSION; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container mx-auto px-4 py-8">
        <!-- Action Bar -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <div class="flex flex-wrap gap-3">
                    <button onclick="openAddSystemModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>시스템 추가</span>
                    </button>
                    <button onclick="openAddManufacturerModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center space-x-2">
                        <i class="fas fa-industry"></i>
                        <span>제조사 추가</span>
                    </button>
                    <button onclick="openImportCsvModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center space-x-2">
                        <i class="fas fa-file-csv"></i>
                        <span>CSV 가져오기</span>
                    </button>
                </div>
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="검색 (시스템, SKU, ID Code...)"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Matrix Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="table-wrapper">
                <table id="dataTable" class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">시스템</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Size</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">SKU</th>
                            <!-- Manufacturer columns will be added dynamically -->
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-gray-200">
                        <!-- Rows will be added dynamically -->
                    </tbody>
                </table>
            </div>
            <div id="loadingIndicator" class="p-8 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                <p class="mt-2 text-gray-600">데이터 로딩 중...</p>
            </div>
        </div>
    </main>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Add System Modal -->
    <div id="addSystemModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">시스템 추가</h2>
                <button onclick="closeModal('addSystemModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="addSystemForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">시스템 이름</label>
                    <input type="text" name="system_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                    <input type="text" name="size" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" name="sku" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        저장
                    </button>
                    <button type="button" onclick="closeModal('addSystemModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition duration-200">
                        취소
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Manufacturer Modal -->
    <div id="addManufacturerModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">제조사 추가</h2>
                <button onclick="closeModal('addManufacturerModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="addManufacturerForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">제조사 이름</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">타입 (CAD 소프트웨어)</label>
                    <input type="text" name="type" required placeholder="예: 3Shape, exocad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        저장
                    </button>
                    <button type="button" onclick="closeModal('addManufacturerModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition duration-200">
                        취소
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div id="importCsvModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">CSV 가져오기</h2>
                <button onclick="closeModal('importCsvModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="importCsvForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CSV 파일 선택</label>
                    <input type="file" name="csv" accept=".csv" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="mt-2 text-sm text-gray-500">CSV 형식: System, Size, SKU, 제조사[타입], ...</p>
                </div>
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        가져오기
                    </button>
                    <button type="button" onclick="closeModal('importCsvModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition duration-200">
                        취소
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Cell Modal -->
    <div id="editCellModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Scanbody 관리</h2>
                <button onclick="closeModal('editCellModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Code</label>
                    <input type="text" id="editIdCode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">STL 파일</label>
                    <div id="currentFileInfo" class="mb-2"></div>
                    <input type="file" id="editStlFile" accept=".stl,.STL" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="flex space-x-3 pt-4">
                    <button onclick="saveCell()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        저장
                    </button>
                    <button onclick="closeModal('editCellModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition duration-200">
                        취소
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentEditCell = null;
        let dataMatrix = null;

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDataMatrix();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                filterTable(searchTerm);
            });

            // Form submissions
            document.getElementById('addSystemForm').addEventListener('submit', handleAddSystem);
            document.getElementById('addManufacturerForm').addEventListener('submit', handleAddManufacturer);
            document.getElementById('importCsvForm').addEventListener('submit', handleImportCsv);
        }

        async function loadDataMatrix() {
            try {
                const response = await fetch('api.php?action=get_data_matrix');
                const result = await response.json();

                if (result.success) {
                    dataMatrix = result.data;
                    renderTable(dataMatrix);
                } else {
                    showToast('데이터 로딩 실패', 'error');
                }
            } catch (error) {
                showToast('데이터 로딩 중 오류 발생', 'error');
                console.error(error);
            }
        }

        function renderTable(data) {
            const table = document.getElementById('dataTable');
            const thead = table.querySelector('thead tr');
            const tbody = document.getElementById('tableBody');

            // Clear existing manufacturer columns
            while (thead.children.length > 3) {
                thead.removeChild(thead.lastChild);
            }

            // Add manufacturer headers
            data.manufacturers.forEach(manufacturer => {
                const th = document.createElement('th');
                th.className = 'px-4 py-3 text-center text-sm font-semibold text-gray-700 bg-blue-50';
                th.innerHTML = `${manufacturer.name}<br><span class="text-xs font-normal text-gray-500">[${manufacturer.type}]</span>`;
                thead.appendChild(th);
            });

            // Clear and populate tbody
            tbody.innerHTML = '';

            data.matrix.forEach((row, rowIndex) => {
                const tr = document.createElement('tr');
                tr.className = rowIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50';

                // System info columns
                tr.innerHTML = `
                    <td class="px-4 py-3 text-sm text-gray-900">${escapeHtml(row.system_name)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${escapeHtml(row.size)}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 font-mono">${escapeHtml(row.sku)}</td>
                `;

                // Scanbody cells
                row.scanbodies.forEach(scanbody => {
                    const td = document.createElement('td');
                    td.className = 'px-4 py-3 text-center';

                    const cellContent = document.createElement('div');
                    cellContent.className = 'flex flex-col items-center space-y-1';

                    if (scanbody.id_code || scanbody.stl_file_path) {
                        if (scanbody.id_code) {
                            const codeSpan = document.createElement('span');
                            codeSpan.className = 'text-sm font-medium text-gray-900';
                            codeSpan.textContent = scanbody.id_code;
                            cellContent.appendChild(codeSpan);
                        }

                        if (scanbody.stl_file_path) {
                            const fileLink = document.createElement('a');
                            fileLink.href = 'uploads/' + scanbody.stl_file_path;
                            fileLink.download = scanbody.original_filename || 'scanbody.stl';
                            fileLink.className = 'text-xs text-blue-600 hover:text-blue-800 flex items-center space-x-1';
                            fileLink.innerHTML = '<i class="fas fa-download"></i><span>STL</span>';
                            cellContent.appendChild(fileLink);
                        }

                        const editBtn = document.createElement('button');
                        editBtn.className = 'text-xs text-gray-600 hover:text-blue-600 transition';
                        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                        editBtn.onclick = () => openEditCellModal(row.system_id, scanbody.manufacturer_id, scanbody.id_code, scanbody.stl_file_path, scanbody.original_filename);
                        cellContent.appendChild(editBtn);
                    } else {
                        const addBtn = document.createElement('button');
                        addBtn.className = 'text-gray-400 hover:text-blue-600 transition';
                        addBtn.innerHTML = '<i class="fas fa-plus-circle text-xl"></i>';
                        addBtn.onclick = () => openEditCellModal(row.system_id, scanbody.manufacturer_id, '', null, null);
                        cellContent.appendChild(addBtn);
                    }

                    td.appendChild(cellContent);
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });

            document.getElementById('loadingIndicator').style.display = 'none';
        }

        function filterTable(searchTerm) {
            const tbody = document.getElementById('tableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        function openEditCellModal(systemId, manufacturerId, idCode, stlPath, originalFilename) {
            currentEditCell = { systemId, manufacturerId };
            document.getElementById('editIdCode').value = idCode || '';

            const fileInfo = document.getElementById('currentFileInfo');
            if (stlPath) {
                fileInfo.innerHTML = `
                    <div class="flex items-center justify-between p-2 bg-blue-50 rounded">
                        <span class="text-sm text-gray-700">
                            <i class="fas fa-file text-blue-600 mr-1"></i>
                            ${escapeHtml(originalFilename || 'scanbody.stl')}
                        </span>
                        <button onclick="deleteStlFile()" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            } else {
                fileInfo.innerHTML = '<p class="text-sm text-gray-500">파일 없음</p>';
            }

            openModal('editCellModal');
        }

        async function saveCell() {
            const idCode = document.getElementById('editIdCode').value.trim();
            const stlFile = document.getElementById('editStlFile').files[0];

            try {
                // Save ID Code
                if (idCode) {
                    const response = await fetch('api.php?action=update_scanbody_code', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            system_id: currentEditCell.systemId,
                            manufacturer_id: currentEditCell.manufacturerId,
                            id_code: idCode
                        })
                    });

                    const result = await response.json();
                    if (!result.success) {
                        throw new Error(result.message);
                    }
                }

                // Upload STL file if selected
                if (stlFile) {
                    const formData = new FormData();
                    formData.append('file', stlFile);
                    formData.append('system_id', currentEditCell.systemId);
                    formData.append('manufacturer_id', currentEditCell.manufacturerId);
                    formData.append('id_code', idCode);

                    const response = await fetch('api.php?action=upload_stl', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (!result.success) {
                        throw new Error(result.message);
                    }
                }

                showToast('저장되었습니다', 'success');
                closeModal('editCellModal');
                loadDataMatrix();
            } catch (error) {
                showToast('저장 실패: ' + error.message, 'error');
            }
        }

        async function deleteStlFile() {
            if (!confirm('STL 파일을 삭제하시겠습니까?')) return;

            try {
                const response = await fetch('api.php?action=delete_stl', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        system_id: currentEditCell.systemId,
                        manufacturer_id: currentEditCell.manufacturerId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('파일이 삭제되었습니다', 'success');
                    closeModal('editCellModal');
                    loadDataMatrix();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('삭제 실패: ' + error.message, 'error');
            }
        }

        async function handleAddSystem(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const response = await fetch('api.php?action=create_system', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const result = await response.json();
                if (result.success) {
                    showToast('시스템이 추가되었습니다', 'success');
                    closeModal('addSystemModal');
                    e.target.reset();
                    loadDataMatrix();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('추가 실패: ' + error.message, 'error');
            }
        }

        async function handleAddManufacturer(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const response = await fetch('api.php?action=create_manufacturer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const result = await response.json();
                if (result.success) {
                    showToast('제조사가 추가되었습니다', 'success');
                    closeModal('addManufacturerModal');
                    e.target.reset();
                    loadDataMatrix();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('추가 실패: ' + error.message, 'error');
            }
        }

        async function handleImportCsv(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const response = await fetch('api.php?action=import_csv', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    showToast(`가져오기 완료: 시스템 ${result.stats.systems}개, 제조사 ${result.stats.manufacturers}개, Scanbody ${result.stats.scanbodies}개`, 'success');
                    closeModal('importCsvModal');
                    e.target.reset();
                    loadDataMatrix();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('가져오기 실패: ' + error.message, 'error');
            }
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openAddSystemModal() {
            openModal('addSystemModal');
        }

        function openAddManufacturerModal() {
            openModal('addManufacturerModal');
        }

        function openImportCsvModal() {
            openModal('importCsvModal');
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');

            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';

            toast.className = `toast ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${escapeHtml(message)}</span>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => container.removeChild(toast), 300);
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
