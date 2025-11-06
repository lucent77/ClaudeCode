<?php
/**
 * Admin - Edit Purchase Request
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin access
requireAdmin();

$pageTitle = 'Edit Request';
$db = Database::getInstance();

// Get request ID
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId < 1) {
    setFlash('Invalid request ID', 'error');
    redirect('/purchase-system/admin/manage-requests.php');
}

// Get request details
$request = $db->fetchOne(
    "SELECT * FROM purchase_requests WHERE id = ?",
    [$requestId]
);

if (!$request) {
    setFlash('Request not found', 'error');
    redirect('/purchase-system/admin/manage-requests.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize($_POST['status'] ?? '');
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0.00;
    $notes = sanitize($_POST['notes'] ?? '');

    try {
        $updateData = [
            'status' => $status,
            'price' => $price,
            'notes' => $notes
        ];

        // Update approved_at if status is approved
        if ($status === 'approved' && empty($request['approved_at'])) {
            $updateData['approved_at'] = date('Y-m-d H:i:s');
        }

        // Update completed_at if status is completed
        if ($status === 'completed' && empty($request['completed_at'])) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        $db->update('purchase_requests', $updateData, 'id = ?', [$requestId]);

        // Notify the requester
        if ($request['user_id']) {
            createNotification(
                $request['user_id'],
                "Your purchase request #{$requestId} status has been updated to: {$status}",
                $requestId
            );
        }

        setFlash('Request updated successfully', 'success');
        redirect('/purchase-system/view-request.php?id=' . $requestId);

    } catch (Exception $e) {
        error_log("Error updating request: " . $e->getMessage());
        setFlash('An error occurred while updating the request', 'error');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/purchase-system/view-request.php?id=<?php echo $request['id']; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Request
        </a>
    </div>

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Purchase Request #<?php echo $request['id']; ?></h1>
        <p class="mt-2 text-sm text-gray-600">Update the status and details of this purchase request.</p>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="" class="bg-white shadow rounded-lg p-8 space-y-6">
        <!-- Request Info (Read-only) -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Product</p>
                    <p class="font-semibold text-gray-900"><?php echo e($request['product_name']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Department</p>
                    <p class="font-semibold text-gray-900"><?php echo e($request['department']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Vendor</p>
                    <p class="font-semibold text-gray-900"><?php echo e($request['vendor_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Quantity</p>
                    <p class="font-semibold text-gray-900"><?php echo $request['quantity']; ?> units</p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                Request Status <span class="text-red-500">*</span>
            </label>
            <select
                id="status"
                name="status"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="ordered" <?php echo $request['status'] === 'ordered' ? 'selected' : ''; ?>>Ordered</option>
                <option value="delivered" <?php echo $request['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="rejected" <?php echo $request['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>

        <!-- Price -->
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                Price per Unit
            </label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">$</span>
                <input
                    type="number"
                    id="price"
                    name="price"
                    step="0.01"
                    min="0"
                    value="<?php echo $request['price']; ?>"
                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
        </div>

        <!-- Admin Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                Admin Notes
            </label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
                placeholder="Add any notes or comments about this request..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ><?php echo e($request['notes']); ?></textarea>
            <p class="mt-1 text-sm text-gray-500">These notes will be visible to the requester</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-6 border-t">
            <a href="/purchase-system/view-request.php?id=<?php echo $request['id']; ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Update Request
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
