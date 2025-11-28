<?php
/**
 * Landing Page
 * 랜딩 페이지
 */

require_once __DIR__ . '/includes/session.php';

// Redirect to dashboard if logged in
if (Session::isLoggedIn()) {
    redirect('/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="ko" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCanvas - 만다라트 기반 자기 관리 웹서비스</title>
    <meta name="description" content="만다라트와 7가지 습관을 기반으로 한 최고의 자기 관리 웹서비스. 목표를 설정하고, 매일 실천하며, 성장을 추적하세요.">

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
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; }

        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .mandal-demo {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .feature-card:hover {
            transform: translateY(-8px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="/" class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                    LifeCanvas
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-gray-600 hover:text-gray-900 font-medium">기능</a>
                    <a href="#how-it-works" class="text-gray-600 hover:text-gray-900 font-medium">사용법</a>
                    <a href="#testimonials" class="text-gray-600 hover:text-gray-900 font-medium">후기</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="/login.php" class="text-gray-600 hover:text-gray-900 font-medium">로그인</a>
                    <a href="/register.php" class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                        무료로 시작하기
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 hero-gradient text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                        목표를 현실로<br>
                        <span class="text-yellow-300">만다라트</span>로 그려보세요
                    </h1>
                    <p class="mt-6 text-lg text-white/90 leading-relaxed">
                        오타니 쇼헤이가 사용한 목표 설정 기법과 스티븐 코비의 7가지 습관을 결합한 자기 관리 시스템.
                        당신의 꿈을 64개의 구체적인 행동으로 만들어보세요.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="/register.php" class="px-8 py-4 bg-white text-primary-600 font-semibold rounded-xl hover:bg-gray-100 transition-all shadow-lg shadow-black/20">
                            무료로 시작하기
                        </a>
                        <a href="#how-it-works" class="px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-xl hover:bg-white/10 transition-all">
                            자세히 알아보기
                        </a>
                    </div>
                    <div class="mt-10 flex items-center gap-6">
                        <div class="flex -space-x-2">
                            <div class="w-10 h-10 rounded-full bg-yellow-400 border-2 border-white flex items-center justify-center">😊</div>
                            <div class="w-10 h-10 rounded-full bg-green-400 border-2 border-white flex items-center justify-center">🎯</div>
                            <div class="w-10 h-10 rounded-full bg-blue-400 border-2 border-white flex items-center justify-center">⭐</div>
                        </div>
                        <p class="text-white/80">
                            <span class="font-bold text-white">1,000+</span> 사용자가 목표를 달성 중
                        </p>
                    </div>
                </div>

                <!-- Mandal-art Demo -->
                <div class="relative mandal-demo">
                    <div class="grid grid-cols-3 gap-2 max-w-md mx-auto">
                        <?php
                        $demoColors = ['#f87171', '#fb923c', '#facc15', '#4ade80', '#22d3ee', '#60a5fa', '#a78bfa', '#f472b6'];
                        $demoLabels = ['체력', '멘탈', '글쓰기', '🎯', '영어', '인맥', '건강', '독서'];
                        for ($i = 0; $i < 9; $i++):
                            $isCenter = $i === 4;
                            $color = $isCenter ? '#6366f1' : ($demoColors[$i > 4 ? $i - 1 : $i] ?? '#94a3b8');
                        ?>
                        <div class="aspect-square rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg <?= $isCenter ? 'scale-110 z-10' : '' ?>"
                             style="background-color: <?= $color ?>">
                            <?= $demoLabels[$i > 4 ? $i - 1 : ($i === 4 ? 3 : $i)] ?>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Floating Elements -->
                    <div class="absolute -top-4 -right-4 w-16 h-16 bg-yellow-400 rounded-2xl shadow-lg flex items-center justify-center text-2xl rotate-12">
                        🏆
                    </div>
                    <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-green-400 rounded-2xl shadow-lg flex items-center justify-center text-2xl -rotate-12">
                        🔥
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">왜 LifeCanvas인가요?</h2>
                <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                    단순한 할일 관리를 넘어, 당신의 삶 전체를 디자인하는 종합 자기 관리 시스템
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center text-2xl mb-6">
                        🎯
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">만다라트 목표 설정</h3>
                    <p class="text-gray-600">
                        9×9 격자로 큰 목표를 64개의 구체적인 행동으로 세분화하세요. 오타니 쇼헤이의 성공 비결!
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center text-2xl mb-6">
                        ✅
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">일일 실행 트래커</h3>
                    <p class="text-gray-600">
                        매일 할 일을 체크하고 실행 점수를 받으세요. 7가지 습관의 "소중한 것을 먼저" 원칙을 적용합니다.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center text-2xl mb-6">
                        🏆
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">게이미피케이션</h3>
                    <p class="text-gray-600">
                        포인트, 레벨, 배지로 목표 달성을 게임처럼 즐기세요. 리더보드에서 친선 경쟁도 할 수 있어요.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center text-2xl mb-6">
                        📊
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">시각적 대시보드</h3>
                    <p class="text-gray-600">
                        실행 점수 추이, 영역별 달성률 등을 직관적인 차트로 확인하고 패턴을 발견하세요.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center text-2xl mb-6">
                        💡
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">스마트 코칭</h3>
                    <p class="text-gray-600">
                        실행률이 낮은 과제에 대해 맞춤 조언을 제공합니다. 무리한 목표는 현실적으로 조정해드려요.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-pink-100 rounded-xl flex items-center justify-center text-2xl mb-6">
                        📝
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">주간/월간 회고</h3>
                    <p class="text-gray-600">
                        정기적인 돌아보기를 통해 계획을 개선하세요. "끊임없이 쇄신하라"의 습관7을 실천합니다.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-20 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">어떻게 시작하나요?</h2>
                <p class="mt-4 text-lg text-gray-600">3단계로 목표 달성 여정을 시작하세요</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-500 to-purple-500 rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                        1
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900">핵심 목표 설정</h3>
                    <p class="mt-3 text-gray-600">
                        당신이 이루고 싶은 가장 중요한 목표를 만다라트 중앙에 적으세요.
                    </p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-500 to-purple-500 rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                        2
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900">세부 계획 수립</h3>
                    <p class="mt-3 text-gray-600">
                        8개의 세부 목표와 64개의 실행 과제로 구체적인 청사진을 그리세요.
                    </p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-500 to-purple-500 rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                        3
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900">매일 실천 & 성장</h3>
                    <p class="mt-3 text-gray-600">
                        매일 할 일을 체크하고, 포인트를 모으며, 배지를 획득하세요!
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">사용자들의 이야기</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p class="text-gray-600 mb-6">
                        "만다라트로 목표를 시각화하니 막연하던 꿈이 구체적인 행동으로 바뀌었어요. 30일 연속 달성 배지까지 받았습니다!"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">👩</div>
                        <div>
                            <p class="font-semibold">김지은</p>
                            <p class="text-sm text-gray-500">디자이너</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p class="text-gray-600 mb-6">
                        "게이미피케이션이 동기부여에 엄청 도움이 돼요. 레벨 올리는 재미에 매일 아침 앱 켜는 게 습관이 됐어요."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">👨</div>
                        <div>
                            <p class="font-semibold">박준호</p>
                            <p class="text-sm text-gray-500">개발자</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p class="text-gray-600 mb-6">
                        "주간 회고 기능 덕분에 매주 자신을 돌아보게 됐어요. 코칭 메시지도 현실적이어서 목표 조정에 큰 도움이 됩니다."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">👩</div>
                        <div>
                            <p class="font-semibold">이수진</p>
                            <p class="text-sm text-gray-500">마케터</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 hero-gradient">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white">
                오늘부터 당신의 목표를 현실로 만들어보세요
            </h2>
            <p class="mt-6 text-xl text-white/90">
                무료로 시작하고, 원하는 만큼 성장하세요.
            </p>
            <a href="/register.php" class="mt-8 inline-block px-10 py-4 bg-white text-primary-600 font-bold text-lg rounded-xl hover:bg-gray-100 transition-all shadow-lg shadow-black/20">
                지금 무료로 시작하기 →
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <span class="text-2xl font-bold text-white">LifeCanvas</span>
                    <p class="mt-2 text-sm">만다라트와 7가지 습관 기반 자기 관리 웹서비스</p>
                </div>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-white transition-colors">이용약관</a>
                    <a href="#" class="hover:text-white transition-colors">개인정보처리방침</a>
                    <a href="#" class="hover:text-white transition-colors">문의하기</a>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm">
                © <?= date('Y') ?> LifeCanvas. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
