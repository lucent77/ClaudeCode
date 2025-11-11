<?php
/**
 * Common Header
 * Included in all authenticated pages
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Production Management System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .nav-link {
            @apply px-3 py-2 rounded-md text-sm font-medium;
        }
        .nav-link:hover {
            @apply bg-blue-700;
        }
        .nav-link.active {
            @apply bg-blue-900;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .sticky-left {
            position: sticky;
            left: 0;
            z-index: 5;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-xl font-bold">PMS</h1>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                                Dashboard
                            </a>
                            <a href="equipment.php" class="nav-link <?php echo $current_page === 'equipment' ? 'active' : ''; ?>">
                                Equipment
                            </a>
                            <a href="tools.php" class="nav-link <?php echo $current_page === 'tools' ? 'active' : ''; ?>">
                                Tools
                            </a>
                            <a href="tool_settings.php" class="nav-link <?php echo $current_page === 'tool_settings' ? 'active' : ''; ?>">
                                Tool Settings
                            </a>
                            <a href="production.php" class="nav-link <?php echo $current_page === 'production' ? 'active' : ''; ?>">
                                Production Records
                            </a>
                            <a href="production_input.php" class="nav-link <?php echo $current_page === 'production_input' ? 'active' : ''; ?>">
                                Daily Input
                            </a>
                            <?php if ($user['role'] === 'admin'): ?>
                            <a href="upload.php" class="nav-link <?php echo $current_page === 'upload' ? 'active' : ''; ?>">
                                Upload Data
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <span class="text-sm mr-4">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                            <span class="text-blue-200">(<?php echo htmlspecialchars($user['role']); ?>)</span>
                        </span>
                        <a href="logout.php" class="bg-blue-700 hover:bg-blue-800 px-3 py-2 rounded-md text-sm font-medium">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="md:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="dashboard.php" class="nav-link block <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    Dashboard
                </a>
                <a href="equipment.php" class="nav-link block <?php echo $current_page === 'equipment' ? 'active' : ''; ?>">
                    Equipment
                </a>
                <a href="tools.php" class="nav-link block <?php echo $current_page === 'tools' ? 'active' : ''; ?>">
                    Tools
                </a>
                <a href="tool_settings.php" class="nav-link block <?php echo $current_page === 'tool_settings' ? 'active' : ''; ?>">
                    Tool Settings
                </a>
                <a href="production.php" class="nav-link block <?php echo $current_page === 'production' ? 'active' : ''; ?>">
                    Production Records
                </a>
                <a href="production_input.php" class="nav-link block <?php echo $current_page === 'production_input' ? 'active' : ''; ?>">
                    Daily Input
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="upload.php" class="nav-link block <?php echo $current_page === 'upload' ? 'active' : ''; ?>">
                    Upload Data
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
