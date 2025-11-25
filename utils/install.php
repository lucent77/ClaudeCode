<?php
/**
 * Magic Rx Scanner - Installation Script
 *
 * Checks system requirements and sets up the application
 *
 * Usage: php utils/install.php
 */

echo "Magic Rx Scanner - Installation Check\n";
echo "======================================\n\n";

$errors = [];
$warnings = [];

// Check PHP version
echo "1. Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo "   ✓ PHP " . PHP_VERSION . " (OK)\n";
} else {
    $errors[] = "PHP 8.0 or higher required. Current: " . PHP_VERSION;
    echo "   ✗ PHP " . PHP_VERSION . " (FAILED)\n";
}

// Check required extensions
echo "\n2. Checking PHP extensions...\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'curl', 'openssl', 'json', 'fileinfo'];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ {$ext}\n";
    } else {
        $errors[] = "Required extension '{$ext}' is not loaded";
        echo "   ✗ {$ext} (MISSING)\n";
    }
}

// Check GD or Imagick
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    $errors[] = "Either GD or Imagick extension is required for image processing";
}

// Check directory permissions
echo "\n3. Checking directory permissions...\n";
$directories = [
    __DIR__ . '/../uploads/documents',
    __DIR__ . '/../uploads/templates',
    __DIR__ . '/../uploads/processed',
    __DIR__ . '/../logs',
    __DIR__ . '/../credentials'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    if (is_writable($dir)) {
        echo "   ✓ " . basename(dirname($dir)) . "/" . basename($dir) . " (writable)\n";
    } else {
        $errors[] = "Directory '{$dir}' is not writable";
        echo "   ✗ " . basename(dirname($dir)) . "/" . basename($dir) . " (NOT writable)\n";
    }
}

// Check configuration file
echo "\n4. Checking configuration...\n";
$configFile = __DIR__ . '/../includes/config.php';

if (file_exists($configFile)) {
    echo "   ✓ config.php exists\n";

    // Check if config has been updated
    require_once $configFile;

    if (DB_USER === 'your_db_username' || DB_PASS === 'your_db_password') {
        $warnings[] = "Database credentials in config.php need to be updated";
        echo "   ⚠ Database credentials not configured\n";
    } else {
        echo "   ✓ Database credentials configured\n";
    }

    if (GOOGLE_PROJECT_ID === 'your-project-id' || GOOGLE_PROCESSOR_ID === 'your-processor-id') {
        $warnings[] = "Google Cloud credentials in config.php need to be updated";
        echo "   ⚠ Google Cloud settings not configured\n";
    } else {
        echo "   ✓ Google Cloud settings configured\n";
    }
} else {
    $errors[] = "Configuration file not found: {$configFile}";
    echo "   ✗ config.php not found\n";
}

// Check Google Service Account
echo "\n5. Checking Google Cloud credentials...\n";
$credentialsFile = __DIR__ . '/../credentials/google-service-account.json';

if (file_exists($credentialsFile)) {
    echo "   ✓ Service account JSON file exists\n";

    // Validate JSON
    $jsonContent = file_get_contents($credentialsFile);
    $credentials = json_decode($jsonContent, true);

    if ($credentials && isset($credentials['project_id']) && isset($credentials['private_key'])) {
        echo "   ✓ Service account JSON is valid\n";
    } else {
        $errors[] = "Service account JSON file is invalid";
        echo "   ✗ Invalid JSON format\n";
    }
} else {
    $warnings[] = "Google Service Account JSON file not found at: {$credentialsFile}";
    echo "   ⚠ google-service-account.json not found\n";
}

// Check database connection
echo "\n6. Checking database connection...\n";
if (file_exists($configFile) && DB_USER !== 'your_db_username') {
    try {
        require_once __DIR__ . '/../includes/database.php';
        $db = Database::getInstance();
        echo "   ✓ Database connection successful\n";

        // Check if tables exist
        $tables = $db->select("SHOW TABLES LIKE 'prescriptions'");
        if (count($tables) > 0) {
            echo "   ✓ Database tables exist\n";
        } else {
            $warnings[] = "Database tables not found. Run database/schema.sql";
            echo "   ⚠ Database tables not found\n";
        }
    } catch (Exception $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
        echo "   ✗ Database connection failed\n";
    }
} else {
    echo "   ⚠ Skipping (config not set up)\n";
}

// Check Apache modules
echo "\n7. Checking Apache configuration...\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $requiredModules = ['mod_rewrite', 'mod_headers'];

    foreach ($requiredModules as $mod) {
        if (in_array($mod, $modules)) {
            echo "   ✓ {$mod}\n";
        } else {
            $warnings[] = "Apache module '{$mod}' is recommended but not detected";
            echo "   ⚠ {$mod} (not detected)\n";
        }
    }
} else {
    echo "   ⚠ Cannot check Apache modules (function not available)\n";
}

// Summary
echo "\n======================================\n";
echo "INSTALLATION CHECK SUMMARY\n";
echo "======================================\n\n";

if (count($errors) === 0) {
    echo "✓ No critical errors found!\n";
} else {
    echo "✗ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

if (count($warnings) > 0) {
    echo "\n⚠ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "\n🎉 Installation check passed! Your system is ready.\n";
} elseif (count($errors) === 0) {
    echo "\n✓ System is functional but some warnings need attention.\n";
} else {
    echo "\n✗ Please fix the errors above before proceeding.\n";
    exit(1);
}

echo "\nNext steps:\n";
echo "1. Update includes/config.php with your settings\n";
echo "2. Place your Google Service Account JSON in credentials/\n";
echo "3. Import database/schema.sql to your MySQL database\n";
echo "4. Access the application via your web browser\n\n";
