<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['full_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swissturn 생산 관리 시스템</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Noto Sans KR', sans-serif;
        }

        .status-running { background-color: #10b981; }
        .status-idle { background-color: #6b7280; }
        .status-maintenance { background-color: #f59e0b; }
        .status-error { background-color: #ef4444; }

        .tool-status-new { background-color: #3b82f6; }
        .tool-status-in_use { background-color: #10b981; }
        .tool-status-used { background-color: #6b7280; }
        .tool-status-maintenance { background-color: #f59e0b; }
        .tool-status-retired { background-color: #ef4444; }

        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>

    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php if ($isLoggedIn): ?>
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    <div>
                        <h1 class="text-xl font-bold">Swissturn 생산관리</h1>
                        <p class="text-xs text-blue-200">통합 생산 및 공구 관리 시스템</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex space-x-1">
                    <a href="dashboard.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'dashboard' ? 'bg-blue-700' : ''; ?>">
                        대시보드
                    </a>
                    <a href="equipment.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'equipment' ? 'bg-blue-700' : ''; ?>">
                        장비 관리
                    </a>
                    <a href="tools.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'tools' ? 'bg-blue-700' : ''; ?>">
                        공구 관리
                    </a>
                    <a href="tool_settings.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'tool_settings' ? 'bg-blue-700' : ''; ?>">
                        공구 세팅
                    </a>
                    <a href="production.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'production' ? 'bg-blue-700' : ''; ?>">
                        생산 기록
                    </a>
                    <?php if ($userRole === 'admin'): ?>
                    <a href="upload.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'upload' ? 'bg-blue-700' : ''; ?>">
                        데이터 업로드
                    </a>
                    <?php endif; ?>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($userName); ?></p>
                        <p class="text-xs text-blue-200"><?php echo $userRole === 'admin' ? '관리자' : '작업자'; ?></p>
                    </div>
                    <a href="logout.php" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 rounded-lg transition">
                        로그아웃
                    </a>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div class="md:hidden pb-4">
                <div class="flex flex-col space-y-2">
                    <a href="dashboard.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'dashboard' ? 'bg-blue-700' : ''; ?>">
                        대시보드
                    </a>
                    <a href="equipment.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'equipment' ? 'bg-blue-700' : ''; ?>">
                        장비 관리
                    </a>
                    <a href="tools.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'tools' ? 'bg-blue-700' : ''; ?>">
                        공구 관리
                    </a>
                    <a href="tool_settings.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'tool_settings' ? 'bg-blue-700' : ''; ?>">
                        공구 세팅
                    </a>
                    <a href="production.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'production' ? 'bg-blue-700' : ''; ?>">
                        생산 기록
                    </a>
                    <?php if ($userRole === 'admin'): ?>
                    <a href="upload.php" class="px-4 py-2 rounded-lg hover:bg-blue-700 transition <?php echo $currentPage === 'upload' ? 'bg-blue-700' : ''; ?>">
                        데이터 업로드
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6">
