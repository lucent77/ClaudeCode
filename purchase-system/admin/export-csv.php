<?php
/**
 * Export Data to CSV
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin access
requireAdmin();

$db = Database::getInstance();

$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'requests';
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $type . '_export_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

switch ($type) {
    case 'requests':
        // CSV headers
        fputcsv($output, [
            'ID',
            'Request Type',
            'Department',
            'Product Name',
            'Vendor Name',
            'Vendor Link',
            'Quantity',
            'Price',
            'Total Cost',
            'Shipping Speed',
            'Status',
            'Requested Date',
            'Approved Date',
            'Completed Date',
            'Reason',
            'Notes'
        ]);

        // Build query
        $whereClause = !empty($statusFilter) ? "WHERE status = ?" : "";
        $params = !empty($statusFilter) ? [$statusFilter] : [];

        $requests = $db->fetchAll(
            "SELECT * FROM purchase_requests {$whereClause} ORDER BY requested_at DESC",
            $params
        );

        // Output data
        foreach ($requests as $request) {
            fputcsv($output, [
                $request['id'],
                $request['request_type'],
                $request['department'],
                $request['product_name'],
                $request['vendor_name'],
                $request['vendor_link'],
                $request['quantity'],
                $request['price'],
                $request['price'] * $request['quantity'],
                $request['shipping_speed'],
                $request['status'],
                $request['requested_at'],
                $request['approved_at'],
                $request['completed_at'],
                $request['reason'],
                $request['notes']
            ]);
        }
        break;

    case 'vendors':
        // CSV headers
        fputcsv($output, [
            'ID',
            'Vendor Name',
            'Website',
            'Contact',
            'Email',
            'Total Purchases',
            'Created Date'
        ]);

        $vendors = $db->fetchAll(
            "SELECT v.*, COUNT(pr.id) as purchase_count
             FROM vendors v
             LEFT JOIN purchase_requests pr ON v.id = pr.vendor_id
             GROUP BY v.id
             ORDER BY v.vendor_name ASC"
        );

        foreach ($vendors as $vendor) {
            fputcsv($output, [
                $vendor['id'],
                $vendor['vendor_name'],
                $vendor['vendor_link'],
                $vendor['contact'],
                $vendor['email'],
                $vendor['purchase_count'],
                $vendor['created_at']
            ]);
        }
        break;

    case 'users':
        // CSV headers
        fputcsv($output, [
            'ID',
            'Username',
            'Department',
            'Role',
            'Email',
            'Created Date'
        ]);

        $users = $db->fetchAll("SELECT * FROM users ORDER BY username ASC");

        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['department'],
                $user['role'],
                $user['email'],
                $user['created_at']
            ]);
        }
        break;

    default:
        fputcsv($output, ['Error: Invalid export type']);
        break;
}

fclose($output);
exit();
?>
