<?php
/**
 * CREODENT Work Manager Installation Script
 *
 * Run this script once to set up the database and initial configuration
 * Access via browser: http://yourdomain.com/install.php
 *
 * IMPORTANT: Delete this file after installation for security
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if already installed
$lockFile = __DIR__ . '/.installed';
if (file_exists($lockFile)) {
    die('<h1>Already Installed</h1><p>The system is already installed. Delete .installed file to reinstall.</p>');
}

require_once __DIR__ . '/vendor/autoload.php';

$errors = [];
$success = [];
$step = $_POST['step'] ?? 'welcome';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREODENT Work Manager - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">CREODENT Work Manager</h1>
                <p class="text-gray-600">Installation Wizard</p>
            </div>

            <!-- Installation Card -->
            <div class="bg-white shadow-xl rounded-lg p-8">

                <?php if ($step === 'welcome'): ?>
                    <!-- Welcome Step -->
                    <h2 class="text-2xl font-bold mb-4">Welcome</h2>
                    <p class="text-gray-600 mb-6">
                        This wizard will guide you through the installation process.
                        Make sure you have:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 mb-6 space-y-2">
                        <li>MySQL database created</li>
                        <li>Database credentials ready</li>
                        <li>Write permissions for storage directories</li>
                        <li>Evolution Web Portal credentials (optional, can be added later)</li>
                    </ul>

                    <form method="POST" action="install.php">
                        <input type="hidden" name="step" value="check">
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                            Start Installation
                        </button>
                    </form>

                <?php elseif ($step === 'check'): ?>
                    <!-- Requirements Check -->
                    <h2 class="text-2xl font-bold mb-4">System Requirements Check</h2>

                    <?php
                    $checks = [
                        'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
                        'PDO Extension' => extension_loaded('pdo'),
                        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
                        'cURL Extension' => extension_loaded('curl'),
                        'JSON Extension' => extension_loaded('json'),
                        'Storage Directory Writable' => is_writable(__DIR__ . '/storage') || mkdir(__DIR__ . '/storage', 0755, true),
                        'Logs Directory Writable' => is_writable(__DIR__ . '/storage/logs') || mkdir(__DIR__ . '/storage/logs', 0755, true),
                    ];

                    $allPassed = true;
                    ?>

                    <div class="space-y-3 mb-6">
                        <?php foreach ($checks as $check => $passed): ?>
                            <?php $allPassed = $allPassed && $passed; ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <span class="text-gray-700"><?= $check ?></span>
                                <span class="<?= $passed ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                                    <?= $passed ? '✓ Passed' : '✗ Failed' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($allPassed): ?>
                        <form method="POST" action="install.php">
                            <input type="hidden" name="step" value="install">
                            <button type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">
                                Continue to Installation
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            Please fix the failed requirements before continuing.
                        </div>
                    <?php endif; ?>

                <?php elseif ($step === 'install'): ?>
                    <!-- Installation Step -->
                    <h2 class="text-2xl font-bold mb-4">Installing...</h2>

                    <?php
                    try {
                        // Create necessary directories
                        $dirs = [
                            __DIR__ . '/storage',
                            __DIR__ . '/storage/logs',
                            __DIR__ . '/storage/cache',
                            __DIR__ . '/storage/uploads',
                        ];

                        foreach ($dirs as $dir) {
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                                $success[] = "Created directory: {$dir}";
                            }
                        }

                        // Copy config file if not exists
                        if (!file_exists(__DIR__ . '/config/config.php')) {
                            copy(__DIR__ . '/config/config.example.php', __DIR__ . '/config/config.php');
                            $success[] = "Created config.php from example";
                        }

                        // Connect to database
                        $config = require __DIR__ . '/config/config.php';
                        $db = \App\Core\Database::getInstance();

                        // Run schema
                        $schema = file_get_contents(__DIR__ . '/database/schema.sql');

                        // Split into individual statements
                        $statements = array_filter(
                            array_map('trim', explode(';', $schema)),
                            fn($s) => !empty($s) && stripos($s, '--') !== 0
                        );

                        foreach ($statements as $statement) {
                            if (empty(trim($statement))) continue;
                            try {
                                $db->getPDO()->exec($statement);
                            } catch (Exception $e) {
                                // Ignore errors for DROP statements
                                if (stripos($statement, 'DROP') === false) {
                                    throw $e;
                                }
                            }
                        }

                        $success[] = "Database schema installed successfully";

                        // Create lock file
                        file_put_contents($lockFile, date('Y-m-d H:i:s'));
                        $success[] = "Installation completed successfully!";

                        // Show success message
                        ?>
                        <div class="space-y-3 mb-6">
                            <?php foreach ($success as $msg): ?>
                                <div class="flex items-start p-3 bg-green-50 rounded">
                                    <span class="text-green-600 mr-2">✓</span>
                                    <span class="text-gray-700"><?= htmlspecialchars($msg) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                            <strong>Default Admin Credentials:</strong><br>
                            Username: <code>admin</code><br>
                            Password: <code>Admin@123</code><br>
                            <strong class="text-red-600">⚠️ Please change this password immediately after logging in!</strong>
                        </div>

                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                            <strong>Important:</strong> Delete install.php for security!
                        </div>

                        <div class="space-y-3">
                            <a href="/login"
                               class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-center">
                                Go to Login
                            </a>
                            <button onclick="if(confirm('Are you sure?')) window.location.href='?delete=1'"
                                    class="block w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg text-center">
                                Delete Install Script
                            </button>
                        </div>

                        <?php
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                        ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <strong>Installation Failed:</strong><br>
                            <?= htmlspecialchars($e->getMessage()) ?>
                        </div>

                        <form method="POST" action="install.php">
                            <input type="hidden" name="step" value="welcome">
                            <button type="submit"
                                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-4 rounded-lg">
                                Start Over
                            </button>
                        </form>
                        <?php
                    }
                    ?>

                <?php endif; ?>

            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-sm text-gray-600">
                <p>&copy; 2025 CREODENT. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Handle delete request
if (isset($_GET['delete']) && $_GET['delete'] === '1' && file_exists($lockFile)) {
    unlink(__FILE__);
    header('Location: /login');
    exit;
}
?>
