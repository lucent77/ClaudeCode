<?php
/**
 * Magic Rx Scanner - Cleanup Utility
 *
 * Removes old uploaded files and processed images
 * Run this script via cron job or manually
 *
 * Usage:
 *   php utils/cleanup.php [days]
 *
 * Example:
 *   php utils/cleanup.php 30  # Delete files older than 30 days
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/file_handler.php';

// Get days parameter (default: 30)
$days = isset($argv[1]) ? (int)$argv[1] : 30;

if ($days < 1) {
    echo "Error: Days must be at least 1\n";
    exit(1);
}

echo "Magic Rx Scanner - File Cleanup Utility\n";
echo "========================================\n";
echo "Cleaning files older than {$days} days...\n\n";

$fileHandler = new FileHandler();

// Clean documents
echo "Cleaning documents directory...\n";
$deletedDocs = $fileHandler->cleanupOldFiles(UPLOAD_DIR, $days);
echo "  Deleted {$deletedDocs} document(s)\n";

// Clean templates
echo "Cleaning templates directory...\n";
$deletedTemplates = $fileHandler->cleanupOldFiles(TEMPLATE_DIR, $days);
echo "  Deleted {$deletedTemplates} template(s)\n";

// Clean processed images
echo "Cleaning processed directory...\n";
$deletedProcessed = $fileHandler->cleanupOldFiles(PROCESSED_DIR, $days);
echo "  Deleted {$deletedProcessed} processed image(s)\n";

$totalDeleted = $deletedDocs + $deletedTemplates + $deletedProcessed;

echo "\n========================================\n";
echo "Total files deleted: {$totalDeleted}\n";
echo "Cleanup completed successfully!\n";
