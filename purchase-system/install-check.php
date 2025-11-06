<?php
/**
 * Installation Checker
 * Purchase Management System
 *
 * Run this file after uploading to check if everything is configured correctly
 */

$checks = [];

// Check PHP Version
$phpVersion = phpversion();
$checks['PHP Version'] = [
    'required' => '8.0',
    'current' => $phpVersion,
    'status' => version_compare($phpVersion, '8.0', '>=')
];

// Check required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'session', 'json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    $checks["Extension: $ext"] = [
        'required' => 'Enabled',
        'current' => extension_loaded($ext) ? 'Enabled' : 'Disabled',
        'status' => extension_loaded($ext)
    ];
}

// Check file permissions
$uploadDir = __DIR__ . '/uploads/';
$checks['Uploads Directory Writable'] = [
    'required' => 'Yes',
    'current' => is_writable($uploadDir) ? 'Yes' : 'No',
    'status' => is_writable($uploadDir)
];

// Check config file exists
$configFile = __DIR__ . '/config/config.php';
$checks['Config File Exists'] = [
    'required' => 'Yes',
    'current' => file_exists($configFile) ? 'Yes' : 'No',
    'status' => file_exists($configFile)
];

// Try database connection (if config exists)
if (file_exists($configFile)) {
    require_once $configFile;
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $checks['Database Connection'] = [
            'required' => 'Connected',
            'current' => 'Connected',
            'status' => true
        ];

        // Check if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = ['users', 'vendors', 'products', 'purchase_requests', 'notifications'];
        $missingTables = array_diff($requiredTables, $tables);

        $checks['Database Tables'] = [
            'required' => implode(', ', $requiredTables),
            'current' => empty($missingTables) ? 'All present' : 'Missing: ' . implode(', ', $missingTables),
            'status' => empty($missingTables)
        ];
    } catch (PDOException $e) {
        $checks['Database Connection'] = [
            'required' => 'Connected',
            'current' => 'Failed: ' . $e->getMessage(),
            'status' => false
        ];
    }
}

// Calculate overall status
$allPassed = true;
foreach ($checks as $check) {
    if (!$check['status']) {
        $allPassed = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Check - Purchase Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📦 Installation Check</h1>
            <p class="text-gray-600 mb-6">Purchase Management System - Environment Verification</p>

            <?php if ($allPassed): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                ✅ <strong>All checks passed!</strong> Your system is ready to use.
                <br>
                <a href="/purchase-system/login.php" class="underline">Click here to login</a>
            </div>
            <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                ❌ <strong>Some checks failed.</strong> Please resolve the issues below.
            </div>
            <?php endif; ?>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Check</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($checks as $name => $check): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $name; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $check['required']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $check['current']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($check['status']): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Pass</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">✗ Fail</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="mt-8 p-4 bg-blue-50 rounded">
                <h3 class="font-semibold text-blue-900 mb-2">📚 Next Steps:</h3>
                <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1">
                    <li>If all checks pass, delete this file (install-check.php) for security</li>
                    <li>Login with default credentials (see README.md)</li>
                    <li>Change admin password immediately</li>
                    <li>Create department users</li>
                    <li>Start managing purchases!</li>
                </ol>
            </div>

            <div class="mt-6 text-center">
                <a href="/purchase-system/" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Go to Application
                </a>
            </div>
        </div>
    </div>
</body>
</html>
