<?php
/**
 * JSON Customer Import Script
 * Smart Delivery Route Optimizer
 *
 * Usage:
 * 1. Web: Access this file directly in browser
 * 2. CLI: php import_json_customers.php /path/to/customers.json
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/functions/customer_import.php';

// Check if running from CLI or Web
$isCli = php_sapi_name() === 'cli';

// Configuration
$defaultJsonPath = __DIR__ . '/data/sample_customers.json';
$geocodeEnabled = true; // Set to false to skip geocoding

/**
 * Output message (works in both CLI and Web)
 */
function output($message, $type = 'info') {
    global $isCli;

    if ($isCli) {
        $prefix = '';
        switch ($type) {
            case 'success': $prefix = "\033[32m[OK]\033[0m "; break;
            case 'error':   $prefix = "\033[31m[ERROR]\033[0m "; break;
            case 'warning': $prefix = "\033[33m[WARN]\033[0m "; break;
            default:        $prefix = "[INFO] ";
        }
        echo $prefix . $message . "\n";
    } else {
        $class = '';
        switch ($type) {
            case 'success': $class = 'text-green-600'; break;
            case 'error':   $class = 'text-red-600'; break;
            case 'warning': $class = 'text-yellow-600'; break;
            default:        $class = 'text-gray-700';
        }
        echo "<p class=\"$class mb-2\">$message</p>";
    }
}

// CLI mode
if ($isCli) {
    echo "\n=== Smart Delivery Route Optimizer ===\n";
    echo "=== JSON Customer Import Tool ===\n\n";

    // Get file path from argument or use default
    $jsonPath = isset($argv[1]) ? $argv[1] : $defaultJsonPath;

    if (!file_exists($jsonPath)) {
        output("File not found: $jsonPath", 'error');
        exit(1);
    }

    output("Importing from: $jsonPath");
    output("Geocoding: " . ($geocodeEnabled ? "Enabled" : "Disabled"));
    echo "\n";

    // Run import
    $result = importCustomersFromJson($jsonPath, $geocodeEnabled);

    // Display results
    echo "\n=== Import Results ===\n";
    output("Total records: " . $result['total']);
    output("Imported: " . $result['imported'], 'success');
    output("Updated: " . $result['updated'], 'warning');
    output("Skipped: " . $result['skipped'], $result['skipped'] > 0 ? 'error' : 'info');
    output("Geocoded: " . $result['geocoded']);

    if (!empty($result['errors'])) {
        echo "\n=== Errors ===\n";
        foreach (array_slice($result['errors'], 0, 10) as $error) {
            output($error, 'error');
        }
        if (count($result['errors']) > 10) {
            output("... and " . (count($result['errors']) - 10) . " more errors", 'warning');
        }
    }

    echo "\n";
    exit($result['success'] ? 0 : 1);
}

// Web mode
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON Customer Import - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">JSON Customer Import</h1>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php
                // Handle file upload or path input
                $jsonPath = null;
                $tempFile = false;

                if (!empty($_FILES['json_file']['tmp_name'])) {
                    $jsonPath = $_FILES['json_file']['tmp_name'];
                    $tempFile = false;
                } elseif (!empty($_POST['json_path'])) {
                    $jsonPath = $_POST['json_path'];
                } else {
                    $jsonPath = $defaultJsonPath;
                }

                $geocodeEnabled = isset($_POST['geocode']) && $_POST['geocode'] === '1';
                ?>

                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <h2 class="font-semibold text-blue-800 mb-2">Import Progress</h2>
                    <div class="space-y-1">
                        <?php output("Source: " . ($tempFile ? 'Uploaded file' : $jsonPath)); ?>
                        <?php output("Geocoding: " . ($geocodeEnabled ? 'Enabled' : 'Disabled')); ?>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h2 class="font-semibold text-gray-800 mb-2">Processing...</h2>
                    <?php
                    flush();
                    ob_flush();

                    $result = importCustomersFromJson($jsonPath, $geocodeEnabled);
                    ?>
                </div>

                <div class="mb-6 p-4 <?= $result['success'] ? 'bg-green-50' : 'bg-red-50' ?> rounded-lg">
                    <h2 class="font-semibold <?= $result['success'] ? 'text-green-800' : 'text-red-800' ?> mb-2">
                        Import <?= $result['success'] ? 'Completed' : 'Failed' ?>
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-gray-700"><?= $result['total'] ?></div>
                            <div class="text-sm text-gray-500">Total</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-green-600"><?= $result['imported'] ?></div>
                            <div class="text-sm text-gray-500">Imported</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-yellow-600"><?= $result['updated'] ?></div>
                            <div class="text-sm text-gray-500">Updated</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-blue-600"><?= $result['geocoded'] ?></div>
                            <div class="text-sm text-gray-500">Geocoded</div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($result['errors'])): ?>
                <div class="mb-6 p-4 bg-red-50 rounded-lg">
                    <h2 class="font-semibold text-red-800 mb-2">Errors (<?= count($result['errors']) ?>)</h2>
                    <div class="max-h-48 overflow-y-auto text-sm">
                        <?php foreach (array_slice($result['errors'], 0, 20) as $error): ?>
                            <p class="text-red-600 mb-1"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                        <?php if (count($result['errors']) > 20): ?>
                            <p class="text-red-800 font-semibold">... and <?= count($result['errors']) - 20 ?> more errors</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex gap-4">
                    <a href="import_json_customers.php" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Import More
                    </a>
                    <a href="index.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        View Customers
                    </a>
                </div>

            <?php else: ?>
                <!-- Import Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload JSON File
                        </label>
                        <input type="file" name="json_file" accept=".json"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div class="text-center text-gray-500">- OR -</div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Server File Path
                        </label>
                        <input type="text" name="json_path" value="<?= htmlspecialchars($defaultJsonPath) ?>"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-sm text-gray-500">Path to JSON file on server</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="geocode" value="1" checked
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">
                                Enable Geocoding (converts addresses to coordinates using Google API)
                            </span>
                        </label>
                        <p class="mt-1 text-sm text-gray-500 ml-6">
                            Note: Geocoding uses Google API calls. Disable if API key is not configured.
                        </p>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                                class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Start Import
                        </button>
                    </div>
                </form>

                <!-- Current Stats -->
                <?php
                $stats = getImportStats();
                if (!isset($stats['error'])):
                ?>
                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <h2 class="font-semibold text-gray-800 mb-4">Current Database Stats</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-gray-700"><?= $stats['total_customers'] ?></div>
                            <div class="text-sm text-gray-500">Total Customers</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-green-600"><?= $stats['geocoded_customers'] ?></div>
                            <div class="text-sm text-gray-500">With Coordinates</div>
                        </div>
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <div class="text-2xl font-bold text-blue-600"><?= $stats['local_courier_count'] ?></div>
                            <div class="text-sm text-gray-500">Local Courier</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
