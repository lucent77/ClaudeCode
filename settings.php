<?php
/**
 * Settings Page
 * 설정 페이지
 */

$pageTitle = '설정';
require_once __DIR__ . '/includes/header.php';

Session::requireAuth();

$user = Session::getUser();
$settings = $user->getSettings();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user->updateSettings([
        'theme' => $_POST['theme'] ?? 'auto',
        'notification_email' => isset($_POST['notification_email']),
        'notification_push' => isset($_POST['notification_push']),
        'reminder_time' => $_POST['reminder_time'] ?? '08:00',
        'weekly_review_day' => intval($_POST['weekly_review_day'] ?? 0),
        'show_on_leaderboard' => isset($_POST['show_on_leaderboard']),
        'profile_visibility' => $_POST['profile_visibility'] ?? 'public'
    ]);

    Session::setFlash('success', '설정이 저장되었습니다.');
    redirect('/settings.php');
}
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">설정</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">앱 환경을 맞춤 설정하세요</p>
    </div>

    <form method="POST" class="space-y-6">
        <!-- Appearance -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">외관</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">테마</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="light" class="hidden peer"
                                   <?= ($settings['theme'] ?? 'auto') === 'light' ? 'checked' : '' ?>>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 transition-all text-center">
                                <span class="text-2xl">☀️</span>
                                <p class="mt-2 text-sm font-medium">라이트</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="dark" class="hidden peer"
                                   <?= ($settings['theme'] ?? 'auto') === 'dark' ? 'checked' : '' ?>>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 transition-all text-center">
                                <span class="text-2xl">🌙</span>
                                <p class="mt-2 text-sm font-medium">다크</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="auto" class="hidden peer"
                                   <?= ($settings['theme'] ?? 'auto') === 'auto' ? 'checked' : '' ?>>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 transition-all text-center">
                                <span class="text-2xl">🌓</span>
                                <p class="mt-2 text-sm font-medium">자동</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">알림</h2>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">이메일 알림</p>
                        <p class="text-sm text-gray-500">주간 리포트 및 중요 알림을 이메일로 받습니다</p>
                    </div>
                    <input type="checkbox" name="notification_email"
                           <?= ($settings['notification_email'] ?? true) ? 'checked' : '' ?>
                           class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                </label>

                <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">푸시 알림</p>
                        <p class="text-sm text-gray-500">일일 리마인더 및 배지 획득 알림을 받습니다</p>
                    </div>
                    <input type="checkbox" name="notification_push"
                           <?= ($settings['notification_push'] ?? true) ? 'checked' : '' ?>
                           class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        일일 리마인더 시간
                    </label>
                    <input type="time" name="reminder_time" value="<?= $settings['reminder_time'] ?? '08:00' ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        주간 회고 알림 요일
                    </label>
                    <select name="weekly_review_day"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        <option value="0" <?= ($settings['weekly_review_day'] ?? 0) == 0 ? 'selected' : '' ?>>일요일</option>
                        <option value="1" <?= ($settings['weekly_review_day'] ?? 0) == 1 ? 'selected' : '' ?>>월요일</option>
                        <option value="5" <?= ($settings['weekly_review_day'] ?? 0) == 5 ? 'selected' : '' ?>>금요일</option>
                        <option value="6" <?= ($settings['weekly_review_day'] ?? 0) == 6 ? 'selected' : '' ?>>토요일</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Privacy -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">개인정보</h2>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl cursor-pointer">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">리더보드에 표시</p>
                        <p class="text-sm text-gray-500">순위표에 내 정보가 표시됩니다</p>
                    </div>
                    <input type="checkbox" name="show_on_leaderboard"
                           <?= ($settings['show_on_leaderboard'] ?? true) ? 'checked' : '' ?>
                           class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        프로필 공개 범위
                    </label>
                    <select name="profile_visibility"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        <option value="public" <?= ($settings['profile_visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>>전체 공개</option>
                        <option value="friends" <?= ($settings['profile_visibility'] ?? 'public') === 'friends' ? 'selected' : '' ?>>친구만</option>
                        <option value="private" <?= ($settings['profile_visibility'] ?? 'public') === 'private' ? 'selected' : '' ?>>비공개</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6 border-2 border-red-200 dark:border-red-800">
            <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-6">위험 구역</h2>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">모든 데이터 초기화</p>
                        <p class="text-sm text-gray-500">모든 목표, 기록, 포인트가 삭제됩니다</p>
                    </div>
                    <button type="button" onclick="confirmReset()"
                            class="px-4 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                        초기화
                    </button>
                </div>

                <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">계정 삭제</p>
                        <p class="text-sm text-gray-500">계정과 모든 데이터가 영구적으로 삭제됩니다</p>
                    </div>
                    <button type="button" onclick="confirmDelete()"
                            class="px-4 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                        삭제
                    </button>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                설정 저장
            </button>
        </div>
    </form>
</div>

<script>
function confirmReset() {
    if (confirm('정말로 모든 데이터를 초기화하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        // Implement reset functionality
        showToast('데이터 초기화 기능 준비 중입니다', 'info');
    }
}

function confirmDelete() {
    if (confirm('정말로 계정을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        // Implement delete functionality
        showToast('계정 삭제 기능 준비 중입니다', 'info');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
