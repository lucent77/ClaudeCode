<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/slack_service.php';

requireAdmin();

$message = '';
$messageType = '';

// Handle CSV file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $csvContent = file_get_contents($file['tmp_name']);

        if ($csvContent) {
            $slackService = new SlackService();
            $result = $slackService->syncTasksFromCSV($csvContent);

            if ($result['success']) {
                $message = "Successfully synced {$result['synced_count']} tasks from CSV file.";
                if (!empty($result['errors'])) {
                    $message .= " Errors: " . implode(', ', $result['errors']);
                }
                $messageType = 'success';

                logActivity($_SESSION['user_id'], 'sync_tasks', null, null,
                    "Synced {$result['synced_count']} tasks from CSV");
            } else {
                $message = "Sync failed: " . $result['message'];
                $messageType = 'error';
            }
        } else {
            $message = "Failed to read CSV file";
            $messageType = 'error';
        }
    } else {
        $message = "File upload error: " . $file['error'];
        $messageType = 'error';
    }
}

// Get recent sync logs
$db = getDB();
$logsStmt = $db->query("
    SELECT * FROM sync_logs
    ORDER BY started_at DESC
    LIMIT 10
");
$logs = $logsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-indigo-600">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <a href="index.php" class="text-white hover:text-indigo-200">
                            <i class="fas fa-arrow-left mr-3"></i>
                        </a>
                        <h1 class="text-xl font-bold text-white">Sync from Slack</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-th-list mr-2"></i>Dashboard
                        </a>
                        <a href="logout.php" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <?php if ($message): ?>
                    <div class="mb-6 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle text-green-400' : 'exclamation-circle text-red-400'; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- CSV Upload -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-file-csv text-indigo-600 mr-2"></i>Upload CSV from Slack List
                        </h2>
                        <div class="mb-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <h3 class="text-sm font-medium text-blue-900 mb-2">How to export from Slack:</h3>
                                <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                                    <li>Open your Slack List Canvas</li>
                                    <li>Click the "..." menu in the top right</li>
                                    <li>Select "Export to CSV"</li>
                                    <li>Save the CSV file to your computer</li>
                                    <li>Upload it here to sync</li>
                                </ol>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select CSV File
                                </label>
                                <input type="file" name="csv_file" accept=".csv" required
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>

                            <button type="submit"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-upload mr-2"></i>Upload and Sync
                            </button>
                        </form>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Slack Canvas Info</h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Workspace ID:</dt>
                                    <dd class="text-gray-900 font-mono"><?php echo SLACK_WORKSPACE_ID; ?></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Canvas ID:</dt>
                                    <dd class="text-gray-900 font-mono"><?php echo SLACK_CANVAS_ID; ?></dd>
                                </div>
                            </dl>
                            <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                               class="mt-3 block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fab fa-slack mr-2"></i>Open in Slack
                            </a>
                        </div>
                    </div>

                    <!-- Sync History -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-history text-indigo-600 mr-2"></i>Recent Sync History
                        </h2>

                        <?php if (empty($logs)): ?>
                            <p class="text-sm text-gray-500 italic">No sync history available</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($logs as $log): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($log['sync_type']); ?>
                                            </span>
                                            <?php if ($log['status'] === 'success'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Success
                                                </span>
                                            <?php elseif ($log['status'] === 'failed'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                    <i class="fas fa-times mr-1"></i>Failed
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-exclamation mr-1"></i>Partial
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-gray-500 space-y-1">
                                            <p><i class="fas fa-tasks mr-1"></i>Tasks synced: <?php echo $log['tasks_synced']; ?></p>
                                            <p><i class="fas fa-clock mr-1"></i><?php echo formatDate($log['started_at'], 'M d, Y g:i A'); ?></p>
                                            <?php if ($log['error_message']): ?>
                                                <p class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i><?php echo htmlspecialchars($log['error_message']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-indigo-600 mr-2"></i>Sync Instructions
                    </h2>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        <p><strong>Important Notes:</strong></p>
                        <ul class="list-disc list-inside space-y-2 mt-2">
                            <li>The CSV file must follow the Slack List export format</li>
                            <li>Existing tasks will be updated based on patient name and surgery date</li>
                            <li>New tasks will be created automatically</li>
                            <li>File attachments (photos, STLs, CBCT) are referenced by their Slack file IDs</li>
                            <li>To access the files, you'll need to view them through Slack</li>
                            <li>Sync operations are logged for audit purposes</li>
                            <li>This operation requires admin privileges</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
