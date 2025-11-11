<?php
/**
 * Bulk Data Upload
 * Upload tools and production data via CSV/JSON
 */

require_once 'config/database.php';
checkAdmin(); // Only admin can upload data

$page_title = 'Upload Data - Production Management System';

include 'includes/header.php';
?>

<div class="px-4 sm:px-0">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Bulk Data Upload</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tools CSV Upload -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Upload Tools (CSV)</h2>
            <p class="text-sm text-gray-600 mb-4">
                Upload tool data from a CSV file. Existing tools (by Tool Code) will be updated, new tools will be added.
            </p>

            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Required CSV Format:</h3>
                <div class="bg-gray-50 p-3 rounded text-xs font-mono overflow-x-auto">
                    <div>Tool Code,Tool Name,Category Name,Tool Size,Supplier Name,</div>
                    <div>Supplier Model Number,Current Stock,Minimum Stock,Lifespan Type,</div>
                    <div>Lifespan Limit,Description</div>
                </div>
            </div>

            <form id="toolsUploadForm" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                    <input type="file"
                           name="tools_csv"
                           accept=".csv"
                           required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                    Upload Tools CSV
                </button>
            </form>

            <div id="toolsUploadResult" class="mt-4"></div>
        </div>

        <!-- Production CSV Upload -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Upload Production Records (CSV)</h2>
            <p class="text-sm text-gray-600 mb-4">
                Upload production records from a CSV file.
            </p>

            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Required CSV Format:</h3>
                <div class="bg-gray-50 p-3 rounded text-xs font-mono overflow-x-auto">
                    <div>DATE,MODEL NAME,SKU,LOT. NO,WORKER,CNC,</div>
                    <div>CNC Run Time,PLAN,UNIT TOTAL,%,...</div>
                </div>
            </div>

            <form id="productionUploadForm" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                    <input type="file"
                           name="production_csv"
                           accept=".csv"
                           required
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div class="flex items-center">
                    <input type="checkbox"
                           id="clearExisting"
                           name="clear_existing"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="clearExisting" class="ml-2 block text-sm text-gray-700">
                        Clear existing production data before upload
                    </label>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <p class="text-xs text-yellow-700">
                        <strong>Warning:</strong> Clearing existing data cannot be undone. Use with caution!
                    </p>
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                    Upload Production CSV
                </button>
            </form>

            <div id="productionUploadResult" class="mt-4"></div>
        </div>
    </div>

    <!-- Sample Files -->
    <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
        <h3 class="text-sm font-medium text-blue-800 mb-2">Sample CSV Files</h3>
        <p class="text-sm text-blue-700 mb-3">
            Download sample CSV files to see the correct format:
        </p>
        <div class="flex space-x-4">
            <a href="samples/tools_sample.csv" download class="text-blue-600 hover:text-blue-800 text-sm font-medium underline">
                Tools Sample CSV
            </a>
            <a href="samples/production_sample.csv" download class="text-blue-600 hover:text-blue-800 text-sm font-medium underline">
                Production Sample CSV
            </a>
        </div>
    </div>

    <!-- Upload History -->
    <div class="mt-8 bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Upload Logs</h2>
        <div class="text-sm text-gray-600">
            <p>Upload logs are stored in the <code class="bg-gray-100 px-2 py-1 rounded">logs/</code> directory.</p>
            <p class="mt-2">Format: <code class="bg-gray-100 px-2 py-1 rounded">csv_upload_YYYY-MM-DD_HH-MM-SS.log</code></p>
        </div>
    </div>
</div>

<script>
// Tools CSV Upload
document.getElementById('toolsUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const resultDiv = document.getElementById('toolsUploadResult');

    resultDiv.innerHTML = '<div class="text-blue-600">Uploading...</div>';

    fetch('api/upload_tools_csv.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <p class="text-sm text-green-700">${result.message}</p>
                    <p class="text-xs text-green-600 mt-1">
                        Added: ${result.added || 0} | Updated: ${result.updated || 0}
                    </p>
                </div>
            `;
            this.reset();
            setTimeout(() => {
                if (confirm('Upload successful! Would you like to view the tools page?')) {
                    location.href = 'tools.php';
                }
            }, 1000);
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <p class="text-sm text-red-700">${result.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                <p class="text-sm text-red-700">Error: ${error}</p>
            </div>
        `;
    });
});

// Production CSV Upload
document.getElementById('productionUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const clearExisting = document.getElementById('clearExisting').checked;

    if (clearExisting && !confirm('Are you sure you want to CLEAR all existing production data? This cannot be undone!')) {
        return;
    }

    const formData = new FormData(this);
    const resultDiv = document.getElementById('productionUploadResult');

    resultDiv.innerHTML = '<div class="text-blue-600">Uploading...</div>';

    fetch('api/upload_production_csv.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <p class="text-sm text-green-700">${result.message}</p>
                    <p class="text-xs text-green-600 mt-1">
                        Processed: ${result.processed || 0} records
                    </p>
                    ${result.errors && result.errors.length > 0 ? `
                        <details class="mt-2">
                            <summary class="text-xs text-yellow-700 cursor-pointer">
                                ${result.errors.length} error(s) (click to expand)
                            </summary>
                            <div class="mt-2 text-xs text-red-600 max-h-40 overflow-y-auto">
                                ${result.errors.join('<br>')}
                            </div>
                        </details>
                    ` : ''}
                </div>
            `;
            this.reset();
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <p class="text-sm text-red-700">${result.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                <p class="text-sm text-red-700">Error: ${error}</p>
            </div>
        `;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
