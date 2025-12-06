<?php
/**
 * Creodent Dashboard - Web-Based Data Import
 *
 * This page allows importing JSON data through a web interface.
 * Admin access required.
 */

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Session.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Request.php';

use Creodent\Core\{Auth, Request, Database};

date_default_timezone_set(TIMEZONE);
Auth::requireRole(['admin']);

$message = '';
$messageType = '';
$stats = [];

// Handle file upload
if (Request::isPost() && Request::validateCsrf()) {
    $branch = strtolower(Request::post('branch', 'nyc'));

    if (!in_array($branch, ['nyc', 'hv'])) {
        $message = 'Invalid branch selected.';
        $messageType = 'error';
    } elseif (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a valid JSON file.';
        $messageType = 'error';
    } else {
        $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Invalid JSON file: ' . json_last_error_msg();
            $messageType = 'error';
        } elseif (!is_array($data)) {
            $message = 'JSON must be an array of objects.';
            $messageType = 'error';
        } else {
            // Import data
            $sql = "INSERT INTO invoices (
                invoice_number, invoice_date, customer_name, product_description,
                product_number, net_price, units, remake_amount, discount_amount,
                notes, case_number, order_date, sales_rep, service_center,
                case_status, invoice_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                customer_name = VALUES(customer_name),
                product_description = VALUES(product_description),
                net_price = VALUES(net_price),
                units = VALUES(units)";

            $pdo = Database::getConnection($branch);
            $stmt = $pdo->prepare($sql);

            $imported = 0;
            $errors = 0;

            foreach ($data as $row) {
                try {
                    $invoiceNumber = $row['formattedinvoicenumber'] ?? $row['invoicenumber'] ?? null;
                    $invoiceDate = $row['invoicedate'] ?? $row['utcinvoicedate'] ?? null;

                    if (!$invoiceNumber || !$invoiceDate) {
                        $errors++;
                        continue;
                    }

                    $stmt->execute([
                        $invoiceNumber,
                        date('Y-m-d H:i:s', strtotime($invoiceDate)),
                        $row['practicename'] ?? $row['lname'] ?? null,
                        $row['printdescription'] ?? null,
                        $row['productnumber'] ?? null,
                        floatval($row['pricenet'] ?? $row['nettotal'] ?? 0),
                        intval($row['units'] ?? 0),
                        floatval($row['remakeamount'] ?? 0),
                        floatval($row['discountamount'] ?? 0),
                        $row['invoicenotes'] ?? null,
                        $row['casenumber'] ?? null,
                        $row['order_date'] ? date('Y-m-d H:i:s', strtotime($row['order_date'])) : null,
                        $row['salesrep'] ?? null,
                        $row['servicecenter'] ?? null,
                        $row['casestatus'] ?? 'C',
                        $row['invoicetype'] ?? 'I'
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                }
            }

            $message = "Import complete! {$imported} records imported, {$errors} errors.";
            $messageType = 'success';
            $stats = ['imported' => $imported, 'errors' => $errors, 'total' => count($data)];
        }
    }
}

// Get current database stats
$nycStats = Database::query("SELECT COUNT(*) as count, MIN(invoice_date) as min_date, MAX(invoice_date) as max_date FROM invoices", [], 'nyc')[0] ?? null;
$hvStats = Database::query("SELECT COUNT(*) as count, MIN(invoice_date) as min_date, MAX(invoice_date) as max_date FROM invoices", [], 'hv')[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Import - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <a href="../pages/dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm">&larr; Back to Dashboard</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-4">Data Import</h1>
            <p class="text-gray-500">Import invoice data from JSON files</p>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Current Stats -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="font-semibold text-gray-900 mb-4">Current Database Status</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-amber-50 rounded-lg">
                    <p class="text-sm font-medium text-amber-700">NYC Branch</p>
                    <p class="text-2xl font-bold text-amber-900"><?= number_format($nycStats['count'] ?? 0) ?></p>
                    <p class="text-xs text-amber-600">invoices</p>
                </div>
                <div class="p-4 bg-emerald-50 rounded-lg">
                    <p class="text-sm font-medium text-emerald-700">HV Branch</p>
                    <p class="text-2xl font-bold text-emerald-900"><?= number_format($hvStats['count'] ?? 0) ?></p>
                    <p class="text-xs text-emerald-600">invoices</p>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Import JSON Data</h2>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= Auth::csrfField() ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Branch</label>
                    <select name="branch" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="nyc">NYC Branch</option>
                        <option value="hv">HV Branch</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">JSON File</label>
                    <input type="file" name="json_file" accept=".json" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Maximum file size: <?= ini_get('upload_max_filesize') ?></p>
                </div>

                <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Import Data
                </button>
            </form>
        </div>

        <!-- Expected Format -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
            <h2 class="font-semibold text-gray-900 mb-4">Expected JSON Format</h2>
            <pre class="bg-gray-50 p-4 rounded-lg text-xs overflow-x-auto"><code>[
  {
    "formattedinvoicenumber": "2024-00123",
    "invoicedate": "2024-01-15 10:30:00",
    "practicename": "Customer Name",
    "printdescription": "Product Description",
    "productnumber": "P001",
    "pricenet": 150.00,
    "units": 2,
    "remakeamount": 0.00,
    "discountamount": 10.00,
    "salesrep": "John Doe",
    ...
  }
]</code></pre>
        </div>
    </div>
</body>
</html>
