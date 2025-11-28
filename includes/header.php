<?php
/**
 * Header Template
 * 공통 헤더
 */

require_once __DIR__ . '/session.php';

$currentUser = Session::getUser();
$flash = Session::getFlash();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="ko" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>LifeCanvas - 자기 관리</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['Pretendard', '-apple-system', 'BlinkMacSystemFont', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Noto Sans KR', sans-serif; }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Mandal-art grid animation */
        .mandal-cell {
            transition: all 0.3s ease;
        }
        .mandal-cell:hover {
            transform: scale(1.02);
            z-index: 10;
        }

        /* Progress ring animation */
        .progress-ring__circle {
            transition: stroke-dashoffset 0.5s ease;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        /* Streak flame animation */
        @keyframes flame {
            0%, 100% { transform: scale(1) rotate(-5deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        .flame-animate {
            animation: flame 0.5s ease-in-out infinite;
        }

        /* Badge shine effect */
        .badge-shine {
            position: relative;
            overflow: hidden;
        }
        .badge-shine::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.3) 50%,
                rgba(255,255,255,0) 100%
            );
            transform: rotate(30deg);
            animation: shine 3s infinite;
        }
        @keyframes shine {
            0% { transform: translateX(-100%) rotate(30deg); }
            100% { transform: translateX(100%) rotate(30deg); }
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <?php if ($currentUser): ?>
    <!-- Main Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700" x-data="{ mobileMenu: false, profileMenu: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo & Main Nav -->
                <div class="flex">
                    <a href="/dashboard.php" class="flex items-center">
                        <span class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                            LifeCanvas
                        </span>
                    </a>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:ml-10 md:flex md:space-x-4">
                        <a href="/dashboard.php"
                           class="<?= $currentPage === 'dashboard' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            대시보드
                        </a>
                        <a href="/mandalart.php"
                           class="<?= $currentPage === 'mandalart' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            만다라트
                        </a>
                        <a href="/daily.php"
                           class="<?= $currentPage === 'daily' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            오늘의 할일
                        </a>
                        <a href="/review.php"
                           class="<?= $currentPage === 'review' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            회고
                        </a>
                        <a href="/leaderboard.php"
                           class="<?= $currentPage === 'leaderboard' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            리더보드
                        </a>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Streak Badge -->
                    <?php if ($currentUser->getStreakDays() > 0): ?>
                    <div class="hidden sm:flex items-center px-3 py-1.5 bg-orange-50 dark:bg-orange-900/30 rounded-full">
                        <span class="text-lg <?= $currentUser->getStreakDays() >= 7 ? 'flame-animate' : '' ?>">🔥</span>
                        <span class="ml-1.5 text-sm font-semibold text-orange-600 dark:text-orange-400"><?= $currentUser->getStreakDays() ?>일</span>
                    </div>
                    <?php endif; ?>

                    <!-- Points -->
                    <div class="hidden sm:flex items-center px-3 py-1.5 bg-yellow-50 dark:bg-yellow-900/30 rounded-full">
                        <span class="text-lg">⭐</span>
                        <span class="ml-1.5 text-sm font-semibold text-yellow-600 dark:text-yellow-400"><?= number_format($currentUser->getPoints()) ?></span>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative" @click.away="profileMenu = false">
                        <button @click="profileMenu = !profileMenu"
                                class="flex items-center space-x-2 p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <?php if ($currentUser->getAvatarUrl()): ?>
                                <img src="<?= e($currentUser->getAvatarUrl()) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-purple-500 flex items-center justify-center text-white font-medium">
                                    <?= mb_substr($currentUser->getUsername(), 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-300"><?= e($currentUser->getUsername()) ?></span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="profileMenu"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 py-1"
                             style="display: none;">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($currentUser->getFullName()) ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">레벨 <?= $currentUser->getLevel() ?></p>
                            </div>
                            <a href="/profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                프로필
                            </a>
                            <a href="/achievements.php" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                업적
                            </a>
                            <a href="/settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                설정
                            </a>
                            <div class="border-t border-gray-100 dark:border-gray-700 mt-1">
                                <a href="/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    로그아웃
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <button @click="mobileMenu = !mobileMenu"
                            class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="mobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div x-show="mobileMenu" class="md:hidden border-t border-gray-200 dark:border-gray-700 py-3" style="display: none;">
                <a href="/dashboard.php" class="block px-3 py-2 rounded-lg text-base font-medium <?= $currentPage === 'dashboard' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' ?>">대시보드</a>
                <a href="/mandalart.php" class="block px-3 py-2 rounded-lg text-base font-medium <?= $currentPage === 'mandalart' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' ?>">만다라트</a>
                <a href="/daily.php" class="block px-3 py-2 rounded-lg text-base font-medium <?= $currentPage === 'daily' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' ?>">오늘의 할일</a>
                <a href="/review.php" class="block px-3 py-2 rounded-lg text-base font-medium <?= $currentPage === 'review' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' ?>">회고</a>
                <a href="/leaderboard.php" class="block px-3 py-2 rounded-lg text-base font-medium <?= $currentPage === 'leaderboard' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50' ?>">리더보드</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <main class="pt-16 min-h-screen">
    <?php endif; ?>

    <?php if ($flash): ?>
    <!-- Flash Message -->
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed top-20 right-4 z-50 max-w-sm">
        <div class="<?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : ($flash['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-blue-50 border-blue-200 text-blue-800') ?> px-4 py-3 rounded-lg border shadow-lg flex items-center">
            <?php if ($flash['type'] === 'success'): ?>
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            <?php elseif ($flash['type'] === 'error'): ?>
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            <?php endif; ?>
            <span class="text-sm font-medium"><?= e($flash['message']) ?></span>
            <button @click="show = false" class="ml-auto -mr-1 p-1 hover:bg-black/5 rounded">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>
