<?php
/**
 * CREODENT Work Management System
 * Installation Wizard
 *
 * Run this file once to set up the system
 * Delete this file after installation for security
 */

// Prevent running if already installed
if (file_exists(__DIR__ . '/config/.installed')) {
    die('System is already installed. Delete config/.installed to reinstall.');
}

define('BASE_PATH', __DIR__);

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Handle installation steps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Step 2: Run database installation
        try {
            $config = require BASE_PATH . '/config/config.php';

            // Connect to database
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['database']['host'],
                $config['database']['port'] ?? 3306,
                $config['database']['database']
            );

            $pdo = new PDO(
                $dsn,
                $config['database']['username'],
                $config['database']['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Read and execute schema
            $schema = file_get_contents(BASE_PATH . '/database/schema.sql');

            // Split by semicolons (handle multi-statement execution)
            $statements = array_filter(
                array_map('trim', explode(';', $schema)),
                fn($stmt) => !empty($stmt) && !str_starts_with(trim($stmt), '--')
            );

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    $pdo->exec($statement);
                }
            }

            // Create .installed file
            file_put_contents(BASE_PATH . '/config/.installed', date('Y-m-d H:i:s'));

            $success[] = 'Database tables created successfully!';
            $success[] = 'Default admin user created: username = admin, password = Admin@123';
            $success[] = '⚠️ IMPORTANT: Change the admin password immediately after first login!';

            $step = 3; // Move to completion step

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Installation error: ' . $e->getMessage();
        }
    }
}

// Check requirements
function checkRequirements() {
    $requirements = [
        'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'XML Extension' => extension_loaded('xml'),
        'mbstring Extension' => extension_loaded('mbstring'),
        'Composer Autoloader' => file_exists(BASE_PATH . '/vendor/autoload.php'),
        'Config File Exists' => file_exists(BASE_PATH . '/config/config.php'),
        'Storage Directory Writable' => is_writable(BASE_PATH . '/storage'),
        'Logs Directory Writable' => is_writable(BASE_PATH . '/storage/logs'),
    ];

    return $requirements;
}

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, $requirements, true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREODENT Work Manager - Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #4caf50;
            color: white;
        }
        .requirements {
            list-style: none;
        }
        .requirements li {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.success {
            background: #e8f5e9;
            color: #4caf50;
        }
        .badge.error {
            background: #ffebee;
            color: #f44336;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        .alert.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        .alert.warning {
            background: #fff3e0;
            color: #e65100;
            border-left: 4px solid #ff9800;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .btn-success {
            background: #4caf50;
        }
        .btn-success:hover {
            background: #45a049;
        }
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin-bottom: 10px;
            color: #667eea;
        }
        .info-box p {
            margin: 5px 0;
            color: #666;
        }
        .credentials {
            background: #fff9e6;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .credentials h3 {
            color: #f57c00;
            margin-bottom: 15px;
        }
        .credentials code {
            background: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦷 CREODENT Work Manager</h1>
            <p>Installation Wizard</p>
        </div>

        <div class="content">
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">1</div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">2</div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
            </div>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <?php foreach ($success as $msg): ?>
                    <div class="alert success"><?= htmlspecialchars($msg) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: Requirements Check -->
                <h2>Step 1: System Requirements</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Checking if your server meets the minimum requirements...
                </p>

                <ul class="requirements">
                    <?php foreach ($requirements as $requirement => $met): ?>
                        <li>
                            <span><?= htmlspecialchars($requirement) ?></span>
                            <span class="badge <?= $met ? 'success' : 'error' ?>">
                                <?= $met ? '✓ Passed' : '✗ Failed' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!$allRequirementsMet): ?>
                    <div class="alert error" style="margin-top: 20px;">
                        Some requirements are not met. Please fix them before continuing.
                    </div>
                <?php endif; ?>

                <div class="actions">
                    <a href="?step=2" class="btn <?= $allRequirementsMet ? '' : 'disabled' ?>"
                       <?= $allRequirementsMet ? '' : 'onclick="return false;"' ?>>
                        Continue to Installation →
                    </a>
                </div>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Database Installation -->
                <h2>Step 2: Database Setup</h2>

                <div class="info-box">
                    <h3>What will be installed:</h3>
                    <p>✓ 16 database tables with proper relationships</p>
                    <p>✓ Indexes for optimal performance</p>
                    <p>✓ Default departments (Solidex, 3D Print, CoCr/ZEST)</p>
                    <p>✓ Default administrator account</p>
                    <p>✓ Initial system settings</p>
                </div>

                <div class="alert warning">
                    ⚠️ <strong>Warning:</strong> This will create all database tables.
                    If tables already exist, they will be dropped and recreated!
                </div>

                <form method="POST" action="?step=2">
                    <div class="actions">
                        <button type="submit" class="btn">Start Installation</button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- Step 3: Completion -->
                <h2>🎉 Installation Complete!</h2>

                <div class="alert success">
                    CREODENT Work Management System has been successfully installed.
                </div>

                <div class="credentials">
                    <h3>🔐 Default Administrator Credentials</h3>
                    <p><strong>Username:</strong> <code>admin</code></p>
                    <p><strong>Password:</strong> <code>Admin@123</code></p>
                    <p style="margin-top: 15px; color: #d32f2f;">
                        <strong>⚠️ IMPORTANT:</strong> Change this password immediately after first login!
                    </p>
                </div>

                <div class="info-box">
                    <h3>Next Steps:</h3>
                    <p>1. <strong>Delete install.php</strong> for security</p>
                    <p>2. Configure Evolution Portal credentials in <code>config/config.php</code></p>
                    <p>3. Set up cron job for automated imports</p>
                    <p>4. Log in and change the admin password</p>
                </div>

                <div class="actions">
                    <a href="/login" class="btn btn-success">Go to Login →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
