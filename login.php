<?php
/**
 * Login Page
 * 로그인 페이지
 */

require_once __DIR__ . '/includes/session.php';

Session::redirectIfAuth();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = '이메일과 비밀번호를 입력해주세요.';
    } else {
        $user = new User();
        $result = $user->login($email, $password);

        if ($result['success']) {
            Session::login($result['user']['id']);
            Session::setFlash('success', '환영합니다, ' . e($result['user']['full_name']) . '님!');
            redirect('/dashboard.php');
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = '로그인';
?>
<!DOCTYPE html>
<html lang="ko" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - LifeCanvas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe',
                            300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1',
                            600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Noto Sans KR', sans-serif; }</style>
</head>
<body class="h-full bg-gradient-to-br from-primary-50 via-white to-purple-50">
    <div class="min-h-full flex">
        <!-- Left Side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 to-purple-700 p-12 flex-col justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">LifeCanvas</h1>
                <p class="mt-2 text-primary-100">당신의 목표를 캔버스에 그리세요</p>
            </div>

            <div class="space-y-8">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl">🎯</div>
                        <div>
                            <h3 class="text-white font-semibold">만다라트 목표 설정</h3>
                            <p class="text-primary-100 text-sm">9×9 격자로 목표를 체계적으로 세분화</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl">📊</div>
                        <div>
                            <h3 class="text-white font-semibold">실행 점수 & 피드백</h3>
                            <p class="text-primary-100 text-sm">일일 실행률을 추적하고 개선점을 발견</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl">🏆</div>
                        <div>
                            <h3 class="text-white font-semibold">게임화 & 동기부여</h3>
                            <p class="text-primary-100 text-sm">포인트, 배지, 레벨로 습관 형성을 재미있게</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-primary-200 text-sm">
                <p>"끝을 생각하며 시작하라" - 스티븐 코비</p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">환영합니다!</h2>
                    <p class="mt-2 text-gray-600">계정에 로그인하여 목표 달성 여정을 계속하세요</p>
                </div>

                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-red-700"><?= e($error) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">이메일</label>
                        <input type="email" id="email" name="email" required
                               value="<?= e($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="your@email.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">비밀번호</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-600">로그인 상태 유지</span>
                        </label>
                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">비밀번호 찾기</a>
                    </div>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-purple-700 focus:ring-4 focus:ring-primary-200 transition-all transform hover:scale-[1.02]">
                        로그인
                    </button>
                </form>

                <div class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-gradient-to-br from-primary-50 via-white to-purple-50 text-gray-500">또는</span>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <p class="text-gray-600">
                            아직 계정이 없으신가요?
                            <a href="/register.php" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">회원가입</a>
                        </p>
                    </div>
                </div>

                <!-- Demo Account Info -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                    <p class="text-sm text-blue-800">
                        <strong>테스트 계정:</strong><br>
                        이메일: test@example.com<br>
                        비밀번호: test123
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
