#!/usr/bin/env php
<?php

/**
 * Cron Job: Slack Canvas Sync
 *
 * This script should be run periodically via cron to sync cases from Slack Canvas
 *
 * Add to crontab:
 * */30 * * * * php /path/to/project/cron/sync.php >> /path/to/project/storage/logs/cron.log 2>&1
 */

// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check if sync is enabled
if (!config('services.sync.enabled')) {
    echo "[" . date('Y-m-d H:i:s') . "] Sync is disabled in configuration\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Slack Canvas sync...\n";

try {
    $syncService = app(\App\Services\SlackSyncService::class);
    $result = $syncService->syncFromCanvas('auto', null);

    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Sync completed successfully\n";
        echo "  - Cases synced: " . $result['cases_synced'] . "\n";
        echo "  - Attachments synced: " . $result['attachments_synced'] . "\n";
        echo "  - Errors: " . $result['errors'] . "\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Sync failed: " . $result['error'] . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Sync error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
