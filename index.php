<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>경품 추첨 이벤트</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 커스텀 스타일 -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700;900&display=swap');

        body {
            font-family: 'Noto Sans KR', sans-serif;
        }

        .raffle-wheel {
            max-width: 600px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen py-8 px-4">
    <div class="container mx-auto max-w-6xl">
        <!-- 헤더 -->
        <div class="text-center mb-12 fade-in">
            <h1 class="text-5xl font-black text-white mb-4 drop-shadow-lg">
                🎁 경품 추첨 이벤트
            </h1>
            <p class="text-xl text-white/90">
                이메일을 입력하고 행운의 룰렛을 돌려보세요!
            </p>
        </div>

        <!-- 메인 컨텐츠 -->
        <div class="grid lg:grid-cols-2 gap-8 items-start">
            <!-- 룰렛 섹션 -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 fade-in">
                <div class="flex justify-center mb-6">
                    <div id="wheelContainer" class="relative"></div>
                </div>

                <!-- 상태 메시지 -->
                <div id="statusMessage" class="text-center mb-6 min-h-[60px]">
                    <p class="text-gray-600 text-lg">
                        룰렛을 돌려서 상품을 받아가세요!
                    </p>
                </div>
            </div>

            <!-- 입력 폼 섹션 -->
            <div class="space-y-6">
                <!-- 추첨 폼 -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 fade-in">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        추첨 참여하기
                    </h2>

                    <form id="raffleForm" class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                이메일 주소
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                placeholder="your@email.com"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all text-lg"
                            >
                        </div>

                        <button
                            type="submit"
                            id="spinButton"
                            class="w-full btn-primary text-white font-bold py-4 px-8 rounded-xl text-lg shadow-lg"
                        >
                            🎰 추첨 시작!
                        </button>
                    </form>

                    <!-- 결과 표시 -->
                    <div id="resultContainer" class="mt-6 hidden">
                        <div class="bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl p-6 text-center">
                            <div class="text-white">
                                <div class="text-3xl mb-2">🎉</div>
                                <h3 class="text-2xl font-bold mb-2">축하합니다!</h3>
                                <p id="resultText" class="text-lg font-medium"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 상품 목록 -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 fade-in">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        🎁 경품 목록
                    </h2>

                    <div id="prizesList" class="space-y-3">
                        <div class="text-center text-gray-500 py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto"></div>
                            <p class="mt-4">상품 정보를 불러오는 중...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 관리자 페이지 링크 -->
        <div class="text-center mt-12">
            <a href="/admin/" class="text-white/80 hover:text-white underline text-sm">
                관리자 페이지
            </a>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/assets/js/raffle.js"></script>
    <script>
        let raffleWheel = null;
        let prizes = [];

        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                // 상품 목록 불러오기
                const data = await RaffleAPI.getPrizes();
                prizes = data.prizes;

                // 룰렛 초기화
                raffleWheel = new RaffleWheel('wheelContainer', prizes);

                // 상품 목록 표시
                displayPrizesList(prizes);

            } catch (error) {
                showError('상품 정보를 불러올 수 없습니다: ' + error.message);
            }
        });

        // 추첨 폼 제출
        document.getElementById('raffleForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const emailInput = document.getElementById('email');
            const email = emailInput.value.trim();
            const spinButton = document.getElementById('spinButton');

            if (!email) {
                alert('이메일 주소를 입력해주세요.');
                return;
            }

            // 버튼 비활성화
            spinButton.disabled = true;
            spinButton.textContent = '추첨 중...';

            // 결과 숨기기
            document.getElementById('resultContainer').classList.add('hidden');

            try {
                // API 호출
                const result = await RaffleAPI.draw(email);

                // 당첨 상품 인덱스 찾기
                const winningIndex = prizes.findIndex(p => p.id === result.winner.prize.id);

                // 상태 메시지 업데이트
                updateStatus('룰렛이 돌아가고 있습니다...', 'spinning');

                // 룰렛 회전
                raffleWheel.spin(winningIndex, () => {
                    // 결과 표시
                    showResult(result.winner);
                    spinButton.disabled = false;
                    spinButton.textContent = '🎰 추첨 시작!';
                });

            } catch (error) {
                showError('추첨 중 오류가 발생했습니다: ' + error.message);
                spinButton.disabled = false;
                spinButton.textContent = '🎰 추첨 시작!';
            }
        });

        // 상품 목록 표시
        function displayPrizesList(prizes) {
            const container = document.getElementById('prizesList');

            if (prizes.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500">등록된 상품이 없습니다.</p>';
                return;
            }

            container.innerHTML = prizes.map(prize => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-4 h-4 rounded-full" style="background-color: ${prize.color}"></div>
                        <span class="font-medium text-gray-800">${prize.name}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-purple-600 font-bold">${prize.probability}%</span>
                        ${prize.stock > 0 ? `<div class="text-xs text-gray-500">남은 수량: ${prize.stock}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }

        // 결과 표시
        function showResult(winner) {
            const resultContainer = document.getElementById('resultContainer');
            const resultText = document.getElementById('resultText');

            resultText.textContent = `${winner.prize.name}에 당첨되셨습니다!`;
            resultContainer.classList.remove('hidden');
            resultContainer.classList.add('fade-in');

            updateStatus(winner.message, 'success');
        }

        // 상태 메시지 업데이트
        function updateStatus(message, type = 'info') {
            const statusMessage = document.getElementById('statusMessage');

            const colors = {
                'info': 'text-gray-600',
                'spinning': 'text-purple-600 pulse-animation',
                'success': 'text-green-600 font-bold',
                'error': 'text-red-600'
            };

            statusMessage.innerHTML = `
                <p class="${colors[type]} text-lg">
                    ${message}
                </p>
            `;
        }

        // 에러 표시
        function showError(message) {
            updateStatus(message, 'error');
            alert(message);
        }
    </script>
</body>
</html>
