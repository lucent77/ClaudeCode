<?php
$page_title = 'Import Customers';
require_once __DIR__ . '/../includes/header.php';
requireAnyRole(['admin', 'manager']);

$db = getDB();
$message = '';
$messageType = '';
$importResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $message = 'Please upload a CSV file';
            $messageType = 'danger';
        } else {
            // Process CSV file
            $handle = fopen($file['tmp_name'], 'r');

            if ($handle !== false) {
                $importResults = [
                    'total' => 0,
                    'success' => 0,
                    'skipped' => 0,
                    'errors' => []
                ];

                // Read header row
                $header = fgetcsv($handle);

                // Map header to field names
                $fieldMap = [
                    'Customer Key' => 'customer_key',
                    'Account Number' => 'account_number',
                    'BillTo Account Number' => 'billto_account_number',
                    'Account Type' => 'account_type',
                    'Account Class' => 'account_class',
                    'Customer Status' => 'customer_status',
                    'Customer Status Description' => 'customer_status_description',
                    'Customer Status Reason' => 'customer_status_reason',
                    'Title' => 'title',
                    'First Name' => 'first_name',
                    'Last Name' => 'last_name',
                    'Middle Initial' => 'middle_initial',
                    'Practice Name' => 'practice_name',
                    'Address Line 1' => 'address_line1',
                    'Address Line 2' => 'address_line2',
                    'Address Line 3' => 'address_line3',
                    'City' => 'city',
                    'State Code' => 'state_code',
                    'Zip Code' => 'zip_code',
                    'Website' => 'website',
                    'Phone Number' => 'phone_number',
                    'Fax Number' => 'fax_number',
                    'Email Address' => 'email_address',
                    'Cell Phone' => 'cell_phone',
                    'Route Key' => 'route_key',
                    'Route Order' => 'route_order',
                    'Route Visit Timeframe' => 'route_visit_timeframe',
                    'BillTo Flag' => 'billto_flag',
                    'ShipTo Flag' => 'shipto_flag',
                    'Allow Case Entry' => 'allow_case_entry',
                    'Always Visit' => 'always_visit',
                    'Date Created' => 'date_created',
                    'Date Of First Case' => 'date_of_first_case',
                    'Date Of Last Case' => 'date_of_last_case',
                    'New Customer' => 'new_customer',
                    'Credit Card AutoPay Group' => 'credit_card_autopay_group'
                ];

                // Prepare insert statement
                $sql = "INSERT INTO customers (
                    customer_key, account_number, billto_account_number, account_type, account_class,
                    customer_status, customer_status_description, customer_status_reason,
                    title, first_name, last_name, middle_initial, practice_name,
                    address_line1, address_line2, address_line3, city, state_code, zip_code,
                    website, phone_number, fax_number, email_address, cell_phone,
                    route_key, route_order, route_visit_timeframe,
                    billto_flag, shipto_flag, allow_case_entry, always_visit,
                    date_created, date_of_first_case, date_of_last_case,
                    new_customer, credit_card_autopay_group,
                    is_active
                ) VALUES (
                    :customer_key, :account_number, :billto_account_number, :account_type, :account_class,
                    :customer_status, :customer_status_description, :customer_status_reason,
                    :title, :first_name, :last_name, :middle_initial, :practice_name,
                    :address_line1, :address_line2, :address_line3, :city, :state_code, :zip_code,
                    :website, :phone_number, :fax_number, :email_address, :cell_phone,
                    :route_key, :route_order, :route_visit_timeframe,
                    :billto_flag, :shipto_flag, :allow_case_entry, :always_visit,
                    :date_created, :date_of_first_case, :date_of_last_case,
                    :new_customer, :credit_card_autopay_group,
                    :is_active
                )";

                $stmt = $db->prepare($sql);

                $lineNumber = 1; // Header is line 1
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNumber++;
                    $importResults['total']++;

                    try {
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            $importResults['skipped']++;
                            continue;
                        }

                        // Map row data to fields
                        $data = [];
                        foreach ($header as $index => $columnName) {
                            $fieldName = $fieldMap[$columnName] ?? null;
                            if ($fieldName) {
                                $value = $row[$index] ?? '';
                                $data[$fieldName] = trim($value);
                            }
                        }

                        // Skip if customer_key or account_number is empty
                        if (empty($data['customer_key']) && empty($data['account_number'])) {
                            $importResults['skipped']++;
                            continue;
                        }

                        // Convert boolean fields
                        $data['billto_flag'] = convertBoolean($data['billto_flag'] ?? 'FALSE');
                        $data['shipto_flag'] = convertBoolean($data['shipto_flag'] ?? 'FALSE');
                        $data['allow_case_entry'] = convertBoolean($data['allow_case_entry'] ?? 'FALSE');
                        $data['always_visit'] = convertBoolean($data['always_visit'] ?? 'FALSE');
                        $data['new_customer'] = convertBoolean($data['new_customer'] ?? '0');

                        // Convert dates
                        $data['date_created'] = convertCSVDate($data['date_created'] ?? '');
                        $data['date_of_first_case'] = convertCSVDate($data['date_of_first_case'] ?? '');
                        $data['date_of_last_case'] = convertCSVDate($data['date_of_last_case'] ?? '');

                        // Set is_active based on customer_status
                        $data['is_active'] = ($data['customer_status'] === 'A') ? 1 : 0;

                        // Execute insert
                        $stmt->execute($data);
                        $importResults['success']++;

                    } catch (PDOException $e) {
                        // Check if it's a duplicate key error
                        if ($e->getCode() == 23000) {
                            $importResults['skipped']++;
                            $importResults['errors'][] = "Line $lineNumber: Duplicate customer (already exists)";
                        } else {
                            $importResults['errors'][] = "Line $lineNumber: " . $e->getMessage();
                        }
                    }
                }

                fclose($handle);

                // Log the import
                logAction('import_customers', 'customers', null, "Imported {$importResults['success']} customers from CSV");

                $message = "Import completed: {$importResults['success']} customers imported, {$importResults['skipped']} skipped";
                $messageType = 'success';
            } else {
                $message = 'Failed to open CSV file';
                $messageType = 'danger';
            }
        }
    } else {
        $message = 'File upload error: ' . $file['error'];
        $messageType = 'danger';
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-upload"></i> Import Customers</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Import Customers</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($importResults): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle"></i> Import Results</h5>
                </div>
                <div class="card-body import-results">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo $importResults['total']; ?></h3>
                                <p class="text-muted">Total Rows</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-success"><?php echo $importResults['success']; ?></h3>
                                <p class="text-muted">Imported</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-warning"><?php echo $importResults['skipped']; ?></h3>
                                <p class="text-muted">Skipped</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-danger"><?php echo count($importResults['errors']); ?></h3>
                                <p class="text-muted">Errors</p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($importResults['errors'])): ?>
                    <div class="mt-4">
                        <h6>Errors:</h6>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (array_slice($importResults['errors'], 0, 10) as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                                <?php if (count($importResults['errors']) > 10): ?>
                                    <li><em>... and <?php echo count($importResults['errors']) - 10; ?> more errors</em></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="csv_file" class="form-label required">Select CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <div class="form-text">
                                Upload a CSV file with customer data. Maximum file size: 5MB
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Important Notes:</h6>
                            <ul class="mb-0">
                                <li>The CSV file must include a header row with column names</li>
                                <li>Required columns: Customer Key or Account Number</li>
                                <li>Duplicate customers (based on Customer Key or Account Number) will be skipped</li>
                                <li>Date format: YYYY-MM-DD HH:MM:SS or MM/DD/YYYY</li>
                                <li>Boolean fields: TRUE/FALSE or 1/0</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload"></i> Upload and Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> CSV Format Example</h5>
                </div>
                <div class="card-body">
                    <p>Your CSV file should have the following columns:</p>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <ul class="small">
                            <li>Customer Key</li>
                            <li>Account Number</li>
                            <li>BillTo Account Number</li>
                            <li>Account Type</li>
                            <li>Account Class</li>
                            <li>Customer Status</li>
                            <li>Customer Status Description</li>
                            <li>Title</li>
                            <li>First Name</li>
                            <li>Last Name</li>
                            <li>Practice Name</li>
                            <li>Address Line 1</li>
                            <li>City</li>
                            <li>State Code</li>
                            <li>Zip Code</li>
                            <li>Phone Number</li>
                            <li>Email Address</li>
                            <li>Date Created</li>
                            <li>... and more</li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <a href="/admin/sample.csv" class="btn btn-sm btn-outline-primary w-100" download>
                            <i class="bi bi-download"></i> Download Sample CSV
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Import Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $totalCustomers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
                        $recentImports = $db->query("
                            SELECT COUNT(*)
                            FROM customers
                            WHERE created_in_system >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        ")->fetchColumn();
                    ?>
                        <div class="mb-3">
                            <strong>Total Customers:</strong><br>
                            <span class="h4"><?php echo number_format($totalCustomers); ?></span>
                        </div>
                        <div>
                            <strong>Imported Last 7 Days:</strong><br>
                            <span class="h4"><?php echo number_format($recentImports); ?></span>
                        </div>
                    <?php
                    } catch (PDOException $e) {
                        echo '<p class="text-muted">Statistics unavailable</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
