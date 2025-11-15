<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/slack_service.php';

requireLogin();

$taskId = intval($_GET['id'] ?? 0);

if (!$taskId) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: index.php');
    exit;
}

// Parse file IDs
$slackService = new SlackService();
$photos = $slackService->parseFileIds($task['photos']);
$stls = $slackService->parseFileIds($task['stls']);
$preOpCbct = $slackService->parseFileIds($task['pre_op_cbct']);
$postOpCbct = $slackService->parseFileIds($task['post_op_cbct']);
$preOpScans = $slackService->parseFileIds($task['pre_op_scans']);
$postOpScans = $slackService->parseFileIds($task['post_op_scans']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($task['patient_name']); ?> - <?php echo APP_NAME; ?></title>
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
                        <h1 class="text-xl font-bold text-white">Task Details</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-th-list mr-2"></i>All Tasks
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
                <!-- Patient Header -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($task['patient_name']); ?></h2>
                            <p class="text-sm text-gray-500 mt-1">Task ID: #<?php echo $task['id']; ?></p>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($task['completed']): ?>
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-2"></i>Completed
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-2"></i>Pending
                                </span>
                            <?php endif; ?>
                            <?php if ($task['ready_for_surgery']): ?>
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-check mr-2"></i>Ready for Surgery
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Task Information -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Surgery Information -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-calendar-alt text-indigo-600 mr-2"></i>Surgery Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Arch</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($task['arch'] ?? '-'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Existing Implants</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php if ($task['existing_implants'] === 'Yes'): ?>
                                            <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Yes</span>
                                        <?php else: ?>
                                            <span class="text-gray-400"><i class="fas fa-times-circle mr-1"></i>No</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Pre-op Scan Date</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo formatDate($task['pre_op_scan_date']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Surgery Date</label>
                                    <p class="mt-1 text-sm text-gray-900 font-medium"><?php echo formatDate($task['surgery_date']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Surgery Time</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($task['surgery_time'] ?: '-'); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Due Date</label>
                                    <p class="mt-1 text-sm text-gray-900 font-medium"><?php echo formatDate($task['due_date']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <?php if (!empty($task['notes'])): ?>
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    <i class="fas fa-sticky-note text-indigo-600 mr-2"></i>Notes
                                </h3>
                                <div class="prose max-w-none">
                                    <pre class="whitespace-pre-wrap text-sm text-gray-700 font-sans"><?php echo htmlspecialchars($task['notes']); ?></pre>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Files Section -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <i class="fas fa-folder text-indigo-600 mr-2"></i>Files
                            </h3>

                            <div class="space-y-4">
                                <!-- Photos -->
                                <?php if (!empty($photos)): ?>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-camera text-purple-600 mr-2"></i>Photos (<?php echo count($photos); ?>)
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($photos as $fileId): ?>
                                                <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-purple-300 rounded-md text-sm text-purple-700 bg-purple-50 hover:bg-purple-100">
                                                    <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($fileId); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- STL Files -->
                                <?php if (!empty($stls)): ?>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-cube text-blue-600 mr-2"></i>STL Files (<?php echo count($stls); ?>)
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($stls as $fileId): ?>
                                                <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm text-blue-700 bg-blue-50 hover:bg-blue-100">
                                                    <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($fileId); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Pre-op CBCT -->
                                <?php if (!empty($preOpCbct)): ?>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-x-ray text-green-600 mr-2"></i>Pre-op CBCT (<?php echo count($preOpCbct); ?>)
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($preOpCbct as $fileId): ?>
                                                <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-green-300 rounded-md text-sm text-green-700 bg-green-50 hover:bg-green-100">
                                                    <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($fileId); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Post-op CBCT -->
                                <?php if (!empty($postOpCbct)): ?>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-x-ray text-orange-600 mr-2"></i>Post-op CBCT (<?php echo count($postOpCbct); ?>)
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($postOpCbct as $fileId): ?>
                                                <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-orange-300 rounded-md text-sm text-orange-700 bg-orange-50 hover:bg-orange-100">
                                                    <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($fileId); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Pre-op Scans -->
                                <?php if (!empty($preOpScans)): ?>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-file-medical text-indigo-600 mr-2"></i>Pre-op Scans (<?php echo count($preOpScans); ?>)
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($preOpScans as $fileId): ?>
                                                <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-indigo-300 rounded-md text-sm text-indigo-700 bg-indigo-50 hover:bg-indigo-100">
                                                    <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($fileId); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Post-op Scans -->
                                <?php if (!empty($postOpScans)): ?>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-file-medical text-pink-600 mr-2"></i>Post-op Scans (<?php echo count($postOpScans); ?>)
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($postOpScans as $fileId): ?>
                                                <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                                                   class="inline-flex items-center px-3 py-2 border border-pink-300 rounded-md text-sm text-pink-700 bg-pink-50 hover:bg-pink-100">
                                                    <i class="fas fa-external-link-alt mr-2"></i><?php echo htmlspecialchars($fileId); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($photos) && empty($stls) && empty($preOpCbct) && empty($postOpCbct) && empty($preOpScans) && empty($postOpScans)): ?>
                                    <p class="text-sm text-gray-500 italic">No files available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Info -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Info</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($task['created_at'], 'M d, Y g:i A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($task['updated_at'], 'M d, Y g:i A'); ?></dd>
                                </div>
                                <?php if ($task['slack_created_by']): ?>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase">Created By (Slack)</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($task['slack_created_by']); ?></dd>
                                    </div>
                                <?php endif; ?>
                                <?php if ($task['slack_last_edited_by']): ?>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase">Last Edited By (Slack)</dt>
                                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($task['slack_last_edited_by']); ?></dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <!-- Slack Link -->
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                            <h3 class="text-sm font-medium text-indigo-900 mb-3">View in Slack</h3>
                            <a href="<?php echo SLACK_CANVAS_URL; ?>" target="_blank"
                               class="block w-full text-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                                <i class="fab fa-slack mr-2"></i>Open Slack Canvas
                            </a>
                        </div>

                        <!-- Actions (Admin Only) -->
                        <?php if (isAdmin()): ?>
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                                <div class="space-y-2">
                                    <button onclick="toggleTaskStatus(<?php echo $task['id']; ?>, <?php echo $task['completed'] ? 'false' : 'true'; ?>)"
                                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-<?php echo $task['completed'] ? 'undo' : 'check'; ?> mr-2"></i>
                                        Mark as <?php echo $task['completed'] ? 'Pending' : 'Completed'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleTaskStatus(taskId, completed) {
            if (!confirm('Are you sure you want to change the task status?')) {
                return;
            }

            fetch('../api/tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_status',
                    task_id: taskId,
                    completed: completed
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating task status');
                console.error(error);
            });
        }
    </script>
</body>
</html>
