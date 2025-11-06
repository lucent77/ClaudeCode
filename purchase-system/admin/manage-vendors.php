<?php
/**
 * Admin - Manage Vendors
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$pageTitle = 'Manage Vendors';
$db = Database::getInstance();

// Handle vendor creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $vendorName = sanitize($_POST['vendor_name'] ?? '');
    $vendorLink = sanitize($_POST['vendor_link'] ?? '');
    $contact = sanitize($_POST['contact'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (!empty($vendorName)) {
        try {
            $db->insert('vendors', [
                'vendor_name' => $vendorName,
                'vendor_link' => $vendorLink,
                'contact' => $contact,
                'email' => $email
            ]);
            setFlash('Vendor created successfully', 'success');
        } catch (Exception $e) {
            setFlash('Error creating vendor: ' . $e->getMessage(), 'error');
        }
    }
}

// Get all vendors with purchase count
$vendors = $db->fetchAll(
    "SELECT v.*, COUNT(pr.id) as purchase_count, SUM(pr.price * pr.quantity) as total_spent
     FROM vendors v
     LEFT JOIN purchase_requests pr ON v.id = pr.vendor_id
     GROUP BY v.id
     ORDER BY v.vendor_name ASC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Manage Vendors</h1>
    <p class="mt-2 text-sm text-gray-600">Create and manage vendor information</p>
</div>

<!-- Create Vendor Form -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Add New Vendor</h2>
    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="create">
        <input type="text" name="vendor_name" placeholder="Vendor Name *" required class="px-4 py-2 border rounded-lg">
        <input type="url" name="vendor_link" placeholder="Vendor Website" class="px-4 py-2 border rounded-lg">
        <input type="text" name="contact" placeholder="Contact Phone" class="px-4 py-2 border rounded-lg">
        <input type="email" name="email" placeholder="Vendor Email" class="px-4 py-2 border rounded-lg">
        <button type="submit" class="md:col-span-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Add Vendor</button>
    </form>
</div>

<!-- Vendors Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Website</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchases</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($vendors as $vendor): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $vendor['id']; ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($vendor['vendor_name']); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                    <?php if (!empty($vendor['vendor_link'])): ?>
                    <a href="<?php echo e($vendor['vendor_link']); ?>" target="_blank" class="hover:text-blue-800">Visit →</a>
                    <?php else: ?>
                    N/A
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($vendor['contact'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($vendor['email'] ?? 'N/A'); ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $vendor['purchase_count']; ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatCurrency($vendor['total_spent'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
