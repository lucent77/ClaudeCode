<?php
/**
 * Register Page
 * 회원가입 페이지
 */

require_once __DIR__ . '/includes/session.php';

Session::redirectIfAuth();

$error = '';
$formData = ['username' => '', 'email' => '', 'full_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? '')
    ];
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($formData['username']) || empty($formData['email']) || empty($password)) {
        $error = '모든 필수 항목을 입력해주세요.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } else {
        $user = new User();
        $result = $user->register(
            $formData['username'],
            $formData['email'],
            $password,
            $formData['full_name']
        );

        if ($result['success']) {
            Session::login($result['user_id']);
            Session::setFlash('success', '회원가입을 환영합니다! 첫 목표를 설정해보세요.');
            redirect('/mandalart.php?new=1');
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = '회원가입';
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

            <div class="space-y-6">
                <h2 class="text-2xl font-bold text-white">목표 달성을 위한 완벽한 도구</h2>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center text-white flex-shrink-0">✓</div>
                        <div>
                            <h4 class="text-white font-medium">만다라트 기법</h4>
                            <p class="text-primary-100 text-sm">오타니 쇼헤이가 사용한 목표 설정 방법</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center text-white flex-shrink-0">✓</div>
                        <div>
                            <h4 class="text-white font-medium">7가지 습관 프레임워크</h4>
                            <p class="text-primary-100 text-sm">스티븐 코비의 성공 원칙을 실행</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center text-white flex-shrink-0">✓</div>
                        <div>
                            <h4 class="text-white font-medium">게임화 요소</h4>
                            <p class="text-primary-100 text-sm">포인트, 배지, 레벨업으로 동기부여</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center text-white flex-shrink-0">✓</div>
                        <div>
                            <h4 class="text-white font-medium">스마트 피드백</h4>
                            <p class="text-primary-100 text-sm">AI 기반 실행 분석과 맞춤 조언</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-primary-200 text-sm">
                <p>"소중한 것을 먼저 하라" - 스티븐 코비</p>
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">시작하기</h2>
                    <p class="mt-2 text-gray-600">무료로 가입하고 목표 달성 여정을 시작하세요</p>
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

                <form method="POST" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">사용자명 *</label>
                            <input type="text" id="username" name="username" required
                                   value="<?= e($formData['username']) ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="username">
                        </div>
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">이름</label>
                            <input type="text" id="full_name" name="full_name"
                                   value="<?= e($formData['full_name']) ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="홍길동">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">이메일 *</label>
                        <input type="email" id="email" name="email" required
                               value="<?= e($formData['email']) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="your@email.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">비밀번호 *</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="6자 이상">
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">비밀번호 확인 *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="비밀번호 재입력">
                    </div>

                    <div class="flex items-start">
                        <input type="checkbox" id="terms" name="terms" required
                               class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="terms" class="ml-2 text-sm text-gray-600">
                            <a href="#" class="text-primary-600 hover:text-primary-700">이용약관</a> 및
                            <a href="#" class="text-primary-600 hover:text-primary-700">개인정보처리방침</a>에 동의합니다
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-xl hover:from-primary-700 hover:to-purple-700 focus:ring-4 focus:ring-primary-200 transition-all transform hover:scale-[1.02]">
                        무료로 시작하기
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <p class="text-gray-600">
                        이미 계정이 있으신가요?
                        <a href="/login.php" class="text-primary-600 hover:text-primary-700 font-semibold ml-1">로그인</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
