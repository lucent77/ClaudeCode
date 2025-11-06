<?php
/**
 * New Purchase Request Form
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
requireLogin();

$pageTitle = 'New Purchase Request';
$db = Database::getInstance();

// Get vendors for dropdown
$vendors = $db->fetchAll("SELECT * FROM vendors ORDER BY vendor_name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlash('Invalid form submission', 'error');
        redirect('/purchase-system/new-request.php');
    }

    // Sanitize and validate input
    $requestType = sanitize($_POST['request_type'] ?? '');
    $productName = sanitize($_POST['product_name'] ?? '');
    $vendorId = !empty($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : null;
    $vendorName = sanitize($_POST['vendor_name'] ?? '');
    $vendorLink = sanitize($_POST['vendor_link'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $shippingSpeed = sanitize($_POST['shipping_speed'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0.00;

    // Validation
    $errors = [];

    if (empty($requestType)) $errors[] = 'Request type is required';
    if (empty($productName)) $errors[] = 'Product name is required';
    if (empty($reason)) $errors[] = 'Purchase reason is required';
    if ($quantity < 1) $errors[] = 'Quantity must be at least 1';

    // Handle file upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = handleFileUpload($_FILES['photo']);
        if ($uploadResult['success']) {
            $photoPath = $uploadResult['path'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Check if vendor exists, if not create it
            if ($vendorId) {
                $vendor = $db->fetchOne("SELECT * FROM vendors WHERE id = ?", [$vendorId]);
                $vendorName = $vendor['vendor_name'];
                $vendorLink = $vendor['vendor_link'];
            } elseif (!empty($vendorName)) {
                // Check if vendor already exists by name
                $existingVendor = $db->fetchOne("SELECT id FROM vendors WHERE vendor_name = ?", [$vendorName]);
                if ($existingVendor) {
                    $vendorId = $existingVendor['id'];
                } else {
                    // Create new vendor
                    $vendorId = $db->insert('vendors', [
                        'vendor_name' => $vendorName,
                        'vendor_link' => $vendorLink
                    ]);
                }
            }

            // Check if product exists, if not create it
            $product = $db->fetchOne("SELECT id FROM products WHERE product_name = ?", [$productName]);
            $productId = null;

            if ($product) {
                $productId = $product['id'];
            } else {
                // Create new product
                $productId = $db->insert('products', [
                    'product_name' => $productName,
                    'default_vendor_id' => $vendorId,
                    'avg_price' => $price
                ]);
            }

            // Insert purchase request
            $requestId = $db->insert('purchase_requests', [
                'request_type' => $requestType,
                'department' => $_SESSION['department'],
                'user_id' => $_SESSION['user_id'],
                'product_id' => $productId,
                'product_name' => $productName,
                'vendor_id' => $vendorId,
                'vendor_name' => $vendorName,
                'vendor_link' => $vendorLink,
                'quantity' => $quantity,
                'shipping_speed' => $shippingSpeed,
                'reason' => $reason,
                'photo_path' => $photoPath,
                'price' => $price,
                'status' => 'pending'
            ]);

            // Notify admins
            $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin'");
            foreach ($admins as $admin) {
                createNotification(
                    $admin['id'],
                    "New purchase request #{$requestId} submitted by {$_SESSION['department']}",
                    $requestId
                );
            }

            $db->commit();

            setFlash('Purchase request submitted successfully!', 'success');
            redirect('/purchase-system/view-request.php?id=' . $requestId);

        } catch (Exception $e) {
            $db->rollback();
            error_log("Error creating purchase request: " . $e->getMessage());
            setFlash('An error occurred while submitting your request. Please try again.', 'error');
        }
    } else {
        foreach ($errors as $error) {
            setFlash($error, 'error');
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

include __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">New Purchase Request</h1>
        <p class="mt-2 text-sm text-gray-600">Fill out the form below to submit a new purchase request.</p>
    </div>

    <!-- Purchase Request Form -->
    <form method="POST" action="" enctype="multipart/form-data" class="bg-white shadow rounded-lg p-8 space-y-6">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <!-- Request Type -->
        <div>
            <label for="request_type" class="block text-sm font-medium text-gray-700 mb-2">
                Purchase Request Type <span class="text-red-500">*</span>
            </label>
            <select
                id="request_type"
                name="request_type"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Select request type</option>
                <option value="Equipment">Equipment</option>
                <option value="Office Supplies">Office Supplies</option>
                <option value="Software">Software</option>
                <option value="Hardware">Hardware</option>
                <option value="Furniture">Furniture</option>
                <option value="Services">Services</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Department (auto-filled) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Department / PAN#
            </label>
            <input
                type="text"
                value="<?php echo e($_SESSION['department']); ?>"
                disabled
                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
            >
            <p class="mt-1 text-sm text-gray-500">This is your department identifier</p>
        </div>

        <!-- Product Name with Autocomplete -->
        <div class="relative">
            <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">
                Product Name <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                id="product_name"
                name="product_name"
                required
                placeholder="Enter product name..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <div id="product_suggestions" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
            <p class="mt-1 text-sm text-gray-500">Start typing to see suggestions from previous purchases</p>
        </div>

        <!-- Vendor Selection -->
        <div>
            <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-2">
                Select Existing Vendor (Optional)
            </label>
            <select
                id="vendor_id"
                name="vendor_id"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                onchange="toggleVendorFields(this.value)"
            >
                <option value="">-- Or enter new vendor below --</option>
                <?php foreach ($vendors as $vendor): ?>
                <option value="<?php echo $vendor['id']; ?>" data-link="<?php echo e($vendor['vendor_link']); ?>">
                    <?php echo e($vendor['vendor_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- New Vendor Name -->
        <div id="new_vendor_fields">
            <label for="vendor_name" class="block text-sm font-medium text-gray-700 mb-2">
                Vendor Name
            </label>
            <input
                type="text"
                id="vendor_name"
                name="vendor_name"
                placeholder="Enter vendor name..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>

        <!-- Vendor Link -->
        <div>
            <label for="vendor_link" class="block text-sm font-medium text-gray-700 mb-2">
                Product/Vendor Link
            </label>
            <input
                type="url"
                id="vendor_link"
                name="vendor_link"
                placeholder="https://example.com/product"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
            <p class="mt-1 text-sm text-gray-500">Paste the link to the product page</p>
        </div>

        <!-- Quantity and Price Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Quantity -->
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                    Order Quantity <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    id="quantity"
                    name="quantity"
                    required
                    min="1"
                    value="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            <!-- Price -->
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                    Price per Unit (Optional)
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
            </div>
        </div>

        <!-- Shipping Speed -->
        <div>
            <label for="shipping_speed" class="block text-sm font-medium text-gray-700 mb-2">
                Shipping Speed
            </label>
            <select
                id="shipping_speed"
                name="shipping_speed"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Select shipping speed</option>
                <option value="Standard (5-7 days)">Standard (5-7 days)</option>
                <option value="Express (2-3 days)">Express (2-3 days)</option>
                <option value="Overnight">Overnight</option>
                <option value="No Preference">No Preference</option>
            </select>
        </div>

        <!-- Purchase Reason -->
        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                Purchase Request Reason <span class="text-red-500">*</span>
            </label>
            <textarea
                id="reason"
                name="reason"
                required
                rows="4"
                placeholder="Explain why this purchase is needed..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
        </div>

        <!-- Photo Upload -->
        <div>
            <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">
                Photo/Document (Optional)
            </label>
            <input
                type="file"
                id="photo"
                name="photo"
                accept="image/*,.pdf"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            >
            <p class="mt-1 text-sm text-gray-500">Accepted: JPG, PNG, GIF, PDF (Max 5MB)</p>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-6 border-t">
            <a href="/purchase-system/dashboard.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Submit Request
            </button>
        </div>
    </form>
</div>

<script>
// Toggle vendor fields based on selection
function toggleVendorFields(vendorId) {
    const newVendorFields = document.getElementById('new_vendor_fields');
    const vendorNameInput = document.getElementById('vendor_name');
    const vendorLinkInput = document.getElementById('vendor_link');

    if (vendorId) {
        // Existing vendor selected
        newVendorFields.style.display = 'none';
        vendorNameInput.required = false;

        // Get selected vendor's link
        const select = document.getElementById('vendor_id');
        const selectedOption = select.options[select.selectedIndex];
        const vendorLink = selectedOption.getAttribute('data-link');
        if (vendorLink) {
            vendorLinkInput.value = vendorLink;
        }
    } else {
        // New vendor
        newVendorFields.style.display = 'block';
        vendorNameInput.required = false; // Optional
        vendorLinkInput.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleVendorFields('');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
