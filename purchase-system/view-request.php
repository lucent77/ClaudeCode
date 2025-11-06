<?php
/**
 * View Purchase Request Details
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
requireLogin();

$pageTitle = 'Request Details';
$db = Database::getInstance();

// Get request ID
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($requestId < 1) {
    setFlash('Invalid request ID', 'error');
    redirect('/purchase-system/dashboard.php');
}

// Get request details
$request = $db->fetchOne(
    "SELECT pr.*, u.username as requester_name, u.email as requester_email
     FROM purchase_requests pr
     LEFT JOIN users u ON pr.user_id = u.id
     WHERE pr.id = ?",
    [$requestId]
);

if (!$request) {
    setFlash('Request not found', 'error');
    redirect('/purchase-system/dashboard.php');
}

// Check permission (users can only view their own department's requests, admins can view all)
if (!isAdmin() && $request['department'] !== $_SESSION['department']) {
    setFlash('You do not have permission to view this request', 'error');
    redirect('/purchase-system/dashboard.php');
}

include __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="/purchase-system/dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Request Header -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Purchase Request #<?php echo $request['id']; ?></h1>
                <p class="text-sm text-gray-500 mt-1">
                    Submitted on <?php echo formatDate($request['requested_at'], 'F d, Y \a\t g:i A'); ?>
                </p>
            </div>
            <div>
                <?php echo getStatusBadge($request['status']); ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t">
            <div>
                <p class="text-sm text-gray-500">Department</p>
                <p class="font-semibold text-gray-900"><?php echo e($request['department']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Requested By</p>
                <p class="font-semibold text-gray-900"><?php echo e($request['requester_name'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Request Type</p>
                <p class="font-semibold text-gray-900"><?php echo e($request['request_type']); ?></p>
            </div>
        </div>
    </div>

    <!-- Product Details -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Product Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Product Name</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo e($request['product_name']); ?></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Vendor</p>
                    <?php if (!empty($request['vendor_link'])): ?>
                    <a href="<?php echo e($request['vendor_link']); ?>" target="_blank" class="text-lg font-semibold text-blue-600 hover:text-blue-800">
                        <?php echo e($request['vendor_name']); ?> →
                    </a>
                    <?php else: ?>
                    <p class="text-lg font-semibold text-gray-900"><?php echo e($request['vendor_name'] ?? 'N/A'); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Quantity</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo $request['quantity']; ?> units</p>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Price per Unit</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo formatCurrency($request['price']); ?></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Total Cost</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo formatCurrency($request['price'] * $request['quantity']); ?></p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Shipping Speed</p>
                    <p class="text-lg font-semibold text-gray-900"><?php echo e($request['shipping_speed'] ?? 'Not specified'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Reason -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Purchase Reason</h2>
        <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($request['reason']); ?></p>
    </div>

    <!-- Photo/Document -->
    <?php if (!empty($request['photo_path'])): ?>
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Attached Photo/Document</h2>
        <?php
        $extension = strtolower(pathinfo($request['photo_path'], PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])):
        ?>
        <img src="/purchase-system/<?php echo e($request['photo_path']); ?>" alt="Product Photo" class="max-w-full h-auto rounded-lg border">
        <?php else: ?>
        <a href="/purchase-system/<?php echo e($request['photo_path']); ?>" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            View Document
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Notes -->
    <?php if (!empty($request['notes'])): ?>
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Admin Notes</h2>
        <p class="text-gray-700 whitespace-pre-wrap"><?php echo e($request['notes']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Status Timeline -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Status Timeline</h2>

        <div class="space-y-4">
            <!-- Requested -->
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Request Submitted</p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['requested_at'], 'F d, Y \a\t g:i A'); ?></p>
                </div>
            </div>

            <!-- Approved -->
            <?php if ($request['approved_at']): ?>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Request Approved</p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['approved_at'], 'F d, Y \a\t g:i A'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Completed -->
            <?php if ($request['completed_at']): ?>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Request Completed</p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['completed_at'], 'F d, Y \a\t g:i A'); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>

        <div class="flex flex-wrap gap-4">
            <?php if (isAdmin()): ?>
            <a href="/purchase-system/admin/edit-request.php?id=<?php echo $request['id']; ?>" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Request
            </a>
            <?php endif; ?>

            <button onclick="window.print()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Print
            </button>

            <?php if ($request['status'] === 'pending' && ($request['user_id'] == $_SESSION['user_id'] || isAdmin())): ?>
            <a
                href="/purchase-system/admin/delete-request.php?id=<?php echo $request['id']; ?>"
                onclick="return confirmDelete('Are you sure you want to delete this request?')"
                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
            >
                Delete Request
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
