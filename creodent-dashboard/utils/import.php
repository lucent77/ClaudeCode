<?php
/**
 * Creodent Dashboard - JSON Data Import Utility
 *
 * This script imports invoice data from JSON format into the MySQL database.
 *
 * Usage:
 *   php import.php <json_file> <branch>
 *
 * Example:
 *   php import.php invoices_nyc.json nyc
 *   php import.php invoices_hv.json hv
 *
 * JSON Format Expected:
 * Array of objects with fields like:
 *   - formattedinvoicenumber -> invoice_number
 *   - invoicedate/utcinvoicedate -> invoice_date
 *   - lname/practicename -> customer_name
 *   - printdescription -> product_description
 *   - productnumber -> product_number
 *   - pricenet/nettotal -> net_price
 *   - units -> units
 *   - remakeamount -> remake_amount
 *   - discountamount -> discount_amount
 *   - invoicenotes -> notes
 *   - casenumber -> case_number
 *   - order_date -> order_date
 *   - salesrep -> sales_rep
 *   - servicecenter -> service_center
 *   - casestatus -> case_status
 *   - invoicetype -> invoice_type
 */

// Only run from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/core/Database.php';

use Creodent\Core\Database;

// Parse arguments
$jsonFile = $argv[1] ?? null;
$branch = strtolower($argv[2] ?? 'nyc');

if (!$jsonFile) {
    echo "Usage: php import.php <json_file> <branch>\n";
    echo "Example: php import.php invoices_nyc.json nyc\n";
    exit(1);
}

if (!in_array($branch, ['nyc', 'hv'])) {
    echo "Error: Branch must be 'nyc' or 'hv'\n";
    exit(1);
}

if (!file_exists($jsonFile)) {
    echo "Error: File not found: {$jsonFile}\n";
    exit(1);
}

echo "==============================================\n";
echo "Creodent Dashboard - Data Import Utility\n";
echo "==============================================\n";
echo "File: {$jsonFile}\n";
echo "Branch: " . strtoupper($branch) . "\n";
echo "==============================================\n\n";

// Read and parse JSON
echo "Reading JSON file...\n";
$jsonContent = file_get_contents($jsonFile);
$data = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON - " . json_last_error_msg() . "\n";
    exit(1);
}

if (!is_array($data)) {
    echo "Error: JSON must be an array of objects\n";
    exit(1);
}

$totalRecords = count($data);
echo "Found {$totalRecords} records to import.\n\n";

// Prepare insert statement
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
    units = VALUES(units),
    remake_amount = VALUES(remake_amount),
    discount_amount = VALUES(discount_amount)";

$pdo = Database::getConnection($branch);
$stmt = $pdo->prepare($sql);

$imported = 0;
$errors = 0;
$batchSize = 100;

echo "Importing records...\n";

foreach ($data as $index => $row) {
    try {
        // Map JSON fields to database fields
        $invoiceNumber = $row['formattedinvoicenumber'] ?? $row['invoicenumber'] ?? null;
        $invoiceDate = $row['invoicedate'] ?? $row['utcinvoicedate'] ?? null;
        $customerName = $row['practicename'] ?? $row['lname'] ?? $row['billtolname'] ?? null;
        $productDescription = $row['printdescription'] ?? null;
        $productNumber = $row['productnumber'] ?? null;
        $netPrice = floatval($row['pricenet'] ?? $row['nettotal'] ?? 0);
        $units = intval($row['units'] ?? 0);
        $remakeAmount = floatval($row['remakeamount'] ?? 0);
        $discountAmount = floatval($row['discountamount'] ?? 0);
        $notes = $row['invoicenotes'] ?? null;
        $caseNumber = $row['casenumber'] ?? null;
        $orderDate = $row['order_date'] ?? null;
        $salesRep = $row['salesrep'] ?? null;
        $serviceCenter = $row['servicecenter'] ?? null;
        $caseStatus = $row['casestatus'] ?? 'C';
        $invoiceType = $row['invoicetype'] ?? 'I';

        // Skip if no invoice number or date
        if (!$invoiceNumber || !$invoiceDate) {
            $errors++;
            continue;
        }

        // Format dates
        if ($invoiceDate) {
            $invoiceDate = date('Y-m-d H:i:s', strtotime($invoiceDate));
        }
        if ($orderDate) {
            $orderDate = date('Y-m-d H:i:s', strtotime($orderDate));
        }

        $stmt->execute([
            $invoiceNumber,
            $invoiceDate,
            $customerName,
            $productDescription,
            $productNumber,
            $netPrice,
            $units,
            $remakeAmount,
            $discountAmount,
            $notes,
            $caseNumber,
            $orderDate,
            $salesRep,
            $serviceCenter,
            $caseStatus,
            $invoiceType
        ]);

        $imported++;

        // Progress indicator
        if (($index + 1) % $batchSize === 0) {
            $percent = round((($index + 1) / $totalRecords) * 100);
            echo "\r  Progress: {$percent}% ({$imported} imported, {$errors} errors)";
        }
    } catch (\Exception $e) {
        $errors++;
        if ($errors <= 10) {
            echo "\n  Error on record " . ($index + 1) . ": " . $e->getMessage();
        }
    }
}

echo "\n\n==============================================\n";
echo "Import Complete!\n";
echo "==============================================\n";
echo "Total Records: {$totalRecords}\n";
echo "Imported: {$imported}\n";
echo "Errors: {$errors}\n";
echo "==============================================\n";

// Show sample of imported data
echo "\nVerifying import...\n";
$verify = Database::query(
    "SELECT COUNT(*) as count, MIN(invoice_date) as min_date, MAX(invoice_date) as max_date FROM invoices",
    [],
    $branch
);

if ($verify && $verify[0]) {
    echo "Database now contains: {$verify[0]['count']} invoices\n";
    echo "Date range: {$verify[0]['min_date']} to {$verify[0]['max_date']}\n";
}

echo "\nDone!\n";
